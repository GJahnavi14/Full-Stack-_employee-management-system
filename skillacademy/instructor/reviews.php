<?php
// instructor/reviews.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in and is an instructor
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: /skillacademy/login.php");
    exit();
}

$db = getDB();
$instructor_id = $_SESSION['user_id'];

// Get all reviews for instructor's courses
$query = "SELECT r.*, u.full_name as student_name, c.title as course_title
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          JOIN courses c ON r.course_id = c.id
          WHERE c.instructor_id = :instructor_id
          ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':instructor_id', $instructor_id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="my_courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book"></i> My Courses
                    </a>
                    <a href="upload_course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle"></i> Upload Course
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dollar-sign"></i> Earnings
                    </a>
                    <a href="reviews.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                    <a href="../profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 py-4">
            <div class="container">
                <h2 class="mb-4">Course Reviews</h2>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($review['course_title']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($review['student_name'] ?? 'Anonymous'); ?>
                                    </h6>
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <?php 
                                $rating = $review['rating'] ?? 0;
                                for($i = 1; $i <= 5; $i++):
                                    if($i <= $rating):
                                ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                <?php endif; endfor; ?>
                                <span class="ms-2">(<?php echo $rating; ?>/5)</span>
                            </div>
                            
                            <?php if(!empty($review['comment'])): ?>
                                <div class="card-text bg-light p-3 rounded">
                                    <i class="fas fa-quote-left text-muted me-1"></i>
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    <i class="fas fa-quote-right text-muted ms-1"></i>
                                </div>
                            <?php else: ?>
                                <p class="text-muted fst-italic">No comment provided.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No reviews yet for your courses. 
                        <br>When students leave reviews, they will appear here.
                    </div>
                    
                    <!-- Sample preview for testing -->
                    <div class="card mt-4 bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Sample Review Preview</h5>
                            <p class="text-muted">This is how reviews will look when you get them:</p>
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h6>Web Development Course</h6>
                                        <small>Mar 15, 2024</small>
                                    </div>
                                    <h6 class="text-muted">by John Doe</h6>
                                    <div class="mb-2">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <span class="ms-2">(5/5)</span>
                                    </div>
                                    <p>Great course! Very informative and well-structured. The instructor explains everything clearly.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>