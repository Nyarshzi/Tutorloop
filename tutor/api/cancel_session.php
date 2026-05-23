<?php
$conn = new mysqli("localhost", "root", "", "tutorloop_db");

if (isset($_GET['id'])) {
    $session_id = (int)$_GET['id'];

    // Update the status to 'Cancelled'
    $sql = "UPDATE sessions SET status = 'Cancelled' WHERE session_id = $session_id";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to the page they came from
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>