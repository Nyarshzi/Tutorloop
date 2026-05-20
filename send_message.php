<?php
session_start();
include("config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id']; 
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($_POST['message']);

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content, date_sent) 
                                VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $content);
        $stmt->execute();
        $stmt->close();
    }
    
    // Check role to redirect to the correct page
    if ($_SESSION['role'] === 'tutor') {
        header("Location: tutor_messages.php?tutee_id=" . $receiver_id);
    } else {
        header("Location: tutee_messages.php?tutor_id=" . $receiver_id);
    }
    exit();
}
?>
