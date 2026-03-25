<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];
$message = "";
$message_type = "";
$existing_profile = null;

// Load existing profile
$sql = "SELECT tp.*, s.subject_name 
        FROM tutor_profiles tp
        LEFT JOIN subjects s ON tp.subject_id = s.subject_id
        WHERE tp.tutor_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $existing_profile = $result->fetch_assoc();
}

// FORM SUBMIT
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $subject_name = trim($_POST['subject_name']);
    $description = trim($_POST['description']);
    $rate = trim($_POST['tutoring_rate']);
    $availability = trim($_POST['availability_schedule']);

    if (!$subject_name || !$description || !$rate || !$availability) {
        $message = "All fields are required.";
        $message_type = "error";
    } else {

        // Normalize subject
        $subject_name = ucwords(strtolower($subject_name));

        // Check if subject exists
        $stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
        $stmt->bind_param("s", $subject_name);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $subject = $res->fetch_assoc();
            $subject_id = $subject['subject_id'];
        } else {
            // Insert new subject
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
            $stmt->bind_param("s", $subject_name);
            $stmt->execute();
            $subject_id = $conn->insert_id;
        }

        if ($existing_profile) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE tutor_profiles 
                SET description=?, tutoring_rate=?, availability_schedule=?, subject_id=?
                WHERE tutor_id=?");

            $stmt->bind_param("sdsii", $description, $rate, $availability, $subject_id, $tutor_id);
            $stmt->execute();

            $message = "Profile updated!";
            $message_type = "success";

        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO tutor_profiles 
                (tutor_id, description, tutoring_rate, availability_schedule, average_rating, subject_id)
                VALUES (?, ?, ?, ?, 0, ?)");

            $stmt->bind_param("isdsi", $tutor_id, $description, $rate, $availability, $subject_id);
            $stmt->execute();

            $message = "Profile created!";
            $message_type = "success";
        }

        // Reload profile
        header("Location: create_tutor_profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Tutor Profile</title>
    <link rel="stylesheet" href="Frontend/css/create_tutor_profile.css">
</head>

<body>

<div class="container">

    <h1><?php echo $existing_profile ? "Edit Tutor Profile" : "Create Tutor Profile"; ?></h1>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject_name"
                value="<?php echo $existing_profile['subject_name'] ?? ''; ?>"
                placeholder="Enter subject (e.g., Math)">
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?php echo $existing_profile['description'] ?? ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Tutoring Rate (₱/hour)</label>
            <input type="number" name="tutoring_rate"
                value="<?php echo $existing_profile['tutoring_rate'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label>Availability</label>
            <input type="text" name="availability_schedule"
                value="<?php echo $existing_profile['availability_schedule'] ?? ''; ?>">
        </div>

        <button type="submit" class="submit-btn">
            <?php echo $existing_profile ? "Update Profile" : "Create Profile"; ?>
        </button>

    </form>

    <a href="tutor_dashboard.php" class="back-link">← Back to Dashboard</a>

</div>

</body>
</html>