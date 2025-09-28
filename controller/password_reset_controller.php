<?php
// Password Reset Controller with 2FA Support for SmartRent
// Handles multi-step password reset process
session_start();

require_once '../model/database.php';
require_once '../model/user_model.php';
require_once '../model/twofa_model.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

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
    case 'identify_user':
        handle_user_identification();
        break;
        
    case 'verify_2fa_reset':
        handle_2fa_verification();
        break;
        
    case 'verify_backup_reset':
        handle_backup_code_verification();
        break;
        
    case 'complete_password_reset':
        handle_password_reset_completion();
        break;
        
    default:
        $response = array('success' => false, 'message' => 'Unknown action');
}

// Return JSON response
echo json_encode($response);
exit();

// Handle user identification step
function handle_user_identification() {
    global $response;
    
    $username_email = isset($_POST['username_email']) ? sanitize_input($_POST['username_email']) : '';
    
    if (empty($username_email)) {
        $response = array('success' => false, 'message' => 'Username or email is required');
        return;
    }
    
    // Find user by username or email
    $query = "SELECT u.user_id, u.username, u.email, u.is_active,
                     ua.is_enabled as has_2fa_enabled
              FROM users u
              LEFT JOIN user_authenticator ua ON u.user_id = ua.user_id
              WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
    
    $result = execute_prepared_query($query, array($username_email, $username_email), 'ss');
    
    if (!$result || mysqli_num_rows($result) == 0) {
        // Don't reveal if user exists or not for security
        $response = array(
            'success' => false,
            'message' => 'If this account exists, you will receive further instructions.'
        );
        return;
    }
    
    $user_data = fetch_single_row($result);
    $user_id = $user_data['user_id'];
    $has_2fa = $user_data['has_2fa_enabled'] == 1;
    
    // Create reset token
    $token_result = create_password_reset_token($user_id, 'email');
    
    if (!$token_result['success']) {
        $response = array('success' => false, 'message' => 'Failed to create reset token');
        return;
    }
    
    $reset_token = $token_result['token'];
    
    // Log password reset attempt
    log_user_activity($user_id, 'create', 'password_reset_tokens', null, null,
                     array('action' => 'password_reset_initiated', 'has_2fa' => $has_2fa));
    
    if ($has_2fa) {
        $response = array(
            'success' => true,
            'message' => 'Account verified. Please complete 2FA verification.',
            'requires_2fa' => true,
            'reset_token' => $reset_token
        );
    } else {
        $response = array(
            'success' => true,
            'message' => 'Account verified. You can now set a new password.',
            'requires_2fa' => false,
            'reset_token' => $reset_token
        );
    }
}

// Handle 2FA code verification for password reset
function handle_2fa_verification() {
    global $response;
    
    $reset_token = isset($_POST['reset_token']) ? sanitize_input($_POST['reset_token']) : '';
    $twofa_code = isset($_POST['twofa_code']) ? sanitize_input($_POST['twofa_code']) : '';
    
    if (empty($reset_token) || empty($twofa_code)) {
        $response = array('success' => false, 'message' => 'Reset token and 2FA code are required');
        return;
    }
    
    // Validate 2FA code format
    if (!preg_match('/^[0-9]{6}$/', $twofa_code)) {
        $response = array('success' => false, 'message' => 'Invalid 2FA code format');
        return;
    }
    
    // Verify reset token
    $token_result = verify_password_reset_token($reset_token);
    if (!$token_result['success']) {
        $response = array('success' => false, 'message' => 'Invalid or expired reset token');
        return;
    }
    
    $token_data = $token_result['token_data'];
    $user_id = $token_data['user_id'];
    
    // Check rate limiting
    if (!check_password_reset_rate_limit($user_id)) {
        $response = array(
            'success' => false,
            'message' => 'Too many attempts. Please try again in 15 minutes.'
        );
        return;
    }
    
    // Verify 2FA code
    $verify_result = verify_2fa_code($user_id, $twofa_code);
    
    if ($verify_result['success']) {
        // Create new token for password reset step
        $new_token_result = create_password_reset_token($user_id, '2fa');
        
        if ($new_token_result['success']) {
            // Mark original token as used
            mark_reset_token_used($reset_token);
            
            // Log successful 2FA verification
            log_user_activity($user_id, 'verify_2fa', 'password_reset', null, null,
                             array('action' => '2fa_verified_for_password_reset'));
            
            $response = array(
                'success' => true,
                'message' => '2FA verified successfully',
                'reset_token' => $new_token_result['token']
            );
        } else {
            $response = array('success' => false, 'message' => 'Failed to create verification token');
        }
    } else {
        // Log failed attempt
        log_user_activity($user_id, 'verify_2fa', 'password_reset', null, null,
                         array('action' => '2fa_verification_failed', 'reason' => 'invalid_code'));
        
        $response = array('success' => false, 'message' => $verify_result['message']);
    }
}

