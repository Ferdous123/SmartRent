<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Handle AJAX actions first
if (isset($_POST['action'])) {
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
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit();
    }
}

// Get user data - use existing function from user_model.php
$user_profile = get_user_by_id($current_user['user_id']);
$user_preferences = get_user_preferences($current_user['user_id']);

// Simple 2FA status for now
$twofa_status = array('is_enabled' => false, 'created_at' => null);

// Include profile view
include '../view/profile.php';

// Handler functions
function handle_update_profile() {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit();
    }
    
    $file = $_FILES['profile_picture'];
    $upload_dir = '../uploads/profiles/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'user_' . $current_user['user_id'] . '.' . $extension;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $db_path = 'uploads/profiles/' . $new_filename;
        $result = update_profile_picture($current_user['user_id'], $db_path);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Photo uploaded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
    }
    exit();
}

function handle_change_password() {
    global $current_user;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }
    
    $result = change_user_password($current_user['user_id'], $current_password, $new_password);
    echo json_encode($result);
    exit();
}

function handle_upload_picture() {
    global $current_user;
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit();
    }
    
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, and GIF files allowed']);
        exit();
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 2MB)']);
        exit();
    }
    
    // define upload directory
    $upload_dir = '../uploads/profiles/';
    
    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $current_user['user_id'] . '_' . time() . '.' . $extension;
    $upload_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $picture_url = 'uploads/profiles/' . $filename;
        
        if (update_profile_picture($current_user['user_id'], $picture_url)) {
            echo json_encode(['success' => true, 'message' => 'Picture updated', 'picture_url' => $picture_url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
    exit();
}
?>