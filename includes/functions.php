<?php
session_start();

// Database connection
function getDB() {
    global $pdo;
    if (!isset($pdo)) {
        require_once __DIR__ . '/../config/database.php';
    }
    return $pdo;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Upload file
function uploadFile($file, $targetDir) {
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        throw new Exception("Sorry, your file is too large. Maximum size is 5MB.");
    }
    
    // Allow certain file formats for payment proofs
    $allowedExtensions = ["jpg", "jpeg", "png", "pdf"];
    if (!in_array($imageFileType, $allowedExtensions)) {
        throw new Exception("Sorry, only JPG, JPEG, PNG & PDF files are allowed.");
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $fileName;
    } else {
        throw new Exception("Sorry, there was an error uploading your file.");
    }
}

// Generate notification
function createNotification($userId, $message) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$userId, $message]);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Check subscription status
function hasActiveSubscription($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT subscription_end FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['subscription_end']) {
            return false;
        }
        
        return strtotime($result['subscription_end']) > time();
    } catch (PDOException $e) {
        error_log("Error checking subscription: " . $e->getMessage());
        return false;
    }
}

// Format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Redirect with message
function redirect($location, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $location");
    exit();
}

// Display message
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

// Check if user has access to course
function canAccessCourse($userId) {
    if (isAdmin()) {
        return true;
    }
    return hasActiveSubscription($userId);
}

// Get user details
function getUserDetails($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user details: " . $e->getMessage());
        return false;
    }
}

// Check for subscription expiration and notify
function checkSubscriptionExpiration($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT subscription_end FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['subscription_end']) {
            $daysLeft = floor((strtotime($result['subscription_end']) - time()) / (60 * 60 * 24));
            
            if ($daysLeft <= 5 && $daysLeft > 0) {
                createNotification($userId, "Your subscription will expire in $daysLeft days. Please renew to maintain access to courses.");
            }
        }
    } catch (PDOException $e) {
        error_log("Error checking subscription expiration: " . $e->getMessage());
    }
}
