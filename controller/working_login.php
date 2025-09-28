<?php
// File: controller/working_login.php
// Complete working login handler
session_start();
require_once '../model/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stay_logged_in = isset($_POST['stay_logged_in']);
    
    if (empty($username) || empty($password)) {
        header("Location: ../view/login.php?error=" . urlencode("Please enter username and password"));
        exit();
    }
    
    try {
        // Find user with profile data
        $query = "SELECT u.*, up.full_name, up.profile_picture_url 
                  FROM users u 
                  LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                  WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
        $result = execute_prepared_query($query, [$username, $username], 'ss');
        
        if ($result && $result->num_rows > 0) {
            $user = fetch_single_row($result);
            
            if (password_verify($password, $user['password_hash'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Create session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['full_name'] = $user['full_name'] ?? 'Unknown User';
                $_SESSION['profile_picture_url'] = $user['profile_picture_url'] ?? '';
                $_SESSION['is_active'] = $user['is_active'];
                $_SESSION['logged_in'] = 1;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['stay_logged_in'] = $stay_logged_in;
                
                // Set session timeout
                if ($stay_logged_in) {
                    $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // 1.5 months
                } else {
                    $_SESSION['session_timeout'] = time() + 60; // 1 minute
                }
                
                // Update last login
                $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                execute_prepared_query($update_query, [$user['user_id']], 'i');
                
                header("Location: ../controller/dashboard_controller.php");
                exit();
            }
        }
        
        header("Location: ../view/login.php?error=" . urlencode("Invalid username or password"));
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: ../view/login.php?error=" . urlencode("Login failed. Please try again."));
        exit();
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ../view/login.php?success=" . urlencode("You have been logged out"));
    exit();
}

// If accessed directly, redirect to login
header("Location: ../view/login.php");
exit();
?>