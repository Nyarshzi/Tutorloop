<?php
include("config/db.php");
session_start();

$search = "";
$result = null;

if (isset($_GET['search'])) {
    $search = $_GET['search'];

    $sql = "SELECT users.full_name, tutor_profiles.bio, tutor_profiles.tutoring_rate,
                   tutor_profiles.subjects_handled, tutor_profiles.availability_schedule
            FROM tutor_profiles
            INNER JOIN users ON tutor_profiles.tutor_id = users.user_id
            WHERE tutor_profiles.subjects_handled LIKE '%$search%'";

    $result = mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Tutors - TutorLoop</title>
</head>
<body>
    <h2>Search Tutors</h2>

    <form method="GET" action="">
        <label>Enter Subject:</label><br>
        <input type="text" name="search" value="<?php echo $search; ?>" required>
        <button type="submit">Search</button>
    </form>

    <br>

    <?php
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<hr>";
                echo "<h3>" . $row['full_name'] . "</h3>";
                echo "<p><strong>Bio:</strong> " . $row['bio'] . "</p>";
                echo "<p><strong>Rate:</strong> " . $row['tutoring_rate'] . "</p>";
                echo "<p><strong>Subjects:</strong> " . $row['subjects_handled'] . "</p>";
                echo "<p><strong>Availability:</strong> " . $row['availability_schedule'] . "</p>";
            }
        } else {
            echo "<p>No tutors found.</p>";
        }
    }
    ?>
</body>
</html>