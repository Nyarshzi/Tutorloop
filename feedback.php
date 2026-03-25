<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$message = "";

// Only show accepted or completed sessions for this tutee
$sessions = mysqli_query($conn, "
    SELECT sessions.session_id, sessions.tutor_id, users.full_name, sessions.subject
    FROM sessions
    INNER JOIN users ON sessions.tutor_id = users.user_id
    WHERE sessions.tutee_id = '$tutee_id'
    AND (sessions.session_status = 'Accepted' OR sessions.session_status = 'Completed')
");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = $_POST['session_id'];
    $tutor_id = $_POST['tutor_id'];
    $rating = $_POST['rating'];
    $feedback_comment = $_POST['feedback_comment'];

    $check_sql = "SELECT * FROM feedback_ratings WHERE session_id='$session_id'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "Feedback for this session already exists.";
    } else {
        $sql = "INSERT INTO feedback_ratings (session_id, tutor_id, rating, feedback_comment)
                VALUES ('$session_id', '$tutor_id', '$rating', '$feedback_comment')";

        if (mysqli_query($conn, $sql)) {
            // Recompute tutor average rating
            $avg_sql = "SELECT AVG(rating) AS avg_rating FROM feedback_ratings WHERE tutor_id='$tutor_id'";
            $avg_result = mysqli_query($conn, $avg_sql);
            $avg_row = mysqli_fetch_assoc($avg_result);
            $average_rating = $avg_row['avg_rating'];

            mysqli_query($conn, "
                UPDATE tutor_profiles
                SET average_rating='$average_rating'
                WHERE tutor_id='$tutor_id'
            ");

            $message = "Feedback submitted successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback - TutorLoop</title>
</head>
<body>

<h2>Submit Feedback</h2>

<?php if ($message != "") echo "<p>$message</p>"; ?>

<form method="POST">
    <label>Select Session:</label><br>
    <select name="session_id" id="session_id" required onchange="fillTutorId()">
        <option value="">Choose Session</option>
        <?php while ($row = mysqli_fetch_assoc($sessions)) { ?>
            <option value="<?php echo $row['session_id']; ?>" data-tutor="<?php echo $row['tutor_id']; ?>">
                <?php echo $row['full_name'] . " - " . $row['subject']; ?>
            </option>
        <?php } ?>
    </select><br><br>

    <input type="hidden" name="tutor_id" id="tutor_id">

    <label>Rating (1 to 5):</label><br>
    <input type="number" name="rating" min="1" max="5" required><br><br>

    <label>Comment:</label><br>
    <textarea name="feedback_comment" required></textarea><br><br>

    <button type="submit">Submit Feedback</button>
</form>

<script>
function fillTutorId() {
    const sessionSelect = document.getElementById("session_id");
    const tutorId = sessionSelect.options[sessionSelect.selectedIndex].getAttribute("data-tutor");
    document.getElementById("tutor_id").value = tutorId;
}
</script>

</body>
</html>