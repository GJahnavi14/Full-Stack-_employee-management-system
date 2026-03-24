<?php
// instructor/course_analytics.php
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

// Get overall statistics
$stats_query = "SELECT 
                COUNT(DISTINCT e.id) as total_students,
                COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) as active_students,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_students,
                AVG(e.progress) as avg_progress,
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT r.id) as total_reviews,
                SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN reviews r ON c.id = r.course_id
                WHERE c.id = :course_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':course_id', $course_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly enrollment data
$monthly_query = "SELECT 
                  DATE_FORMAT(e.enrollment_date, '%Y-%m') as month,
                  COUNT(*) as enrollments
                  FROM enrollments e
                  WHERE e.course_id = :course_id
                  AND e.enrollment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
                  ORDER BY month ASC";

$monthly_stmt = $db->prepare($monthly_query);
$monthly_stmt->bindParam(':course_id', $course_id);
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent enrollments
$recent_query = "SELECT u.full_name as student_name, u.email, e.enrollment_date, e.progress, e.status
                FROM enrollments e
                JOIN users u ON e.student_id = u.id
                WHERE e.course_id = :course_id
                ORDER BY e.enrollment_date DESC
                LIMIT 10";

$recent_stmt = $db->prepare($recent_query);
$recent_stmt->bindParam(':course_id', $course_id);
$recent_stmt->execute();
$recent_enrollments = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent reviews
$reviews_query = "SELECT r.*, u.full_name as student_name
                 FROM reviews r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.course_id = :course_id
                 ORDER BY r.created_at DESC
                 LIMIT 5";

$reviews_stmt = $db->prepare($reviews_query);
$reviews_stmt->bindParam(':course_id', $course_id);
$reviews_stmt->execute();
$recent_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <a href="analytics.php" class="list-group-item list-group-item-action active">
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
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Course Analytics: <?php echo htmlspecialchars($course['title']); ?></h2>
                        <p class="text-muted">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name'] ?? 'Uncategorized'); ?> |
                            <i class="fas fa-signal"></i> Level: <?php echo ucfirst($course['level']); ?> |
                            <i class="fas fa-clock"></i> Created: <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Course
                        </a>
                        <a href="my_courses.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2><?php echo $stats['total_students'] ?? 0; ?></h2>
                                <small>All time enrollments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Students</h5>
                                <h2><?php echo $stats['active_students'] ?? 0; ?></h2>
                                <small>Currently learning</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed</h5>
                                <h2><?php echo $stats['completed_students'] ?? 0; ?></h2>
                                <small>Finished course</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Avg Progress</h5>
                                <h2><?php echo round($stats['avg_progress'] ?? 0); ?>%</h2>
                                <small>Overall completion</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Enrollment Trends (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="enrollmentChart" height="300"></canvas>
                                <?php if(empty($monthly_data)): ?>
                                    <p class="text-muted text-center mt-3">No enrollment data available yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Rating Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ratingChart" height="250"></canvas>
                                <?php if(($stats['total_reviews'] ?? 0) == 0): ?>
                                    <p class="text-muted text-center mt-3">No reviews yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Rating Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Rating Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-4 text-center">
                                        <h1 class="display-4"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></h1>
                                        <div class="mb-2">
                                            <?php 
                                            $avg_rating = round($stats['avg_rating'] ?? 0);
                                            for($i = 1; $i <= 5; $i++):
                                                if($i <= $avg_rating):
                                            ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; endfor; ?>
                                        </div>
                                        <p class="text-muted">Based on <?php echo $stats['total_reviews'] ?? 0; ?> reviews</p>
                                    </div>
                                    <div class="col-md-8">
                                        <?php
                                        $ratings = [
                                            5 => $stats['five_star'] ?? 0,
                                            4 => $stats['four_star'] ?? 0,
                                            3 => $stats['three_star'] ?? 0,
                                            2 => $stats['two_star'] ?? 0,
                                            1 => $stats['one_star'] ?? 0
                                        ];
                                        $total = array_sum($ratings);
                                        foreach($ratings as $star => $count):
                                            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
                                        ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2" style="width: 30px;"><?php echo $star; ?> ★</span>
                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="ms-2" style="width: 40px;"><?php echo $count; ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td>Completion Rate:</td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $completion_rate = ($stats['total_students'] ?? 0) > 0 
                                                    ? round(($stats['completed_students'] / $stats['total_students']) * 100, 1) 
                                                    : 0;
                                                echo $completion_rate; ?>%
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Active Rate:</td>
                                        <td class="text-end">
                                            <strong>
                                                <?php 
                                                $active_rate = ($stats['total_students'] ?? 0) > 0 
                                                    ? round(($stats['active_students'] / $stats['total_students']) * 100, 1) 
                                                    : 0;
                                                echo $active_rate; ?>%
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Total Revenue:</td>
                                        <td class="text-end">
                                            <strong>$<?php echo number_format(($stats['total_students'] ?? 0) * $course['price'], 2); ?></strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Course Status:</td>
                                        <td class="text-end">
                                            <?php if($course['status'] == 'published'): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php elseif($course['status'] == 'draft'): ?>
                                                <span class="badge bg-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Enrollments -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Enrollments</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($recent_enrollments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Email</th>
                                                    <th>Enrolled</th>
                                                    <th>Progress</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($recent_enrollments as $enrollment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" style="width: <?php echo $enrollment['progress']; ?>%">
                                                                <?php echo $enrollment['progress']; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if($enrollment['status'] == 'active'): ?>
                                                            <span class="badge bg-primary">Active</span>
                                                        <?php elseif($enrollment['status'] == 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Dropped</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No enrollments yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Reviews -->
                <?php if(count($recent_reviews) > 0): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Reviews</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach($recent_reviews as $review): ?>
                                <div class="border-bottom mb-3 pb-3">
                                    <div class="d-flex justify-content-between">
                                        <h6><?php echo htmlspecialchars($review['student_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $review['rating']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Enrollment Chart
<?php if(!empty($monthly_data)): ?>
const ctx1 = document.getElementById('enrollmentChart').getContext('2d');
const months = <?php echo json_encode(array_column($monthly_data, 'month')); ?>;
const enrollments = <?php echo json_encode(array_column($monthly_data, 'enrollments')); ?>;

new Chart(ctx1, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Enrollments',
            data: enrollments,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>

// Rating Chart
<?php if(($stats['total_reviews'] ?? 0) > 0): ?>
const ctx2 = document.getElementById('ratingChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
        datasets: [{
            data: [
                <?php echo $stats['five_star'] ?? 0; ?>,
                <?php echo $stats['four_star'] ?? 0; ?>,
                <?php echo $stats['three_star'] ?? 0; ?>,
                <?php echo $stats['two_star'] ?? 0; ?>,
                <?php echo $stats['one_star'] ?? 0; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(23, 162, 184, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(253, 126, 20, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>