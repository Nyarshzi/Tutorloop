<?php
session_start();
include("../../config/db.php");
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutee') {
    header("Location: ../login.php");
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
$sql = "SELECT 
            tp.tutor_id,
            u.name,
            tp.tutoring_rate,
            ts.subject_id,
            s.subject_name
        FROM tutor_profiles tp
        INNER JOIN users u 
            ON tp.tutor_id = u.user_id
        LEFT JOIN tutor_subjects ts 
            ON tp.tutor_id = ts.tutor_id
        LEFT JOIN subjects s 
            ON ts.subject_id = s.subject_id
        WHERE tp.tutor_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tutor not found.");
}

$tutor = $result->fetch_assoc();

// Load tutor availability for this subject
$avail_sql = "SELECT day_of_week, start_time, end_time 
              FROM tutor_availability ta
              JOIN tutor_subjects ts ON ta.tutor_subject_id = ts.id
              WHERE ts.tutor_id = ? AND ts.subject_id = ?
              ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$avail_stmt = $conn->prepare($avail_sql);
$avail_stmt->bind_param("ii", $tutor_id, $tutor['subject_id']);
$avail_stmt->execute();
$avail_result = $avail_stmt->get_result();

$availability_slots = [];
while ($row = $avail_result->fetch_assoc()) {
    $availability_slots[] = [
        'day_of_week' => $row['day_of_week'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

// Submit request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule = $_POST['requested_schedule'];
    $note = $_POST['request_note'];

    if (empty($schedule)) {
        $message = "Schedule required.";
        $message_type = "error";
    } elseif (!empty($availability_slots)) {
        // Validate that the requested schedule falls within tutor's availability
        $requested_datetime = new DateTime($schedule);
        $requested_day = $requested_datetime->format('l'); // e.g., "Monday"
        $requested_time = $requested_datetime->format('H:i:s'); // 24-hour format
        
        $is_available = false;
        foreach ($availability_slots as $slot) {
            if ($slot['day_of_week'] === $requested_day) {
                if ($requested_time >= $slot['start_time'] && $requested_time <= $slot['end_time']) {
                    $is_available = true;
                    break;
                }
            }
        }
        
        if (!$is_available) {
            $message = "The tutor is not available at the selected time. Please choose from their available schedule.";
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
    } else {
        // No availability slots defined - allow the request
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Session</title>
    <link rel="stylesheet" href="../../Frontend/css/request_session.css">
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
    </div>

    <script type="application/json" id="availability-data">
        <?php echo json_encode($availability_slots); ?>
    </script>

    <form method="POST">

        <label>Requested Schedule</label>
        <input type="datetime-local" name="requested_schedule" required>

        <label>Message (optional)</label>
        <textarea name="request_note"></textarea>

        <button type="submit">Submit Request</button>

    </form>

    <a href="/tutorloop/tutee/tutee_dashboard.php"class="back">← Back</a>

</div>

<script src="../../Frontend/js/request_session.js"></script>
</body>
</html>
