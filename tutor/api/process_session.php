<?php
include("../../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from the form
    $tutor_id = (int)$_POST['tutor_id'];
    $tutee_id = (int)$_POST['tutee_id'];
    $subject_id = (int)$_POST['subject_id'];
    $requested_schedule = $_POST['requested_schedule']; // Matches DATETIME
    $request_note = $conn->real_escape_string($_POST['request_note']);

    // Insert as 'Pending' so the tutor can see the request
    $sql = "INSERT INTO sessions (tutor_id, tutee_id, subject_id, requested_schedule, request_note, status, session_status) 
            VALUES ($tutor_id, $tutee_id, $subject_id, '$requested_schedule', '$request_note', 'Pending', 'Pending')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Booking request sent!'); window.location.href='../../tutee/tutee_dashboard.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>