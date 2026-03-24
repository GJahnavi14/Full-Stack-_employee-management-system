<?php
// courses.php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

$db = getDB();

// Get category filter
$category_id = isset($_GET['category']) ? $_GET['category'] : '';

// Debug: Check if courses exist
$debug_query = "SELECT COUNT(*) as total FROM courses WHERE status = 'published'";
$debug_stmt = $db->prepare($debug_query);
$debug_stmt->execute();
$debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
$total_courses = $debug_result['total'];

// Build query
$query = "SELECT c.*, u.full_name as instructor_name, cat.name as category_name
          FROM courses c
          JOIN users u ON c.instructor_id = u.id
          JOIN categories cat ON c.category_id = cat.id
          WHERE c.status = 'published'";

if(!empty($category_id)) {
    $query .= " AND c.category_id = :category_id";
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);

if(!empty($category_id)) {
    $stmt->bindParam(':category_id', $category_id);
}

$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 class="mb-4">All Courses</h1>
    
    <!-- Debug Info (remove after testing) -->
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
    <div class="alert alert-info">
        <strong>Debug:</strong> Total published courses: <?php echo $total_courses; ?>
    </div>
    <?php endif; ?>
    
    <!-- Category Filter -->
    <div class="row mb-4">
        <div class="col-md-4">
            <form method="GET" action="">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" 
                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="col-md-8 text-end">
            <a href="courses.php" class="btn btn-outline-secondary">Clear Filter</a>
        </div>
    </div>
    
    <!-- Courses Grid -->
    <div class="row">
        <?php if(count($courses) > 0): ?>
            <?php foreach($courses as $course): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-signal"></i> Level: <?php echo ucfirst($course['level']); ?>
                        </p>
                        <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="h5 mb-0 text-primary">$<?php echo number_format($course['price'], 2); ?></span>
                            
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student'): ?>
                                <a href="student/enroll.php?id=<?php echo $course['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-graduation-cap"></i> Enroll Now
                                </a>
                            <?php elseif(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-lock"></i> Students Only
                                </button>
                            <?php else: ?>
                                <a href="login.php?redirect=courses.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login to Enroll
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?php if(!empty($category_id)): ?>
                        No courses found in this category. 
                        <a href="courses.php" class="alert-link">View all courses</a>
                    <?php else: ?>
                        No courses available yet. Please check back later.
                    <?php endif; ?>
                </div>
                
                <!-- Show add course link for instructors/admin -->
                <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'instructor' || $_SESSION['role'] == 'admin')): ?>
                <div class="text-center mt-3">
                    <a href="instructor/upload_course.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Course
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>