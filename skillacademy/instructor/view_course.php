<?php
// instructor/view_course.php
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
$course_id = $_GET['id'] ?? 0;

// Verify course belongs to this instructor
$course_query = "SELECT c.*, cat.name as category_name
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.id = :course_id AND c.instructor_id = :instructor_id";
$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':course_id', $course_id);
$course_stmt->bindParam(':instructor_id', $instructor_id);
$course_stmt->execute();
$course = $course_stmt->fetch(PDO::FETCH_ASSOC);

if(!$course) {
    header("Location: my_courses.php?error=Course not found");
    exit();
}

// Get modules and lessons for this course
$modules_query = "SELECT * FROM modules WHERE course_id = :course_id ORDER BY order_number";
$modules_stmt = $db->prepare($modules_query);
$modules_stmt->bindParam(':course_id', $course_id);
$modules_stmt->execute();
$modules = $modules_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get lessons for each module
$lessons_by_module = [];
foreach($modules as $module) {
    $lessons_query = "SELECT * FROM lessons WHERE module_id = :module_id ORDER BY order_number";
    $lessons_stmt = $db->prepare($lessons_query);
    $lessons_stmt->bindParam(':module_id', $module['id']);
    $lessons_stmt->execute();
    $lessons_by_module[$module['id']] = $lessons_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get course statistics
$stats_query = "SELECT 
                COUNT(DISTINCT e.id) as total_students,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_students,
                AVG(r.rating) as avg_rating,
                COUNT(r.id) as total_reviews
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN reviews r ON c.id = r.course_id
                WHERE c.id = :course_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':course_id', $course_id);
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
                <!-- Course Header -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                        <p class="text-muted">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?> |
                            <i class="fas fa-signal"></i> Level: <?php echo ucfirst($course['level']); ?> |
                            <i class="fas fa-clock"></i> Duration: <?php echo floor($course['duration'] / 60); ?> hours |
                            <i class="fas fa-dollar-sign"></i> Price: $<?php echo number_format($course['price'], 2); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group">
                            <a href="edit_course.php?id=<?php echo $course_id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Course
                            </a>
                            <a href="add_modules.php?course_id=<?php echo $course_id; ?>" class="btn btn-info">
                                <i class="fas fa-plus-circle"></i> Add Content
                            </a>
                            <a href="my_courses.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Course Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2><?php echo $stats['total_students'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed</h5>
                                <h2><?php echo $stats['completed_students'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Rating</h5>
                                <h2><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Reviews</h5>
                                <h2><?php echo $stats['total_reviews'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Description -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Course Description</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>
                </div>
                
                <!-- Course Thumbnail -->
                <?php if($course['thumbnail']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Course Thumbnail</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="/skillacademy/uploads/<?php echo $course['thumbnail']; ?>" 
                             alt="Course thumbnail" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Course Content -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Course Content</h5>
                        <span class="badge bg-primary"><?php echo count($modules); ?> Modules</span>
                    </div>
                    <div class="card-body">
                        <?php if(count($modules) > 0): ?>
                            <div class="accordion" id="courseContent">
                                <?php foreach($modules as $index => $module): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $module['id']; ?>">
                                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                                type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $module['id']; ?>">
                                            <strong>Module <?php echo $index + 1; ?>:</strong> 
                                            <?php echo htmlspecialchars($module['title']); ?>
                                            <span class="badge bg-secondary ms-2">
                                                <?php echo count($lessons_by_module[$module['id']] ?? []); ?> lessons
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $module['id']; ?>" 
                                         class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" 
                                         data-bs-parent="#courseContent">
                                        <div class="accordion-body">
                                            <?php if(!empty($module['description'])): ?>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($module['description'])); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($lessons_by_module[$module['id']]) && count($lessons_by_module[$module['id']]) > 0): ?>
                                                <ul class="list-group">
                                                    <?php foreach($lessons_by_module[$module['id']] as $lesson): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-play-circle text-success me-2"></i>
                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                        </div>
                                                        <div>
                                                            <?php if($lesson['duration']): ?>
                                                                <span class="badge bg-info me-2">
                                                                    <i class="far fa-clock"></i> <?php echo $lesson['duration']; ?> min
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if($lesson['video_url']): ?>
                                                                <span class="badge bg-primary">
                                                                    <i class="fas fa-video"></i> Video
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted fst-italic">No lessons in this module yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No modules added yet.</p>
                                <a href="add_modules.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add Modules
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <a href="edit_course.php?id=<?php echo $course_id; ?>" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit Details
                                </a>
                                <a href="add_modules.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-success me-2">
                                    <i class="fas fa-plus-circle"></i> Add Content
                                </a>
                                <a href="../course-details.php?id=<?php echo $course_id; ?>" class="btn btn-outline-info me-2" target="_blank">
                                    <i class="fas fa-eye"></i> Preview as Student
                                </a>
                                <?php if($course['status'] == 'draft'): ?>
                                <a href="publish_course.php?id=<?php echo $course_id; ?>" class="btn btn-success" onclick="return confirm('Publish this course?')">
                                    <i class="fas fa-globe"></i> Publish Course
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>