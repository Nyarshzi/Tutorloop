<?php
include("../../config/db.php");

if (isset($_GET['session_id'])) {

    $session_id = (int)$_GET['session_id'];

    // Update the status to Cancelled
    $sql = "UPDATE sessions 
            SET session_status = 'Cancelled' 
            WHERE session_id = $session_id";

    if ($conn->query($sql) === TRUE) {

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();

    } else {

        echo "Error updating record: " . $conn->error;
    }

} else {

    echo "No session ID provided.";
}
?>