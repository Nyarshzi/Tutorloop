<?php
session_start();
include("config/db.php");

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

// Check if session_id is provided
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    header("Location: tutor_dashboard.php");
    exit();
}

$session_id = intval($_GET['session_id']);

// Verify that this session belongs to the current tutor
$verify_sql = "SELECT tutor_id FROM sessions WHERE session_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("i", $session_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0 || $verify_result->fetch_assoc()['tutor_id'] != $tutor_id) {
    header("Location: tutor_dashboard.php");
    exit();
}

$verify_stmt->close();

// Update session status to Declined
$update_sql = "UPDATE sessions SET session_status = 'Declined' WHERE session_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $session_id);

if ($update_stmt->execute()) {
    $_SESSION['message'] = "Session declined.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error declining session.";
    $_SESSION['message_type'] = "error";
}

$update_stmt->close();
header("Location: tutor_dashboard.php");
?>