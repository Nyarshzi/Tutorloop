<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['tutor', 'tutee'])) {
    header("Location: /tutorloop/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SHARED PLATFORM DATA (both roles see this)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// 1. Total completed sessions on platform
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions 
    WHERE session_status = 'Completed'")->fetch_assoc()['count'];

// 2. Most requested subjects platform-wide
$subjects_query = $conn->query("
    SELECT sub.subject_name, COUNT(*) as count
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.subject_id
    GROUP BY s.subject_id
    ORDER BY count DESC
    LIMIT 10
");
$subjects_labels = [];
$subjects_data = [];
while ($row = $subjects_query->fetch_assoc()) {
    $subjects_labels[] = $row['subject_name'];
    $subjects_data[] = $row['count'];
}

// 3. Top rated tutors platform-wide
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

// 4. User distribution platform-wide
$role_distribution = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$tutors_count = 0;
$tutees_count = 0;
while ($row = $role_distribution->fetch_assoc()) {
    if ($row['role'] == 'tutor') $tutors_count = $row['count'];
    if ($row['role'] == 'tutee') $tutees_count = $row['count'];
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// RECENT PLATFORM ACTIVITY
// Uses only columns confirmed to exist in the live DB.
// No timestamp columns — ordered by session_id instead.
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$activity_query = $conn->query("
    SELECT activity_type, actor_name, detail, sort_key
    FROM (

        SELECT 'Session Requested' AS activity_type,
            tutee.name AS actor_name,
            CONCAT('Requested ', sub.subject_name, ' with ', tutor.name) AS detail,
            s.session_id AS sort_key
        FROM sessions s
        JOIN users tutee  ON s.tutee_id   = tutee.user_id
        JOIN users tutor  ON s.tutor_id   = tutor.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.session_status = 'Pending'

        UNION ALL

        SELECT 'Session Accepted' AS activity_type,
            tutor.name AS actor_name,
            CONCAT('Accepted ', sub.subject_name, ' session with ', tutee.name) AS detail,
            s.session_id AS sort_key
        FROM sessions s
        JOIN users tutor  ON s.tutor_id   = tutor.user_id
        JOIN users tutee  ON s.tutee_id   = tutee.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.session_status = 'Accepted'

        UNION ALL

        SELECT 'Session Completed' AS activity_type,
            tutee.name AS actor_name,
            CONCAT('Completed ', sub.subject_name, ' session with ', tutor.name) AS detail,
            s.session_id AS sort_key
        FROM sessions s
        JOIN users tutee  ON s.tutee_id   = tutee.user_id
        JOIN users tutor  ON s.tutor_id   = tutor.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.session_status = 'Completed'

        UNION ALL

        SELECT 'Session Declined' AS activity_type,
            tutor.name AS actor_name,
            CONCAT('Declined ', sub.subject_name, ' session request') AS detail,
            s.session_id AS sort_key
        FROM sessions s
        JOIN users tutor  ON s.tutor_id   = tutor.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.session_status = 'Declined'

        UNION ALL

        SELECT 'Session Cancelled' AS activity_type,
            tutee.name AS actor_name,
            CONCAT('Cancelled ', sub.subject_name, ' session') AS detail,
            s.session_id AS sort_key
        FROM sessions s
        JOIN users tutee  ON s.tutee_id   = tutee.user_id
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.session_status = 'Cancelled'

        UNION ALL

        SELECT 'Feedback Submitted' AS activity_type,
            u.name AS actor_name,
            CONCAT('Rated tutor ', tutor.name, ' - ', fr.rating, '/5 stars') AS detail,
            fr.feedback_id AS sort_key
        FROM feedback_ratings fr
        JOIN sessions s  ON fr.session_id = s.session_id
        JOIN users u     ON s.tutee_id    = u.user_id
        JOIN users tutor ON fr.tutor_id   = tutor.user_id

    ) AS activity
    ORDER BY sort_key DESC
    LIMIT 8
");

$activities = [];
if ($activity_query) {
    while ($row = $activity_query->fetch_assoc()) {
        $activities[] = $row;
    }
}

function get_activity_meta(string $type): array {
    return match($type) {
        'Session Requested'     => ['icon' => '📋', 'class' => 'act-requested'],
        'Session Accepted'      => ['icon' => '✅', 'class' => 'act-accepted'],
        'Session Completed'     => ['icon' => '🎓', 'class' => 'act-completed'],
        'Session Declined'      => ['icon' => '❌', 'class' => 'act-declined'],
        'Session Cancelled'     => ['icon' => '🚫', 'class' => 'act-cancelled'],
        'Feedback Submitted'    => ['icon' => '⭐', 'class' => 'act-feedback'],
        default                 => ['icon' => '🔔', 'class' => 'act-default'],
    };
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// TUTOR PERSONAL DATA
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if ($role === 'tutor') {
    $tutor_id = $user_id;

    $my_completed = $conn->query("SELECT COUNT(*) as count FROM sessions 
        WHERE tutor_id = $tutor_id AND session_status = 'Completed'")->fetch_assoc()['count'];

    $my_students = $conn->query("SELECT COUNT(DISTINCT tutee_id) as count FROM sessions 
        WHERE tutor_id = $tutor_id AND session_status IN ('Accepted','Completed')")->fetch_assoc()['count'];

    $my_pending = $conn->query("SELECT COUNT(*) as count FROM sessions 
        WHERE tutor_id = $tutor_id AND session_status = 'Pending'")->fetch_assoc()['count'];

    $my_rating_row = $conn->query("SELECT average_rating FROM tutor_profiles 
        WHERE tutor_id = $tutor_id")->fetch_assoc();
    $my_rating = $my_rating_row ? round($my_rating_row['average_rating'], 1) : 0;

    $my_subjects_query = $conn->query("
        SELECT sub.subject_name, COUNT(*) as count
        FROM sessions s
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutor_id = $tutor_id
        GROUP BY s.subject_id
        ORDER BY count DESC
    ");
    $my_subjects_labels = [];
    $my_subjects_data = [];
    while ($row = $my_subjects_query->fetch_assoc()) {
        $my_subjects_labels[] = $row['subject_name'];
        $my_subjects_data[] = $row['count'];
    }

    $status_query = $conn->query("
        SELECT session_status, COUNT(*) as count
        FROM sessions WHERE tutor_id = $tutor_id
        GROUP BY session_status
    ");
    $status_labels = [];
    $status_data = [];
    while ($row = $status_query->fetch_assoc()) {
        $status_labels[] = $row['session_status'];
        $status_data[] = $row['count'];
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// TUTEE PERSONAL DATA
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if ($role === 'tutee') {
    $tutee_id = $user_id;

    $my_completed = $conn->query("SELECT COUNT(*) as count FROM sessions 
        WHERE tutee_id = $tutee_id AND session_status = 'Completed'")->fetch_assoc()['count'];

    $my_pending = $conn->query("SELECT COUNT(*) as count FROM sessions 
        WHERE tutee_id = $tutee_id AND session_status = 'Pending'")->fetch_assoc()['count'];

    $my_upcoming = $conn->query("SELECT COUNT(*) as count FROM sessions 
        WHERE tutee_id = $tutee_id 
        AND session_status IN ('Accepted','Ongoing') 
        AND requested_schedule > NOW()")->fetch_assoc()['count'];

    $my_tutors = $conn->query("SELECT COUNT(DISTINCT tutor_id) as count FROM sessions 
        WHERE tutee_id = $tutee_id")->fetch_assoc()['count'];

    $my_subjects_query = $conn->query("
        SELECT sub.subject_name, COUNT(*) as count
        FROM sessions s
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutee_id = $tutee_id
        GROUP BY s.subject_id
        ORDER BY count DESC
    ");
    $my_subjects_labels = [];
    $my_subjects_data = [];
    while ($row = $my_subjects_query->fetch_assoc()) {
        $my_subjects_labels[] = $row['subject_name'];
        $my_subjects_data[] = $row['count'];
    }

    $status_query = $conn->query("
        SELECT session_status, COUNT(*) as count
        FROM sessions WHERE tutee_id = $tutee_id
        GROUP BY session_status
    ");
    $status_labels = [];
    $status_data = [];
    while ($row = $status_query->fetch_assoc()) {
        $status_labels[] = $row['session_status'];
        $status_data[] = $row['count'];
    }

    $my_tutors_query = $conn->query("
        SELECT DISTINCT u.name, tp.average_rating
        FROM sessions s
        JOIN users u ON s.tutor_id = u.user_id
        JOIN tutor_profiles tp ON s.tutor_id = tp.tutor_id
        WHERE s.tutee_id = $tutee_id
        ORDER BY tp.average_rating DESC
        LIMIT 10
    ");
    $my_tutors_labels = [];
    $my_tutors_ratings = [];
    while ($row = $my_tutors_query->fetch_assoc()) {
        $my_tutors_labels[] = $row['name'];
        $my_tutors_ratings[] = round($row['average_rating'], 1);
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

    <!-- ━━━ SIDEBAR ━━━ -->
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>

        <!-- Mobile hamburger toggle -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>

        <nav id="sidebarNav">
            <?php if ($role === 'tutor'): ?>
                <a href="/tutorloop/tutor/tutor_dashboard.php">Dashboard</a>
                <a href="/tutorloop/tutor/tutor_profile.php">My Profile</a>
                <a href="/tutorloop/tutor/tutor_myschedule.php">My Schedule</a>
                <a href="/tutorloop/tutor/tutor_session_request.php">Session Requests</a>
                <a href="/tutorloop/tutor/tutor_mystudents.php">My Students</a>
                <a href="/tutorloop/tutor/tutor_messages.php">Messages</a>
                <a href="/tutorloop/tutor/tutor_ratings.php">My Ratings</a>
                <a href="/tutorloop/analytics.php" class="active">Analytics</a>
            <?php else: ?>
                <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
                <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
                <a href="/tutorloop/search_results.php">Find a Tutor</a>
                <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
                <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
                <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
                <a href="/tutorloop/analytics.php" class="active">Analytics</a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ━━━ MAIN CONTENT ━━━ -->
    <main class="main">

        <header class="topbar">
            <h1>Analytics</h1>
            <a href="/tutorloop/logout.php" class="logout">Logout</a>
        </header>

        <!-- ━━━ PERSONAL STATS ━━━ -->
        <h2 class="section-heading">
            <?php echo $role === 'tutor' ? 'My Performance' : 'My Learning Summary'; ?>
        </h2>

        <section class="stats-cards">
            <?php if ($role === 'tutor'): ?>
                <div class="stat-card">
                    <h2><?php echo $my_completed; ?></h2>
                    <p>Sessions Completed</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_students; ?></h2>
                    <p>Total Students</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_pending; ?></h2>
                    <p>Pending Requests</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_rating > 0 ? $my_rating . '/5' : 'N/A'; ?></h2>
                    <p>My Average Rating</p>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <h2><?php echo $my_completed; ?></h2>
                    <p>Sessions Completed</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_upcoming; ?></h2>
                    <p>Upcoming Sessions</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_pending; ?></h2>
                    <p>Pending Requests</p>
                </div>
                <div class="stat-card">
                    <h2><?php echo $my_tutors; ?></h2>
                    <p>Tutors Worked With</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ━━━ PERSONAL CHARTS ━━━ -->
        <section class="charts">
            <div class="chart-container">
                <h3>My Sessions by Subject</h3>
                <?php if (empty($my_subjects_data)): ?>
                    <p class="empty-msg">No sessions yet.</p>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="mySubjectsChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>My Session Status Breakdown</h3>
                <?php if (empty($status_data)): ?>
                    <p class="empty-msg">No sessions yet.</p>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="myStatusChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($role === 'tutee' && !empty($my_tutors_labels)): ?>
            <div class="chart-container">
                <h3>My Tutors' Ratings</h3>
                <div class="chart-wrapper">
                    <canvas id="myTutorsChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- ━━━ PLATFORM-WIDE STATS ━━━ -->
        <h2 class="section-heading">Platform Overview</h2>

        <section class="stats-cards stats-3col">
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

        <!-- ━━━ PLATFORM CHARTS ━━━ -->
        <section class="charts">
            <div class="chart-container">
                <h3>Most Requested Subjects</h3>
                <div class="chart-wrapper">
                    <canvas id="subjectsChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3>Top Rated Tutors</h3>
                <?php if (empty($top_tutors_data)): ?>
                    <p class="empty-msg">No ratings submitted yet.</p>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="tutorsChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chart-container">
                <h3>User Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- ━━━ RECENT PLATFORM ACTIVITY ━━━ -->
            <div class="chart-container activity-feed">
                <h3>Recent Platform Activity</h3>
                <?php if (empty($activities)): ?>
                    <p class="activity-empty">No recent activity to display.</p>
                <?php else: ?>
                    <div class="activity-list" id="activityList">
                        <?php foreach ($activities as $item):
                            $meta = get_activity_meta($item['activity_type']);
                        ?>
                        <div class="activity-item <?php echo $meta['class']; ?>">
                            <div class="activity-icon">
                                <?php echo $meta['icon']; ?>
                            </div>
                            <div class="activity-body">
                                <p class="activity-actor">
                                    <?php echo htmlspecialchars($item['actor_name']); ?>
                                    <span class="activity-type-badge">
                                        <?php echo htmlspecialchars($item['activity_type']); ?>
                                    </span>
                                </p>
                                <p class="activity-detail">
                                    <?php echo htmlspecialchars($item['detail']); ?>
                                </p>
                            </div>
                            <div class="activity-time">
                                #<?php echo $item['sort_key']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>

<script>
// ━━━ CHART DEFAULTS ━━━
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.color = '#444';

// ━━━ PERSONAL CHARTS ━━━
<?php if (!empty($my_subjects_data)): ?>
new Chart(document.getElementById('mySubjectsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($my_subjects_labels); ?>,
        datasets: [{
            label: 'Sessions',
            data: <?php echo json_encode($my_subjects_data); ?>,
            backgroundColor: '#d4a017',
            borderColor: '#b88a14',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

<?php if (!empty($status_data)): ?>
new Chart(document.getElementById('myStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_data); ?>,
            backgroundColor: ['#d4a017', '#0d2a4a', '#28a745', '#dc3545', '#6c757d'],
            borderWidth: 2,
            borderColor: '#f1f1f1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } } }
    }
});
<?php endif; ?>

<?php if ($role === 'tutee' && !empty($my_tutors_ratings)): ?>
new Chart(document.getElementById('myTutorsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($my_tutors_labels); ?>,
        datasets: [{
            label: 'Rating',
            data: <?php echo json_encode($my_tutors_ratings); ?>,
            backgroundColor: '#0d2a4a',
            borderColor: '#0a1f35',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 5 } },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

// ━━━ PLATFORM CHARTS ━━━
new Chart(document.getElementById('subjectsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($subjects_labels); ?>,
        datasets: [{
            label: 'Sessions',
            data: <?php echo json_encode($subjects_data); ?>,
            backgroundColor: '#d4a017',
            borderColor: '#b88a14',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        plugins: { legend: { display: false } }
    }
});

<?php if (!empty($top_tutors_data)): ?>
new Chart(document.getElementById('tutorsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($top_tutors_labels); ?>,
        datasets: [{
            label: 'Average Rating',
            data: <?php echo json_encode($top_tutors_data); ?>,
            backgroundColor: '#0d2a4a',
            borderColor: '#0a1f35',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 5 } },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

new Chart(document.getElementById('distributionChart'), {
    type: 'pie',
    data: {
        labels: ['Tutors', 'Tutees'],
        datasets: [{
            data: [<?php echo $tutors_count; ?>, <?php echo $tutees_count; ?>],
            backgroundColor: ['#d4a017', '#0d2a4a'],
            borderColor: ['#b88a14', '#0a1f35'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } } }
    }
});

// ━━━ SIDEBAR MOBILE TOGGLE ━━━
const toggleBtn = document.getElementById('sidebarToggle');
const nav = document.getElementById('sidebarNav');
if (toggleBtn && nav) {
    toggleBtn.addEventListener('click', () => {
        nav.classList.toggle('nav-open');
        toggleBtn.textContent = nav.classList.contains('nav-open') ? '✕' : '☰';
    });
}

// ━━━ AUTO-REFRESH ACTIVITY FEED (every 60 seconds) ━━━
function refreshActivity() {
    fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newList = doc.getElementById('activityList');
            const currentList = document.getElementById('activityList');
            if (newList && currentList) {
                currentList.innerHTML = newList.innerHTML;
            }
        })
        .catch(() => {});
}
setInterval(refreshActivity, 60000);
</script>
</body>
</html>