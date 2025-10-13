<?php
// Simplified Session Management Controller for SmartRent
// No warnings, just stay logged in feature

// Prevent multiple inclusions
if (defined('SESSION_CONTROLLER_INCLUDED')) {
    return;
}
define('SESSION_CONTROLLER_INCLUDED', true);

// Include database functions
require_once dirname(__DIR__) . '/model/database.php';

// Configure session settings only if session hasn't started yet
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); 
    session_start();
}

// Create user session after successful login
if (!function_exists('create_user_session')) {
    function create_user_session($user, $stay_logged_in = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['profile_picture_url'] = $user['profile_picture_url'] ?? '';
        $_SESSION['is_active'] = $user['is_active'] ?? 1;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['stay_logged_in'] = $stay_logged_in;
        $_SESSION['logged_in'] = 1;
        
        // Set session timeout based on "stay logged in" choice
        if ($stay_logged_in) {
            $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // 1.5 months (45 days)
        } else {
            $_SESSION['session_timeout'] = time() + 3600; // 1 hour default
        }
        
        // Update user last login
        update_user_last_login($user['user_id']);
        
        return true;
    }
}

// Check if user is logged in and session is valid
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        // Check if basic session exists
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== 1) {
            return false;
        }
        
        // Initialize session timeout if not set (for existing sessions)
        if (!isset($_SESSION['session_timeout'])) {
            $_SESSION['session_timeout'] = time() + 3600; // Default 1 hour
            $_SESSION['stay_logged_in'] = false;
        }
        
        // Check session timeout
        if (time() > $_SESSION['session_timeout']) {
            clear_user_session();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Extend session timeout based on stay logged in preference
        if (isset($_SESSION['stay_logged_in']) && $_SESSION['stay_logged_in']) {
            $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // Extend 1.5 months
        } else {
            $_SESSION['session_timeout'] = time() + 3600; // Extend 1 hour
        }
        
        return true;
    }
}

// Get current user data from session
if (!function_exists('get_logged_in_user')) {
    function get_logged_in_user() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        return array(
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'user_type' => $_SESSION['user_type'],
            'full_name' => $_SESSION['full_name'],
            'profile_picture_url' => $_SESSION['profile_picture_url'],
            'is_active' => $_SESSION['is_active'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity']
        );
    }
}

// Clear user session (logout)
if (!function_exists('clear_user_session')) {
    function clear_user_session() {
        // Clear all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
}

// Update user last login time
if (!function_exists('update_user_last_login')) {
    function update_user_last_login($user_id) {
        $query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $params = array($user_id);
        $types = 'i';
        
        execute_prepared_query($query, $params, $types);
    }
}

// Handle AJAX session check - SIMPLIFIED (no warnings)
if (isset($_POST['action']) && $_POST['action'] === 'check_session') {
    header('Content-Type: application/json');
    
    if (is_user_logged_in()) {
        echo json_encode(array('logged_in' => true));
    } else {
        echo json_encode(array('logged_in' => false));
    }
    exit();
}
?>