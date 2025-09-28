<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../model/database.php';
require_once '../model/user_model.php';

// SIMPLE session check - no complex validation that causes loops
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../view/login.php?error=session_required");
    exit();
}

// Get user data directly from session (no function calls that might fail)
$current_user = array(
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Unknown',
    'user_type' => $_SESSION['user_type'] ?? 'tenant',
    'full_name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['email'] ?? '',
    'profile_picture_url' => $_SESSION['profile_picture_url'] ?? ''
);

// Validate user type
$valid_user_types = ['owner', 'manager', 'tenant'];
if (!in_array($current_user['user_type'], $valid_user_types)) {
    session_destroy();
    header("Location: ../view/login.php?error=invalid_user_type");
    exit();
}

// Update session timeout (simple approach)
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['session_timeout'])) {
    $_SESSION['session_timeout'] = time() + 60; // 1 hour default
}

// Dashboard access control - redirect if accessing wrong dashboard
$requested_dashboard = basename($_SERVER['PHP_SELF']);
$user_dashboard_map = array(
    'owner' => 'dashboard_owner.php',
    'manager' => 'dashboard_manager.php', 
    'tenant' => 'dashboard_tenant.php'
);

// Check if user is trying to access wrong dashboard directly
if (isset($user_dashboard_map[$current_user['user_type']])) {
    $correct_dashboard = $user_dashboard_map[$current_user['user_type']];
    if ($requested_dashboard !== 'dashboard_controller.php' && $requested_dashboard !== $correct_dashboard) {
        header("Location: dashboard_controller.php");
        exit();
    }
}

// Check if session expired
if (time() > $_SESSION['session_timeout']) {
    session_destroy();
    header("Location: ../view/login.php?error=session_expired");
    exit();
}

// Extend session
$_SESSION['session_timeout'] = time() + 3600; // Extend 1 hour

// Handle AJAX requests for dashboard data
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_dashboard_stats':
            $stats = get_dashboard_statistics($current_user);
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit();
            
        case 'get_notifications':
            $notifications = get_user_notifications($current_user['user_id']);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            exit();
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit();
    }
}

// Get user preferences (with defaults)
$user_preferences = array(
    'theme_mode' => 'light',
    'language_code' => 'en',
    'nav_color' => '#667eea',
    'primary_bg_color' => '#ffffff',
    'secondary_bg_color' => '#f5f7fa',
    'font_size' => 'medium'
);

// Get notifications for the user
$notifications = array(); // Will be loaded via AJAX

// Set dashboard access flag for profile security
$_SESSION['dashboard_access_verified'] = true;

// Handle profile redirect
if (isset($_GET['redirect']) && $_GET['redirect'] === 'profile') {
    header("Location: profile_controller.php");
    exit();
}

// Get dashboard statistics based on user type
function get_dashboard_statistics($current_user) {
    $user_id = $current_user['user_id'];
    $user_type = $current_user['user_type'];
    
    switch ($user_type) {
        case 'manager':
            return get_manager_statistics($user_id);
        case 'owner':
            return get_owner_statistics($user_id);
        case 'tenant':
            return get_tenant_statistics($user_id);
        default:
            return array();
    }
}

function get_manager_statistics($manager_id) {
    // For now, return demo data since we don't have the full database structure
    return array(
        'managed_buildings' => 2,
        'total_flats' => 25,
        'total_tenants' => 20,
        'pending_service_requests' => 3
    );
}

function get_owner_statistics($owner_id) {
    return array(
        'total_buildings' => 5,
        'total_flats' => 60,
        'total_tenants' => 52,
        'monthly_revenue' => '245,000',
        'occupancy_rate' => 86.7
    );
}

function get_tenant_statistics($tenant_id) {
    return array(
        'has_assignment' => true,
        'flat_info' => 'Green Valley - 2A',
        'outstanding_dues' => '0.00',
        'last_payment_date' => 'Oct 15, 2024',
        'active_service_requests' => 1
    );
}

function get_user_notifications($user_id) {
    // Demo notifications
    return array(
        array(
            'id' => 1,
            'type' => 'payment',
            'title' => 'Payment Received',
            'message' => 'Tenant Rahman Ahmed paid rent for October',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ),
        array(
            'id' => 2,
            'type' => 'maintenance',
            'title' => 'New Service Request',
            'message' => 'Plumbing issue reported in Building A - Flat 2C',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        )
    );
}

// Route to appropriate dashboard based on user type
switch ($current_user['user_type']) {
    case 'owner':
        include '../view/dashboard_owner.php';
        break;
    case 'manager':
        include '../view/dashboard_manager.php';
        break;
    case 'tenant':
        include '../view/dashboard_tenant.php';
        break;
    default:
        session_destroy();
        header("Location: ../view/login.php?error=invalid_user_type");
        exit();
}
?>