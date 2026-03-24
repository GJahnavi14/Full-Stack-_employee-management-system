<?php
// index.php
session_start();
require_once 'includes/db.php';

$db = getDB();

// Get featured courses
$query = "SELECT c.*, u.full_name as instructor_name, cat.name as category_name,
          COUNT(DISTINCT e.id) as student_count,
          AVG(r.rating) as avg_rating
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          LEFT JOIN categories cat ON c.category_id = cat.id
          LEFT JOIN enrollments e ON c.id = e.course_id
          LEFT JOIN reviews r ON c.id = r.course_id
          WHERE c.status = 'published'
          GROUP BY c.id
          ORDER BY c.created_at DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1>Learn from the Best</h1>
                <p class="lead">Join thousands of students mastering new skills with our expert-led courses.</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-light btn-lg">Get Started</a>
                <?php else: ?>
                    <a href="courses.php" class="btn btn-light btn-lg">Browse Courses</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-graduation-cap fa-6x"></i>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<div class="container py-5">
    <h2 class="text-center mb-5">Popular Categories</h2>
    <div class="row g-4">
        <?php if(count($categories) > 0): ?>
            <?php foreach($categories as $cat): ?>
            <div class="col-md-3">
                <div class="card text-center h-100 shadow-sm">
                    <div class="card-body">
                        <?php
                        $icons = [
                            'Web Development' => 'fa-code',
                            'Data Science' => 'fa-chart-line',
                            'Mobile Development' => 'fa-mobile-alt',
                            'DevOps' => 'fa-cloud'
                        ];
                        $icon = $icons[$cat['name']] ?? 'fa-book';
                        ?>
                        <i class="fas <?php echo $icon; ?> fa-3x text-primary mb-3"></i>
                        <h5 class="card-title"><?php echo htmlspecialchars($cat['name']); ?></h5>
                        <p class="card-text text-muted small"><?php echo htmlspecialchars($cat['description']); ?></p>
                        <a href="courses.php?category=<?php echo $cat['id']; ?>" class="btn btn-outline-primary btn-sm mt-2">Browse</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p>No categories available.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Featured Courses Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Courses</h2>
        
        <?php if(count($featured_courses) > 0): ?>
            <div class="row g-4">
                <?php foreach($featured_courses as $course): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <?php 
                        $image_path = 'uploads/' . $course['thumbnail'];
                        if($course['thumbnail'] && file_exists($image_path)): 
                        ?>
                            <img src="<?php echo $image_path; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary" 
                                 style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-graduation-cap fa-4x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                            </p>
                            <div class="mb-2">
                                <?php 
                                $rating = round($course['avg_rating'] ?? 0);
                                for($i = 1; $i <= 5; $i++):
                                    if($i <= $rating):
                                ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                <?php endif; 
                                endfor; 
                                ?>
                                <span class="text-muted small ms-1">(<?php echo $course['student_count'] ?? 0; ?> students)</span>
                            </div>
                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="h5 text-primary mb-0">$<?php echo number_format($course['price'], 2); ?></span>
                                <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                    View Course <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No courses available yet. Check back soon!
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Why Choose Us Section -->
<div class="container py-5">
    <h2 class="text-center mb-5">Why Choose Skill Academy?</h2>
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-chalkboard-teacher fa-3x text-primary mb-3"></i>
                    <h5>Expert Instructors</h5>
                    <p class="text-muted">Learn from industry professionals with years of experience</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                    <h5>Learn at Your Pace</h5>
                    <p class="text-muted">Access course materials anytime, anywhere</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                    <h5>Get Certified</h5>
                    <p class="text-muted">Earn certificates upon course completion</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h5>Community Support</h5>
                    <p class="text-muted">Join a community of passionate learners</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>