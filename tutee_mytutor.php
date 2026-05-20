<?php
session_start();
include("config/db.php");
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$message = '';
$selected_session_id = isset($_GET['rate_session_id']) ? intval($_GET['rate_session_id']) : 0;
$selected_session = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = intval($_POST['session_id'] ?? 0);
    $tutor_id = intval($_POST['tutor_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);

    if (!$session_id || !$tutor_id || $rating < 1 || $rating > 10) {
        $message = 'Please select a valid session and enter a rating from 1 to 10.';
    } else {
        $check_stmt = $conn->prepare("SELECT session_status FROM sessions WHERE session_id = ? AND tutee_id = ?");
        $check_stmt->bind_param("ii", $session_id, $tutee_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $message = 'Session not found or does not belong to you.';
        } else {
            $session_row = $check_result->fetch_assoc();
            $session_status = $session_row['session_status'];

            if ($session_status === 'Completed') {
                $message = 'Rating is only available before tutor marks the session done.';
            } else {
                $rating_check = $conn->prepare("SELECT session_id FROM feedback_ratings WHERE session_id = ?");
                $rating_check->bind_param("i", $session_id);
                $rating_check->execute();
                $rating_check_result = $rating_check->get_result();

                if ($rating_check_result->num_rows > 0) {
                    $message = 'You have already rated this session.';
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO feedback_ratings (session_id, tutor_id, rating, feedback_comment) VALUES (?, ?, ?, '')");
                    $insert_stmt->bind_param("iii", $session_id, $tutor_id, $rating);

                    if ($insert_stmt->execute()) {
                        $avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM feedback_ratings WHERE tutor_id = ?");
                        $avg_stmt->bind_param("i", $tutor_id);
                        $avg_stmt->execute();
                        $avg_result = $avg_stmt->get_result();
                        $avg_row = $avg_result->fetch_assoc();
                        $average_rating = $avg_row['avg_rating'] ?? 0;
                        
                        $update_stmt = $conn->prepare("UPDATE tutor_profiles SET average_rating = ? WHERE tutor_id = ?");
                        $update_stmt->bind_param("di", $average_rating, $tutor_id);
                        $update_stmt->execute();

                        $message = 'Your rating has been submitted successfully.';
                        $selected_session_id = 0;
                    } else {
                        $message = 'Error saving rating: ' . $conn->error;
                    }
                }

                $rating_check->close();
            }
        }

        $check_stmt->close();
    }
}

if ($selected_session_id) {
    $select_stmt = $conn->prepare(
        "SELECT s.session_id, s.tutor_id, s.session_status, s.requested_schedule, u.name AS tutor_name, fr.rating
         FROM sessions s
         JOIN users u ON s.tutor_id = u.user_id
         LEFT JOIN feedback_ratings fr ON fr.session_id = s.session_id
         WHERE s.session_id = ? AND s.tutee_id = ?"
    );
    $select_stmt->bind_param("ii", $selected_session_id, $tutee_id);
    $select_stmt->execute();
    $selected_result = $select_stmt->get_result();
    if ($selected_result && $selected_result->num_rows > 0) {
        $selected_session = $selected_result->fetch_assoc();
    }
    $select_stmt->close();
}

$query = "SELECT s.session_id, s.tutor_id, s.requested_schedule, s.session_status, u.name AS tutor_name, fr.rating
          FROM sessions s
          JOIN users u ON s.tutor_id = u.user_id
          LEFT JOIN feedback_ratings fr ON fr.session_id = s.session_id
          WHERE s.tutee_id = ?
          ORDER BY s.requested_schedule DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tutee_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tutors | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <style>
        .rate-card { margin-bottom: 24px; padding: 24px; background: #ffffff; border-radius: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .tutor-card { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 20px 0; border-bottom: 1px solid #f0f0f0; }
        .tutor-card:last-child { border-bottom: none; }
        .tutor-info h3 { margin-bottom: 6px; font-size: 1.1rem; }
        .tutor-info p { margin: 4px 0; color: #4a5568; font-size: 0.95rem; }
        .rate-btn, .rate-disabled { background: #d4a017; color: #0d2a4a; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .rate-disabled { opacity: 0.5; cursor: not-allowed; }
        .status-pill { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #edf2f7; color: #2d3748; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; }
        .rating-label { font-weight: 700; color: #155724; }
        .message-box { margin-bottom: 20px; padding: 18px; border-radius: 14px; background: #e6ffed; border: 1px solid #b7eb8f; color: #22543d; }
        .error-box { margin-bottom: 20px; padding: 18px; border-radius: 14px; background: #fff1f2; border: 1px solid #f5c2c7; color: #842029; }
        .rate-form label { display: block; margin-bottom: 10px; font-weight: 700; }
        .rate-form input[type="number"] { width: 96px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-right: 12px; }
        .rate-form button { margin-top: 10px; }
        .note { color: #4a5568; font-size: 0.95rem; margin-top: 10px; }
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
            <a href="tutee_dashboard.php">Dashboard</a>
            <a href="tutee_profile.php">My Profile</a>
            <a href="search_results.php">Find a Tutor</a>
            <a href="tutee_session.php">My Sessions</a>
            <a href="tutee_mytutor.php" class="active">My Tutors</a>
            <a href="tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>My Tutor</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="rate-card">
            <h2>Rate your tutors</h2>
            <p class="note">The rate button is enabled only for completed sessions that haven't been rated yet. Each session can be rated only once.</p>
        </section>

        <?php if ($message): ?>
            <div class="message-box"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($selected_session && $selected_session['rating'] === null && $selected_session['session_status'] === 'Completed'): ?>
            <section class="rate-card">
                <h2>Rate <?php echo htmlspecialchars($selected_session['tutor_name']); ?></h2>
                <p><strong>Session status:</strong> <?php echo htmlspecialchars($selected_session['session_status']); ?></p>
                <p><strong>Schedule:</strong> <?php echo date('F j, Y - g:i A', strtotime($selected_session['requested_schedule'])); ?></p>
                <form class="rate-form" method="POST">
                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($selected_session['session_id']); ?>">
                    <input type="hidden" name="tutor_id" value="<?php echo htmlspecialchars($selected_session['tutor_id']); ?>">
                    <label for="rating">Rating (1 to 10):</label>
                    <input type="number" id="rating" name="rating" min="1" max="10" required>
                    <button type="submit" class="rate-btn">Submit Rating</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="cards" style="grid-template-columns: 1fr; gap: 0;">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="tutor-card">
                        <div class="tutor-info">
                            <h3><?php echo htmlspecialchars($row['tutor_name']); ?></h3>
                            <p><strong>Session:</strong> <?php echo date('F j, Y', strtotime($row['requested_schedule'])); ?> at <?php echo date('g:i A', strtotime($row['requested_schedule'])); ?></p>
                            <p><strong>Status:</strong> <span class="status-pill"><?php echo htmlspecialchars($row['session_status']); ?></span></p>
                        </div>
                        <div class="actions">
                            <?php if ($row['rating'] !== null): ?>
                                <span class="rating-label">Rated: <?php echo htmlspecialchars($row['rating']); ?>/10</span>
                            <?php elseif ($row['session_status'] === 'Completed'): ?>
                                <a href="?rate_session_id=<?php echo htmlspecialchars($row['session_id']); ?>" class="rate-btn">Rate</a>
                            <?php else: ?>
                                <button class="rate-disabled" disabled>Rate disabled</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="rate-card">
                    <p>No tutor sessions were found yet.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
