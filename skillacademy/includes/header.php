<?php
// includes/header.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Academy - Learn Anywhere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/skillacademy/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/skillacademy/index.php">
                <i class="fas fa-graduation-cap"></i> Skill Academy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/skillacademy/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/skillacademy/courses.php">Courses</a>
                    </li>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'instructor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/skillacademy/instructor/dashboard.php">Instructor Dashboard</a>
                    </li>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/skillacademy/admin/dashboard.php">Admin Dashboard</a>
                    </li>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/skillacademy/student/dashboard.php">My Dashboard</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> 
                                <?php 
                                // Safely display user name
                                if(isset($_SESSION['name']) && !empty($_SESSION['name'])) {
                                    echo htmlspecialchars($_SESSION['name']);
                                } elseif(isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
                                    echo htmlspecialchars($_SESSION['full_name']);
                                } elseif(isset($_SESSION['email']) && !empty($_SESSION['email'])) {
                                    $email_parts = explode('@', $_SESSION['email']);
                                    echo htmlspecialchars($email_parts[0]);
                                } else {
                                    echo 'User';
                                }
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/skillacademy/profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/skillacademy/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/skillacademy/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/skillacademy/register.php"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="py-4">