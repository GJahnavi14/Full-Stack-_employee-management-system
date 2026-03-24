<?php
// instructor/edit_course.php
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

// Get categories for dropdown
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $level = $_POST['level'] ?? 'beginner';
    $duration = $_POST['duration'] ?? 0;
    $status = $_POST['status'] ?? 'draft';
    
    if(empty($title)) {
        $error = "Course title is required";
    } else {
        // Handle thumbnail upload
        $thumbnail = $course['thumbnail'];
        if(isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['thumbnail']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                // Delete old thumbnail
                if($thumbnail && file_exists('../uploads/' . $thumbnail)) {
                    unlink('../uploads/' . $thumbnail);
                }
                
                $thumbnail = time() . '_' . $filename;
                $upload_path = '../uploads/' . $thumbnail;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path);
            }
        }
        
        // Update course
        $update_query = "UPDATE courses SET 
                        title = :title, 
                        description = :description, 
                        price = :price, 
                        category_id = :category_id, 
                        level = :level, 
                        duration = :duration, 
                        thumbnail = :thumbnail, 
                        status = :status 
                        WHERE id = :id AND instructor_id = :instructor_id";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':title', $title);
        $update_stmt->bindParam(':description', $description);
        $update_stmt->bindParam(':price', $price);
        $update_stmt->bindParam(':category_id', $category_id);
        $update_stmt->bindParam(':level', $level);
        $update_stmt->bindParam(':duration', $duration);
        $update_stmt->bindParam(':thumbnail', $thumbnail);
        $update_stmt->bindParam(':status', $status);
        $update_stmt->bindParam(':id', $course_id);
        $update_stmt->bindParam(':instructor_id', $instructor_id);
        
        if($update_stmt->execute()) {
            $message = "Course updated successfully!";
            
            // Refresh course data
            $course_stmt->execute();
            $course = $course_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Error updating course";
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
                    <h2>Edit Course</h2>
                    <div>
                        <a href="add_modules.php?course_id=<?php echo $course_id; ?>" class="btn btn-info me-2">
                            <i class="fas fa-plus-circle"></i> Add Modules
                        </a>
                        <a href="my_courses.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Courses
                        </a>
                    </div>
                </div>
                
                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Course Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($course['title']); ?>" required>
                                        <div class="invalid-feedback">Please enter a course title.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Course Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($course['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">Price ($)</label>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   step="0.01" min="0" value="<?php echo $course['price']; ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="duration" class="form-label">Duration (minutes)</label>
                                            <input type="number" class="form-control" id="duration" name="duration" 
                                                   min="0" value="<?php echo $course['duration']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo $course['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="level" class="form-label">Level</label>
                                            <select class="form-select" id="level" name="level">
                                                <option value="beginner" <?php echo $course['level'] == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="intermediate" <?php echo $course['level'] == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="advanced" <?php echo $course['level'] == 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo $course['status'] == 'draft' ? 'selected' : ''; ?>>Save as Draft</option>
                                            <option value="published" <?php echo $course['status'] == 'published' ? 'selected' : ''; ?>>Publish Now</option>
                                            <option value="archived" <?php echo $course['status'] == 'archived' ? 'selected' : ''; ?>>Archive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Thumbnail -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Course Thumbnail</h5>
                                </div>
                                <div class="card-body">
                                    <?php if($course['thumbnail'] && file_exists('../uploads/' . $course['thumbnail'])): ?>
                                        <div class="mb-3 text-center">
                                            <img src="/skillacademy/uploads/<?php echo $course['thumbnail']; ?>" 
                                                 alt="Current thumbnail" class="img-fluid rounded" style="max-height: 150px;">
                                            <p class="text-muted small mt-1">Current thumbnail</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="thumbnail" class="form-label">Upload New Image</label>
                                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                        <small class="text-muted">Allowed: JPG, PNG, GIF (Max: 2MB)</small>
                                    </div>
                                    <div id="thumbnailPreview" class="mt-2 text-center">
                                        <img src="#" alt="Preview" style="max-width: 100%; max-height: 150px; display: none;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Course Stats -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Course Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Course ID
                                            <span class="badge bg-primary rounded-pill"><?php echo $course['id']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Created
                                            <span><?php echo date('M d, Y', strtotime($course['created_at'])); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Last Updated
                                            <span><?php echo date('M d, Y', strtotime($course['updated_at'])); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Course
                        </button>
                        <a href="my_courses.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
document.getElementById('thumbnail').addEventListener('change', function(e) {
    const preview = document.querySelector('#thumbnailPreview img');
    const file = e.target.files[0];
    
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>