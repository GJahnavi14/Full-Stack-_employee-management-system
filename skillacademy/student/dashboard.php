<?php
// student/dashboard.php
require_once '../includes/db.php';  // Add this to get database connection
require_once '../includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /skillacademy/login.php");
    exit();
}

// Get database connection
$db = getDB();  // This creates the PDO object
$student_id = $_SESSION['user_id'];

// Get enrolled courses - Using $db instead of $pdo
$stmt = $db->prepare("SELECT c.*, u.full_name as instructor_name 
                      FROM courses c 
                      JOIN enrollments e ON c.id = e.course_id 
                      JOIN users u ON c.instructor_id = u.id 
                      WHERE e.student_id = ? AND e.status = 'active'");
$stmt->execute([$student_id]);
$enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available courses (not enrolled) - Using $db instead of $pdo
$stmt = $db->prepare("SELECT c.*, u.full_name as instructor_name 
                      FROM courses c 
                      JOIN users u ON c.instructor_id = u.id 
                      WHERE c.status = 'published' 
                      AND c.id NOT IN (
                          SELECT course_id FROM enrollments WHERE student_id = ?
                      )");
$stmt->execute([$student_id]);
$availableCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get completed courses
$stmt = $db->prepare("SELECT c.*, u.full_name as instructor_name, e.completed_at
                      FROM courses c 
                      JOIN enrollments e ON c.id = e.course_id 
                      JOIN users u ON c.instructor_id = u.id 
                      WHERE e.student_id = ? AND e.status = 'completed'");
$stmt->execute([$student_id]);
$completedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <h1>Student Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student'); ?>!</p>

    <!-- Stats Section -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Enrolled Courses</h5>
                    <h2><?php echo count($enrolledCourses); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <h2><?php echo count($completedCourses); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Available</h5>
                    <h2><?php echo count($availableCourses); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- My Enrollments Section -->
    <section class="my-enrollments mb-5">
        <h2>My Enrollments (<?php echo count($enrolledCourses); ?>)</h2>
        <div class="row">
            <?php if(count($enrolledCourses) > 0): ?>
                <?php foreach ($enrolledCourses as $course): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="text-muted">By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                            <a href="course_content.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">Continue Learning</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        You haven't enrolled in any courses yet. Check out available courses below!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Available Courses Section -->
    <section class="available-courses">
        <h2>Available Courses (<?php echo count($availableCourses); ?>)</h2>
        <div class="row">
            <?php if(count($availableCourses) > 0): ?>
                <?php foreach ($availableCourses as $course): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="text-muted">By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                            <p class="text-primary fw-bold">$<?php echo number_format($course['price'], 2); ?></p>
                            <a href="enroll.php?id=<?php echo $course['id']; ?>" class="btn btn-success">Enroll Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-success">
                        Great job! You're enrolled in all available courses!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
