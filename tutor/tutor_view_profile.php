<?php
session_start();
include("../config/db.php");
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tutee') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['tutor_id'])) {
    header("Location: ../search_results.php");
    exit();
}

$tutor_id = intval($_GET['tutor_id']);

$sql = "SELECT u.name, u.email, u.profile_pic,
               tp.description, tp.phone_number, 
               tp.average_rating, tp.tutoring_rate
        FROM users u
        JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
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

    <link rel="stylesheet" href="../Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="../Frontend/css/tutor_view_profile.css">

    
</head>

<body>

<div class="container">

    <aside class="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>

        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="../tutee/search_results.php" class="active">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">

        <header class="topbar">
            <h1>Tutor Profile</h1>
        </header>

        <section class="profile-container">

            <div class="profile-card-view">

                <!-- PROFILE HEADER -->
                <div class="profile-header">

                    <!-- PROFILE IMAGE -->
                    <div class="profile-img-container">

                        <?php if(!empty($tutor['profile_pic']) &&
                        file_exists("../uploads/" . $tutor['profile_pic'])): ?>

                            <img src="../uploads/<?php echo htmlspecialchars($tutor['profile_pic']); ?>"
                                 alt="Tutor">

                        <?php else: ?>

                            <div class="placeholder-avatar">
                                <?php echo strtoupper(substr($tutor['name'], 0, 1)); ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- PROFILE INFO -->
                    <div class="profile-main-info">

                        <h2>
                            <?php echo htmlspecialchars($tutor['name']); ?>
                        </h2>

                        <?php if(!empty($tutor['average_rating'])): ?>

                            <p class="rating">
                                ⭐ <?php echo number_format($tutor['average_rating'], 1); ?>/5.0
                            </p>

                        <?php else: ?>

                            <p class="rating" style="color:#999;">
                                No ratings yet
                            </p>

                        <?php endif; ?>

                        <?php if(!empty($tutor['phone_number'])): ?>

                            <p class="phone">
                                <strong>Phone:</strong>
                                <?php echo htmlspecialchars($tutor['phone_number']); ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

                <hr>

                <!-- ABOUT -->
                <div class="profile-body">

                    <h3>About Me</h3>

                    <?php if(!empty($tutor['description'])): ?>

                        <p class="bio">
                            <?php echo nl2br(htmlspecialchars($tutor['description'])); ?>
                        </p>

                    <?php else: ?>

                        <p class="bio" style="color:#999;">
                            No bio added yet.
                        </p>

                    <?php endif; ?>

                </div>

                <hr>

                <!-- SUBJECTS -->
                <h3 style="margin-bottom:20px;">Subjects & Availability</h3>

                <?php

                $subjects_sql = "SELECT ts.id as tutor_subject_id,
                                        s.subject_name, ts.rate
                                 FROM tutor_subjects ts
                                 JOIN subjects s ON ts.subject_id = s.subject_id
                                 WHERE ts.tutor_id = ?
                                 ORDER BY s.subject_name ASC";

                $subjects_stmt = $conn->prepare($subjects_sql);
                $subjects_stmt->bind_param("i", $tutor_id);
                $subjects_stmt->execute();

                $subjects_result = $subjects_stmt->get_result();

                if ($subjects_result->num_rows > 0):

                    while($subject = $subjects_result->fetch_assoc()):

                ?>

                <div class="subject-card">

                    <h4>
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                        — ₱<?php echo number_format($subject['rate'], 2); ?>/hr
                    </h4>

                    <?php

                    $avail_sql = "SELECT day_of_week, start_time, end_time
                                  FROM tutor_availability
                                  WHERE tutor_subject_id = ?
                                  ORDER BY FIELD(day_of_week,
                                  'Monday','Tuesday','Wednesday',
                                  'Thursday','Friday','Saturday','Sunday')";

                    $avail_stmt = $conn->prepare($avail_sql);
                    $avail_stmt->bind_param("i", $subject['tutor_subject_id']);
                    $avail_stmt->execute();

                    $avail_result = $avail_stmt->get_result();

                    if ($avail_result->num_rows > 0):

                    ?>

                    <table>

                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>

                        <?php while($avail = $avail_result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($avail['day_of_week']); ?>
                            </td>

                            <td>
                                <?php echo date('g:i A', strtotime($avail['start_time'])); ?>
                            </td>

                            <td>
                                <?php echo date('g:i A', strtotime($avail['end_time'])); ?>
                            </td>

                        </tr>

                        <?php endwhile; ?>

                    </table>

                    <?php else: ?>

                        <p style="color:#666;">
                            No schedule set for this subject yet.
                        </p>

                    <?php endif; ?>

                </div>

                <?php
                    endwhile;
                else:
                ?>

                    <p style="color:#666;">
                        No subjects added yet.
                    </p>

                <?php endif; ?>

                <!-- BUTTON -->
                <div class="profile-footer">
    <a href="/TutorLoop/search_results.php" class="back-btn">
        ← Back to Search
    </a>
    <button class="book-btn"
        onclick="location.href='/tutorloop/tutee/api/request_session.php?tutor_id=<?php echo $tutor_id; ?>'">
        Request a Session
    </button>
</div>

            </div>

        </section>

    </main>

</div>

</body>
</html>