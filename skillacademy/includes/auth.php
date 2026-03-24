<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // For backward compatibility
                $_SESSION['name'] = $user['full_name'];
                
                return true;
            }
        }
        return false;
    }
    
    public function register($username, $email, $password, $full_name, $role = 'student') {
        try {
            // Check if user exists
            $checkQuery = "SELECT id FROM users WHERE email = :email OR username = :username";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'User already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user - using full_name column
            $query = "INSERT INTO users (username, email, password, full_name, role) 
                      VALUES (:username, :email, :password, :full_name, :role)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            
            if($stmt->execute()) {
                return ['success' => true, 'message' => 'Registration successful! You can now login.'];
            } else {
                $errorInfo = $stmt->errorInfo();
                return ['success' => false, 'message' => 'Registration failed: ' . $errorInfo[2]];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if(!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Helper functions
function requireLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: /skillacademy/login.php");
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if($_SESSION['role'] !== 'student') {
        header("Location: /skillacademy/index.php");
        exit();
    }
}

function requireInstructor() {
    requireLogin();
    if($_SESSION['role'] !== 'instructor') {
        header("Location: /skillacademy/index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if($_SESSION['role'] !== 'admin') {
        header("Location: /skillacademy/index.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if(isset($_SESSION['user_id'])) {
        switch($_SESSION['role']) {
            case 'admin':
                header("Location: /skillacademy/admin/dashboard.php");
                break;
            case 'instructor':
                header("Location: /skillacademy/instructor/dashboard.php");
                break;
            default:
                header("Location: /skillacademy/student/dashboard.php");
        }
        exit();
    }
}
?>