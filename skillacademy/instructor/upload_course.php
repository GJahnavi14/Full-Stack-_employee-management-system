<?php
// instructor/upload_course.php
session_start();
require_once '../includes/db.php';  // FIXED: Changed from include's to ../includes/
require_once '../includes/auth.php'; // FIXED: Added proper path

// Check if user is logged in and is an instructor
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: /skillacademy/login.php");
    exit();
}

$db = getDB();
$message = '';
$error = '';

// Get categories for dropdown
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create uploads directory if it doesn't exist
if(!file_exists('../uploads/')) {
    mkdir('../uploads/', 0777, true);
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $level = $_POST['level'] ?? 'beginner';
    $duration = $_POST['duration'] ?? 0;
    $status = $_POST['status'] ?? 'draft';
    
    // Validate
    if(empty($title)) {
        $error = "Course title is required";
    } else {
        // Handle thumbnail upload
        $thumbnail = null;
        if(isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['thumbnail']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $file_size = $_FILES['thumbnail']['size'];
            
            if(in_array($ext, $allowed)) {
                if($file_size <= 2097152) { // 2MB max
                    $thumbnail = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
                    $upload_path = '../uploads/' . $thumbnail;
                    
                    if(move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                        $message = "Image uploaded successfully!";
                    } else {
                        $error = "Failed to upload image";
                    }
                } else {
                    $error = "File size too large. Maximum 2MB allowed.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
            }
        }
        
        // Insert course
        $query = "INSERT INTO courses (title, description, price, category_id, instructor_id, 
                  thumbnail, level, duration, status, created_at) 
                  VALUES (:title, :description, :price, :category_id, :instructor_id, 
                  :thumbnail, :level, :duration, :status, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':instructor_id', $_SESSION['user_id']);
        $stmt->bindParam(':thumbnail', $thumbnail);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindParam(':status', $status);
        
        if($stmt->execute()) {
            $course_id = $db->lastInsertId();
            $message = "Course created successfully!";
            // Redirect to add modules
            header("Location: add_modules.php?course_id=" . $course_id);
            exit();
        } else {
            $error = "Error creating course";
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
                    <a href="upload_course.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Create New Course</h2>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
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
                                        <input type="text" class="form-control" id="title" name="title" required>
                                        <div class="invalid-feedback">Please enter a course title.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Course Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">Price ($)</label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="0">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="duration" class="form-label">Duration (minutes)</label>
                                            <input type="number" class="form-control" id="duration" name="duration" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="level" class="form-label">Level</label>
                                            <select class="form-select" id="level" name="level">
                                                <option value="beginner">Beginner</option>
                                                <option value="intermediate">Intermediate</option>
                                                <option value="advanced">Advanced</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft">Save as Draft</option>
                                            <option value="published">Publish Now</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Thumbnail Upload -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Course Thumbnail</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="thumbnail" class="form-label">Upload Image</label>
                                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                        <small class="text-muted">Allowed: JPG, PNG, GIF (Max: 2MB)</small>
                                    </div>
                                    <div id="thumbnailPreview" class="mt-2 text-center">
                                        <img src="#" alt="Preview" style="max-width: 100%; max-height: 150px; display: none;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tips -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Tips for Good Thumbnails</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="small">
                                        <li>Use high-quality images (at least 800x450 pixels)</li>
                                        <li>Include the course title in the image</li>
                                        <li>Use bright, engaging colors</li>
                                        <li>Keep it simple and readable</li>
                                        <li>Show what students will learn</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
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