// Handle backup code verification for password reset
function handle_backup_code_verification() {
    global $response;
    
    $reset_token = isset($_POST['reset_token']) ? sanitize_input($_POST['reset_token']) : '';
    $backup_code = isset($_POST['backup_code']) ? strtoupper(sanitize_input($_POST['backup_code'])) : '';
    
    if (empty($reset_token) || empty($backup_code)) {
        $response = array('success' => false, 'message' => 'Reset token and backup code are required');
        return;
    }
    
    // Validate backup code format
    if (!preg_match('/^[A-Z0-9]{8}$/', $backup_code)) {
        $response = array('success' => false, 'message' => 'Invalid backup code format');
        return;
    }
    
    // Verify reset token
    $token_result = verify_password_reset_token($reset_token);
    if (!$token_result['success']) {
        $response = array('success' => false, 'message' => 'Invalid or expired reset token');
        return;
    }
    
    $token_data = $token_result['token_data'];
    $user_id = $token_data['user_id'];
    
    // Check rate limiting
    if (!check_password_reset_rate_limit($user_id)) {
        $response = array(
            'success' => false,
            'message' => 'Too many attempts. Please try again in 15 minutes.'
        );
        return;
    }
    
    // Verify backup code
    $verify_result = verify_backup_code($user_id, $backup_code, 'password_reset');
    
    if ($verify_result['success']) {
        // Create new token for password reset step
        $new_token_result = create_password_reset_token($user_id, 'backup');
        
        if ($new_token_result['success']) {
            // Mark original token as used
            mark_reset_token_used($reset_token);
            
            // Log successful backup code verification
            log_user_activity($user_id, 'verify_backup_code', 'password_reset', null, null,
                             array('action' => 'backup_code_verified_for_password_reset', '2fa_disabled' => true));
            
            $response = array(
                'success' => true,
                'message' => 'Backup code verified successfully. Note: 2FA has been disabled.',
                'reset_token' => $new_token_result['token'],
                'twofa_disabled' => true
            );
        } else {
            $response = array('success' => false, 'message' => 'Failed to create verification token');
        }
    } else {
        // Log failed attempt
        log_user_activity($user_id, 'verify_backup_code', 'password_reset', null, null,
                         array('action' => 'backup_code_verification_failed', 'reason' => 'invalid_code'));
        
        $response = array('success' => false, 'message' => $verify_result['message']);
    }
}

// Handle password reset completion
function handle_password_reset_completion() {
    global $response;
    
    $reset_token = isset($_POST['reset_token']) ? sanitize_input($_POST['reset_token']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($reset_token) || empty($new_password) || empty($confirm_password)) {
        $response = array('success' => false, 'message' => 'All fields are required');
        return;
    }
    
    // Validate password
    if (strlen($new_password) < 6) {
        $response = array('success' => false, 'message' => 'Password must be at least 6 characters');
        return;
    }
    
    if ($new_password !== $confirm_password) {
        $response = array('success' => false, 'message' => 'Passwords do not match');
        return;
    }
    
    // Verify reset token
    $token_result = verify_password_reset_token($reset_token);
    if (!$token_result['success']) {
        $response = array('success' => false, 'message' => 'Invalid or expired reset token');
        return;
    }
    
    $token_data = $token_result['token_data'];
    
    // Check if token is from 2FA or backup verification step
    if (!in_array($token_data['token_type'], array('2fa', 'backup'))) {
        $response = array('success' => false, 'message' => 'Invalid verification token');
        return;
    }
    
    // Complete password reset
    $reset_result = complete_password_reset($reset_token, $new_password);
    
    if ($reset_result['success']) {
        $twofa_disabled = $token_data['token_type'] === 'backup';
        
        $response = array(
            'success' => true,
            'message' => 'Password reset successfully',
            'twofa_disabled' => $twofa_disabled
        );
    } else {
        $response = array('success' => false, 'message' => $reset_result['message']);
    }
}

