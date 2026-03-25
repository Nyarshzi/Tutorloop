<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Check tutor_id
if (!isset($_GET['tutor_id']) || empty($_GET['tutor_id'])) {
    die("Invalid tutor selected.");
}

$tutor_id = intval($_GET['tutor_id']);

// Load tutor
$sql = "SELECT tp.tutor_id, u.name, tp.tutoring_rate, tp.availability_schedule, tp.subject_id, s.subject_name
        FROM tutor_profiles tp
        INNER JOIN users u ON tp.tutor_id = u.user_id
        INNER JOIN subjects s ON tp.subject_id = s.subject_id
        WHERE tp.tutor_id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tutor not found.");
}

$tutor = $result->fetch_assoc();

// Submit request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule = $_POST['requested_schedule'];
    $note = $_POST['request_note'];

    if (empty($schedule)) {
        $message = "Schedule required.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO sessions 
            (tutor_id, tutee_id, subject_id, requested_schedule, session_status, request_note)
            VALUES (?, ?, ?, ?, 'Pending', ?)");

        $stmt->bind_param("iiiss", $tutor_id, $tutee_id, $tutor['subject_id'], $schedule, $note);
        $stmt->execute();

        $message = "Request sent!";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Session</title>
    <link rel="stylesheet" href="Frontend/css/request_session.css">
</head>

<body>

<div class="container">

    <h1>Request Tutoring Session</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="tutor-info">
        <p><strong>Tutor:</strong> <?php echo htmlspecialchars($tutor['name']); ?></p>
        <p><strong>Subject:</strong> <?php echo htmlspecialchars($tutor['subject_name']); ?></p>
        <p><strong>Rate:</strong> ₱<?php echo $tutor['tutoring_rate']; ?></p>
        <p><strong>Availability:</strong> <?php echo $tutor['availability_schedule']; ?></p>
    </div>

    <form method="POST">

        <label>Requested Schedule</label>
        <input type="datetime-local" name="requested_schedule" required>

        <label>Message (optional)</label>
        <textarea name="request_note"></textarea>

        <button type="submit">Submit Request</button>

    </form>

    <a href="tutee_dashboard.php" class="back">← Back</a>

</div>

</body>
</html>