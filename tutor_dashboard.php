<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

// Get pending requests count
$pending_count_sql = "SELECT COUNT(*) as count FROM sessions WHERE tutor_id = ? AND session_status = 'Pending'";
$stmt_count = $conn->prepare($pending_count_sql);
$stmt_count->bind_param("i", $tutor_id);
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$count_row = $count_result->fetch_assoc();
$pending_requests_count = $count_row['count'];
$stmt_count->close();

// Get pending requests details
$pending_requests_sql = "SELECT 
                            s.session_id,
                            s.requested_schedule,
                            s.session_status,
                            s.request_note,
                            u.name AS tutee_name,
                            sub.subject_name
                        FROM sessions s
                        INNER JOIN users u ON s.tutee_id = u.user_id
                        INNER JOIN subjects sub ON s.subject_id = sub.subject_id
                        WHERE s.tutor_id = ? AND s.session_status = 'Pending'
                        ORDER BY s.requested_schedule ASC";

$stmt_requests = $conn->prepare($pending_requests_sql);
$stmt_requests->bind_param("i", $tutor_id);
$stmt_requests->execute();
$pending_requests = $stmt_requests->get_result();
$stmt_requests->close();

// Get accepted/upcoming sessions
$upcoming_sessions_sql = "SELECT 
                            s.session_id,
                            s.requested_schedule,
                            s.session_status,
                            s.request_note,
                            u.name AS tutee_name,
                            sub.subject_name
                        FROM sessions s
                        INNER JOIN users u ON s.tutee_id = u.user_id
                        INNER JOIN subjects sub ON s.subject_id = sub.subject_id
                        WHERE s.tutor_id = ? AND s.session_status = 'Accepted'
                        ORDER BY s.requested_schedule ASC";

$stmt_upcoming = $conn->prepare($upcoming_sessions_sql);
$stmt_upcoming->bind_param("i", $tutor_id);
$stmt_upcoming->execute();
$upcoming_sessions = $stmt_upcoming->get_result();
$stmt_upcoming->close();

// Get messages from session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : "";
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor Dashboard | TutorLoop</title>

  <!-- CSS -->
  <link rel="stylesheet" href="Frontend/css/tutor_dashboard.css">
</head>

<body>

<div class="phone-wrapper">

  <!-- 🔝 TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <img src="Frontend/images/tutorloop_logo.jpg" alt="TutorLoop Logo" class="top-logo">
    </div>
    <div class="topbar-right">☰</div>
  </header>

  <!-- 📦 MAIN -->
  <main class="dashboard-content">

    <!-- HEADER -->
    <section class="dashboard-header">
      <h1>Tutor Dashboard</h1>
      <p>Welcome back! Here's your tutoring activity.</p>
    </section>

    <!-- MESSAGE DISPLAY -->
    <?php if ($message): ?>
      <div class="message message-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- 📊 STATS -->
    <section class="stats-grid">

      <div class="stat-card">
        <h2 id="sessions">0</h2>
        <p>Total Sessions</p>
      </div>

      <div class="stat-card">
        <h2 id="requests"><?php echo $pending_requests_count; ?></h2>
        <p>Pending Requests</p>
      </div>

      <div class="stat-card">
        <h2 id="rating">0.0</h2>
        <p>Average Rating</p>
      </div>

      <div class="stat-card">
        <h2 id="earnings">₱0</h2>
        <p>Total Earnings</p>
      </div>

    </section>

    <!-- 📥 PENDING REQUESTS -->
    <section class="card-section">
      <div class="section-header">
        <h2>Pending Requests</h2>
        <a href="view_requests.php">View All</a>
      </div>

      <?php if ($pending_requests->num_rows > 0): ?>
        <?php while ($request = $pending_requests->fetch_assoc()): ?>
          <div class="request-item">
            <div class="request-info">
              <h4><?php echo htmlspecialchars($request['tutee_name']); ?></h4>
              <p><strong>Subject:</strong> <?php echo htmlspecialchars($request['subject_name']); ?></p>
              <p><strong>Schedule:</strong> <?php echo htmlspecialchars($request['requested_schedule']); ?></p>
              <?php if (!empty($request['request_note'])): ?>
                <p><strong>Note:</strong> <?php echo htmlspecialchars($request['request_note']); ?></p>
              <?php endif; ?>
            </div>
            <div class="request-actions">
              <a href="accept_session.php?session_id=<?php echo $request['session_id']; ?>" class="accept-small">Accept</a>
              <a href="decline_session.php?session_id=<?php echo $request['session_id']; ?>" class="decline-small">Decline</a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-box">No pending requests yet</div>
      <?php endif; ?>
    </section>

    <!-- 📅 UPCOMING -->
    <section class="card-section">
      <div class="section-header">
        <h2>Upcoming Sessions</h2>
      </div>

      <?php if ($upcoming_sessions->num_rows > 0): ?>
        <?php while ($session = $upcoming_sessions->fetch_assoc()): ?>
          <div class="session-item">
            <h4><?php echo htmlspecialchars($session['tutee_name']); ?></h4>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($session['subject_name']); ?></p>
            <p><strong>Schedule:</strong> <?php echo htmlspecialchars($session['requested_schedule']); ?></p>
            <p class="status-accepted"><strong>Status:</strong> Accepted</p>
            <?php if (!empty($session['request_note'])): ?>
              <p><strong>Note:</strong> <?php echo htmlspecialchars($session['request_note']); ?></p>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-box">No upcoming sessions</div>
      <?php endif; ?>
    </section>

    <!-- 💬 MESSAGES -->
    <section class="card-section">
      <div class="section-header">
        <h2>Recent Messages</h2>
      </div>

      <div class="empty-box">No messages yet</div>

      <a href="messages.php" class="outline-btn">View All Messages</a>
    </section>

    <!-- ⭐ RATINGS -->
    <section class="card-section">
      <div class="section-header">
        <h2>Recent Ratings</h2>
      </div>

      <div class="empty-box">No ratings yet</div>
    </section>

    <!-- ⚡ ACTIONS -->
    <section class="bottom-actions">

      <a href="view_requests.php" class="action-card dark">
        <h3>Manage Requests</h3>
        <p>Accept or decline tutoring requests</p>
      </a>

      <a href="create_tutor_profile.php" class="action-card light">
        <h3>Manage Profile</h3>
        <p>Update your subjects, rates, and availability</p>
      </a>

      <a href="#" class="action-card light">
        <h3>View Analytics</h3>
        <p>See platform insights and trends</p>
      </a>

      <a href="messages.php" class="action-card light">
        <h3>Messages</h3>
        <p>Chat with your students</p>
      </a>

      <a href="logout.php" class="action-card logout">
        <h3>Logout</h3>
        <p>Sign out from your account</p>
      </a>

    </section>

  </main>

</div>

<!-- JS -->
<script src="Frontend/js/tutor_dashboard.js"></script>

</body>
</html>