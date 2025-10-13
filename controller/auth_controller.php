<?php
// Handles login, logout, and registration processes

require_once '../model/database.php';
require_once '../model/user_model.php';
require_once 'session_controller.php';

// Handle GET logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_start();
    session_destroy();
    header("Location: ../view/login.php?success=logged_out");
    exit();
}

// Process login/logout/register actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
    
    switch ($action) {
        case 'login':
            handle_login();
            break;
        case 'logout':
            handle_logout();
            break;
        case 'register':
            handle_registration();
            break;
        default:
            redirect_with_error('../view/login.php', 'Invalid action');
    }
} else {
    // Direct access - redirect to login
    header("Location: ../view/login.php");
    exit();
}

// Handle user login
function handle_login() {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stay_logged_in = isset($_POST['stay_logged_in']) && $_POST['stay_logged_in'];
    $redirect_url = sanitize_input($_POST['redirect_url'] ?? '');
    
    // Validate input
    if (empty($username) || empty($password)) {
        redirect_with_error('../view/login.php', 'Please enter both username and password');
        return;
    }
    
    // Check if user exists and verify password
    $user = authenticate_user($username, $password);
    
    if (!$user) {
        redirect_with_error('../view/login.php', 'Invalid username or password');
        return;
    }
    
    // Check if user account is active
    if (!$user['is_active']) {
        redirect_with_error('../view/login.php', 'Your account has been deactivated. Please contact support.');
        return;
    }
    
    // Create user session with proper timeout settings
    if (create_user_session($user, $stay_logged_in)) {
        // Log successful login
        log_user_activity($user['user_id'], 'login', 'users', $user['user_id'], null, array(
            'username' => $user['username'],
            'login_method' => 'password',
            'stay_logged_in' => $stay_logged_in
        ));
        
        // Determine redirect destination
        $dashboard_url = '../controller/dashboard_controller.php';
        
        if (!empty($redirect_url)) {
            // Validate redirect URL to prevent open redirect attacks
            if (is_safe_redirect_url($redirect_url)) {
                $dashboard_url = $redirect_url;
            }
        }
        
        redirect_with_success($dashboard_url, 'Login successful');
    } else {
        redirect_with_error('../view/login.php', 'Failed to create session. Please try again.');
    }
}

// Handle user logout
function handle_logout() {
    $current_user = get_logged_in_user();
    
    if ($current_user) {
        // Log logout activity
        log_user_activity($current_user['user_id'], 'logout', 'users', $current_user['user_id']);
    }
    
    // Clear session
    clear_user_session();
    
    redirect_with_success('../view/login.php', 'You have been logged out successfully');
}

// Handle user registration
function handle_registration() {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = sanitize_input($_POST['user_type'] ?? 'tenant');
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $phone_number = sanitize_input($_POST['phone_number'] ?? '');
    
    // Validate input
    $validation_errors = validate_registration_input($username, $email, $password, $confirm_password, $full_name);
    
    if (!empty($validation_errors)) {
        $error_message = implode(', ', $validation_errors);
        redirect_with_error('../view/register.php', $error_message);
        return;
    }
    
    // Check if username or email already exists
    if (user_exists($username, $email)) {
        redirect_with_error('../view/register.php', 'Username or email already exists');
        return;
    }
    
    // Register user
    $result = register_user($username, $email, $password, $user_type, $full_name, $phone_number);
    
    if ($result['success']) {
        // Log registration
        log_user_activity($result['user_id'], 'register', 'users', $result['user_id'], null, array(
            'username' => $username,
            'email' => $email,
            'user_type' => $user_type
        ));
        
        redirect_with_success('../view/login.php', 'Registration successful! Please login to continue.');
    } else {
        redirect_with_error('../view/register.php', $result['message']);
    }
}

// Authenticate user with username/email and password
function authenticate_user($username, $password) {
    // Try to find user by username or email
    $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1";
    $result = execute_prepared_query($query, array($username, $username), 'ss');
    
    if (!$result || $result->num_rows === 0) {
        return false;
    }
    
    $user = fetch_single_row($result);
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Remove password hash from returned data
        unset($user['password_hash']);
        return $user;
    }
    
    return false;
}

