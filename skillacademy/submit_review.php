<?php
// submit_review.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';

// Validate inputs
if(!$course_id || !$rating || !$comment) {
    header("Location: course-details.php?id=$course_id&error=All fields are required");
    exit();
}

// Check if student is enrolled
$check_query = "SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':student_id', $student_id);
$check_stmt->bindParam(':course_id', $course_id);
$check_stmt->execute();

if($check_stmt->rowCount() == 0) {
    header("Location: course-details.php?id=$course_id&error=You must be enrolled to review");
    exit();
}

// Check if already reviewed
$review_check = "SELECT id FROM reviews WHERE user_id = :user_id AND course_id = :course_id";
$review_stmt = $db->prepare($review_check);
$review_stmt->bindParam(':user_id', $student_id);
$review_stmt->bindParam(':course_id', $course_id);
$review_stmt->execute();

if($review_stmt->rowCount() > 0) {
    // Update existing review
    $query = "UPDATE reviews SET rating = :rating, comment = :comment, created_at = NOW() 
              WHERE user_id = :user_id AND course_id = :course_id";
} else {
    // Insert new review
    $query = "INSERT INTO reviews (course_id, user_id, rating, comment, created_at) 
              VALUES (:course_id, :user_id, :rating, :comment, NOW())";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':course_id', $course_id);
$stmt->bindParam(':user_id', $student_id);
$stmt->bindParam(':rating', $rating);
$stmt->bindParam(':comment', $comment);

if($stmt->execute()) {
    header("Location: course-details.php?id=$course_id&message=Review submitted successfully");
} else {
    header("Location: course-details.php?id=$course_id&error=Failed to submit review");
}
exit();
?>