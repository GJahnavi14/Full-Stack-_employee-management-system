<?php
// instructor/dashboard.php
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

// Get instructor's courses
$courses_query = "SELECT c.*, cat.name as category_name,
                  COUNT(DISTINCT e.id) as student_count,
                  AVG(r.rating) as avg_rating
                  FROM courses c
                  LEFT JOIN categories cat ON c.category_id = cat.id
                  LEFT JOIN enrollments e ON c.id = e.course_id
                  LEFT JOIN reviews r ON c.id = r.course_id
                  WHERE c.instructor_id = :instructor_id
                  GROUP BY c.id
                  ORDER BY c.created_at DESC";

$courses_stmt = $db->prepare($courses_query);
$courses_stmt->bindParam(':instructor_id', $instructor_id);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_courses,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_courses,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_courses,
                (SELECT COUNT(*) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = :inst_id) as total_students
                FROM courses 
                WHERE instructor_id = :instructor_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':instructor_id', $instructor_id);
$stats_stmt->bindParam(':inst_id', $instructor_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
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
                    <a href="reviews.php" class="list-group-item list-group-item-action">
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
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Instructor Dashboard</h2>
                        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Instructor'); ?>!</p>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="value"><?php echo $stats['total_courses'] ?? 0; ?></div>
                                    <div class="label">Total Courses</div>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="value"><?php echo $stats['published_courses'] ?? 0; ?></div>
                                    <div class="label">Published</div>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="value"><?php echo $stats['draft_courses'] ?? 0; ?></div>
                                    <div class="label">Drafts</div>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-pencil-alt text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="value"><?php echo $stats['total_students'] ?? 0; ?></div>
                                    <div class="label">Total Students</div>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12">
                        <a href="upload_course.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Course
                        </a>
                    </div>
                </div>
                
                <!-- My Courses List -->
                <div class="row">
                    <div class="col-12">
                        <h4>My Courses</h4>
                        <hr>
                        
                        <?php if(count($courses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Students</th>
                                            <th>Rating</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($courses as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                            <td><?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?></td>
                                            <td>
                                                <?php if($course['status'] == 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $course['student_count'] ?? 0; ?></td>
                                            <td>
                                                <?php 
                                                $rating = round($course['avg_rating'] ?? 0);
                                                for($i = 1; $i <= 5; $i++):
                                                    if($i <= $rating):
                                                ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; endfor; ?>
                                            </td>
                                            <td>
                                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You haven't created any courses yet.
                                <a href="upload_course.php" class="alert-link">Create your first course</a> to get started!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>