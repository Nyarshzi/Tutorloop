<?php
session_start();
// Include the centralized database connection
include("config/db.php"); 

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info - profile_pic is now centralized in the users table
$query = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Fallback values for display
$display_name = !empty($user['name']) ? $user['name'] : "Tutee";
$profile_img = !empty($user['profile_pic']) ? $user['profile_pic'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | TutorLoop</title>
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
            <a href="search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1>My Profile</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="profile">
            <div class="profile-card">
                <form id="profilePicForm" action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                    <div class="profile-avatar-container">
                        <?php if($profile_img): ?>
                            <img src="uploads/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="avatar-img">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo strtoupper(substr($display_name, 0, 1)); ?></div>
                        <?php endif; ?>
                        
                        <input type="file" name="profile_pic" id="fileInput" style="display: none;" onchange="document.getElementById('profilePicForm').submit();">
                        
                        <div class="change-pic-overlay" onclick="document.getElementById('fileInput').click();">
                            <span>Change</span>
                        </div>
                    </div>
                </form>

                <h2><?php echo htmlspecialchars($display_name); ?></h2>
                <p class="role-tag">Tutee</p>
                
                <div class="profile-details">
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($display_name); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Status:</strong> <span class="status-active">Active Account</span></p>
                </div>

                <button class="search-btn" style="width: 100%; padding: 15px;" onclick="location.href='edit_profile.php'">Edit Profile</button>
            </div>
        </section> 
    </main>
</div>

<script src="js/tutee_dashboard.js"></script>
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