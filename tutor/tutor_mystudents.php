<?php
session_start();
include("../config/db.php"); // Using your existing db config path

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

$tutor_id = $_SESSION['user_id']; 

// Updated Query: Includes both 'Accepted' and 'Completed' statuses
// Added ORDER BY to show most recent activity first
$sql = "SELECT DISTINCT u.name, u.email, sub.subject_name, fr.rating, fr.feedback_comment, s.session_status
        FROM sessions s
        JOIN users u ON s.tutee_id = u.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        LEFT JOIN feedback_ratings fr ON s.session_id = fr.session_id
        WHERE s.tutor_id = ? 
        AND s.session_status IN ('Accepted', 'Completed')
        ORDER BY s.session_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutor_myschedule.css">    <style>
        .sidebar nav a { text-decoration: none !important; color: white !important; display: block; padding: 12px; margin-bottom: 10px; border-radius: 8px; }
        .sidebar nav a.active, .sidebar nav a:hover { background: #d4a017; color: black !important; }
        
        .rating-star { color: #d4a017; font-weight: bold; }
        .student-email { color: #666; font-size: 0.9em; }
        .status-badge { 
            font-size: 0.75em; 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-accepted { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a href="tutor_profile.php">My Profile</a>
            <a href="tutor_myschedule.php">My Schedule</a>
            <a href="tutor_session_request.php">Session Requests</a>
            <a href="tutor_mystudents.php" class="active">My Students</a>
            <a href="tutor_messages.php">Messages</a>
            <a href="tutor_ratings.php">My Ratings</a>
            <a href="../analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>My Students</h1>
            <button class="logout" onclick="location.href='../logout.php'">Logout</button>
        </header>

        <section class="schedule">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="schedule-card">
                        <div class="info">
                            <h3>
                                <?php echo htmlspecialchars($row['name']); ?>
                                <span class="status-badge <?php echo ($row['session_status'] == 'Accepted') ? 'status-accepted' : 'status-completed'; ?>">
                                    <?php echo htmlspecialchars($row['session_status']); ?>
                                </span>
                            </h3>
                            <p class="student-email"><?php echo htmlspecialchars($row['email']); ?></p>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?></p>
                        </div>
                        <div class="actions" style="text-align: right;">
                            <p class="rating-star">
                                <?php 
                                if ($row['session_status'] == 'Completed') {
                                    echo $row['rating'] ? str_repeat("★", $row['rating']) : "No rating yet"; 
                                } else {
                                    echo "Ongoing Session";
                                }
                                ?>
                            </p>
                            <?php if ($row['feedback_comment']): ?>
                                <p style="font-style: italic; font-size: 0.8em;">"<?php echo htmlspecialchars($row['feedback_comment']); ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="schedule-card empty">
                    <p>No active or completed students found.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>