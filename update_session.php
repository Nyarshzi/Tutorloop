<?php
session_start();
include("config/db.php");

if (isset($_POST['session_id']) && isset($_POST['action'])) {
    $session_id = $_POST['session_id'];
    $action = $_POST['action']; // 'accepted' or 'declined'
    $tutor_id = $_SESSION['user_id'];

    $sql = "UPDATE sessions SET status = ? WHERE session_id = ? AND tutor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $action, $session_id, $tutor_id);
    
    if ($stmt->execute()) {
        header("Location: tutor_session_request.php?msg=updated");
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>