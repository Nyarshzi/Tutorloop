<?php
session_start();
include("config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_pic'];

    $fileName = time() . '_' . basename($file['name']);
    $targetPath = "uploads/" . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    // Allow only images
    if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Update database
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
            $stmt->bind_param("si", $fileName, $user_id);
            $stmt->execute();
        }
    }
    
    header("Location: tutee_profile.php");
    exit();
}
?>