<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$tutor_id = $_SESSION['user_id'];

// 1. FETCH CURRENT DATA
$sql = "SELECT u.name, u.email, u.profile_pic, tp.description, tp.phone_number, tp.tutoring_rate, 
               tp.availability_schedule, tp.subject_id, s.subject_name 
        FROM users u 
        LEFT JOIN tutor_profiles tp ON u.user_id = tp.tutor_id 
        LEFT JOIN subjects s ON tp.subject_id = s.subject_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// 2. HANDLE REMOVE ACTION
if (isset($_GET['remove_service'])) {
    $clear = $conn->prepare("UPDATE tutor_profiles SET tutoring_rate = 0, availability_schedule = '', subject_id = NULL WHERE tutor_id = ?");
    $clear->bind_param("i", $tutor_id);
    $clear->execute();
    header("Location: create_tutor_profile.php?status=removed");
    exit();
}

// 3. UPDATE LOGIC (Save All Changes)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];
    $rate = $_POST['tutoring_rate'];
    
    // Subject Logic
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
    if ($subject_id == "new" && !empty($_POST['new_subject_name'])) {
        $new_sub = $_POST['new_subject_name'];
        $stmt_sub = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt_sub->bind_param("s", $new_sub);
        $stmt_sub->execute();
        $subject_id = $conn->insert_id;
    }

    // Availability String Logic
    $days = $_POST['avail_day'] ?? [];
    $starts = $_POST['avail_start'] ?? [];
    $ends = $_POST['avail_end'] ?? [];
    $schedule_entries = [];
    
    for ($i = 0; $i < count($days); $i++) {
        if (!empty($days[$i]) && !empty($starts[$i]) && !empty($ends[$i])) {
            $schedule_entries[] = $days[$i] . "," . $starts[$i] . "," . $ends[$i];
        }
    }
    $availability_string = implode("|", $schedule_entries);

    // 4. IMAGE UPLOAD LOGIC
    $profile_pic = $user_data['profile_pic'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $ext = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $new_name = "tutor_" . $tutor_id . "_" . time() . "." . $ext;
        
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], "uploads/" . $new_name)) {
            $profile_pic = $new_name;
        }
    }

    // 5. UPDATE DATABASE (UPSERT LOGIC)
    
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
        $upd = $conn->prepare("UPDATE tutor_profiles SET description=?, phone_number=?, tutoring_rate=?, availability_schedule=?, subject_id=? WHERE tutor_id=?");
        $upd->bind_param("ssdsii", $bio, $phone, $rate, $availability_string, $subject_id, $tutor_id);
    } else {
        // INSERT new record for new tutor
        $upd = $conn->prepare("INSERT INTO tutor_profiles (description, phone_number, tutoring_rate, availability_schedule, subject_id, tutor_id) VALUES (?, ?, ?, ?, ?, ?)");
        $upd->bind_param("ssdsii", $bio, $phone, $rate, $availability_string, $subject_id, $tutor_id);
    }
    $upd->execute();

    header("Location: tutor_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
            <a href="tutor_session_request.php">Sessions</a>
            <a href="create_tutor_profile.php" class="active">Profile</a>
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

                <?php if (!empty($user_data['subject_id'])): ?>
                <div class="section-card">
                    <h3 class="section-title">Current Active Service</h3>
                    <div class="saved-info-container">
                        <div class="saved-item">
                            <div>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($user_data['subject_name']); ?><br>
                                <strong>Rate:</strong> ₱<?php echo number_format($user_data['tutoring_rate'], 2); ?>/hr
                            </div>
                            <div>
                                <span class="btn-edit" onclick="populateFields('<?php echo $user_data['subject_id']; ?>', '<?php echo $user_data['tutoring_rate']; ?>')">Edit</span>
                                <a href="?remove_service=1" class="btn-remove" onclick="return confirm('Remove this service?')">Remove</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="section-card">
                    <h3 class="section-title">Tutoring Schedule & Rate</h3>
                    <div class="form-row">
                        <label>Subject</label>
                        <select name="subject_id" id="input_subject">
                            <option value="">-- Select Subject --</option>
                            <?php
                            $subj_query = "SELECT * FROM subjects";
                            $subj_result = $conn->query($subj_query);
                            while($subj = $subj_result->fetch_assoc()) {
                                $selected = ($subj['subject_id'] == $user_data['subject_id']) ? "selected" : "";
                                echo "<option value='{$subj['subject_id']}' $selected>{$subj['subject_name']}</option>";
                            }
                            ?>
                            <option value="new">+ Add New Subject</option>
                        </select>
                        <input type="text" name="new_subject_name" id="new-subject-input" placeholder="Enter new subject name">
                    </div>
                    <div class="form-row">
                        <label>Rate (₱)/hr</label>
                        <input type="number" step="0.01" name="tutoring_rate" id="input_rate" value="<?php echo htmlspecialchars($user_data['tutoring_rate'] ?? ''); ?>" placeholder="0.00">
                    </div>
                    <label class="slot-label">Available Slots</label>
                    <div id="availability-container"></div>
                    <button type="button" id="add-row-btn" class="add-sub-btn">+ Add Day & Time Slot</button>
                </div>

                <div class="section-card">
                    <h3 class="section-title">Bio</h3>
                    <textarea name="bio"><?php echo htmlspecialchars($user_data['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
    const savedSchedule = "<?php echo $user_data['availability_schedule'] ?? ''; ?>";
    
    document.getElementById('input_subject').addEventListener('change', function() {
        document.getElementById('new-subject-input').style.display = (this.value === 'new') ? 'block' : 'none';
    });

    function populateFields(subId, rate) {
        document.getElementById('input_subject').value = subId;
        document.getElementById('input_rate').value = rate;
        document.getElementById('input_subject').scrollIntoView({ behavior: 'smooth' });
    }
</script>
<script src="Frontend/js/create_tutor_profile.js"></script>
</body>
</html>