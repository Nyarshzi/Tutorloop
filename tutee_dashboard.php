<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];

// Get tutee's requested sessions
$requested_sessions_sql = "SELECT 
                            s.session_id,
                            s.requested_schedule,
                            s.session_status,
                            s.request_note,
                            u.name AS tutor_name,
                            sub.subject_name
                        FROM sessions s
                        INNER JOIN users u ON s.tutor_id = u.user_id
                        INNER JOIN subjects sub ON s.subject_id = sub.subject_id
                        WHERE s.tutee_id = ?
                        ORDER BY s.requested_schedule DESC
                        LIMIT 5";

$stmt_sessions = $conn->prepare($requested_sessions_sql);
$stmt_sessions->bind_param("i", $tutee_id);
$stmt_sessions->execute();
$requested_sessions = $stmt_sessions->get_result();
$stmt_sessions->close();

// Count pending requests
$pending_count_sql = "SELECT COUNT(*) as count FROM sessions WHERE tutee_id = ? AND session_status = 'Pending'";
$stmt_pending = $conn->prepare($pending_count_sql);
$stmt_pending->bind_param("i", $tutee_id);
$stmt_pending->execute();
$pending_result = $stmt_pending->get_result();
$pending_row = $pending_result->fetch_assoc();
$pending_count = $pending_row['count'];
$stmt_pending->close();

// Get accepted/upcoming sessions for tutee
$upcoming_sessions_sql = "SELECT 
                            s.session_id,
                            s.requested_schedule,
                            s.session_status,
                            u.name AS tutor_name,
                            sub.subject_name
                        FROM sessions s
                        INNER JOIN users u ON s.tutor_id = u.user_id
                        INNER JOIN subjects sub ON s.subject_id = sub.subject_id
                        WHERE s.tutee_id = ? AND s.session_status = 'Accepted'
                        ORDER BY s.requested_schedule ASC";

$stmt_upcoming = $conn->prepare($upcoming_sessions_sql);
$stmt_upcoming->bind_param("i", $tutee_id);
$stmt_upcoming->execute();
$upcoming_sessions = $stmt_upcoming->get_result();
$stmt_upcoming->close();

// Handle tutor search
$search_query = "";
$search_results = [];
$search_performed = false;

if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $search_performed = true;

    if (!empty($search_query)) {
        $sql = "SELECT 
                    tp.tutor_id,
                    u.user_id,
                    u.name,
                    tp.description,
                    tp.tutoring_rate,
                    tp.availability_schedule,
                    tp.average_rating,
                    s.subject_name
                FROM tutor_profiles tp
                INNER JOIN users u ON tp.tutor_id = u.user_id
                INNER JOIN subjects s ON tp.subject_id = s.subject_id
                WHERE LOWER(TRIM(u.role)) = 'tutor'
                AND (
                    s.subject_name LIKE ?
                    OR tp.description LIKE ?
                    OR u.name LIKE ?
                )";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $search_param = "%" . $search_query . "%";
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutee Dashboard | TutorLoop</title>
  <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
</head>

<body>

<div class="app">

  <header class="topbar">
    <h1>Dashboard</h1>
    <div class="topbar-right">
      <div class="profile">👤</div>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </header>

  <main class="main">

    <section class="stats">
      <div class="card"><h2>0</h2><p>Completed</p></div>
      <div class="card"><h2><?php echo $pending_count; ?></h2><p>Requests</p></div>
      <div class="card"><h2>0</h2><p>Subjects</p></div>
      <div class="card"><h2>0</h2><p>Upcoming</p></div>
    </section>

    <section class="search">
      <h2>Find a Tutor</h2>
      <form method="GET" action="">
        <input type="text" name="search" placeholder="Search subject, tutor, or keyword..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Search</button>
      </form>
    </section>

    <?php if ($search_performed): ?>
      <section class="search-results">
        <?php if (!empty($search_results)): ?>
          <h3>Search Results</h3>
          <?php foreach ($search_results as $tutor): ?>
    <div class="tutor-card">
        <h4><?php echo htmlspecialchars($tutor['name']); ?></h4>

        <p><strong>Subject:</strong> <?php echo htmlspecialchars($tutor['subject_name']); ?></p>
        <p><strong>Rate:</strong> ₱<?php echo htmlspecialchars($tutor['tutoring_rate']); ?>/hour</p>
        <p><strong>Availability:</strong> <?php echo htmlspecialchars($tutor['availability_schedule']); ?></p>
        <p><strong>Rating:</strong> ⭐ <?php echo htmlspecialchars($tutor['average_rating']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($tutor['description']); ?></p>

        <a href="request_session.php?tutor_id=<?php echo $tutor['tutor_id']; ?>" class="request-btn">Request Session</a>
    </div>
<?php endforeach; ?>
        <?php else: ?>
          <div class="no-results">
            <p>No tutors found.</p>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="grid">
      <div class="box">
        <h3>My Requests</h3>
        <?php if ($requested_sessions->num_rows > 0): ?>
          <?php while ($session = $requested_sessions->fetch_assoc()): ?>
            <div class="session-item">
              <h4><?php echo htmlspecialchars($session['tutor_name']); ?></h4>
              <p><strong>Subject:</strong> <?php echo htmlspecialchars($session['subject_name']); ?></p>
              <p><strong>Schedule:</strong> <?php echo htmlspecialchars($session['requested_schedule']); ?></p>
              <p class="status-<?php echo strtolower($session['session_status']); ?>">
                <strong>Status:</strong> <?php echo htmlspecialchars($session['session_status']); ?>
              </p>
              <?php if (!empty($session['request_note'])): ?>
                <p><strong>Note:</strong> <?php echo htmlspecialchars($session['request_note']); ?></p>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No requests yet</p>
        <?php endif; ?>
      </div>
      
      <div class="box">
        <h3>Upcoming</h3>
        <?php if ($upcoming_sessions->num_rows > 0): ?>
          <?php while ($session = $upcoming_sessions->fetch_assoc()): ?>
            <div class="session-item">
              <h4><?php echo htmlspecialchars($session['tutor_name']); ?></h4>
              <p><strong>Subject:</strong> <?php echo htmlspecialchars($session['subject_name']); ?></p>
              <p><strong>Schedule:</strong> <?php echo htmlspecialchars($session['requested_schedule']); ?></p>
              <p class="status-accepted"><strong>Status:</strong> Accepted</p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No sessions yet</p>
        <?php endif; ?>
      </div>
      <div class="box"><h3>Recommended</h3><p>No tutors yet</p></div>
      <div class="box"><h3>Messages</h3><p>No messages yet</p></div>
    </section>

  </main>

  <nav class="bottom-nav">
    <a class="active">🏠</a>
    <a>🔍</a>
    <a>💬</a>
    <a>⭐</a>
    <a>⚙️</a>
  </nav>

</div>

<script src="Frontend/js/tutee_dashboard.js"></script>
</body>
</html>