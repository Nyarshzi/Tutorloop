<?php
session_start();
$conn = new mysqli("localhost", "root", "", "tutorloop_db");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id']; 
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($conn->real_escape_string($_POST['message']));

    if (!empty($content)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_content, date_sent) 
                VALUES ($sender_id, $receiver_id, '$content', NOW())";
        $conn->query($sql);
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