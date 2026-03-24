<?php
// instructor/analytics.php
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

// Get overall statistics
$stats_query = "SELECT 
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT e.id) as total_enrollments,
                SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completions,
                AVG(r.rating) as avg_rating,
                SUM(c.price) as potential_earnings
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN reviews r ON c.id = r.course_id
                WHERE c.instructor_id = :instructor_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':instructor_id', $instructor_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly enrollment data for chart
$monthly_query = "SELECT 
                  DATE_FORMAT(e.enrollment_date, '%Y-%m') as month,
                  DATE_FORMAT(e.enrollment_date, '%b %Y') as month_label,
                  COUNT(*) as enrollments
                  FROM enrollments e
                  JOIN courses c ON e.course_id = c.id
                  WHERE c.instructor_id = :instructor_id
                  AND e.enrollment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
                  ORDER BY month ASC";

$monthly_stmt = $db->prepare($monthly_query);
$monthly_stmt->bindParam(':instructor_id', $instructor_id);
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create complete 6-month array with all months
$all_months = [];
$current_date = new DateTime();
for($i = 5; $i >= 0; $i--) {
    $date = clone $current_date;
    $date->modify("-$i months");
    $month_key = $date->format('Y-m');
    $month_label = $date->format('M Y');
    $all_months[$month_key] = [
        'month' => $month_key,
        'month_label' => $month_label,
        'enrollments' => 0
    ];
}

// Fill in actual data
foreach($monthly_data as $data) {
    if(isset($all_months[$data['month']])) {
        $all_months[$data['month']]['enrollments'] = $data['enrollments'];
        $all_months[$data['month']]['month_label'] = $data['month_label'];
    }
}

// Convert to arrays for chart
$chart_labels = [];
$chart_data = [];
foreach($all_months as $month) {
    $chart_labels[] = $month['month_label'];
    $chart_data[] = $month['enrollments'];
}

// Get course performance
$course_query = "SELECT 
                c.id,
                c.title,
                COUNT(DISTINCT e.id) as students,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed,
                AVG(r.rating) as rating
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN reviews r ON c.id = r.course_id
                WHERE c.instructor_id = :instructor_id
                GROUP BY c.id
                ORDER BY students DESC
                LIMIT 5";

$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':instructor_id', $instructor_id);
$course_stmt->execute();
$course_performance = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h2 class="mb-4">Analytics Dashboard</h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Courses</h5>
                                <h2><?php echo $stats['total_courses'] ?? 0; ?></h2>
                                <i class="fas fa-book fa-2x position-absolute end-0 bottom-0 me-3 mb-3 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2><?php echo $stats['total_enrollments'] ?? 0; ?></h2>
                                <i class="fas fa-users fa-2x position-absolute end-0 bottom-0 me-3 mb-3 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completions</h5>
                                <h2><?php echo $stats['completions'] ?? 0; ?></h2>
                                <i class="fas fa-check-circle fa-2x position-absolute end-0 bottom-0 me-3 mb-3 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Avg Rating</h5>
                                <h2><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?> / 5</h2>
                                <i class="fas fa-star fa-2x position-absolute end-0 bottom-0 me-3 mb-3 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Enrollment Trends (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="enrollmentChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Completion Rate
                                        <span class="badge bg-primary rounded-pill">
                                            <?php 
                                            $rate = $stats['total_enrollments'] > 0 
                                                ? round(($stats['completions'] / $stats['total_enrollments']) * 100, 1) 
                                                : 0;
                                            echo $rate; ?>%
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Potential Earnings
                                        <span class="badge bg-success rounded-pill">
                                            $<?php echo number_format($stats['potential_earnings'] ?? 0, 2); ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Avg Students/Course
                                        <span class="badge bg-info rounded-pill">
                                            <?php 
                                            $avg = $stats['total_courses'] > 0 
                                                ? round($stats['total_enrollments'] / $stats['total_courses'], 1) 
                                                : 0;
                                            echo $avg; ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Performing Courses -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Performing Courses</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Course Title</th>
                                                <th>Students</th>
                                                <th>Completed</th>
                                                <th>Completion Rate</th>
                                                <th>Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($course_performance) > 0): ?>
                                                <?php foreach($course_performance as $course): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                                    <td><?php echo $course['students'] ?? 0; ?></td>
                                                    <td><?php echo $course['completed'] ?? 0; ?></td>
                                                    <td>
                                                        <?php 
                                                        $rate = $course['students'] > 0 
                                                            ? round(($course['completed'] / $course['students']) * 100, 1) 
                                                            : 0;
                                                        ?>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" style="width: <?php echo $rate; ?>%">
                                                                <?php echo $rate; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $rating = round($course['rating'] ?? 0);
                                                        for($i = 1; $i <= 5; $i++):
                                                            if($i <= $rating):
                                                        ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-warning"></i>
                                                        <?php endif; endfor; ?>
                                                        (<?php echo number_format($course['rating'] ?? 0, 1); ?>)
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No course data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Enrollment Chart - Now with all 6 months showing
const ctx = document.getElementById('enrollmentChart').getContext('2d');
const months = <?php echo json_encode($chart_labels); ?>;
const enrollments = <?php echo json_encode($chart_data); ?>;

// Debug output (can be removed later)
console.log('Chart Months:', months);
console.log('Chart Data:', enrollments);

new Chart(ctx, {
    type: 'bar', // Changed to bar chart for better visibility
    data: {
        labels: months,
        datasets: [{
            label: 'Enrollments',
            data: enrollments,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgb(75, 192, 192)',
            borderWidth: 2,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Enrollments: ' + context.raw;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        return value;
                    }
                },
                title: {
                    display: true,
                    text: 'Number of Enrollments'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month'
                }
            }
        }
    }
});
</script>

<style>
.card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning {
    transition: transform 0.3s;
}
.card.bg-primary:hover, .card.bg-success:hover, .card.bg-info:hover, .card.bg-warning:hover {
    transform: translateY(-5px);
}
.progress {
    background-color: #e9ecef;
}
.progress-bar {
    background-color: #28a745;
    transition: width 0.6s ease;
}
</style>

<?php include '../includes/footer.php'; ?>