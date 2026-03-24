<?php
// student/complete_course.php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'] ?? 0;

if(!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit();
}

// Check if enrollment exists
$check_query = "SELECT * FROM enrollments WHERE student_id = :student_id AND course_id = :course_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':student_id', $student_id);
$check_stmt->bindParam(':course_id', $course_id);
$check_stmt->execute();
$enrollment = $check_stmt->fetch(PDO::FETCH_ASSOC);

if(!$enrollment) {
    echo json_encode(['success' => false, 'message' => 'Not enrolled in this course']);
    exit();
}

// Check if already completed
if($enrollment['status'] === 'completed') {
    echo json_encode(['success' => true, 'message' => 'Course already completed']);
    exit();
}

// Update enrollment status to completed
$update_query = "UPDATE enrollments 
                 SET status = 'completed', 
                     progress = 100, 
                     completed_at = NOW() 
                 WHERE student_id = :student_id AND course_id = :course_id";

$update_stmt = $db->prepare($update_query);
$update_stmt->bindParam(':student_id', $student_id);
$update_stmt->bindParam(':course_id', $course_id);

if($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Course completed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error completing course']);
}
?>