// Check if user exists by username or email
function user_exists($username, $email) {
    $query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $result = execute_prepared_query($query, array($username, $email), 'ss');
    
    return $result && $result->num_rows > 0;
}


// Validate registration input
function validate_registration_input($username, $email, $password, $confirm_password, $full_name) {
    $errors = array();
    
    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Full name validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($full_name) < 2) {
        $errors[] = 'Full name must be at least 2 characters long';
    }
    
    return $errors;
}

// Check if redirect URL is safe (prevent open redirect attacks)
function is_safe_redirect_url($url) {
    // Only allow relative URLs within the application
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return false; // Absolute URLs not allowed
    }
    
    // Check for common malicious patterns
    $malicious_patterns = array('../', '..\\', 'javascript:', 'data:', 'vbscript:');
    
    foreach ($malicious_patterns as $pattern) {
        if (stripos($url, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

// Redirect with error message
function redirect_with_error($url, $message) {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header("Location: {$url}{$separator}error=" . urlencode($message));
    exit();
}

// Redirect with success message
function redirect_with_success($url, $message) {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header("Location: {$url}{$separator}success=" . urlencode($message));
    exit();
}

// Handle password reset request
function handle_password_reset_request() {
    // Implementation for password reset
    // This would typically generate a reset token and send email
}

// Handle password reset
function handle_password_reset() {
    // Implementation for password reset with token verification
}

// Get user preferences (if not already defined in user_model.php)
if (!function_exists('get_user_preferences')) {
    function get_user_preferences($user_id) {
        $query = "SELECT * FROM user_preferences WHERE user_id = ?";
        $result = execute_prepared_query($query, array($user_id), 'i');
        
        if ($result && $result->num_rows > 0) {
            return fetch_single_row($result);
        }
        
        // Return default preferences
        return array(
            'theme_mode' => 'light',
            'language_code' => 'en',
            'nav_color' => 'blue',
            'primary_bg_color' => 'white',
            'secondary_bg_color' => 'gray',
            'font_size' => 'medium'
        );
    }
}

// Update user preferences (if not already defined in user_model.php)
if (!function_exists('update_user_preferences')) {
    function update_user_preferences($user_id, $preferences) {
        try {
            // Check if preferences exist
            $check_query = "SELECT user_id FROM user_preferences WHERE user_id = ?";
            $check_result = execute_prepared_query($check_query, array($user_id), 'i');
            
            if ($check_result && $check_result->num_rows > 0) {
                // Update existing preferences
                $update_parts = array();
                $params = array();
                $types = '';
                
                foreach ($preferences as $key => $value) {
                    $update_parts[] = "$key = ?";
                    $params[] = $value;
                    $types .= 's';
                }
                
                $params[] = $user_id;
                $types .= 'i';
                
                $query = "UPDATE user_preferences SET " . implode(', ', $update_parts) . " WHERE user_id = ?";
                return execute_prepared_query($query, $params, $types);
            } else {
                // Insert new preferences
                $keys = array_keys($preferences);
                $values = array_values($preferences);
                
                $keys[] = 'user_id';
                $values[] = $user_id;
                
                $placeholders = str_repeat('?,', count($keys) - 1) . '?';
                $types = str_repeat('s', count($preferences)) . 'i';
                
                $query = "INSERT INTO user_preferences (" . implode(', ', $keys) . ") VALUES ($placeholders)";
                return execute_prepared_query($query, $values, $types);
            }
        } catch (Exception $e) {
            error_log("Update preferences error: " . $e->getMessage());
            return false;
        }
    }
}

// already defined in user_model.php
if (!function_exists('get_user_authenticator')) {
    function get_user_authenticator($user_id) {
        $query = "SELECT * FROM user_authenticators WHERE user_id = ?";
        $result = execute_prepared_query($query, array($user_id), 'i');
        
        return $result ? fetch_single_row($result) : null;
    }
}
?>