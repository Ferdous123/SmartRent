<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clean any previous output
if (ob_get_contents()) ob_clean();

session_start();
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
    // EXIT HERE - Don't include the view for AJAX requests
    exit();
}

// Only include the view if NOT an AJAX request
$user_profile = get_user_by_id($current_user['user_id']);
$user_preferences = get_user_preferences($current_user['user_id']);
$twofa_status = array('is_enabled' => false, 'created_at' => null);

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
    
    // Generate unique filename using uniqid() + random bytes for maximum uniqueness
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

// Placeholder 2FA handlers (implement these based on your 2FA system)
function handle_get_2fa_status() {
    global $current_user;
    // Replace this with actual 2FA status check
    echo json_encode([
        'success' => true, 
        'status' => ['is_enabled' => false, 'created_at' => null]
    ]);
}

function handle_setup_2fa() {
    // Implement 2FA setup logic
    echo json_encode(['success' => false, 'message' => '2FA setup not implemented yet']);
}

function handle_verify_2fa_setup() {
    // Implement 2FA verification logic
    echo json_encode(['success' => false, 'message' => '2FA verification not implemented yet']);
}

function handle_disable_2fa() {
    // Implement 2FA disable logic
    echo json_encode(['success' => false, 'message' => '2FA disable not implemented yet']);
}
?>