<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Ensures timezone matches your location

/**
 * 1. DATABASE CONNECTION
 */
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutorloop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * 2. SESSION AUTHENTICATION
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id']; 
$current_time = date("Y-m-d H:i:s"); 

/**
 * 3. LOGIC: GET BOOKED TIMES
 */
$booked_slots = [];
$check_booked = "SELECT requested_schedule FROM sessions 
                 WHERE tutor_id = $tutor_id AND session_status IN ('Accepted', 'Ongoing')";
$res_booked = $conn->query($check_booked);

if ($res_booked) {
    while($slot = $res_booked->fetch_assoc()) {
        $booked_slots[] = $slot['requested_schedule'];
    }
}

/**
 * 4. LOGIC: FETCH ALL SESSIONS
 */
$sql = "SELECT s.*, u.name AS student_name, sub.subject_name 
        FROM sessions s
        JOIN users u ON s.tutee_id = u.user_id 
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutor_id = $tutor_id 
        ORDER BY s.requested_schedule DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Management | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutor_session_request.css">
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a class="active">Session Requests</a>
            <a href="tutor_myschedule.php">My Schedule</a>
            <a href="tutor_mystudents.php">My Students</a>
            <a href="tutor_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Session Management</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="requests">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $session_time = $row['requested_schedule'];
                    $session_status = $row['session_status']; 
                    
                    // Logic checks
                    $is_booked = in_array($session_time, $booked_slots);
                    // Comparison: If scheduled time is earlier than NOW
                    $is_outdated = (strtotime($session_time) < time());
                    
                    $is_conflict = ($session_status == 'Pending' && $is_booked);
                    $is_expired = ($session_status == 'Pending' && $is_outdated);
                ?>
                    
                    <div class="request-card <?php echo ($session_status == 'Completed' || $session_status == 'Declined' || $is_expired) ? 'archived-card' : ''; ?>">
                        <div class="info">
                            <div class="header-row">
                                <h3><?php echo htmlspecialchars($row['student_name']); ?></h3>
                                
                                <?php if($is_expired): ?>
                                    <span class="badge-expired">Expired</span>
                                <?php elseif($is_conflict): ?>
                                    <span class="badge-unavailable">Conflict</span>
                                <?php elseif($session_status == 'Ongoing' || $session_status == 'Accepted'): ?>
                                    <span class="status-badge status-ongoing">On-Going</span>
                                <?php elseif($session_status == 'Completed'): ?>
                                    <span class="status-badge status-completed">Completed</span>
                                <?php elseif($session_status == 'Declined'): ?>
                                    <span class="status-badge status-declined">Declined</span>
                                <?php endif; ?>
                            </div>
                            
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?></p>
                            <p><strong>Schedule:</strong> <?php echo date("F j, Y - g:i A", strtotime($session_time)); ?></p>
                            
                            <?php if($is_expired): ?>
                                <p class="conflict-text" style="color: #d9534f; font-weight: bold;">⚠️ This request has expired and can no longer be accepted.</p>
                            <?php elseif($is_conflict): ?>
                                <p class="conflict-text">⚠️ Slot occupied by a confirmed session.</p>
                            <?php endif; ?>
                        </div>

                        <div class="actions">
                            <?php if ($is_expired): ?>
                                <span class="history-label">Expired</span>
                            
                            <?php elseif ($session_status == 'Pending' && !$is_booked): ?>
                                <form action="handle_request.php" method="POST">
                                    <input type="hidden" name="session_id" value="<?php echo $row['session_id']; ?>">
                                    <button type="submit" name="action" value="Accept" class="accept">Accept</button>
                                    <button type="submit" name="action" value="Decline" class="decline">Decline</button>
                                </form>

                            <?php elseif ($session_status == 'Accepted' || $session_status == 'Ongoing'): ?>
                                <form action="handle_request.php" method="POST">
                                    <input type="hidden" name="session_id" value="<?php echo $row['session_id']; ?>">
                                    <button type="submit" name="action" value="Complete" class="accept">Mark Done</button>
                                </form>

                            <?php else: ?>
                                <span class="history-label">Session Finalized</span>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="request-card empty"><p>No session requests found.</p></div>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>