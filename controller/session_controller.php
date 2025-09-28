<?php
// Session Management Controller for SmartRent
// Handles session creation, validation, and security

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
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    ini_set('session.gc_maxlifetime', 60); // 1 minute default
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
            
            // Set long-lived cookie
            setcookie('smartrent_remember', generate_remember_token($user['user_id']), time() + (45 * 24 * 60 * 60), '/', '', false, true);
        } else {
            $_SESSION['session_timeout'] = time() + 60; // 1 minute default
        }
        
        // Store session fingerprint for security
        $_SESSION['user_fingerprint'] = generate_session_fingerprint();
        
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
            $_SESSION['session_timeout'] = time() + 60; // Default 1 minute
            $_SESSION['stay_logged_in'] = false;
        }
        
        // Check session timeout
        if (time() > $_SESSION['session_timeout']) {
            clear_user_session();
            return false;
        }
        
        // Initialize fingerprint if not set (for existing sessions)
        if (!isset($_SESSION['user_fingerprint'])) {
            $_SESSION['user_fingerprint'] = generate_session_fingerprint();
        }
        
        // Check session fingerprint for security
        if (!verify_session_fingerprint()) {
            clear_user_session();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Extend session timeout based on stay logged in preference
        if (isset($_SESSION['stay_logged_in']) && $_SESSION['stay_logged_in']) {
            $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // Extend 1.5 months
        } else {
            $_SESSION['session_timeout'] = time() + 60; // Extend 1 minute
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
        // Clear remember me cookie
        if (isset($_COOKIE['smartrent_remember'])) {
            setcookie('smartrent_remember', '', time() - 3600, '/', '', false, true);
        }
        
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

// Require login - redirect if not logged in
if (!function_exists('require_login')) {
    function require_login($redirect_url = null) {
        if (!is_user_logged_in()) {
            $login_url = '../view/login.php';
            
            if ($redirect_url) {
                $login_url .= '?redirect=' . urlencode($redirect_url);
            }
            
            header("Location: $login_url");
            exit();
        }
    }
}

// Check user role/permission
if (!function_exists('require_role')) {
    function require_role($allowed_roles) {
        require_login();
        
        $current_user = get_logged_in_user();
        
        if (!$current_user) {
            header("Location: ../view/login.php");
            exit();
        }
        
        // Convert single role to array
        if (is_string($allowed_roles)) {
            $allowed_roles = array($allowed_roles);
        }
        
        if (!in_array($current_user['user_type'], $allowed_roles)) {
            // Redirect to appropriate dashboard or show access denied
            header("Location: dashboard_controller.php?error=access_denied");
            exit();
        }
    }
}

// Generate session fingerprint for security
if (!function_exists('generate_session_fingerprint')) {
    function generate_session_fingerprint() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $remote_addr = get_client_ip();
        
        // Create fingerprint hash
        $fingerprint_data = $user_agent . $accept_language . $remote_addr;
        return hash('sha256', $fingerprint_data);
    }
}

// Verify session fingerprint
if (!function_exists('verify_session_fingerprint')) {
    function verify_session_fingerprint() {
        if (!isset($_SESSION['user_fingerprint'])) {
            return false;
        }
        
        $current_fingerprint = generate_session_fingerprint();
        return hash_equals($_SESSION['user_fingerprint'], $current_fingerprint);
    }
}

// Get client IP address
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// Generate remember me token
if (!function_exists('generate_remember_token')) {
    function generate_remember_token($user_id) {
        $token = bin2hex(random_bytes(32));
        return hash('sha256', $user_id . $token);
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

// Update user activity tracking
if (!function_exists('update_user_session_activity')) {
    function update_user_session_activity($user_id, $page_visited) {
        update_user_activity($user_id, $page_visited);
    }
}

// Check for session timeout warning
if (!function_exists('get_session_timeout_warning')) {
    function get_session_timeout_warning() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        $timeout = $_SESSION['session_timeout'];
        $current_time = time();
        $time_remaining = $timeout - $current_time;
        
        // Warning if less than 30 seconds remaining (for 1-minute sessions)
        $warning_threshold = isset($_SESSION['stay_logged_in']) && $_SESSION['stay_logged_in'] ? 300 : 30;
        
        if ($time_remaining < $warning_threshold && $time_remaining > 0) {
            return array(
                'warning' => true,
                'minutes_remaining' => ceil($time_remaining / 60),
                'seconds_remaining' => $time_remaining
            );
        }
        
        return array(
            'warning' => false,
            'minutes_remaining' => ceil($time_remaining / 60),
            'seconds_remaining' => $time_remaining
        );
    }
}

// Extend session (for AJAX calls)
if (!function_exists('extend_user_session')) {
    function extend_user_session() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        // Extend timeout based on stay logged in preference
        if (isset($_SESSION['stay_logged_in']) && $_SESSION['stay_logged_in']) {
            $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // 1.5 months
        } else {
            $_SESSION['session_timeout'] = time() + 60; // 1 minute
        }
        
        return true;
    }
}

// Get session info for frontend
if (!function_exists('get_session_info')) {
    function get_session_info() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        $timeout_info = get_session_timeout_warning();
        
        return array(
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'user_type' => $_SESSION['user_type'],
            'full_name' => $_SESSION['full_name'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity'],
            'session_timeout' => $_SESSION['session_timeout'],
            'stay_logged_in' => $_SESSION['stay_logged_in'] ?? false,
            'timeout_warning' => $timeout_info['warning'],
            'minutes_remaining' => $timeout_info['minutes_remaining'],
            'seconds_remaining' => $timeout_info['seconds_remaining']
        );
    }
}

// Check if user has specific permission
if (!function_exists('has_permission')) {
    function has_permission($permission) {
        $current_user = get_logged_in_user();
        
        if (!$current_user) {
            return false;
        }
        
        $user_type = $current_user['user_type'];
        
        // Define permissions for each user type
        $permissions = array(
            'owner' => array(
                'view_all_buildings', 'create_building', 'update_building', 'delete_building',
                'view_all_tenants', 'create_tenant', 'update_tenant', 'delete_tenant',
                'view_all_payments', 'create_payment', 'update_payment', 'delete_payment',
                'view_reports', 'backup_database', 'restore_database',
                'manage_managers', 'send_notices', 'view_logs'
            ),
            'manager' => array(
                'view_assigned_buildings', 'view_building_tenants', 'create_tenant', 'update_tenant',
                'view_building_payments', 'create_payment', 'update_payment',
                'send_tenant_notices', 'handle_service_requests', 'view_building_logs'
            ),
            'tenant' => array(
                'view_own_profile', 'update_own_profile', 'view_own_payments', 'make_payment',
                'create_service_request', 'view_own_service_requests', 'send_messages'
            )
        );
        
        if (!isset($permissions[$user_type])) {
            return false;
        }
        
        return in_array($permission, $permissions[$user_type]);
    }
}

// Prevent session fixation attacks
if (!function_exists('regenerate_session_id')) {
    function regenerate_session_id() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

// Handle AJAX session check
if (isset($_POST['action']) && $_POST['action'] === 'check_session') {
    header('Content-Type: application/json');
    
    $session_info = get_session_info();
    
    if ($session_info) {
        echo json_encode(array(
            'logged_in' => true,
            'session_info' => $session_info
        ));
    } else {
        echo json_encode(array(
            'logged_in' => false,
            'redirect' => '../view/login.php'
        ));
    }
    exit();
}

// Handle session extension
if (isset($_POST['action']) && $_POST['action'] === 'extend_session') {
    header('Content-Type: application/json');
    
    $result = extend_user_session();
    
    echo json_encode(array(
        'success' => $result,
        'session_info' => get_session_info()
    ));
    exit();
}
// Redirect authenticated users away from auth pages
function redirect_if_authenticated() {
    if (is_user_logged_in()) {
        header("Location: ../controller/dashboard_controller.php");
        exit();
    }
}

// Redirect to correct dashboard based on user type
function redirect_to_correct_dashboard($current_user_type) {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}

?>