<?php
include("config/db.php");

// 1. Get the search term from the URL
$search_query = isset($_GET['tutor_query']) ? $_GET['tutor_query'] : '';

// 2. Prepare terms (Force to lowercase for comparison)
$search_term = strtolower($search_query);
$likeTerm = "%" . $search_term . "%";
$role = 'tutor';

// 3. Updated SQL: Checks name OR subject_name with case-insensitivity
// Added a JOIN to the subjects table to allow subject searching
$sql = "SELECT u.user_id, u.name, u.profile_pic, tp.tutoring_rate, tp.description, s.subject_name 
        FROM users u
        JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
        LEFT JOIN subjects s ON tp.subject_id = s.subject_id
        WHERE (LOWER(u.name) LIKE ? OR LOWER(s.subject_name) LIKE ?) 
        AND u.role = ?";

$stmt = $conn->prepare($sql);

// 4. Bind parameters (Now 3 placeholders: name, subject, and role)
$stmt->bind_param("sss", $likeTerm, $likeTerm, $role); 

$stmt->execute();
$results = $stmt->get_result();
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
            <a href="tutee_dashboard.php">Dashboard</a>
            <a href="tutee_session.php">Sessions</a>
            <a href="tutee_messages.php">Messages</a>
            <a href="tutee_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
            <a href="tutee_dashboard.php" class="logout">Back</a>
        </header>

        <section class="results-container">
            <?php if ($results && $results->num_rows > 0): ?>
                <div class="tutor-grid">
                    <?php while($tutor = $results->fetch_assoc()): ?>
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
                                <a href="tutor_view_profile.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="tutor-name-link">
                                    <h3><?php echo htmlspecialchars($tutor['name']); ?></h3>
                                </a>
                                
                                <p class="tutor-subject"><?php echo htmlspecialchars($tutor['subject_name'] ?? 'General Tutor'); ?></p>
                                <p class="tutor-rate">₱<?php echo number_format($tutor['tutoring_rate'], 2); ?>/hr</p>
                                
                                <button class="view-btn" onclick="location.href='tutor_view_profile.php?tutor_id=<?php echo $tutor['user_id']; ?>'">View Profile</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>No tutors found matching "<strong><?php echo htmlspecialchars($search_query); ?></strong>".</p>
                    <a href="tutee_dashboard.php">Try searching for a name or a subject like 'PHP'</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>