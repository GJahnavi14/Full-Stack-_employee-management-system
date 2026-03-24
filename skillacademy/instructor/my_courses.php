<?php
// instructor/my_courses.php
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

// Get all courses by this instructor
$query = "SELECT c.*, cat.name as category_name,
          COUNT(DISTINCT e.id) as student_count,
          AVG(r.rating) as avg_rating
          FROM courses c
          LEFT JOIN categories cat ON c.category_id = cat.id
          LEFT JOIN enrollments e ON c.id = e.course_id
          LEFT JOIN reviews r ON c.id = r.course_id
          WHERE c.instructor_id = :instructor_id
          GROUP BY c.id
          ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':instructor_id', $instructor_id);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <a href="my_courses.php" class="list-group-item list-group-item-action active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>My Courses</h2>
                    <a href="upload_course.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Course
                    </a>
                </div>
                
                <?php if(count($courses) > 0): ?>
                    <div class="row">
                        <?php foreach($courses as $course): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <?php if($course['thumbnail']): ?>
                                    <img src="/skillacademy/uploads/<?php echo $course['thumbnail']; ?>" class="card-img-top" alt="<?php echo $course['title']; ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 180px;">
                                        <i class="fas fa-image fa-3x text-light"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="text-muted small"><?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?></p>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge <?php echo $course['status'] == 'published' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="fas fa-users"></i> <?php echo $course['student_count'] ?? 0; ?> students
                                        </span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <?php 
                                        $rating = round($course['avg_rating'] ?? 0);
                                        for($i = 1; $i <= 5; $i++):
                                            if($i <= $rating):
                                        ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; endfor; ?>
                                        <small class="text-muted">(<?php echo number_format($course['avg_rating'] ?? 0, 1); ?>)</small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="course_analytics.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-chart-line"></i> Stats
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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

<?php include '../includes/footer.php'; ?>