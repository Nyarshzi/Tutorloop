<?php
session_start();
include("../config/db.php");
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutee') {
    header("Location: ../login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];

// Get all sessions including Completed and Declined
$query = "SELECT s.*, u.name as tutor_name, sub.subject_name 
          FROM sessions s 
          JOIN users u ON s.tutor_id = u.user_id 
          JOIN subjects sub ON s.subject_id = sub.subject_id 
          WHERE s.tutee_id = ?
          ORDER BY s.requested_schedule DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tutee_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if tutee already gave feedback for a session
function hasFeedback($conn, $session_id) {
    $stmt = $conn->prepare(
        "SELECT feedback_id FROM feedback_ratings WHERE session_id = ?"
    );
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="../Frontend/css/tutee_session.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="/tutorloop/search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php" class="active">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
            <a href="/tutorloop/analytics.php">Analytics</a>    
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1>My Sessions</h1>
            <button class="logout" onclick="location.href='/tutorloop/logout.php'">Logout</button>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background:#d4edda; color:#155724; padding:12px 16px;
                        border-radius:8px; margin:0 20px 16px;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <section class="sessions">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()):
                    $timestamp = strtotime($row['requested_schedule']);
                    $status = $row['session_status'];
                    $already_rated = hasFeedback($conn, $row['session_id']);
                ?>
                <div class="session-card">
                    <div class="session-info">
                        <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                        <p><strong>Tutor:</strong> 
                            <?php echo htmlspecialchars($row['tutor_name']); ?>
                        </p>
                        <p><strong>Date:</strong> 
                            <?php echo date("F j, Y", $timestamp); ?>
                        </p>
                        <p><strong>Time:</strong> 
                            <?php echo date("g:i A", $timestamp); ?>
                        </p>
                        <?php if (!empty($row['request_note'])): ?>
                            <p><strong>Your note:</strong> 
                                <?php echo htmlspecialchars($row['request_note']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="session-actions">
                        <?php if ($status == 'Pending'): ?>
                            <span class="pending-btn">Pending</span>
                            <button class="cancel" 
                                onclick="confirmCancel(<?php echo $row['session_id']; ?>)">
                                Cancel
                            </button>

                        <?php elseif ($status == 'Accepted'): ?>
                            <span class="join">Accepted</span>
                            <button class="cancel"
                                onclick="confirmCancel(<?php echo $row['session_id']; ?>)">
                                Cancel
                            </button>

                        <?php elseif ($status == 'Completed'): ?>
                            <span style="background:#28a745; color:#fff; 
                                         padding:6px 14px; border-radius:6px;
                                         font-weight:600;">
                                Completed
                            </span>
                            <?php if (!$already_rated): ?>
                                <button class="join" 
                                    onclick="location.href='/tutorloop/feedback.php?session_id=<?php echo $row['session_id']; ?>'">
                                    Leave Feedback
                                </button>
                            <?php else: ?>
                                <span style="color:#666; font-size:13px;">
                                    ✓ Feedback submitted
                                </span>
                            <?php endif; ?>

                        <?php elseif ($status == 'Declined'): ?>
                            <span style="background:#dc3545; color:#fff;
                                         padding:6px 14px; border-radius:6px;
                                         font-weight:600;">
                                Declined
                            </span>

                        <?php else: ?>
                            <span class="pending-btn">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="session-card empty">
                    <p>No sessions found. 
                        <a href="/tutorloop/search_results.php">Find a tutor</a> to get started.
                    </p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (menuBtn && sidebar && overlay) {
  menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.classList.toggle('sidebar-open');
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
  });

  // Close sidebar when a nav link is tapped on mobile
  sidebar.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
      document.body.classList.remove('sidebar-open');
    });
  });
} else {
  console.warn('Hamburger menu: missing element(s). Check IDs: menuBtn, sidebar, sidebarOverlay');
}

function confirmCancel(id) {
    if (confirm('Are you sure you want to cancel this request?')) {
        window.location.href = '/tutorloop/tutor/api/cancel_session.php?session_id=' + id;
    }
}
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}
</script>
</body>
</html>