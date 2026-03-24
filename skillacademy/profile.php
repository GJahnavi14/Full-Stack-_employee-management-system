<?php
// profile.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';
        
        // Check if email already exists for another user
        $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':id', $user_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            $error = "Email already exists for another user";
        } else {
            // Handle profile image upload
            $profile_image = $user['profile_image'];
            if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if(in_array($ext, $allowed)) {
                    $profile_image = time() . '_' . $filename;
                    $upload_path = 'uploads/profiles/' . $profile_image;
                    
                    // Create directory if not exists
                    if(!file_exists('uploads/profiles')) {
                        mkdir('uploads/profiles', 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path);
                    
                    // Delete old image if exists
                    if($user['profile_image'] && file_exists('uploads/profiles/' . $user['profile_image'])) {
                        unlink('uploads/profiles/' . $user['profile_image']);
                    }
                }
            }
            
            // Update user data
            $update_query = "UPDATE users SET full_name = :full_name, email = :email, 
                            bio = :bio, profile_image = :profile_image WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':full_name', $full_name);
            $update_stmt->bindParam(':email', $email);
            $update_stmt->bindParam(':bio', $bio);
            $update_stmt->bindParam(':profile_image', $profile_image);
            $update_stmt->bindParam(':id', $user_id);
            
            if($update_stmt->execute()) {
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['name'] = $full_name;
                $_SESSION['email'] = $email;
                
                $message = "Profile updated successfully!";
                
                // Refresh user data
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Error updating profile";
            }
        }
    }
    
    // Handle password change
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        if(password_verify($current_password, $user['password'])) {
            if($new_password === $confirm_password) {
                if(strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $pass_query = "UPDATE users SET password = :password WHERE id = :id";
                    $pass_stmt = $db->prepare($pass_query);
                    $pass_stmt->bindParam(':password', $hashed_password);
                    $pass_stmt->bindParam(':id', $user_id);
                    
                    if($pass_stmt->execute()) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Error changing password";
                    }
                } else {
                    $error = "New password must be at least 6 characters";
                }
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Profile</h1>
            
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
        </div>
    </div>
    
    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if($user['profile_image'] && file_exists('uploads/profiles/' . $user['profile_image'])): ?>
                            <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" 
                                 alt="Profile" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-5x text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <p class="mb-1"><strong><?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?></strong></p>
                    <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                    <p class="text-muted"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted"><i class="fas fa-calendar"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Edit Profile Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <small class="text-muted">Allowed: JPG, PNG, GIF (Max: 2MB)</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" onsubmit="return validatePassword()">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validatePassword() {
    var newPass = document.getElementById('new_password').value;
    var confirmPass = document.getElementById('confirm_password').value;
    
    if(newPass.length < 6) {
        alert('New password must be at least 6 characters long');
        return false;
    }
    
    if(newPass !== confirmPass) {
        alert('New passwords do not match');
        return false;
    }
    
    return true;
}

// Image preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            // You can add a preview here if you want
            console.log('Image selected:', file.name);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'includes/footer.php'; ?>