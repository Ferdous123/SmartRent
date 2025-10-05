<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../model/database.php';
require_once 'session_controller.php';

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stay_logged_in = isset($_POST['stay_logged_in']);
    
    if (empty($username) || empty($password)) {
        header("Location: ../view/login.php?error=" . urlencode("Please enter username and password"));
        exit();
    }
    
    try {
        $query = "SELECT u.*, up.full_name, up.profile_picture_url 
                  FROM users u 
                  LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                  WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
        $result = execute_prepared_query($query, [$username, $username], 'ss');
        
        if ($result && $result->num_rows > 0) {
            $user = fetch_single_row($result);
            
            if (password_verify($password, $user['password_hash'])) {
                // Use centralized session creation
                create_user_session($user, $stay_logged_in);
                
                header("Location: ../controller/dashboard_controller.php");
                exit();
            }
        }
        
        header("Location: ../view/login.php?error=" . urlencode("Invalid username or password"));
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: ../view/login.php?error=" . urlencode("Login failed"));
        exit();
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    clear_user_session();
    header("Location: ../view/login.php?success=" . urlencode("Logged out successfully"));
    exit();
}

header("Location: ../view/login.php");
exit();
?>