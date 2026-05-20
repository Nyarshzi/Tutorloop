<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/config/db.php';

$payload = json_decode(file_get_contents('php://input'), true);
$subjectName = isset($payload['subject_name']) ? trim($payload['subject_name']) : '';

if ($subjectName === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subject name cannot be empty']);
    exit;
}

$stmt = $conn->prepare('SELECT subject_id, subject_name FROM subjects WHERE LOWER(subject_name) = LOWER(?) LIMIT 1');
$stmt->bind_param('s', $subjectName);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $subject = $result->fetch_assoc();
    echo json_encode([
        'success' => false,
        'message' => 'Subject already exists',
        'subject_id' => $subject['subject_id'],
        'subject_name' => $subject['subject_name']
    ]);
    exit;
}
$stmt->close();

$insert = $conn->prepare('INSERT INTO subjects (subject_name) VALUES (?)');
$insert->bind_param('s', $subjectName);
if (!$insert->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to add subject']);
    exit;
}

$newSubjectId = $insert->insert_id;
$insert->close();

echo json_encode([
    'success' => true,
    'subject_id' => $newSubjectId,
    'subject_name' => $subjectName
]);
exit;
