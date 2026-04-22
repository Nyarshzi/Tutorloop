<?php
session_start();
include("config/db.php");

if (!isset($_GET['tutor_id'])) {
    header("Location: tutee_dashboard.php");
    exit();
}

$tutor_id = intval($_GET['tutor_id']);

// Fetch tutor details
$sql = "SELECT u.name, u.profile_pic, tp.description, tp.tutoring_rate, s.subject_name 
        FROM users u 
        JOIN tutor_profiles tp ON u.user_id = tp.tutor_id 
        LEFT JOIN subjects s ON tp.subject_id = s.subject_id
        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();

if (!$tutor) {
    echo "Tutor not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutor['name']); ?> | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/tutor_view_profile.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutee_dashboard.php">Dashboard</a>
            <a href="tutee_session.php">Sessions</a>
            <a href="tutee_messages.php">Messages</a>
            <a href="tutee_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Tutor Profile</h1>
            <a href="search_results.php" class="logout">Back to Search</a>
        </header>

        <section class="profile-container">
            <div class="profile-card-view">
                <div class="profile-header">
                    <div class="profile-img-container">
                        <?php if(!empty($tutor['profile_pic'])): ?>
                            <img src="uploads/<?php echo $tutor['profile_pic']; ?>" alt="Tutor">
                        <?php else: ?>
                            <div class="placeholder-avatar"><?php echo strtoupper(substr($tutor['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-main-info">
                        <h2><?php echo htmlspecialchars($tutor['name']); ?></h2>
                        <span class="subject-tag"><?php echo htmlspecialchars($tutor['subject_name'] ?? 'General Tutor'); ?></span>
                        <p class="rate"><strong>Rate:</strong> ₱<?php echo number_format($tutor['tutoring_rate'], 2); ?>/hr</p>
                    </div>
                </div>

                <hr>

                <div class="profile-body">
                    <h3>About Me</h3>
                    <p class="bio"><?php echo nl2br(htmlspecialchars($tutor['description'])); ?></p>
                </div>

                <div class="profile-footer">
                    <button class="book-btn" onclick="location.href='request_session.php?tutor_id=<?php echo $tutor_id; ?>'">Book a Session</button>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>