<?php
// instructor/add_modules.php
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
$course_id = $_GET['course_id'] ?? 0;

// Verify course belongs to this instructor
$course_query = "SELECT * FROM courses WHERE id = :course_id AND instructor_id = :instructor_id";
$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':course_id', $course_id);
$course_stmt->bindParam(':instructor_id', $instructor_id);
$course_stmt->execute();
$course = $course_stmt->fetch(PDO::FETCH_ASSOC);

if(!$course) {
    header("Location: my_courses.php?error=Course not found");
    exit();
}

// Handle form submission
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['save_modules'])) {
        // Process modules and lessons
        $modules = $_POST['modules'] ?? [];
        $success = true;
        
        foreach($modules as $module_index => $module) {
            if(empty($module['title'])) continue;
            
            // Insert module
            $module_query = "INSERT INTO modules (course_id, title, description, order_number) 
                            VALUES (:course_id, :title, :description, :order_number)";
            $module_stmt = $db->prepare($module_query);
            $module_stmt->bindParam(':course_id', $course_id);
            $module_stmt->bindParam(':title', $module['title']);
            $module_stmt->bindParam(':description', $module['description']);
            $module_stmt->bindParam(':order_number', $module_index);
            
            if($module_stmt->execute()) {
                $module_id = $db->lastInsertId();
                
                // Insert lessons for this module
                $lessons = $module['lessons'] ?? [];
                foreach($lessons as $lesson_index => $lesson) {
                    if(empty($lesson['title'])) continue;
                    
                    $lesson_query = "INSERT INTO lessons (module_id, title, content, video_url, duration, order_number) 
                                    VALUES (:module_id, :title, :content, :video_url, :duration, :order_number)";
                    $lesson_stmt = $db->prepare($lesson_query);
                    $lesson_stmt->bindParam(':module_id', $module_id);
                    $lesson_stmt->bindParam(':title', $lesson['title']);
                    $lesson_stmt->bindParam(':content', $lesson['content']);
                    $lesson_stmt->bindParam(':video_url', $lesson['video_url']);
                    $lesson_stmt->bindParam(':duration', $lesson['duration']);
                    $lesson_stmt->bindParam(':order_number', $lesson_index);
                    
                    if(!$lesson_stmt->execute()) {
                        $success = false;
                    }
                }
            } else {
                $success = false;
            }
        }
        
        if($success) {
            // Update course status or whatever
            header("Location: my_courses.php?message=Course content added successfully");
            exit();
        } else {
            $error = "Error adding course content";
        }
    }
}

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
                    <h2>Add Content to: <?php echo htmlspecialchars($course['title']); ?></h2>
                    <a href="my_courses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Courses
                    </a>
                </div>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contentForm">
                    <div id="modulesContainer">
                        <!-- Modules will be added here dynamically -->
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" onclick="addModule()">
                                <i class="fas fa-plus"></i> Add Module
                            </button>
                            <button type="submit" name="save_modules" class="btn btn-success">
                                <i class="fas fa-save"></i> Save All Content
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let moduleCount = 0;

function addModule() {
    moduleCount++;
    const moduleHtml = `
        <div class="card mb-4 module-card" id="module-${moduleCount}">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Module ${moduleCount}</h5>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeModule(${moduleCount})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Module Title</label>
                    <input type="text" class="form-control" name="modules[${moduleCount}][title]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Description</label>
                    <textarea class="form-control" name="modules[${moduleCount}][description]" rows="2"></textarea>
                </div>
                
                <div class="lessons-container" id="lessons-${moduleCount}">
                    <!-- Lessons will be added here -->
                </div>
                
                <button type="button" class="btn btn-sm btn-info" onclick="addLesson(${moduleCount})">
                    <i class="fas fa-plus"></i> Add Lesson
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('modulesContainer').insertAdjacentHTML('beforeend', moduleHtml);
}

function removeModule(moduleId) {
    if(confirm('Are you sure you want to remove this module and all its lessons?')) {
        document.getElementById(`module-${moduleId}`).remove();
    }
}

function addLesson(moduleId) {
    const lessonContainer = document.getElementById(`lessons-${moduleId}`);
    const lessonCount = lessonContainer.children.length + 1;
    
    const lessonHtml = `
        <div class="card mb-3 lesson-card" id="lesson-${moduleId}-${lessonCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Lesson ${lessonCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLesson(${moduleId}, ${lessonCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-2">
                        <input type="text" class="form-control" 
                               name="modules[${moduleId}][lessons][${lessonCount}][title]" 
                               placeholder="Lesson Title" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <input type="number" class="form-control" 
                               name="modules[${moduleId}][lessons][${lessonCount}][duration]" 
                               placeholder="Duration (mins)">
                    </div>
                </div>
                <div class="mb-2">
                    <textarea class="form-control" 
                              name="modules[${moduleId}][lessons][${lessonCount}][content]" 
                              placeholder="Lesson Content" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <input type="url" class="form-control" 
                           name="modules[${moduleId}][lessons][${lessonCount}][video_url]" 
                           placeholder="Video URL (YouTube/Vimeo)">
                </div>
            </div>
        </div>
    `;
    
    lessonContainer.insertAdjacentHTML('beforeend', lessonHtml);
}

function removeLesson(moduleId, lessonId) {
    if(confirm('Are you sure you want to remove this lesson?')) {
        document.getElementById(`lesson-${moduleId}-${lessonId}`).remove();
    }
}

// Add first module automatically on page load
window.onload = function() {
    addModule();
};
</script>

<style>
.module-card {
    border: 2px solid #dee2e6;
    margin-bottom: 20px;
}
.lesson-card {
    background-color: #f8f9fa;
    margin-left: 20px;
}
</style>

<?php include '../includes/footer.php'; ?>