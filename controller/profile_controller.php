<?php
ob_start();

// Clean any previous output
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once 'auth_header.php';
require_once '../model/user_model.php';
require_once '../model/database.php';
require_once '../model/user_model.php';
require_once 'session_controller.php';

// Simple session check
if (!is_user_logged_in()) {
    header("Location: ../view/login.php");
    exit();
}

$current_user = get_logged_in_user();

// Handle AJAX actions first - STOP ALL OTHER OUTPUT
if (isset($_POST['action'])) {
    // Clean any output and set headers
    if (ob_get_contents()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    $action = sanitize_input($_POST['action']);
    
    switch ($action) {
        case 'update_profile':
            handle_update_profile();
            break;
        case 'change_password':
            handle_change_password();
            break;
        case 'upload_picture':
            handle_upload_picture();
            break;
        case 'get_2fa_status':
            handle_get_2fa_status();
            break;
        case 'setup_2fa':
            handle_setup_2fa();
            break;
        case 'verify_2fa_setup':
            handle_verify_2fa_setup();
            break;
        case 'disable_2fa':
            handle_disable_2fa();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit();
    }
    exit();
}

// Only include the view if NOT an AJAX request
$user_profile = get_user_by_id($current_user['user_id']);
$user_preferences = get_user_preferences($current_user['user_id']);

// Get actual 2FA status from database
$twofa_query = "SELECT is_enabled, created_at FROM user_authenticator WHERE user_id = ?";
$twofa_result = execute_prepared_query($twofa_query, array($current_user['user_id']), 'i');

if ($twofa_result && mysqli_num_rows($twofa_result) > 0) {
    $twofa_row = fetch_single_row($twofa_result);
    $twofa_status = array(
        'is_enabled' => $twofa_row['is_enabled'] ? true : false,
        'created_at' => $twofa_row['created_at']
    );
} else {
    $twofa_status = array('is_enabled' => false, 'created_at' => null);
}

include '../view/profile.php';

// Handler functions
function handle_update_profile() {
    global $current_user;
    
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $nid_number = sanitize_input($_POST['nid_number'] ?? '');
    $permanent_address = sanitize_input($_POST['permanent_address'] ?? '');
    $phone_number = sanitize_input($_POST['phone_number'] ?? '');
    
    if (empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required']);
        return;
    }
    
    $profile_result = update_user_profile($current_user['user_id'], $full_name, $nid_number, $permanent_address);
    
    if (!empty($phone_number)) {
        update_user_contact($current_user['user_id'], $phone_number);
    }
    
    if ($profile_result) {
        // Update session data to reflect the changes
        $_SESSION['user']['full_name'] = $full_name;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}


    function handle_change_password() {
    // Clear any output buffer
    if (ob_get_length()) ob_clean();

    global $current_user;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }
    
    $result = change_user_password($current_user['user_id'], $current_password, $new_password);
    echo json_encode($result);
}

function handle_upload_picture() {
    global $current_user;
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, and GIF files allowed']);
        return;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 2MB)']);
        return;
    }
    
    // Get current profile picture to delete it later
    $current_user_data = get_user_by_id($current_user['user_id']);
    $old_picture_url = $current_user_data['profile_picture_url'] ?? null;
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_hash = uniqid('profile_', true) . '_' . bin2hex(random_bytes(8));
    $filename = $current_user['user_id'] . '_' . $unique_hash . '.' . $extension;
    $upload_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $picture_url = 'uploads/profiles/' . $filename;
        
        if (update_profile_picture($current_user['user_id'], $picture_url)) {
            // Delete old profile picture if it exists
            if ($old_picture_url && file_exists('../' . $old_picture_url)) {
                unlink('../' . $old_picture_url);
            }
            
            echo json_encode(['success' => true, 'message' => 'Picture updated', 'picture_url' => $picture_url]);
        } else {
            // If database update failed, delete the newly uploaded file
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
}

// Get 2FA status - simple function
function handle_get_2fa_status() {
    global $current_user;
    
    $user_id = $current_user['user_id'];
    
    // Simple query to check 2FA
    $query = "SELECT is_enabled, created_at FROM user_authenticator WHERE user_id = ?";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = fetch_single_row($result);
        $is_enabled = $row['is_enabled'] ? true : false;
        $created_at = $row['created_at'];
    } else {
        $is_enabled = false;
        $created_at = null;
    }
    
    echo json_encode(array(
        'success' => true, 
        'status' => array(
            'is_enabled' => $is_enabled,
            'created_at' => $created_at
        )
    ));
}

// Setup 2FA - simple function
function handle_setup_2fa() {
    global $current_user;
    
    // Include the Google Authenticator file
    require_once '../lib/PHPGangsta_GoogleAuthenticator.php';
    
    // Create new authenticator
    $ga = new PHPGangsta_GoogleAuthenticator();
    
    // Generate secret key
    $secret = $ga->createSecret();
    
    // Get user name instead of email
    $user_name = $current_user['full_name'];

    // Generate QR code URL with name
    $qr_url = $ga->getQRCodeGoogleUrl($user_name, $secret, 'SmartRent', 200);
    
    // Generate 10 backup codes
    $backup_codes = array();
    for ($i = 0; $i < 10; $i++) {
        $random_bytes = random_bytes(10);
        $code = strtoupper(substr(md5($random_bytes), 0, 8));
        $backup_codes[] = $code;
    }
    
    // Save in session temporarily
    $_SESSION['temp_2fa_secret'] = $secret;
    $_SESSION['temp_2fa_backup_codes'] = $backup_codes;
    
    // Send response
    echo json_encode(array(
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qr_url,
        'backup_codes' => $backup_codes
    ));
}

