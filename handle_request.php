<?php
session_start();
include("config/db.php");

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Process the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id']) && isset($_POST['action'])) {
    
    $session_id = intval($_POST['session_id']);
    $raw_action = $_POST['action'];
    $tutor_id = $_SESSION['user_id'];

    /**
     * Mapping Logic:
     * This ensures that whatever the button says, the database gets the 
     * exact string it expects in the 'session_status' ENUM column.
     */
    $status_map = [
        'Accept'   => 'Accepted',
        'Decline'  => 'Declined',
        'Complete' => 'Completed', // Matches 'Completed' in your DB screenshot
        'Paid'     => 'Completed'  // If you use a 'Paid' button, it marks it done too
    ];

    if (array_key_exists($raw_action, $status_map)) {
        $final_status = $status_map[$raw_action];
        
        // Update the database using 'session_status'
        // This change will be visible to the Tutee immediately on their dashboard
        $stmt = $conn->prepare("UPDATE sessions 
                                SET session_status = ? 
                                WHERE session_id = ? AND tutor_id = ?");
        $stmt->bind_param("sii", $final_status, $session_id, $tutor_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Redirect back with a success message
            header("Location: tutor_session_request.php?msg=success&new_status=" . $final_status);
            exit();
        } else {
            $stmt->close();
            echo "Error updating record: " . $conn->error;
        }
    } else {
        echo "Invalid action received: " . htmlspecialchars($raw_action);
    }
} else {
    header("Location: tutor_session_request.php");
    exit();
}
?>