<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch current user data
$query = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];

    if (!empty($new_password)) {

        // Update with new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?");
        $update->bind_param("sssi", $new_name, $new_email, $hashed_password, $user_id);

    } else {

        // Update without password
        $update = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $update->bind_param("ssi", $new_name, $new_email, $user_id);
    }

    if ($update->execute()) {

        $_SESSION['user_name'] = $new_name;

        header("Location: /tutorloop/tutee/tutee_profile.php?success=1");
        exit();

    } else {

        $message = "Error updating profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/tutee_profile.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php" class="active">My Profile</a>
            <a href="/tutorloop/search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
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
            <h1>Edit Profile</h1>
            <a href="/tutorloop/tutee/tutee_profile.php" class="logout">Cancel</a>
        </header>

        <section class="profile">
            <div class="profile-card">
                <form action="edit_profile.php" method="POST">
                    <div class="profile-details" style="text-align: left;">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="search-bar" style="width:100%; margin-bottom:15px; padding:10px;">
                        
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="search-bar" style="width:100%; margin-bottom:15px; padding:10px;">
                        
                        <label>New Password (Leave blank to keep current)</label>
                        <input type="password" name="password" class="search-bar" style="width:100%; margin-bottom:15px; padding:10px;">
                    </div>

                    <button type="submit" class="search-btn" style="width: 100%;">Save Changes</button>
                </form>
                <?php if($message) echo "<p style='color:red; margin-top:10px;'>$message</p>"; ?>
            </div>
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
</script>
</body>
</html>
