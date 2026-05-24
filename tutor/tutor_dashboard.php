<?php
session_start();
include("../config/db.php");
date_default_timezone_set('Asia/Manila'); // Keep tutor greeting in sync with dashboard timestamps

// 1. Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$tutor_name = $_SESSION['user_name'] ?? 'Tutor';
$current_time = date("Y-m-d H:i:s");

$currentHour = (int) date('H');
if ($currentHour >= 12 && $currentHour < 18) {
    $greeting = 'Good Afternoon ';
} elseif ($currentHour >= 18 && $currentHour <= 23) {
    $greeting = 'Good Evening ';
} else {
    $greeting = 'Good Morning';
}

// Section 2: Fetch Dashboard Statistics
$pending_count   = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutor_id = $tutor_id AND session_status = 'Pending'")->fetch_assoc()['c'];
$students_count  = $conn->query("SELECT COUNT(DISTINCT tutee_id) as c FROM sessions WHERE tutor_id = $tutor_id AND session_status = 'Accepted'")->fetch_assoc()['c'];
$completed_count = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutor_id = $tutor_id AND session_status = 'Completed'")->fetch_assoc()['c'];
$upcoming_count  = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutor_id = $tutor_id AND session_status IN ('Accepted', 'Ongoing')")->fetch_assoc()['c'];

$rating_row = $conn->query("SELECT average_rating FROM tutor_profiles WHERE tutor_id = $tutor_id")->fetch_assoc();
$my_rating = $rating_row ? round($rating_row['average_rating'], 1) : 0;
// Section 3: Recent Requests
$pending_requests = $conn->query("SELECT s.requested_schedule, u.name AS tutee_name, sub.subject_name 
    FROM sessions s 
    JOIN users u ON s.tutee_id = u.user_id 
    JOIN subjects sub ON s.subject_id = sub.subject_id 
    WHERE s.tutor_id = $tutor_id AND s.session_status = 'Pending' 
    ORDER BY s.requested_schedule ASC LIMIT 3");

// Section 4: Upcoming Sessions
$upcoming_sessions = $conn->query("SELECT 
    s.session_id, 
    s.requested_schedule, 
    u.name AS tutee_name, 
    sub.subject_name, 
    tp.tutoring_rate AS hourly_rate 
    FROM sessions s 
    JOIN users u ON s.tutee_id = u.user_id 
    JOIN subjects sub ON s.subject_id = sub.subject_id 
    JOIN tutor_profiles tp ON s.tutor_id = tp.tutor_id
    WHERE s.tutor_id = $tutor_id AND s.session_status IN ('Accepted', 'Ongoing') 
    AND s.requested_schedule >= '$current_time'
    ORDER BY s.requested_schedule ASC LIMIT 3");

// NEW SECTION: Fetch Recent Messages for the Tutor Dashboard 
$recent_messages = $conn->query("SELECT m.message_content, m.date_sent, u.name AS tutee_name, m.sender_id
    FROM messages m
    JOIN users u ON (m.sender_id = u.user_id)
    WHERE m.receiver_id = $tutor_id
    ORDER BY m.date_sent DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard | TutorLoop</title>
<link rel="stylesheet" href="../Frontend/css/tutor_dashboard.css"></head>
<body>
<div class="container"> 
    <aside class="sidebar"> 
        <div class="logo">
<img src="../Frontend/images/Tutorloop_logo.png" alt="logo">            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutor/tutor_dashboard.php" class="active">Dashboard</a>
            <a href="/tutorloop/tutor/tutor_profile.php">My Profile</a>
            <a href="/tutorloop/tutor/tutor_myschedule.php">My Schedule</a>
            <a href="/tutorloop/tutor/tutor_session_request.php">Session Requests</a>
            <a href="/tutorloop/tutor/tutor_mystudents.php">My Students</a>
            <a href="/tutorloop/tutor/tutor_messages.php">Messages</a>
            <a href="/tutorloop/tutor/tutor_ratings.php">My Ratings</a>
            <a href="/tutorloop/analytics.php">Analytics</a> 
        </nav>
    </aside>

    <main class="main"> 
        <header class="topbar">
           <h1 id="greetingText" data-name="<?php echo htmlspecialchars($tutor_name); ?>">
    Loading...</h1>
            <a href="/tutorloop/logout.php" class="logout">Logout</a>
        </header>

        <section class="cards">
            <div class="card">
                <h2><?php echo $pending_count; ?></h2>
                <p>Session Requests</p>
            </div>
            <div class="card">
                <h2><?php echo $upcoming_count; ?></h2>
                <p>My Schedule</p>
            </div>
            <div class="card">
                <h2><?php echo $students_count; ?></h2>
                <p>Students</p>
            </div>
            <div class="card">
                <h2><?php echo $completed_count; ?></h2>
                <p>Completed</p>
            </div>
            
        </section>

        <section class="bottom">
            <div class="box">
                <h3>Recent Requests</h3>
                <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                    <?php while($row = $pending_requests->fetch_assoc()): ?>
                        <div class="session-item">
                            <p><strong><?php echo htmlspecialchars($row['subject_name']); ?></strong> w/ <?php echo htmlspecialchars($row['tutee_name']); ?></p>
                            <p class="session-details">Date: <?php echo date("M j, g:i A", strtotime($row['requested_schedule'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No requests yet</p>
                <?php endif; ?>
            </div>

            <div class="box">
                <h3>Upcoming Sessions</h3>
                <?php if ($upcoming_sessions && $upcoming_sessions->num_rows > 0): ?>
                    <?php while($row = $upcoming_sessions->fetch_assoc()): ?>
                        <div class="session-item">
                            <p><strong><?php echo htmlspecialchars($row['subject_name']); ?></strong></p>
                            <p class="session-details">Schedule: <?php echo date("F j, Y - g:i A", strtotime($row['requested_schedule'])); ?></p>
                            <p class="session-details">Student: <?php echo htmlspecialchars($row['tutee_name']); ?> | Rate: ₱<?php echo number_format($row['hourly_rate'], 2); ?></p>
                            
                            <div class="dashboard-actions">
                                <form action="../tutor/api/handle_request.php" method="POST">
                                    <input type="hidden" name="session_id" value="<?php echo $row['session_id']; ?>">
                                    <button type="submit" name="action" value="Complete" class="btn-small btn-done">Done</button>
                                    <button type="submit" name="action" value="Paid" class="btn-small btn-paid">Paid</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No sessions scheduled</p>
                <?php endif; ?>
            </div>

            <div class="box">
                <h3>Recent Messages</h3>
                <?php if ($recent_messages && $recent_messages->num_rows > 0): ?>
                    <?php while($msg = $recent_messages->fetch_assoc()): ?>
                        <div class="session-item" onclick="location.href='/tutorloop/tutor/tutor_messages.php?tutee_id=<?php echo $msg['sender_id']; ?>'" style="cursor:pointer;">
                            <p><strong><?php echo htmlspecialchars($msg['tutee_name']); ?></strong></p>
                            <p class="session-details" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($msg['message_content']); ?>
                            </p>
                            <p class="session-details" style="font-size: 11px; color: #999;">
                                <?php echo date("M j, g:i A", strtotime($msg['date_sent'])); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No recent messages</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
 <script src="/TutorLoop/Frontend/js/tutor_dashboard.js"></script>
</body>
</html>