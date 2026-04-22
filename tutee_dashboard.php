<?php
session_start();
include("config/db.php");
date_default_timezone_set('Asia/Manila'); // Matches your tutor side logic

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$tutee_name = $_SESSION['user_name'] ?? 'Tutee';

// --- DYNAMIC STATISTICS ---

// 1. Completed
$completed_count = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutee_id = $tutee_id AND session_status = 'Completed'")->fetch_assoc()['c'];

// 2. Requests (Pending sessions that are still in the future)
$requests_count = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutee_id = $tutee_id AND session_status = 'Pending' AND requested_schedule > NOW()")->fetch_assoc()['c'];

// 3. Subjects
$subjects_count = $conn->query("SELECT COUNT(DISTINCT subject_id) as c FROM sessions WHERE tutee_id = $tutee_id")->fetch_assoc()['c'];

// 4. Upcoming (Accepted or Ongoing sessions that haven't passed yet)
$upcoming_count = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutee_id = $tutee_id AND session_status IN ('Accepted', 'Ongoing') AND requested_schedule > NOW()")->fetch_assoc()['c'];

// Fetch Recent Activity for "My Requests"
$my_requests = $conn->query("SELECT s.session_status, s.requested_schedule, 
                                    u.name as tutor_name, 
                                    sub.subject_name 
                             FROM sessions s 
                             JOIN users u ON s.tutor_id = u.user_id 
                             JOIN subjects sub ON s.subject_id = sub.subject_id 
                             WHERE s.tutee_id = $tutee_id 
                             ORDER BY s.session_id DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutee Dashboard | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .status-tag { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .pending { background: #FFF3CD; color: #856404; }
        .accepted { background: #D1E7FF; color: #004085; }
        .ongoing { background: #E0F7FA; color: #006064; }
        .declined { background: #F8D7DA; color: #721C24; }
        .completed { background: #D4EDDA; color: #155724; }
        .expired { background: #E2E3E5; color: #383D41; }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutee_dashboard.php" class="active">Dashboard</a>
            <a href="tutee_session.php">Sessions</a>
            <a href="tutee_messages.php">Messages</a>
            <a href="tutee_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Good Morning ☀️, <?php echo htmlspecialchars($tutee_name); ?> 🎓</h1>
            <a href="logout.php" class="logout" style="text-decoration: none; padding: 8px 16px; background: #fec107; color: #000; border-radius: 8px; font-weight: 600;">Logout</a>
        </header>

        <section class="cards">
            <div class="card"><h2><?php echo $completed_count; ?></h2><p>Completed</p></div>
            <div class="card"><h2><?php echo $requests_count; ?></h2><p>Requests</p></div>
            <div class="card"><h2><?php echo $subjects_count; ?></h2><p>Subjects</p></div>
            <div class="card"><h2><?php echo $upcoming_count; ?></h2><p>Upcoming</p></div>
        </section>

        <section class="search-section">
            <div class="box search-box">
                <h3>Find a Tutor</h3>
                <form action="search_results.php" method="GET" class="search-bar">
                    <input type="text" name="tutor_query" placeholder="Enter tutor name..." required>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </section>

        <section class="bottom">
            <div class="box">
                <h3>My Requests</h3>
                <?php if ($my_requests && $my_requests->num_rows > 0): ?>
                    <?php while($row = $my_requests->fetch_assoc()): 
                        $is_outdated = (strtotime($row['requested_schedule']) < time());
                        $current_db_status = $row['session_status'];
                        
                        // If it's still pending but the time has passed, show as Expired
                        $display_status = ($current_db_status == 'Pending' && $is_outdated) ? 'Expired' : $current_db_status;
                    ?>
                        <div class="activity-item" style="border-bottom: 1px solid #edf2f7; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                            <p style="margin: 0;">
                                <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong> 
                                <br><small>Tutor: <?php echo htmlspecialchars($row['tutor_name']); ?></small>
                            </p>
                            <span class="status-tag <?php echo strtolower($display_status); ?>">
                                <?php echo $display_status; ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You haven't made any requests yet. Find a tutor above!</p>
                <?php endif; ?>
            </div>

            <div class="box">
                <h3>Upcoming Sessions</h3>
                <?php
                // Standardizing to include 'Ongoing' in the upcoming list if the time hasn't passed
                $upcoming_list = $conn->query("SELECT s.requested_schedule, u.name as tutor_name 
                                               FROM sessions s 
                                               JOIN users u ON s.tutor_id = u.user_id 
                                               WHERE s.tutee_id = $tutee_id 
                                               AND s.session_status IN ('Accepted', 'Ongoing') 
                                               AND s.requested_schedule > NOW() 
                                               ORDER BY s.requested_schedule ASC 
                                               LIMIT 2");
                
                if ($upcoming_list && $upcoming_list->num_rows > 0): 
                    while($up = $upcoming_list->fetch_assoc()): ?>
                        <div class="activity-item" style="padding: 10px 0; border-bottom: 1px solid #edf2f7;">
                            <p style="margin: 0;">
                                <strong><?php echo htmlspecialchars($up['tutor_name']); ?></strong><br>
                                <span class="session-time" style="font-size: 13px; color: #4a5568;">
                                    🗓️ <?php echo date("M d, Y - h:i A", strtotime($up['requested_schedule'])); ?>
                                </span>
                            </p>
                        </div>
                    <?php endwhile;
                else: ?>
                    <p style="color: #718096; font-size: 14px;">No active upcoming sessions.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>