// Mark reset token as used
function mark_reset_token_used($reset_token) {
    $query = "UPDATE password_reset_tokens SET is_used = 1, used_at = NOW() WHERE reset_token = ?";
    return execute_prepared_query($query, array($reset_token), 's');
}

// Rate limiting for password reset attempts
function check_password_reset_rate_limit($user_id, $max_attempts = 5, $time_window = 900) {
    $session_key = 'reset_attempts_' . $user_id;
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

// Send password reset email (placeholder function)
function send_password_reset_email($user_email, $user_name, $reset_token) {
    // In production, this would integrate with your email service
    $reset_link = "https://yoursite.com/view/reset_password.php?token=" . $reset_token;
    
    $subject = "Password Reset - SmartRent";
    $message = "Hello $user_name,\n\n";
    $message .= "You requested a password reset for your SmartRent account.\n";
    $message .= "Click the link below to reset your password:\n\n";
    $message .= $reset_link . "\n\n";
    $message .= "This link will expire in 1 hour.\n";
    $message .= "If you didn't request this, please ignore this email.\n\n";
    $message .= "Best regards,\nSmartRent Team";
    
    // Log email attempt (in production, actually send email)
    error_log("Password Reset Email: To: $user_email, Token: $reset_token");
    
    return true;
}

// Clean up expired tokens (call this periodically)
function cleanup_expired_tokens() {
    return cleanup_expired_reset_tokens();
}

// Validate password strength
function validate_password_strength($password) {
    $errors = array();
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return $errors;
}

// Check if password was recently used
function check_password_history($user_id, $new_password, $history_count = 5) {
    // This would check against a password history table in production
    // For now, just ensure it's different from current password
    
    $query = "SELECT password_hash FROM users WHERE user_id = ?";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = fetch_single_row($result);
        if (password_verify($new_password, $user_data['password_hash'])) {
            return false; // Same as current password
        }
    }
    
    return true; // Password is different
}

// Generate secure reset token
function generate_secure_reset_token() {
    return bin2hex(random_bytes(32));
}

// Log password reset events
function log_password_reset_event($user_id, $event_type, $details = null) {
    $log_data = array(
        'event' => $event_type,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    );
    
    if ($details) {
        $log_data = array_merge($log_data, $details);
    }
    
    return log_user_activity($user_id, 'password_reset', 'users', $user_id, null, $log_data);
}

// Get password reset statistics (for admin)
function get_password_reset_stats($days = 30) {
    $query = "SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN token_type = 'email' THEN 1 END) as email_requests,
                COUNT(CASE WHEN token_type = '2fa' THEN 1 END) as twofa_verifications,
                COUNT(CASE WHEN token_type = 'backup' THEN 1 END) as backup_code_uses,
                COUNT(CASE WHEN is_used = 1 THEN 1 END) as completed_resets,
                COUNT(CASE WHEN expires_at < NOW() AND is_used = 0 THEN 1 END) as expired_tokens
              FROM password_reset_tokens 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    
    $result = execute_prepared_query($query, array($days), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $stats = fetch_single_row($result);
        
        // Calculate success rate
        if ($stats['total_requests'] > 0) {
            $stats['success_rate'] = round(($stats['completed_resets'] / $stats['total_requests']) * 100, 1);
        } else {
            $stats['success_rate'] = 0;
        }
        
        return $stats;
    }
    
    return null;
}
?>