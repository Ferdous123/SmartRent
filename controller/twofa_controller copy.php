<?php
// Two-Factor Authentication Controller for SmartRent
// Handles all 2FA operations from profile management
session_start();

require_once '../model/database.php';
require_once '../model/user_model.php';
require_once '../model/twofa_model.php';
require_once 'session_controller.php';

// Ensure user is logged in
require_login();

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Get current user
$current_user = get_logged_in_user();

if (!$current_user) {
    echo json_encode(array('success' => false, 'message' => 'User not authenticated'));
    exit();
}

// Initialize response array
$response = array('success' => false, 'message' => 'Invalid request');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit();
}

// Get the action from POST data
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

// Handle different actions
switch ($action) {
    case 'setup_2fa':
        handle_setup_2fa();
        break;
        
    case 'verify_2fa_setup':
        handle_verify_2fa_setup();
        break;
        
    case 'disable_2fa':
        handle_disable_2fa();
        break;
        
    case 'get_2fa_status':
        handle_get_2fa_status();
        break;
        
    case 'get_backup_codes':
        handle_get_backup_codes();
        break;
        
    case 'regenerate_backup_codes':
        handle_regenerate_backup_codes();
        break;
        
    case 'verify_2fa_code':
        handle_verify_2fa_code();
        break;
        
    default:
        $response = array('success' => false, 'message' => 'Unknown action');
}

// Return JSON response
echo json_encode($response);
exit();

// Handle 2FA setup initiation
function handle_setup_2fa() {
    global $response, $current_user;
    
    $user_id = $current_user['user_id'];
    $user_email = $current_user['email'];
    $user_name = $current_user['full_name'];
    
    // Setup 2FA
    $setup_result = setup_user_2fa($user_id, $user_email, $user_name);
    
    if ($setup_result['success']) {
        $response = array(
            'success' => true,
            'message' => $setup_result['message'],
            'qr_url' => $setup_result['qr_url'],
            'backup_codes' => $setup_result['backup_codes'],
            'secret' => $setup_result['secret'] // For manual entry if QR fails
        );
    } else {
        $response = array(
            'success' => false,
            'message' => $setup_result['message']
        );
    }
}

// Handle 2FA setup verification
function handle_verify_2fa_setup() {
    global $response, $current_user;
    
    $verification_code = isset($_POST['verification_code']) ? sanitize_input($_POST['verification_code']) : '';
    
    if (empty($verification_code)) {
        $response = array('success' => false, 'message' => 'Verification code is required');
        return;
    }
    
    if (!preg_match('/^[0-9]{6}$/', $verification_code)) {
        $response = array('success' => false, 'message' => 'Verification code must be 6 digits');
        return;
    }
    
    // Verify and enable 2FA
    $verify_result = verify_and_enable_2fa($current_user['user_id'], $verification_code);
    
    if ($verify_result['success']) {
        $response = array(
            'success' => true,
            'message' => $verify_result['message'],
            'reload_page' => true // Signal to reload page to update 2FA status
        );
    } else {
        $response = array(
            'success' => false,
            'message' => $verify_result['message']
        );
    }
}

// Handle 2FA disabling
function handle_disable_2fa() {
    global $response, $current_user;
    
    $verification_code = isset($_POST['verification_code']) ? sanitize_input($_POST['verification_code']) : '';
    $confirm_disable = isset($_POST['confirm_disable']) ? true : false;
    
    if (!$confirm_disable) {
        $response = array('success' => false, 'message' => 'You must confirm that you want to disable 2FA');
        return;
    }
    
    if (empty($verification_code)) {
        $response = array('success' => false, 'message' => 'Current 2FA code is required to disable 2FA');
        return;
    }
    
    if (!preg_match('/^[0-9]{6}$/', $verification_code)) {
        $response = array('success' => false, 'message' => '2FA code must be 6 digits');
        return;
    }
    
    // Disable 2FA
    $disable_result = disable_2fa($current_user['user_id'], $verification_code);
    
    if ($disable_result['success']) {
        $response = array(
            'success' => true,
            'message' => $disable_result['message'],
            'reload_page' => true
        );
    } else {
        $response = array(
            'success' => false,
            'message' => $disable_result['message']
        );
    }
}

// Handle getting 2FA status
function handle_get_2fa_status() {
    global $response, $current_user;
    
    $status = get_2fa_status($current_user['user_id']);
    
    $response = array(
        'success' => true,
        'status' => $status
    );
}

// Handle getting backup codes
function handle_get_backup_codes() {
    global $response, $current_user;
    
    // Only allow if 2FA is enabled
    $status = get_2fa_status($current_user['user_id']);
    
    if (!$status['is_enabled']) {
        $response = array('success' => false, 'message' => '2FA is not enabled');
        return;
    }
    
    $backup_codes = get_masked_backup_codes($current_user['user_id']);
    
    $response = array(
        'success' => true,
        'backup_codes' => $backup_codes
    );
}

