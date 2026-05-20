<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$tutor_name = $_SESSION['user_name'] ?? 'Tutor';

// 1. Total tutoring sessions conducted
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions WHERE session_status = 'Completed'")->fetch_assoc()['count'];

// 2. Most requested subjects
$subjects_query = $conn->query("
    SELECT sub.subject_name, COUNT(*) as count
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.subject_id
    GROUP BY s.subject_id
    ORDER BY count DESC
    LIMIT 10
");
$subjects_data = [];
$subjects_labels = [];
while ($row = $subjects_query->fetch_assoc()) {
    $subjects_labels[] = $row['subject_name'];
    $subjects_data[] = $row['count'];
}

// 3. Top rated tutors
$top_tutors_query = $conn->query("
    SELECT u.name, AVG(fr.rating) as avg_rating
    FROM feedback_ratings fr
    JOIN users u ON fr.tutor_id = u.user_id
    GROUP BY fr.tutor_id
    ORDER BY avg_rating DESC
    LIMIT 10
");
$top_tutors_labels = [];
$top_tutors_data = [];
while ($row = $top_tutors_query->fetch_assoc()) {
    $top_tutors_labels[] = $row['name'];
    $top_tutors_data[] = round($row['avg_rating'], 1);
}

// 4. Distribution of tutors vs tutees
$role_distribution = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$tutors_count = 0;
$tutees_count = 0;
while ($row = $role_distribution->fetch_assoc()) {
    if ($row['role'] == 'tutor') {
        $tutors_count = $row['count'];
    } elseif ($row['role'] == 'tutee') {
        $tutees_count = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/analytics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container"> 
    <aside class="sidebar"> 
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a href="tutor_profile.php">My Profile</a>
            <a href="tutor_myschedule.php">My Schedule</a>
            <a href="tutor_session_request.php">Session Requests</a>
            <a href="tutor_mystudents.php">My Students</a>
            <a href="tutor_messages.php">Messages</a>
            <a href="tutor_ratings.php">My Ratings</a>
            <a href="analytics.php" class="active">Analytics</a>
        </nav>
    </aside>

    <main class="main"> 
        <header class="topbar">
            <h1>Platform Analytics</h1>
            <a href="logout.php" class="logout">Logout</a>
        </header>

        <section class="stats-cards">
            <div class="stat-card">
                <h2><?php echo $total_sessions; ?></h2>
                <p>Total Sessions Completed</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $tutors_count; ?></h2>
                <p>Registered Tutors</p>
            </div>
            <div class="stat-card">
                <h2><?php echo $tutees_count; ?></h2>
                <p>Registered Tutees</p>
            </div>
        </section>

        <section class="charts">
            <div class="chart-container">
                <h3>Most Requested Subjects</h3>
                <canvas id="subjectsChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Top Rated Tutors</h3>
                <?php if (empty($top_tutors_data)): ?>
                    <p>No ratings submitted yet</p>
                <?php else: ?>
                    <canvas id="tutorsChart"></canvas>
                <?php endif; ?>
            </div>
            <div class="chart-container">
                <h3>User Distribution</h3>
                <canvas id="distributionChart"></canvas>
            </div>
        </section>
    </main>
</div>

<script>
const subjectsData = <?php echo json_encode($subjects_data); ?>;
const subjectsLabels = <?php echo json_encode($subjects_labels); ?>;
const topTutorsData = <?php echo json_encode($top_tutors_data); ?>;
const topTutorsLabels = <?php echo json_encode($top_tutors_labels); ?>;
const tutorsCount = <?php echo $tutors_count; ?>;
const tuteesCount = <?php echo $tutees_count; ?>;

// Subjects Bar Chart
new Chart(document.getElementById('subjectsChart'), {
    type: 'bar',
    data: {
        labels: subjectsLabels,
        datasets: [{
            label: 'Sessions',
            data: subjectsData,
            backgroundColor: '#d4a017',
            borderColor: '#b88a14',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Top Tutors Bar Chart
new Chart(document.getElementById('tutorsChart'), {
    type: 'bar',
    data: {
        labels: topTutorsLabels,
        datasets: [{
            label: 'Average Rating',
            data: topTutorsData,
            backgroundColor: '#0d2a4a',
            borderColor: '#0a1f35',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 10
            }
        }
    }
});

// User Distribution Pie Chart
new Chart(document.getElementById('distributionChart'), {
    type: 'pie',
    data: {
        labels: ['Tutors', 'Tutees'],
        datasets: [{
            data: [tutorsCount, tuteesCount],
            backgroundColor: ['#d4a017', '#0d2a4a'],
            borderColor: ['#b88a14', '#0a1f35'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true
    }
});
</script>
</body>
</html>