<?php
header('Content-Type: application/json');

include("config/db.php");

if (!isset($_GET['tutor_subject_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'tutor_subject_id is required']);
    exit;
}

$tutor_subject_id = intval($_GET['tutor_subject_id']);

if ($tutor_subject_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid tutor_subject_id']);
    exit;
}

$sql = "SELECT day_of_week, start_time, end_time
        FROM tutor_availability
        WHERE tutor_subject_id = ?
        ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_subject_id);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = [
        'day' => $row['day_of_week'],
        'start' => date('g:i A', strtotime($row['start_time'])),
        'end' => date('g:i A', strtotime($row['end_time'])),
        'start_raw' => $row['start_time'],
        'end_raw' => $row['end_time']
    ];
}

echo json_encode($slots);
$stmt->close();
