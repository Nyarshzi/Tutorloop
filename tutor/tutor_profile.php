<?php
session_start();
include("../config/db.php");

// --- START OF ADDED VERIFICATION LOGIC ---
// 1. Access Control: Check if logged in, if role is tutor, and if verified
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
        header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the account is verified in the database
$status_check = $conn->prepare("SELECT is_verified FROM users WHERE user_id = ?");
$status_check->bind_param("i", $user_id);
$status_check->execute();
$status_res = $status_check->get_result()->fetch_assoc();

if (!$status_res || (int)$status_res['is_verified'] === 0) {
    // If not verified, kick them back to login with a message
    session_destroy();
    header("Location: ../login.php?error=unverified");    exit();
}
// --- END OF ADDED VERIFICATION LOGIC ---


// FROM HERE DOWN: EVERYTHING IS YOUR ORIGINAL UNTOUCHED CODE
// FETCHING DATA - Updated to pull profile_pic from 'users u'
$sql = "SELECT 
            u.name,
            u.email,
            u.profile_pic,
            tp.tutor_id,
            tp.average_rating,
            tp.description,
            tp.phone_number,
            ts.rate AS tutoring_rate,
            GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') AS subject_name
        FROM users u
        JOIN tutor_profiles tp 
            ON u.user_id = tp.tutor_id
        LEFT JOIN tutor_subjects ts
            ON tp.tutor_id = ts.tutor_id
        LEFT JOIN subjects s
            ON ts.subject_id = s.subject_id
        WHERE u.user_id = ?
        GROUP BY u.user_id";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $initials = strtoupper(substr($row['name'], 0, 1));
    
    // Student Count Logic
    $student_query = "SELECT COUNT(DISTINCT tutee_id) as total_students FROM sessions WHERE tutor_id = ?";
    $stmt_students = $conn->prepare($student_query);
    $stmt_students->bind_param("i", $row['tutor_id']);
    $stmt_students->execute();
    $total_students = $stmt_students->get_result()->fetch_assoc()['total_students'] ?? 0;

    $rating_val = ($row['average_rating'] > 0) ? number_format($row['average_rating'], 1) : "0.0";
} else {
    header("Location: ../create_tutor_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutor_profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutor/tutor_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutor/tutor_profile.php" class="active">My Profile</a>
            <a href="/tutorloop/tutor/tutor_myschedule.php">My Schedule</a>
            <a href="/tutorloop/tutor/tutor_session_request.php">Session Requests</a>
            <a href="/tutorloop/tutor/tutor_mystudents.php">My Students</a>
            <a href="/tutorloop/tutor/tutor_messages.php">Messages</a>
            <a href="/tutorloop/tutor/tutor_ratings.php">My Ratings</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>My Profile</h1>
            <button class="logout" onclick="window.location.href='/tutorloop/logout.php'">Logout</button>
        </header>

        <section class="profile">
            <div class="profile-card">
                <div class="profile-header-accent"></div>
                <div class="profile-content">
                    <div class="avatar-container">
                    <div class="avatar-frame" style="border-radius: 50%; overflow: hidden; border: 4px solid #d4a017;">
                    <?php
                    $profilePic = !empty($row['profile_pic'])
                        ? "../uploads/" . htmlspecialchars($row['profile_pic'])
                        : "";
                        ?>

                        <?php if(!empty($row['profile_pic'])): ?>

                        <img src="<?php echo $profilePic; ?>?v=<?php echo time(); ?>"
                        alt="Profile"
                        style="width: 100%; height: 100%; object-fit: cover;">

                            <?php else: ?>

                            <span class="avatar-text">
                                <?php echo $initials; ?>
                            </span>

                        <?php endif; ?>

                </div>
             </div>
                    
                    <h2 class="profile-name"><?php echo htmlspecialchars($row['name']); ?></h2>
                    <p class="profile-tagline" style="color: #d4a017; font-weight: bold;">
                        <?php echo htmlspecialchars($row['subject_name'] ?? 'Not set'); ?>
                    </p>
                    <p class="profile-tagline"><?php echo htmlspecialchars($row['description']); ?></p>
                    
                    <div class="action-area">
                        <a href="/tutorloop/create_tutor_profile.php">
                            <button class="primary-btn">Edit Profile</button>
                        </a>
                    </div>
                </div>

                <div class="profile-stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $total_students; ?></span>
                        <span class="stat-label">Students</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $rating_val; ?></span>
                        <span class="stat-label">Rating</span>
                    </div>
                </div>
            </div>

            <div class="profile-details">
                <div class="detail-box">
                    <h3>Personal Information</h3>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($row['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo !empty($row['phone_number']) ? htmlspecialchars($row['phone_number']) : 'Not set'; ?></p>
                </div>

                <div class="detail-box">
    <h3>Tutoring Details</h3>
    <div class="subjects-scroll">
    <?php
    $subjects_sql = "SELECT ts.id, s.subject_name, ts.rate
                     FROM tutor_subjects ts
                     JOIN subjects s ON ts.subject_id = s.subject_id
                     WHERE ts.tutor_id = ?
                     ORDER BY ts.id ASC";
    $subjects_stmt = $conn->prepare($subjects_sql);
    $subjects_stmt->bind_param("i", $user_id);
    $subjects_stmt->execute();
    $subjects_result = $subjects_stmt->get_result();

    if ($subjects_result->num_rows > 0):
        while ($subj = $subjects_result->fetch_assoc()):
            $avail_sql = "SELECT day_of_week, start_time, end_time
                          FROM tutor_availability
                          WHERE tutor_subject_id = ?
                          ORDER BY FIELD(day_of_week,
                          'Monday','Tuesday','Wednesday',
                          'Thursday','Friday','Saturday','Sunday')";
            $avail_stmt = $conn->prepare($avail_sql);
            $avail_stmt->bind_param("i", $subj['id']);
            $avail_stmt->execute();
            $avail_result = $avail_stmt->get_result();
    ?>
        <div class="subject-detail-card">
    <div class="subject-detail-header">
        <p class="subject-detail-name"><?php echo htmlspecialchars($subj['subject_name']); ?></p>
        <span class="subject-detail-rate">₱<?php echo number_format($subj['rate'], 2); ?>/hr</span>
    </div>
    <?php if ($avail_result->num_rows > 0): ?>
        <table class="subject-schedule-table">
            <tr>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
            </tr>
            <?php while ($avail = $avail_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($avail['day_of_week']); ?></td>
                <td><?php echo date('g:i A', strtotime($avail['start_time'])); ?></td>
                <td><?php echo date('g:i A', strtotime($avail['end_time'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="no-schedule-msg">No schedule set.</p>
    <?php endif; ?>
</div>
    <?php
        endwhile;
    else:
    ?>
            <p class="no-subjects-msg">No subjects added yet. Go to <a href="/tutorloop/tutor/tutor_myschedule.php">My Schedule</a> to add subjects.</p>
    <?php endif; ?>
    </div>
</div>
            </div>
        </section>
    </main>
</div>
<script src="../Frontend/js/phone_validation.js"></script>
<script>
    attachPhoneValidation('phone_number', 'phone_error');
    blockIfInvalid('your_form_id', 'phone_number');
</script>
</body>
</html>