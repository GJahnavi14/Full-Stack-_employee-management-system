<?php
// ajax/update_progress.php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$lesson_id = $_POST['lesson_id'] ?? 0;
$course_id = $_POST['course_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$lesson_id || !$course_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    // Check if student is enrolled
    $checkQuery = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$student_id, $course_id]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Not enrolled']);
        exit();
    }
    
    // Update progress logic here
    // For now, just return success
    echo json_encode([
        'success' => true,
        'overall_progress' => 50 // Calculate actual progress
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>