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

    <style>
        .profile-container {
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        .profile-card-view {
            background: #fff;
            width: 100%;
            max-width: 950px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .profile-header {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 40px;
            align-items: start;
        }

        .profile-img-container {
            width: 200px;
            height: 200px;
            overflow: hidden;
            border-radius: 20px;
            border: 3px solid #d4a017;
            flex-shrink: 0;
        }

        .profile-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .placeholder-avatar {
            width: 100%;
            height: 100%;
            background: #0b2c59;
            color: white;
            font-size: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-main-info h2 {
            font-size: 42px;
            margin-bottom: 10px;
            color: #0b2c59;
        }

        .rating {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .phone {
            font-size: 17px;
            color: #444;
        }

        hr {
            border: none;
            border-top: 1px solid #e5e5e5;
            margin: 30px 0;
        }

        .profile-body h3 {
            color: #0b2c59;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .bio {
            line-height: 1.8;
            color: #555;
            font-size: 16px;
        }

        .subject-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .subject-card h4 {
            margin-bottom: 15px;
            color: #0b2c59;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
        }

        table th {
            background: #f8f8f8;
        }

        table tr {
            border-bottom: 1px solid #eee;
        }

        .profile-footer {
            margin-top: 35px;
            display: flex;
            justify-content: flex-end;
        }

        .book-btn {
            background: #d4a017;
            color: #0b2c59;
            border: none;
            padding: 15px 35px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .book-btn:hover {
            background: #b88a14;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {

            .profile-header {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .profile-img-container {
                margin: 0 auto;
            }

            .profile-main-info h2 {
                font-size: 32px;
            }

            .profile-footer {
                justify-content: center;
            }
        }
    </style>
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
            <a href="/TutorLoop/search_results.php" class="logout">
                ← Back to Search
            </a>

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