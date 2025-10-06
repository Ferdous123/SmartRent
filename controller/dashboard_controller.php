<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use centralized authentication
require_once 'auth_header.php';

// Include required models
require_once '../model/database.php';
require_once '../model/user_model.php';

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

// Handle AJAX requests for dashboard data
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_dashboard_stats':
            $stats = get_dashboard_statistics($current_user);
            echo json_encode(array('success' => true, 'stats' => $stats));
            exit();
            
        case 'get_notifications':
            $notifications = get_user_notifications($current_user['user_id']);
            echo json_encode(array('success' => true, 'notifications' => $notifications));
            exit();

        case 'get_buildings_overview':
            $buildings = get_owner_buildings_overview($current_user['user_id']);
            echo json_encode(array('success' => true, 'buildings' => $buildings));
            exit();
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Unknown action'));
            exit();
    }
}

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
    // Get total buildings
    $buildings_query = "SELECT COUNT(*) as total FROM buildings WHERE owner_id = ?";
    $buildings_result = execute_prepared_query($buildings_query, array($owner_id), 'i');
    $total_buildings = $buildings_result ? fetch_single_row($buildings_result)['total'] : 0;
    
    // Get total flats
    $flats_query = "SELECT COUNT(f.flat_id) as total 
                    FROM flats f 
                    JOIN buildings b ON f.building_id = b.building_id 
                    WHERE b.owner_id = ?";
    $flats_result = execute_prepared_query($flats_query, array($owner_id), 'i');
    $total_flats = $flats_result ? fetch_single_row($flats_result)['total'] : 0;
    
    // Get occupied flats
    $occupied_query = "SELECT COUNT(DISTINCT fa.flat_id) as total 
                       FROM flat_assignments fa
                       JOIN flats f ON fa.flat_id = f.flat_id
                       JOIN buildings b ON f.building_id = b.building_id
                       WHERE b.owner_id = ? 
                       AND fa.status = 'confirmed' 
                       AND fa.actual_ended_at IS NULL";
    $occupied_result = execute_prepared_query($occupied_query, array($owner_id), 'i');
    $occupied_flats = $occupied_result ? fetch_single_row($occupied_result)['total'] : 0;
    
    // Get total tenants
    $tenants_query = "SELECT COUNT(DISTINCT fa.tenant_id) as total 
                      FROM flat_assignments fa
                      JOIN flats f ON fa.flat_id = f.flat_id
                      JOIN buildings b ON f.building_id = b.building_id
                      WHERE b.owner_id = ? 
                      AND fa.status = 'confirmed' 
                      AND fa.actual_ended_at IS NULL";
    $tenants_result = execute_prepared_query($tenants_query, array($owner_id), 'i');
    $total_tenants = $tenants_result ? fetch_single_row($tenants_result)['total'] : 0;
    
    // Get monthly revenue (sum of base rents)
    $revenue_query = "SELECT COALESCE(SUM(f.base_rent), 0) as total 
                      FROM flats f
                      JOIN buildings b ON f.building_id = b.building_id
                      JOIN flat_assignments fa ON f.flat_id = fa.flat_id
                      WHERE b.owner_id = ? 
                      AND fa.status = 'confirmed' 
                      AND fa.actual_ended_at IS NULL";
    $revenue_result = execute_prepared_query($revenue_query, array($owner_id), 'i');
    $monthly_revenue = $revenue_result ? fetch_single_row($revenue_result)['total'] : 0;
    
    // Calculate occupancy rate
    $occupancy_rate = 0;
    if ($total_flats > 0) {
        $occupancy_rate = round(($occupied_flats / $total_flats) * 100, 1);
    }
    
    return array(
        'total_buildings' => $total_buildings,
        'total_flats' => $total_flats,
        'occupied_flats' => $occupied_flats,
        'available_flats' => $total_flats - $occupied_flats,
        'total_tenants' => $total_tenants,
        'monthly_revenue' => number_format($monthly_revenue, 2),
        'occupancy_rate' => $occupancy_rate
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

// Get building overview for dashboard
function get_owner_buildings_overview($owner_id, $limit = 5) {
    $query = "SELECT b.building_id, b.building_name, b.address,
              COUNT(f.flat_id) as total_flats,
              COUNT(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN 1 END) as occupied_flats
              FROM buildings b
              LEFT JOIN flats f ON b.building_id = f.building_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id
              WHERE b.owner_id = ?
              GROUP BY b.building_id
              ORDER BY b.created_at DESC
              LIMIT ?";
    
    $result = execute_prepared_query($query, array($owner_id, $limit), 'ii');
    return $result ? fetch_all_rows($result) : array();
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