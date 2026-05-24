<?php
session_start();
include("../config/db.php");
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutee') {
    header("Location: ../login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$tutee_name = $_SESSION['user_name'] ?? 'Tutee';

$currentHour = (int) date('H');
if ($currentHour >= 12 && $currentHour < 18) {
    $greeting = 'Good Afternoon';
} elseif ($currentHour >= 18 && $currentHour <= 23) {
    $greeting = 'Good Evening';
} else {
    $greeting = 'Good Morning';
}

$completed_count = $conn->query("SELECT COUNT(*) as c FROM sessions 
    WHERE tutee_id = $tutee_id 
    AND session_status = 'Completed'")->fetch_assoc()['c'];

$requests_count = $conn->query("SELECT COUNT(*) as c FROM sessions 
    WHERE tutee_id = $tutee_id 
    AND session_status = 'Pending' 
    AND requested_schedule > NOW()")->fetch_assoc()['c'];

$subjects_count = $conn->query("SELECT COUNT(DISTINCT subject_id) as c 
    FROM sessions 
    WHERE tutee_id = $tutee_id")->fetch_assoc()['c'];

$upcoming_count = $conn->query("SELECT COUNT(*) as c FROM sessions 
    WHERE tutee_id = $tutee_id 
    AND session_status IN ('Accepted', 'Ongoing') 
    AND requested_schedule > NOW()")->fetch_assoc()['c'];

$my_requests = $conn->query("
    SELECT s.session_status, s.requested_schedule, 
           u.name as tutor_name, 
           sub.subject_name 
    FROM sessions s 
    JOIN users u ON s.tutor_id = u.user_id 
    JOIN subjects sub ON s.subject_id = sub.subject_id 
    WHERE s.tutee_id = $tutee_id 
    ORDER BY s.session_id DESC LIMIT 3
");

// Fetch Recent Messages for the Tutee Dashboard
$recent_messages = $conn->query("
    SELECT m.message_content, m.date_sent, u.name AS sender_name, m.sender_id
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.receiver_id = $tutee_id
    ORDER BY m.date_sent DESC LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutee Dashboard | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutee_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .status-tag { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .pending  { background: #FFF3CD; color: #856404; }
        .accepted { background: #D1E7FF; color: #004085; }
        .ongoing  { background: #E0F7FA; color: #006064; }
        .declined { background: #F8D7DA; color: #721C24; }
        .completed { background: #D4EDDA; color: #155724; }
        .expired  { background: #E2E3E5; color: #383D41; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php" class="active">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="/tutorloop/search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn">☰</button>
             <h1 id="greetingText" data-name="<?php echo htmlspecialchars($tutee_name); ?>">
                Loading...</h1>
            <h1 class="greeting-short">
                Hi, <?php echo htmlspecialchars($tutee_name); ?>! 👋
            </h1>
            <a href="/tutorloop/logout.php" class="logout">Logout</a>
        </header>

        <!-- Stats Cards -->
        <section class="cards">
            <div class="card">
                <h2><?php echo $completed_count; ?></h2>
                <p>Completed</p>
            </div>
            <div class="card">
                <h2><?php echo $requests_count; ?></h2>
                <p>Requests</p>
            </div>
            <div class="card">
                <h2><?php echo $subjects_count; ?></h2>
                <p>Subjects</p>
            </div>
            <div class="card">
                <h2><?php echo $upcoming_count; ?></h2>
                <p>Upcoming</p>
            </div>
        </section>

        <!-- Find a Tutor Search -->
        <section class="search-section">
            <div class="box">
                <h3>Find a Tutor</h3>
                <form action="/tutorloop/search_results.php" method="GET" class="search-bar">
                    <input type="text" name="tutor_query" 
                           placeholder="Enter tutor name..." required>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </section>

        <!-- My Requests + Upcoming Sessions -->
        <section class="bottom">
            <div class="box">
                <h3>My Requests</h3>
                <?php if ($my_requests && $my_requests->num_rows > 0): ?>
                    <?php while($row = $my_requests->fetch_assoc()):
                        $is_outdated = (strtotime($row['requested_schedule']) < time());
                        $current_db_status = $row['session_status'];
                        $display_status = ($current_db_status == 'Pending' && $is_outdated)
                            ? 'Expired' : $current_db_status;
                    ?>
                    <div class="activity-item">
                        <p style="margin:0;">
                            <strong>
                                <?php echo htmlspecialchars($row['subject_name']); ?>
                            </strong>
                            <br>
                            <small style="color:#718096;">
                                Tutor: <?php echo htmlspecialchars($row['tutor_name']); ?>
                            </small>
                        </p>
                        <span class="status-tag <?php echo strtolower($display_status); ?>">
                            <?php echo $display_status; ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#718096; font-size:14px;">
                        No requests yet. 
                        <a href="/tutorloop/search_results.php" 
                           style="color:var(--gold);">Find a tutor!</a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="box">
                <h3>Upcoming Sessions</h3>
                <?php
                $upcoming_list = $conn->query("
                    SELECT s.requested_schedule, u.name as tutor_name 
                    FROM sessions s 
                    JOIN users u ON s.tutor_id = u.user_id 
                    WHERE s.tutee_id = $tutee_id 
                    AND s.session_status IN ('Accepted', 'Ongoing') 
                    AND s.requested_schedule > NOW() 
                    ORDER BY s.requested_schedule ASC 
                    LIMIT 2
                ");
                if ($upcoming_list && $upcoming_list->num_rows > 0):
                    while($up = $upcoming_list->fetch_assoc()): ?>
                    <div class="activity-item">
                        <p style="margin:0;">
                            <strong>
                                <?php echo htmlspecialchars($up['tutor_name']); ?>
                            </strong>
                            <br>
                            <span class="session-time">
                                🗓️ <?php echo date("M d, Y - h:i A", 
                                    strtotime($up['requested_schedule'])); ?>
                            </span>
                        </p>
                    </div>
                    <?php endwhile;
                else: ?>
                    <p style="color:#718096; font-size:14px;">
                        No upcoming sessions yet.
                    </p>
                <?php endif; ?>
            </div>

            <div class="box">
                <h3>Recent Messages</h3>
                <?php if ($recent_messages && $recent_messages->num_rows > 0): ?>
                    <?php while($msg = $recent_messages->fetch_assoc()): ?>
                        <div class="activity-item" onclick="location.href='tutee_messages.php?tutor_id=<?php echo $msg['sender_id']; ?>'" style="cursor:pointer;">
                            <p style="margin:0;">
                                <strong>
                                    <?php echo htmlspecialchars($msg['sender_name']); ?>
                                </strong>
                                <br>
                                <small style="color:#718096; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 200px;">
                                    <?php echo htmlspecialchars(substr($msg['message_content'], 0, 50)); ?><?php echo strlen($msg['message_content']) > 50 ? '...' : ''; ?>
                                </small>
                            </p>
                            <span style="font-size: 11px; color: #999;">
                                <?php echo date("M j, g:i A", strtotime($msg['date_sent'])); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#718096; font-size:14px;">
                        No recent messages.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
}
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
}
</script>
<script src="/TutorLoop/Frontend/js/tutee_dashboard.js"></script>
</body>
</html>