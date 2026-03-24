<?php
// student/save_progress.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$course_id = $_POST['course_id'] ?? 0;
$lesson_id = $_POST['lesson_id'] ?? 0;
$action = $_POST['action'] ?? '';

if($action == 'complete') {
    // Initialize session array for completed lessons
    $key = 'completed_lessons_' . $course_id;
    if(!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Add lesson to completed if not already there
    if(!in_array($lesson_id, $_SESSION[$key])) {
        $_SESSION[$key][] = $lesson_id;
    }
    
    // Calculate progress
    $total_lessons = 5;
    $completed_count = count($_SESSION[$key]);
    $progress = ($completed_count / $total_lessons) * 100;
    
    echo json_encode([
        'success' => true,
        'completed_count' => $completed_count,
        'progress' => $progress,
        'message' => 'Progress saved successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>