// Handle regenerating backup codes
function handle_regenerate_backup_codes() {
    global $response, $current_user;
    
    $verification_code = isset($_POST['verification_code']) ? sanitize_input($_POST['verification_code']) : '';
    
    if (empty($verification_code)) {
        $response = array('success' => false, 'message' => '2FA code is required to regenerate backup codes');
        return;
    }
    
    // Verify current 2FA code first
    $verify_result = verify_2fa_code($current_user['user_id'], $verification_code);
    
    if (!$verify_result['success']) {
        $response = array('success' => false, 'message' => 'Invalid 2FA code');
        return;
    }
    
    // Generate new backup codes
    $new_backup_codes = generate_backup_codes();
    $backup_codes_json = json_encode($new_backup_codes);
    
    // Update backup codes in database
    $update_query = "UPDATE user_authenticator 
                     SET backup_codes = ?, backup_codes_generated_at = NOW() 
                     WHERE user_id = ? AND is_enabled = 1";
    
    $update_result = execute_prepared_query($update_query, array($backup_codes_json, $current_user['user_id']), 'si');
    
    if ($update_result) {
        // Log backup code regeneration
        log_user_activity($current_user['user_id'], 'update', 'user_authenticator', $current_user['user_id'], null,
                         array('action' => 'backup_codes_regenerated'));
        
        $response = array(
            'success' => true,
            'message' => 'New backup codes generated successfully',
            'backup_codes' => array_map(function($code) {
                return array('code' => $code, 'used' => false);
            }, $new_backup_codes)
        );
    } else {
        $response = array('success' => false, 'message' => 'Failed to regenerate backup codes');
    }
}

// Handle 2FA code verification (for general use)
function handle_verify_2fa_code() {
    global $response, $current_user;
    
    $verification_code = isset($_POST['verification_code']) ? sanitize_input($_POST['verification_code']) : '';
    $purpose = isset($_POST['purpose']) ? sanitize_input($_POST['purpose']) : 'general';
    
    if (empty($verification_code)) {
        $response = array('success' => false, 'message' => 'Verification code is required');
        return;
    }
    
    // Verify 2FA code
    $verify_result = verify_2fa_code($current_user['user_id'], $verification_code);
    
    if ($verify_result['success']) {
        // Store verification in session for temporary use
        $_SESSION['2fa_verified'] = time();
        $_SESSION['2fa_purpose'] = $purpose;
        
        $response = array(
            'success' => true,
            'message' => $verify_result['message'],
            'verified_for' => $purpose
        );
    } else {
        $response = array(
            'success' => false,
            'message' => $verify_result['message']
        );
    }
}

// Helper function to check if 2FA was recently verified
function is_2fa_recently_verified($purpose = 'general', $valid_for_seconds = 300) {
    return isset($_SESSION['2fa_verified']) && 
           isset($_SESSION['2fa_purpose']) &&
           $_SESSION['2fa_purpose'] === $purpose &&
           (time() - $_SESSION['2fa_verified']) < $valid_for_seconds;
}

// Clear 2FA verification
function clear_2fa_verification() {
    unset($_SESSION['2fa_verified']);
    unset($_SESSION['2fa_purpose']);
}

// Rate limiting for 2FA attempts
function check_2fa_rate_limit($user_id, $max_attempts = 5, $time_window = 300) {
    $session_key = '2fa_attempts_' . $user_id;
    $current_time = time();
    
    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = array();
    }
    
    // Clean old attempts
    $_SESSION[$session_key] = array_filter($_SESSION[$session_key], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Check if limit exceeded
    if (count($_SESSION[$session_key]) >= $max_attempts) {
        return false;
    }
    
    // Add current attempt
    $_SESSION[$session_key][] = $current_time;
    
    return true;
}

// Log 2FA attempt
function log_2fa_attempt($user_id, $action, $success, $method = '2fa_code') {
    $log_data = array(
        'action' => $action,
        'method' => $method,
        'success' => $success,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    log_user_activity($user_id, '2fa_attempt', 'user_authenticator', $user_id, null, $log_data);
}

// Clean up old 2FA rate limiting data
function cleanup_2fa_rate_limiting() {
    $current_time = time();
    $time_window = 300; // 5 minutes
    
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, '2fa_attempts_') === 0 && is_array($value)) {
            $_SESSION[$key] = array_filter($value, function($timestamp) use ($current_time, $time_window) {
                return ($current_time - $timestamp) < $time_window;
            });
            
            // Remove empty arrays
            if (empty($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }
    }
}

// Check if user can perform sensitive actions (requires recent 2FA verification)
function require_recent_2fa_verification($purpose = 'sensitive_action', $valid_for_seconds = 300) {
    if (!is_2fa_recently_verified($purpose, $valid_for_seconds)) {
        return array(
            'success' => false, 
            'message' => 'This action requires 2FA verification',
            'requires_2fa' => true
        );
    }
    
    return array('success' => true);
}

// Handle AJAX requests for getting 2FA setup HTML
if (isset($_GET['get_2fa_html'])) {
    $current_user = get_logged_in_user();
    $status = get_2fa_status($current_user['user_id']);
    
    ob_start();
    include '../view/2fa_setup.php';
    $html = ob_get_clean();
    
    echo json_encode(array(
        'success' => true,
        'html' => $html,
        'status' => $status
    ));
    exit();
}
?>