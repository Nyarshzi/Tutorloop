<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

$sql = "SELECT 
            s.session_id,
            s.requested_schedule,
            s.session_status,
            s.request_note,
            u.name AS tutee_name,
            sub.subject_name
        FROM sessions s
        INNER JOIN users u ON s.tutee_id = u.user_id
        INNER JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutor_id = ? AND s.session_status = 'Pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Requests</title>
    <link rel="stylesheet" href="Frontend/css/view_requests.css">
</head>
<body>

<div class="container">
    <h1>Pending Requests</h1>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="request-card">
                <h3><?php echo htmlspecialchars($row['tutee_name']); ?></h3>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?></p>
                <p><strong>Schedule:</strong> <?php echo htmlspecialchars($row['requested_schedule']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($row['session_status']); ?></p>
                <p><strong>Note:</strong> <?php echo !empty($row['request_note']) ? htmlspecialchars($row['request_note']) : 'No note'; ?></p>

                <div class="actions">
                    <a href="accept_session.php?session_id=<?php echo $row['session_id']; ?>" class="accept-btn">Accept</a>
                    <a href="decline_session.php?session_id=<?php echo $row['session_id']; ?>" class="decline-btn">Decline</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-box">No pending requests yet</div>
    <?php endif; ?>

    <a href="/tutorloop/tutor/tutor_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>