// Verify 2FA setup - simple function
function handle_verify_2fa_setup() {
    global $current_user;
    
    // Get verification code from POST
    $verification_code = isset($_POST['verification_code']) ? $_POST['verification_code'] : '';
    
    // Check if code is provided
    if (empty($verification_code)) {
        echo json_encode(array('success' => false, 'message' => 'Verification code required'));
        return;
    }
    
    // Check if session has secret
    if (!isset($_SESSION['temp_2fa_secret'])) {
        echo json_encode(array('success' => false, 'message' => 'Setup session expired. Please start again.'));
        return;
    }
    
    // Include Google Authenticator
    require_once '../lib/PHPGangsta_GoogleAuthenticator.php';
    
    // Create authenticator
    $ga = new PHPGangsta_GoogleAuthenticator();
    
    // Get secret from session
    $secret = $_SESSION['temp_2fa_secret'];
    
    // Verify the code (with 2 time slots tolerance)
    $is_valid = $ga->verifyCode($secret, $verification_code, 2);
    
    if ($is_valid) {
        // Get backup codes from session
        $backup_codes = $_SESSION['temp_2fa_backup_codes'];
        
        // Convert backup codes array to JSON
        $backup_codes_json = json_encode($backup_codes);
        
        // Generate QR URL again
        $qr_url = $ga->getQRCodeGoogleUrl($current_user['email'], $secret, 'SmartRent', 200);
        
        // Get user ID
        $user_id = $current_user['user_id'];
        
        // Check if record exists
        $check_query = "SELECT auth_id FROM user_authenticator WHERE user_id = ?";
        $check_result = execute_prepared_query($check_query, array($user_id), 'i');
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $update_query = "UPDATE user_authenticator SET secret_key = ?, qr_code_url = ?, backup_codes = ?, is_enabled = 1 WHERE user_id = ?";
            $result = execute_prepared_query($update_query, array($secret, $qr_url, $backup_codes_json, $user_id), 'sssi');
        } else {
            // Insert new record
            $insert_query = "INSERT INTO user_authenticator (user_id, secret_key, qr_code_url, backup_codes, is_enabled) VALUES (?, ?, ?, ?, 1)";
            $result = execute_prepared_query($insert_query, array($user_id, $secret, $qr_url, $backup_codes_json), 'isss');
        }
        
        if ($result) {
            // Clear session data
            unset($_SESSION['temp_2fa_secret']);
            unset($_SESSION['temp_2fa_backup_codes']);
            
            // Log activity
            log_user_activity($user_id, 'create', 'user_authenticator', $user_id, null, array('action' => '2fa_enabled'));
            
            echo json_encode(array('success' => true, 'message' => '2FA enabled successfully'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Failed to save 2FA settings'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invalid verification code. Please try again.'));
    }
}

// Disable 2FA - simple function
function handle_disable_2fa() {
    global $current_user;
    
    // Get verification code or backup code
    $verification_code = isset($_POST['verification_code']) ? trim($_POST['verification_code']) : '';
    
    if (empty($verification_code)) {
        echo json_encode(array('success' => false, 'message' => 'Verification code or backup code required'));
        return;
    }
    
    // Get user ID
    $user_id = $current_user['user_id'];
    
    // Get current 2FA settings from database
    $query = "SELECT secret_key, is_enabled, backup_codes FROM user_authenticator WHERE user_id = ?";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(array('success' => false, 'message' => '2FA is not enabled'));
        return;
    }
    
    $row = fetch_single_row($result);
    $secret_key = $row['secret_key'];
    $is_enabled = $row['is_enabled'];
    $backup_codes_json = $row['backup_codes'];
    
    if (!$is_enabled) {
        echo json_encode(array('success' => false, 'message' => '2FA is not enabled'));
        return;
    }
    
    $is_valid = false;
    
    // Check if it's a 6-digit code (2FA) or 8-character code (backup)
    if (strlen($verification_code) == 6 && ctype_digit($verification_code)) {
        // This is a 2FA code
        require_once '../lib/PHPGangsta_GoogleAuthenticator.php';
        $ga = new PHPGangsta_GoogleAuthenticator();
        $is_valid = $ga->verifyCode($secret_key, $verification_code, 4);
        
    } else if (strlen($verification_code) == 8) {
        // This might be a backup code
        $backup_codes = json_decode($backup_codes_json, true);
        
        if (is_array($backup_codes)) {
            // Check if entered code matches any backup code
            $verification_code_upper = strtoupper($verification_code);
            foreach ($backup_codes as $backup_code) {
                if (strtoupper($backup_code) === $verification_code_upper) {
                    $is_valid = true;
                    break;
                }
            }
        }
    }
    
    if ($is_valid) {
        // DELETE all 2FA data completely
        $delete_query = "DELETE FROM user_authenticator WHERE user_id = ?";
        $delete_result = execute_prepared_query($delete_query, array($user_id), 'i');
        
        if ($delete_result) {
            // Log activity
            log_user_activity($user_id, 'delete', 'user_authenticator', $user_id, null, array('action' => '2fa_deleted'));
            
            echo json_encode(array('success' => true, 'message' => '2FA disabled successfully'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Failed to disable 2FA'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invalid verification code or backup code'));
    }
}
?>