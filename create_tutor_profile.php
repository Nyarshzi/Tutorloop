<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$tutor_id = $_SESSION['user_id'];

// 1. FETCH CURRENT DATA
$sql = "SELECT u.name, u.email, u.profile_pic, tp.description, tp.phone_number 
        FROM users u 
        LEFT JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// 3. UPDATE LOGIC (Save All Changes)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];
    
    // Update profile picture if uploaded
    $profile_pic = $user_data['profile_pic'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $ext = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $new_name = "tutor_" . $tutor_id . "_" . time() . "." . $ext;
        
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], "uploads/" . $new_name)) {
            $profile_pic = $new_name;
        }
    }
    
    // Always update 'users' table
    $upd_user = $conn->prepare("UPDATE users SET name=?, email=?, profile_pic=? WHERE user_id=?");
    $upd_user->bind_param("sssi", $full_name, $email, $profile_pic, $tutor_id);
    $upd_user->execute();

    // Check if profile exists to decide between INSERT or UPDATE
    $check_profile = $conn->prepare("SELECT tutor_id FROM tutor_profiles WHERE tutor_id = ?");
    $check_profile->bind_param("i", $tutor_id);
    $check_profile->execute();
    $profile_exists = $check_profile->get_result()->num_rows > 0;

    if ($profile_exists) {
        // UPDATE existing record
        $upd = $conn->prepare("UPDATE tutor_profiles SET description=?, phone_number=? WHERE tutor_id=?");
        $upd->bind_param("ssi", $bio, $phone, $tutor_id);
    } else {
        // INSERT new record for new tutor
        $upd = $conn->prepare("INSERT INTO tutor_profiles (description, phone_number, tutor_id) VALUES (?, ?, ?)");
        $upd->bind_param("ssi", $bio, $phone, $tutor_id);
    }
    $upd->execute();

    header("Location: create_tutor_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/create_tutor_profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .saved-info-container { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .saved-item { display: flex; justify-content: space-between; align-items: center; }
        .btn-edit { color: #d4a017; cursor: pointer; font-weight: bold; margin-right: 15px; }
        .btn-remove { color: #e74c3c; text-decoration: none; font-weight: bold; }
        #new-subject-input { margin-top: 10px; display: none; }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="Frontend/images/Tutorloop_logo.png" class="sidebar-logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutor_dashboard.php">Dashboard</a>
            <a href="create_tutor_profile.php" class="active">My Profile</a>
            <a href="tutor_myschedule.php">My Schedule</a>
            <a href="tutor_session_request.php">Session Requests</a>
            <a href="tutor_mystudents.php">My Students</a>
            <a href="tutor_messages.php">Messages</a>
            <a href="tutor_ratings.php">My Ratings</a>
            <a href="analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <div class="top-banner">
            <h1>Edit Profile and Services</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="profile-grid">
            <div class="identity-card-column">
                <div class="identity-card">
                    <div class="card-header">
                        <div class="avatar-wrapper">
                            <div class="avatar-circle">
                                <?php if(!empty($user_data['profile_pic'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($user_data['profile_pic']); ?>" class="profile-img">
                                <?php else: ?>
                                    <span class="initials"><?php echo strtoupper(substr($user_data['name'] ?? 'T', 0, 1)); ?></span>
                                <?php endif; ?>
                                <label for="pic-upload" class="camera-overlay"><span>Change</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="identity-info">
                        <h2><?php echo htmlspecialchars($user_data['name']); ?></h2>
                        <p class="role-text">Tutor</p>
                        <button type="submit" class="save-btn">Save All Changes</button>
                    </div>
                </div>
            </div>
            <input type="file" name="profile_picture" id="pic-upload" hidden accept="image/*">

            <div class="form-content">
                <div class="section-card">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-row"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required></div>
                    <div class="form-row"><label>Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required></div>
                    <div class="form-row"><label>Contact Number</label><input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>"></div>
                </div>

                <div class="section-card">
                    <h3 class="section-title">Bio</h3>
                    <textarea name="bio"><?php echo htmlspecialchars($user_data['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <p style="margin: 0; font-size: 16px; color: #333;">
                To manage your subjects and availability, go to <a href="tutor_myschedule.php" style="color: #d4a017; font-weight: bold;">My Schedule</a>
            </p>
        </div>
    </main>
</div>

<script src="Frontend/js/create_tutor_profile.js"></script>
</body>
</html>