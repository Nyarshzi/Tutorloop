<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

// Get average rating
$avg_stmt = $conn->prepare(
    "SELECT average_rating FROM tutor_profiles WHERE tutor_id = ?"
);
$avg_stmt->bind_param("i", $tutor_id);
$avg_stmt->execute();
$avg_row = $avg_stmt->get_result()->fetch_assoc();
$avg_rating = $avg_row['average_rating'] ?? 0;

// Get total number of ratings
$count_stmt = $conn->prepare(
    "SELECT COUNT(*) as total FROM feedback_ratings WHERE tutor_id = ?"
);
$count_stmt->bind_param("i", $tutor_id);
$count_stmt->execute();
$count_row = $count_stmt->get_result()->fetch_assoc();
$total_ratings = $count_row['total'];

// Get all feedback
$feedback_stmt = $conn->prepare("
    SELECT fr.rating, fr.feedback_comment, 
           u.name AS tutee_name, 
           sub.subject_name, 
           s.requested_schedule
    FROM feedback_ratings fr
    JOIN sessions s ON fr.session_id = s.session_id
    JOIN users u ON s.tutee_id = u.user_id
    JOIN subjects sub ON s.subject_id = sub.subject_id
    WHERE fr.tutor_id = ?
    ORDER BY s.requested_schedule DESC
");
$feedback_stmt->bind_param("i", $tutor_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutor_dashboard.css">
    <link rel="stylesheet" href="../Frontend/css/tutor_ratings.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a href="../create_tutor_profile.php">My Profile</a>
            <a href="tutor_myschedule.php">My Schedule</a>
            <a href="tutor_session_request.php">Session Requests</a>
            <a href="tutor_mystudents.php">My Students</a>
            <a href="tutor_messages.php">Messages</a>
            <a href="tutor_ratings.php" class="active">My Ratings</a>
            <a href="../analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>My Ratings & Feedback</h1>
            <a href="../logout.php" class="logout">Logout</a>
        </header>

        <!-- Average Rating Summary -->
        <section class="average-rating">
            <div class="rating-summary">
                <h2>
                    Overall Rating: 
                    <?php echo $avg_rating > 0 
                        ? number_format($avg_rating, 1) . '/5.0' 
                        : 'No ratings yet'; ?>
                </h2>
                <?php if ($avg_rating > 0): ?>
                <div class="stars" style="font-size:28px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span style="color: <?php echo $i <= round($avg_rating) 
                            ? '#f5a623' : '#ccc'; ?>;">★</span>
                    <?php endfor; ?>
                </div>
                <p style="color:#666; margin-top:4px;">
                    Based on <?php echo $total_ratings; ?> 
                    <?php echo $total_ratings == 1 ? 'review' : 'reviews'; ?>
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Feedback List -->
        <section class="feedback-list">
            <h3>Feedback from Students</h3>

            <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
                <?php while($row = $feedback_result->fetch_assoc()): ?>
                <div class="feedback-card">
                    <div class="rating" style="font-size:20px; margin-bottom:6px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span style="color: <?php echo $i <= $row['rating'] 
                                ? '#f5a623' : '#ccc'; ?>;">★</span>
                        <?php endfor; ?>
                        <span style="font-size:14px; color:#666; margin-left:6px;">
                            <?php echo $row['rating']; ?>/5
                        </span>
                    </div>

                    <p class="comment" style="margin:8px 0; font-size:15px;">
                        "<?php echo htmlspecialchars($row['feedback_comment']); ?>"
                    </p>

                    <div class="details" style="font-size:13px; color:#666; 
                                                 display:flex; gap:16px; flex-wrap:wrap;">
                        <span>👤 <?php echo htmlspecialchars($row['tutee_name']); ?></span>
                        <span>📚 <?php echo htmlspecialchars($row['subject_name']); ?></span>
                        <span>📅 <?php echo date("M j, Y", 
                            strtotime($row['requested_schedule'])); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding:20px; color:#666; text-align:center;">
                    No feedback received yet. Complete sessions to receive ratings.
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>