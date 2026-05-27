<?php
session_start();
header('Content-Type: application/json');
include("../../config/db.php");

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 1. AUTHENTICATION CHECK
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutee') {
    echo json_encode([
        "success" => false,
        "error" => "You must be logged in as a tutee to submit a rating."
    ]);
    exit();
}

$tutee_id = $_SESSION['user_id'];

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 2. REQUEST METHOD CHECK
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method."
    ]);
    exit();
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 3. GET AND SANITIZE INPUT
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
$rating_raw = $_POST['rating'] ?? '';

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 4. VALIDATE SESSION_ID
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if ($session_id <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid session ID."
    ]);
    exit();
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 5. VALIDATE RATING VALUE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Must be numeric, a whole number, and between 1-10
if (!is_numeric($rating_raw) 
    || intval($rating_raw) != floatval($rating_raw) 
    || intval($rating_raw) < 1 
    || intval($rating_raw) > 10) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid rating value. Please select a whole number between 1 and 10."
    ]);
    exit();
}

$rating = intval($rating_raw);

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 6. VERIFY SESSION OWNERSHIP & STATUS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$session_check_stmt = $conn->prepare("
    SELECT s.session_id, s.tutor_id, s.tutee_id, s.session_status
    FROM sessions s
    WHERE s.session_id = ?
");
$session_check_stmt->bind_param("i", $session_id);
$session_check_stmt->execute();
$session_result = $session_check_stmt->get_result();

if ($session_result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "error" => "Session not found."
    ]);
    exit();
}

$session = $session_result->fetch_assoc();

// Check if this session belongs to the logged-in tutee
if ($session['tutee_id'] != $tutee_id) {
    echo json_encode([
        "success" => false,
        "error" => "This session does not belong to you."
    ]);
    exit();
}

// Check if session status is 'Completed'
if ($session['session_status'] !== 'Completed') {
    echo json_encode([
        "success" => false,
        "error" => "Ratings can only be submitted for completed sessions."
    ]);
    exit();
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 7. CHECK FOR DUPLICATE RATING
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$duplicate_check_stmt = $conn->prepare("
    SELECT feedback_id FROM feedback_ratings WHERE session_id = ?
");
$duplicate_check_stmt->bind_param("i", $session_id);
$duplicate_check_stmt->execute();

if ($duplicate_check_stmt->get_result()->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "error" => "You have already rated this session."
    ]);
    exit();
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 8. INSERT RATING
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$insert_stmt = $conn->prepare("
    INSERT INTO feedback_ratings (session_id, tutor_id, rating, tutee_id)
    VALUES (?, ?, ?, ?)
");
$insert_stmt->bind_param("iiii", 
    $session_id, 
    $session['tutor_id'], 
    $rating, 
    $tutee_id
);

if (!$insert_stmt->execute()) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to submit rating. Please try again."
    ]);
    exit();
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 9. RECALCULATE TUTOR'S AVERAGE RATING
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$avg_stmt = $conn->prepare("
    UPDATE tutor_profiles 
    SET average_rating = (
        SELECT ROUND(AVG(rating), 1) 
        FROM feedback_ratings 
        WHERE tutor_id = ?
    )
    WHERE tutor_id = ?
");
$avg_stmt->bind_param("ii", $session['tutor_id'], $session['tutor_id']);

if (!$avg_stmt->execute()) {
    // Rating was inserted but average update failed
    // Log error but return success since rating was saved
    error_log("Failed to update average rating for tutor_id: " . $session['tutor_id']);
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 10. RETURN SUCCESS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo json_encode([
    "success" => true,
    "message" => "Rating submitted successfully!"
]);
exit();
?>