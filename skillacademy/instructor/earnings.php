<?php
// instructor/earnings.php
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

// Get total earnings (all time)
$total_query = "SELECT SUM(c.price) as total_earnings
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE c.instructor_id = :instructor_id
                AND e.status IN ('active', 'completed')";

$total_stmt = $db->prepare($total_query);
$total_stmt->bindParam(':instructor_id', $instructor_id);
$total_stmt->execute();
$total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
$total_earnings = $total_result['total_earnings'] ?? 0;

// Get this month's earnings
$this_month_query = "SELECT SUM(c.price) as monthly_earnings
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.id
                    WHERE c.instructor_id = :instructor_id
                    AND e.status IN ('active', 'completed')
                    AND MONTH(e.enrollment_date) = MONTH(CURRENT_DATE())
                    AND YEAR(e.enrollment_date) = YEAR(CURRENT_DATE())";

$this_month_stmt = $db->prepare($this_month_query);
$this_month_stmt->bindParam(':instructor_id', $instructor_id);
$this_month_stmt->execute();
$this_month_result = $this_month_stmt->fetch(PDO::FETCH_ASSOC);
$this_month_earnings = $this_month_result['monthly_earnings'] ?? 0;

// Get pending earnings (enrollments that are active but not completed? Or use a threshold)
$pending_query = "SELECT SUM(c.price) as pending_earnings
                  FROM enrollments e
                  JOIN courses c ON e.course_id = c.id
                  WHERE c.instructor_id = :instructor_id
                  AND e.status = 'active'
                  AND e.progress < 100";

$pending_stmt = $db->prepare($pending_query);
$pending_stmt->bindParam(':instructor_id', $instructor_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->fetch(PDO::FETCH_ASSOC);
$pending_earnings = $pending_result['pending_earnings'] ?? 0;

// Get earnings by course
$course_query = "SELECT c.title, 
                 COUNT(e.id) as student_count,
                 SUM(c.price) as course_earnings
                 FROM courses c
                 LEFT JOIN enrollments e ON c.id = e.course_id
                 WHERE c.instructor_id = :instructor_id
                 AND e.status IN ('active', 'completed')
                 GROUP BY c.id
                 ORDER BY course_earnings DESC";

$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':instructor_id', $instructor_id);
$course_stmt->execute();
$course_earnings = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly earnings for chart (last 12 months)
$monthly_chart_query = "SELECT 
                        DATE_FORMAT(e.enrollment_date, '%Y-%m') as month,
                        SUM(c.price) as monthly_total
                        FROM enrollments e
                        JOIN courses c ON e.course_id = c.id
                        WHERE c.instructor_id = :instructor_id
                        AND e.status IN ('active', 'completed')
                        AND e.enrollment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
                        ORDER BY month ASC";

$monthly_chart_stmt = $db->prepare($monthly_chart_query);
$monthly_chart_stmt->bindParam(':instructor_id', $instructor_id);
$monthly_chart_stmt->execute();
$monthly_earnings_data = $monthly_chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent transactions
$recent_query = "SELECT e.enrollment_date, c.title as course_title, 
                 u.full_name as student_name, c.price as amount
                 FROM enrollments e
                 JOIN courses c ON e.course_id = c.id
                 JOIN users u ON e.student_id = u.id
                 WHERE c.instructor_id = :instructor_id
                 AND e.status IN ('active', 'completed')
                 ORDER BY e.enrollment_date DESC
                 LIMIT 10";

$recent_stmt = $db->prepare($recent_query);
$recent_stmt->bindParam(':instructor_id', $instructor_id);
$recent_stmt->execute();
$recent_transactions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Earnings Dashboard</h2>
                
                <!-- Earnings Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <h2>$<?php echo number_format($total_earnings, 2); ?></h2>
                                <small>Lifetime earnings</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">This Month</h5>
                                <h2>$<?php echo number_format($this_month_earnings, 2); ?></h2>
                                <small><?php echo date('F Y'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <h2>$<?php echo number_format($pending_earnings, 2); ?></h2>
                                <small>Active enrollments</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Earnings Chart -->
                <?php if(count($monthly_earnings_data) > 0): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Earnings Trend (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="earningsChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Earnings by Course -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Earnings by Course</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($course_earnings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course Title</th>
                                                    <th>Students Enrolled</th>
                                                    <th>Total Earnings</th>
                                                    <th>Average per Student</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($course_earnings as $course): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                                    <td><?php echo $course['student_count']; ?></td>
                                                    <td class="text-success fw-bold">$<?php echo number_format($course['course_earnings'], 2); ?></td>
                                                    <td>$<?php echo number_format($course['course_earnings'] / $course['student_count'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No earnings data available yet. 
                                        <a href="my_courses.php" class="alert-link">Create courses</a> and get students enrolled to start earning!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <?php if(count($recent_transactions) > 0): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($transaction['enrollment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['course_title']); ?></td>
                                                <td class="text-success">+$<?php echo number_format($transaction['amount'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tips for Increasing Earnings -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-lightbulb text-warning"></i> Tips to Increase Your Earnings</h5>
                                <ul class="mb-0">
                                    <li>Create high-quality, engaging course content</li>
                                    <li>Promote your courses through social media and email</li>
                                    <li>Offer limited-time discounts to attract more students</li>
                                    <li>Respond to student reviews and improve based on feedback</li>
                                    <li>Create multiple courses to build your portfolio</li>
                                </ul>
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
// Earnings Chart
<?php if(count($monthly_earnings_data) > 0): ?>
const ctx = document.getElementById('earningsChart').getContext('2d');
const months = <?php echo json_encode(array_column($monthly_earnings_data, 'month')); ?>;
const earnings = <?php echo json_encode(array_column($monthly_earnings_data, 'monthly_total')); ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Earnings ($)',
            data: earnings,
            backgroundColor: 'rgba(40, 167, 69, 0.6)',
            borderColor: 'rgb(40, 167, 69)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<style>
.card.bg-primary, .card.bg-success, .card.bg-warning {
    transition: transform 0.3s;
}
.card.bg-primary:hover, .card.bg-success:hover, .card.bg-warning:hover {
    transform: translateY(-5px);
}
</style>

<?php include '../includes/footer.php'; ?>