<?php
require_once '../includes/auth.php';
requireAdmin();

// Get statistics
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalInstructors = $pdo->query("SELECT COUNT(*) FROM users WHERE role='instructor'")->fetchColumn();
$totalEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();

// Get recent courses - FIXED: changed to match your column name
$stmt = $pdo->query("SELECT c.*, u.username as instructor_name 
                      FROM courses c 
                      JOIN users u ON c.instructor_id = u.id 
                      ORDER BY c.created_at DESC 
                      LIMIT 5");
$recentCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Courses</h3>
            <p class="stat-number"><?php echo $totalCourses; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Students</h3>
            <p class="stat-number"><?php echo $totalStudents; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Instructors</h3>
            <p class="stat-number"><?php echo $totalInstructors; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Enrollments</h3>
            <p class="stat-number"><?php echo $totalEnrollments; ?></p>
        </div>
    </div>

    <div class="admin-actions">
        <a href="manage_courses.php" class="btn btn-primary">Manage All Courses</a>
        <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
    </div>

    <section class="recent-courses">
        <h2>Recent Courses</h2>
        <div class="courses-list">
            <?php foreach ($recentCourses as $course): ?>
            <div class="course-item">
                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                <p>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                <p>Price: ₹ <?php echo number_format($course['price'], 2); ?></p>
                <div class="actions">
                    <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn-edit">Edit</a>
                    <a href="delete_course.php?id=<?php echo $course['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
<div class="container">

    <div class="stats-row">
        <div class="stat-card">
            <h3>Total Courses</h3>
            <p>3</p>
        </div>

        <div class="stat-card">
            <h3>Total Enrollments</h3>
            <p>3</p>
        </div>

        <div class="stat-card">
            <h3>Active Students</h3>
            <p>12</p>
        </div>
    </div>

    <div class="action-row">
        <a href="add_course.php" class="btn-primary">+ Add Course</a>
    </div>

    <h2 class="section-title">Admin Courses</h2>

    <div class="course-grid">
        <!-- Loop your courses here -->
    </div>

</div>