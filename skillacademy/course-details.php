<?php
// course-details.php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

$db = getDB();
$course_id = $_GET['id'] ?? 0;

// Get course details
$query = "SELECT c.*, u.full_name as instructor_name, u.bio as instructor_bio, 
          u.profile_image as instructor_image, cat.name as category_name
          FROM courses c
          JOIN users u ON c.instructor_id = u.id
          JOIN categories cat ON c.category_id = cat.id
          WHERE c.id = :course_id AND c.status = 'published'";

$stmt = $db->prepare($query);
$stmt->bindParam(':course_id', $course_id);
$stmt->execute();
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$course) {
    header("Location: courses.php?error=Course not found");
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

// Get total lessons count
$total_lessons = 0;
foreach($lessons_by_module as $lessons) {
    $total_lessons += count($lessons);
}

// Get reviews for this course
$reviews_query = "SELECT r.*, u.full_name as student_name, u.profile_image as student_image
                 FROM reviews r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.course_id = :course_id
                 ORDER BY r.created_at DESC";

$reviews_stmt = $db->prepare($reviews_query);
$reviews_stmt->bindParam(':course_id', $course_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach($reviews as $review) {
    $avg_rating += $review['rating'];
    $rating_counts[$review['rating']]++;
}
$avg_rating = count($reviews) > 0 ? round($avg_rating / count($reviews), 1) : 0;

// Check if user is enrolled (if logged in as student)
$is_enrolled = false;
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
    $check_query = "SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':student_id', $_SESSION['user_id']);
    $check_stmt->bindParam(':course_id', $course_id);
    $check_stmt->execute();
    $is_enrolled = $check_stmt->rowCount() > 0;
}
?>

<div class="container">
    <!-- Course Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
            
            <div class="d-flex flex-wrap gap-3 mb-3">
                <span class="badge bg-primary"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name']); ?></span>
                <span class="badge bg-info"><i class="fas fa-signal"></i> Level: <?php echo ucfirst($course['level']); ?></span>
                <span class="badge bg-success"><i class="fas fa-clock"></i> Duration: <?php echo floor($course['duration'] / 60); ?> hours</span>
                <span class="badge bg-warning"><i class="fas fa-star"></i> Rating: <?php echo $avg_rating; ?>/5 (<?php echo count($reviews); ?> reviews)</span>
                <span class="badge bg-secondary"><i class="fas fa-users"></i> Students: <?php echo $course['enrollment_count'] ?? 0; ?></span>
            </div>
            
            <!-- Price and Enrollment -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="h2 text-primary">$<?php echo number_format($course['price'], 2); ?></span>
                            <?php if($course['price'] == 0): ?>
                                <span class="badge bg-success ms-2">Free</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($_SESSION['role'] == 'student'): ?>
                                <?php if($is_enrolled): ?>
                                    <a href="student/course_content.php?id=<?php echo $course_id; ?>" class="btn btn-success btn-lg">
                                        <i class="fas fa-play-circle"></i> Go to Course
                                    </a>
                                <?php else: ?>
                                    <a href="student/enroll.php?id=<?php echo $course_id; ?>" class="btn btn-primary btn-lg">
                                        <i class="fas fa-graduation-cap"></i> Enroll Now
                                    </a>
                                <?php endif; ?>
                            <?php elseif($_SESSION['role'] == 'instructor'): ?>
                                <a href="instructor/view_course.php?id=<?php echo $course_id; ?>" class="btn btn-info btn-lg">
                                    <i class="fas fa-eye"></i> View as Instructor
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php?redirect=course-details.php?id=<?php echo $course_id; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login to Enroll
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Instructor Card -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Your Instructor</h5>
                </div>
                <div class="card-body text-center">
                    <?php if($course['instructor_image'] && file_exists('uploads/profiles/' . $course['instructor_image'])): ?>
                        <img src="uploads/profiles/<?php echo $course['instructor_image']; ?>" 
                             alt="<?php echo htmlspecialchars($course['instructor_name']); ?>"
                             class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-4x text-white"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?php echo htmlspecialchars($course['instructor_name']); ?></h5>
                    <p class="text-muted">Instructor</p>
                    
                    <?php if(!empty($course['instructor_bio'])): ?>
                        <p class="small"><?php echo nl2br(htmlspecialchars($course['instructor_bio'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Course Content -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Course Content</h5>
                    <span class="badge bg-light text-dark"><?php echo count($modules); ?> modules • <?php echo $total_lessons; ?> lessons</span>
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
                                                    <?php if($lesson['duration']): ?>
                                                        <span class="badge bg-info">
                                                            <i class="far fa-clock"></i> <?php echo $lesson['duration']; ?> min
                                                        </span>
                                                    <?php endif; ?>
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
                        <p class="text-muted text-center">Course content is being prepared. Check back soon!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- What You'll Learn -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> What You'll Learn</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Master core concepts and fundamentals</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Build real-world projects</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Understand best practices and industry standards</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Get hands-on experience with practical examples</li>
                        <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Learn from expert instructors</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> Requirements</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="fas fa-laptop me-2"></i> Basic computer skills</li>
                        <li class="list-group-item"><i class="fas fa-wifi me-2"></i> Internet connection</li>
                        <li class="list-group-item"><i class="fas fa-heart me-2"></i> Willingness to learn</li>
                        <li class="list-group-item"><i class="fas fa-clock me-2"></i> Dedicate <?php echo floor($course['duration'] / 60); ?> hours of study</li>
                        <?php if($course['level'] == 'beginner'): ?>
                            <li class="list-group-item"><i class="fas fa-smile me-2"></i> No prior experience needed</li>
                        <?php elseif($course['level'] == 'intermediate'): ?>
                            <li class="list-group-item"><i class="fas fa-star me-2"></i> Basic knowledge recommended</li>
                        <?php else: ?>
                            <li class="list-group-item"><i class="fas fa-trophy me-2"></i> Advanced level - prior experience required</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Student Reviews</h5>
                    <span class="badge bg-light text-dark"><?php echo count($reviews); ?> reviews</span>
                </div>
                <div class="card-body">
                    <?php if(count($reviews) > 0): ?>
                        <!-- Rating Summary -->
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <h1 class="display-3"><?php echo $avg_rating; ?></h1>
                                <div class="mb-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= round($avg_rating)): ?>
                                            <i class="fas fa-star text-warning fa-lg"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning fa-lg"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted">Based on <?php echo count($reviews); ?> reviews</p>
                            </div>
                            <div class="col-md-8">
                                <?php for($star = 5; $star >= 1; $star--): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="width: 60px;"><?php echo $star; ?> ★</span>
                                        <div class="progress flex-grow-1" style="height: 15px;">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?php echo count($reviews) > 0 ? ($rating_counts[$star] / count($reviews)) * 100 : 0; ?>%"></div>
                                        </div>
                                        <span class="ms-2" style="width: 40px;"><?php echo $rating_counts[$star]; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Individual Reviews -->
                        <?php foreach($reviews as $review): ?>
                        <div class="border-bottom mb-3 pb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex">
                                    <?php if($review['student_image'] && file_exists('uploads/profiles/' . $review['student_image'])): ?>
                                        <img src="uploads/profiles/<?php echo $review['student_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($review['student_name']); ?>"
                                             class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center me-2"
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['student_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if(!empty($review['comment'])): ?>
                                <p class="mt-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No reviews yet. Be the first to review this course!</p>
                    <?php endif; ?>
                    
                    <!-- Review Form (for enrolled students) -->
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student' && $is_enrolled): ?>
                        <div class="mt-4">
                            <h5>Write a Review</h5>
                            <form action="submit_review.php" method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input">
                                        <?php for($i = 5; $i >= 1; $i--): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="rating" 
                                                       id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="form-check-label" for="star<?php echo $i; ?>"><?php echo $i; ?> ★</label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>