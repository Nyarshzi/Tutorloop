<?php
session_start();
include("config/db.php");

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

/**
 * FETCH ACCEPTED SESSIONS
 * Fixed column: session_status instead of status
 */
$tutor_id = $_SESSION['user_id']; 

$sql = "SELECT s.*, u.name AS student_name, sub.subject_name 
        FROM sessions s
        JOIN users u ON s.tutee_id = u.user_id 
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutor_id = $tutor_id AND s.session_status = 'Accepted'
        ORDER BY s.requested_schedule ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutor_myschedule.css">
    
    <style>
        /* CSS FIXES FOR NAVIGATION */
        .sidebar nav a {
            text-decoration: none !important;
            color: white !important;
            display: block;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .sidebar nav a.active,
        .sidebar nav a:hover {
            background: #d4a017;
            color: black !important;
            font-weight: 500;
        }

        .logout {
            text-decoration: none;
            color: black;
            cursor: pointer;
        }
        
        .status.upcoming {
            background-color: #d1e7ff;
            color: #004085;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a href="tutor_session_request.php">Session Requests</a>
            <a href="tutor_myschedule.php" class="active">My Schedule</a>
            <a href="tutor_mystudents.php">My Students</a>
            <a href="tutor_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn">☰</button>
            <h1>My Schedule</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="schedule">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    // Formatting the datetime
                    $timestamp = strtotime($row['requested_schedule']);
                    $displayDate = date("F j, Y", $timestamp);
                    $displayTime = date("g:i A", $timestamp);
                ?>
                    <div class="schedule-card">
                        <div class="info">
                            <h3><?php echo htmlspecialchars($row['student_name']); ?></h3>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?></p>
                            <p><strong>Date:</strong> <?php echo $displayDate; ?></p>
                            <p><strong>Time:</strong> <?php echo $displayTime; ?></p>
                        </div>
                        <div class="actions">
                            <span class="status upcoming">Upcoming</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="schedule-card empty">
                    <p>No scheduled sessions yet. Check your <a href="tutor_session_request.php" style="color: #d4a017;">Session Requests</a> to accept new ones!</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    if(menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
</script>

</body>
</html>