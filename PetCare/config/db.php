<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petcare_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session management
session_start();

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user type
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Helper function to redirect based on user type
function redirectByUserType() {
    if (!isLoggedIn()) {
        header('Location: /PetCare/auth/login.php');
        exit();
    }
    
    $userType = getUserType();
    switch($userType) {
        case 'admin':
            header('Location: /PetCare/admin/dashboard.php');
            break;
        case 'shelter':
            header('Location: /PetCare/shelter/dashboard.php');
            break;
        case 'adopter':
            header('Location: /PetCare/adopter/dashboard.php');
            break;
        default:
            header('Location: /PetCare/auth/login.php');
    }
    exit();
}

// Helper function to require specific user type
function requireUserType($requiredType) {
    if (!isLoggedIn()) {
        header('Location: /PetCare/auth/login.php');
        exit();
    }
    
    if (getUserType() !== $requiredType) {
        header('Location: /PetCare/index.php');
        exit();
    }
}

// Helper function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Helper function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Helper function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>