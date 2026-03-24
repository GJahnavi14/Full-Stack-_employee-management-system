<?php
// student/my_courses.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /skillacademy/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];

// Get message from URL
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Get enrolled courses
$query = "SELECT c.*, e.progress, e.enrollment_date, e.status as enroll_status,
          u.full_name as instructor_name
          FROM enrollments e
          JOIN courses c ON e.course_id = c.id
          JOIN users u ON c.instructor_id = u.id
          WHERE e.student_id = :student_id
          ORDER BY e.enrollment_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <h1 class="mb-4">My Courses</h1>
    
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(count($enrolled_courses) > 0): ?>
        <div class="row">
            <?php foreach($enrolled_courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                        <p class="text-muted">By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                        <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                        
                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <label class="form-label">Progress: <?php echo $course['progress']; ?>%</label>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $course['progress']; ?>%"
                                     aria-valuenow="<?php echo $course['progress']; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $course['progress']; ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Enrolled: <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?>
                            </small>
                            <a href="course_content.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                Continue <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't enrolled in any courses yet.
            <a href="../courses.php" class="alert-link">Browse courses</a> to get started!
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>