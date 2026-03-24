<?php
// student/enroll.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /skillacademy/login.php?redirect=student/enroll.php&id=" . $_GET['id']);
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? 0;

// Validate course exists
$check_query = "SELECT * FROM courses WHERE id = :id AND status = 'published'";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $course_id);
$check_stmt->execute();
$course = $check_stmt->fetch(PDO::FETCH_ASSOC);

if(!$course) {
    header("Location: /skillacademy/courses.php?error=Course not found");
    exit();
}

// Check if already enrolled
$enroll_check = "SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id";
$enroll_stmt = $db->prepare($enroll_check);
$enroll_stmt->bindParam(':student_id', $student_id);
$enroll_stmt->bindParam(':course_id', $course_id);
$enroll_stmt->execute();

if($enroll_stmt->rowCount() > 0) {
    header("Location: /skillacademy/student/my_courses.php?message=Already enrolled");
    exit();
}

// Enroll the student
$enroll_query = "INSERT INTO enrollments (student_id, course_id, status, progress, enrollment_date) 
                 VALUES (:student_id, :course_id, 'active', 0, NOW())";
$enroll_stmt = $db->prepare($enroll_query);
$enroll_stmt->bindParam(':student_id', $student_id);
$enroll_stmt->bindParam(':course_id', $course_id);

if($enroll_stmt->execute()) {
    header("Location: /skillacademy/student/my_courses.php?message=Enrollment successful");
} else {
    header("Location: /skillacademy/courses.php?error=Enrollment failed");
}
exit();
?>