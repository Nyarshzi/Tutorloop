<?php
include("config/db.php");

$selected_subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$subjects_sql = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjects_result = $conn->query($subjects_sql);

if ($selected_subject_id > 0) {
    $sql = "SELECT DISTINCT 
                u.user_id as tutor_id,
                u.name,
                u.profile_pic,
                tp.description,
                tp.average_rating,
                tp.tutoring_rate
            FROM users u
            JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
            JOIN tutor_subjects ts ON u.user_id = ts.tutor_id
            WHERE ts.subject_id = ?
            AND u.role = 'tutor'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_subject_id);
    $stmt->execute();
    $results = $stmt->get_result();
} else {
    $sql = "SELECT DISTINCT 
                u.user_id as tutor_id,
                u.name,
                u.profile_pic,
                tp.description,
                tp.average_rating,
                tp.tutoring_rate
            FROM users u
            JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
            WHERE u.role = 'tutor'";
    $results = $conn->query($sql);
}

function getTutorSubjects($conn, $tutor_id) {
    $sql = "SELECT s.subject_name, ts.rate
            FROM tutor_subjects ts
            JOIN subjects s ON ts.subject_id = s.subject_id
            WHERE ts.tutor_id = ?
            ORDER BY s.subject_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Tutors | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/search_results.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="search_results.php" class="active">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Find a Tutor</h1>
            <a href="/tutorloop/tutee/tutee_dashboard.php" class="logout">Back</a>
        </header>

        <section class="filter-section">
            <form method="GET" class="filter-form">
                <label for="subject_id">Filter by Subject:</label>
                <select name="subject_id" id="subject_id" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php while($subject = $subjects_result->fetch_assoc()): ?>
                        <option value="<?php echo $subject['subject_id']; ?>"
                                <?php echo ($selected_subject_id == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </section>

        <section class="results-container">
            <?php if ($results && $results->num_rows > 0): ?>
                <div class="tutor-grid">
                    <?php while($tutor = $results->fetch_assoc()):
                        $tutor_subjects = getTutorSubjects($conn, $tutor['tutor_id']);
                    ?>
                        <div class="tutor-card">
                            <div class="tutor-image">
                                <?php if(!empty($tutor['profile_pic'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($tutor['profile_pic']); ?>" alt="Profile">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($tutor['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="tutor-info">
                                <a href="tutor_view_profile.php?tutor_id=<?php echo $tutor['tutor_id']; ?>" class="tutor-name-link">
                                    <h3><?php echo htmlspecialchars($tutor['name']); ?></h3>
                                </a>

                                <?php if(!empty($tutor['average_rating'])): ?>
                                    <p class="tutor-rating">⭐ <?php echo number_format($tutor['average_rating'], 1); ?>/5.0</p>
                                <?php else: ?>
                                    <p class="tutor-rating">No ratings yet</p>
                                <?php endif; ?>

                                <?php if(!empty($tutor['description'])): ?>
                                    <p class="tutor-desc">
                                        <?php echo htmlspecialchars(substr($tutor['description'], 0, 100)); ?>
                                        <?php echo strlen($tutor['description']) > 100 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="tutor-subjects">
                                    <p><strong>Subjects:</strong></p>
                                    <?php 
                                    $has_subjects = false;
                                    while($subj = $tutor_subjects->fetch_assoc()): 
                                        $has_subjects = true;
                                    ?>
                                        <span class="subject-tag">
                                            <?php echo htmlspecialchars($subj['subject_name']); ?>
                                            (₱<?php echo number_format($subj['rate'], 2); ?>/hr)
                                        </span>
                                    <?php endwhile; ?>
                                    <?php if(!$has_subjects): ?>
                                        <span class="subject-tag">No subjects listed yet</span>
                                    <?php endif; ?>
                                </div>

                                <button class="view-btn" 
                                    onclick="location.href='/tutorloop/tutor/tutor_view_profile.php?tutor_id=<?php echo $tutor['tutor_id']; ?>'">
                                    View Profile
                                </button>
                            </div>
                        </div
                    <?php endwhile; ?>
            </div

            <?php else: ?>
                <div class="no-results">
                    <p><?php echo $selected_subject_id > 0 ? 'No tutors found teaching this subject.' : 'No tutors available yet.'; ?></p>
                    <?php if($selected_subject_id > 0): ?>
                        <a href="search_results.php">Show all tutors</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>