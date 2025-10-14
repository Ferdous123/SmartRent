<?php

require_once 'auth_header.php';
require_once '../model/database.php';
require_once '../model/tenant_model.php';
require_once '../model/property_model.php';

header('Content-Type: application/json');

$user_id = $current_user['user_id'];
$user_type = $current_user['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_tenants':
            handle_get_tenants($user_id, $user_type);
            break;
            
        case 'get_pending_assignments':
            handle_get_pending_assignments($user_id, $user_type);
            break;
            
        case 'get_outstanding_tenants':
            handle_get_outstanding_tenants($user_id, $user_type);
            break;
            
        case 'get_tenant_details':
            handle_get_tenant_details($user_id, $user_type);
            break;
            
        case 'generate_otp':
            handle_generate_otp($user_id, $user_type);
            break;
            
        case 'assign_tenant_direct':
            handle_assign_tenant_direct($user_id, $user_type);
            break;
            
        case 'generate_credentials':
            handle_generate_credentials($user_id, $user_type);
            break;
            
        case 'move_tenant':
            handle_move_tenant($user_id, $user_type);
            break;
            
        case 'get_buildings':
            handle_get_buildings($user_id, $user_type);
            break;
        case 'send_end_notice':
            handle_send_end_notice($user_id, $user_type);
            break;
            
        case 'cancel_end_notice':
            handle_cancel_end_notice($user_id, $user_type);
            break;
            
        case 'process_end_tenancy':
            handle_process_end_tenancy($user_id, $user_type);
            break;
            
        case 'update_tenant_profile':
            handle_update_tenant_profile($user_id, $user_type);
            break;
            
        case 'get_available_flats':
            handle_get_available_flats($user_id, $user_type);
            break;
            
        case 'search_tenants':
            handle_search_tenants($user_id, $user_type);
            break;

        case 'search_all_tenants':
            handle_search_all_tenants($user_id, $user_type);
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            break;
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
}
exit();


function handle_get_tenants($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $tenants = get_all_tenants($user_id, $user_type);
    echo json_encode(array('success' => true, 'tenants' => $tenants));
    exit();
}


function handle_get_pending_assignments($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $assignments = get_pending_assignments($user_id, $user_type);
    echo json_encode(array('success' => true, 'assignments' => $assignments));
    exit();
}


function handle_get_outstanding_tenants($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $tenants = get_tenants_with_outstanding($user_id, $user_type);
    echo json_encode(array('success' => true, 'tenants' => $tenants));
    exit();
}


function handle_get_tenant_details($user_id, $user_type) {
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    
    $tenant = get_tenant_details($tenant_id, $user_id, $user_type);
    
    if ($tenant) {
        echo json_encode(array('success' => true, 'tenant' => $tenant));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Tenant not found'));
    }
    exit();
}


function handle_generate_otp($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Only owners and managers can generate OTP'));
        exit();
    }
    
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $advance_amount = isset($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
    
    if ($advance_amount <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Advance amount must be greater than zero'));
        exit();
    }
    
    $result = generate_flat_otp($flat_id, $advance_amount, $user_id);
    echo json_encode($result);
    exit();
}


function handle_assign_tenant_direct($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Only owners and managers can assign tenants'));
        exit();
    }
    
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $advance_amount = isset($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
    $auto_confirm = isset($_POST['auto_confirm']) && $_POST['auto_confirm'] === 'true'; // NEW
    
    if ($advance_amount <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Advance amount must be greater than zero'));
        exit();
    }
    
    $result = assign_tenant_direct($flat_id, $tenant_id, $advance_amount, $user_id, $auto_confirm); // UPDATED
    echo json_encode($result);
    exit();
}


function handle_generate_credentials($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Only owners and managers can generate credentials'));
        exit();
    }
    
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $advance_amount = isset($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
    
    if ($advance_amount <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Advance amount must be greater than zero'));
        exit();
    }
    
    $result = generate_tenant_credentials($flat_id, $advance_amount, $user_id);
    echo json_encode($result);
    exit();
}


function handle_move_tenant($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $new_flat_id = isset($_POST['new_flat_id']) ? intval($_POST['new_flat_id']) : 0;
    $transfer_advance = isset($_POST['transfer_advance']) ? floatval($_POST['transfer_advance']) : 0;
    $additional_advance = isset($_POST['additional_advance']) ? floatval($_POST['additional_advance']) : 0;
    
    $result = move_tenant_to_flat($assignment_id, $new_flat_id, $transfer_advance, $additional_advance, $user_id);
    echo json_encode($result);
    exit();
}


function handle_send_end_notice($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    
    $result = send_end_tenancy_notice($assignment_id, $user_id);
    echo json_encode($result);
    exit();
}


function handle_cancel_end_notice($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    
    $result = cancel_end_notice($assignment_id, $user_id);
    echo json_encode($result);
    exit();
}


function handle_process_end_tenancy($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    
    $result = process_end_tenancy($assignment_id, $user_id);
    echo json_encode($result);
    exit();
}


function handle_update_tenant_profile($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $nid_number = isset($_POST['nid_number']) ? trim($_POST['nid_number']) : '';
    $permanent_address = isset($_POST['permanent_address']) ? trim($_POST['permanent_address']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    
    if (empty($full_name)) {
        echo json_encode(array('success' => false, 'message' => 'Full name is required'));
        exit();
    }
    
    $result = update_tenant_profile($tenant_id, $full_name, $nid_number, $permanent_address, $contact_number, $user_id);
    echo json_encode($result);
    exit();
}


function handle_search_all_tenants($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';
    
    if (empty($search_term)) {
        echo json_encode(array('success' => true, 'tenants' => array()));
        exit();
    }
    
    $search_param = '%' . $search_term . '%';
    

    $query = "SELECT DISTINCT u.user_id, u.username, u.email, up.full_name
              FROM users u
              JOIN user_profiles up ON u.user_id = up.user_id
              WHERE u.user_type = 'tenant' 
              AND u.is_active = 1
              AND (up.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)
              ORDER BY up.full_name 
              LIMIT 20";
    
    $result = execute_prepared_query($query, array($search_param, $search_param, $search_param), 'sss');
    
    $tenants = $result ? fetch_all_rows($result) : array();
    echo json_encode(array('success' => true, 'tenants' => $tenants));
    exit();
}


function handle_get_available_flats($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if ($user_type === 'owner') {
        $query = "SELECT f.flat_id, f.flat_number, f.floor_number, b.building_name, b.building_id
                  FROM flats f
                  JOIN buildings b ON f.building_id = b.building_id
                  WHERE f.status = 'available' AND b.owner_id = ?
                  ORDER BY b.building_name, f.floor_number, f.flat_number";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        $query = "SELECT f.flat_id, f.flat_number, f.floor_number, b.building_name, b.building_id
                  FROM flats f
                  JOIN buildings b ON f.building_id = b.building_id
                  JOIN building_managers bm ON b.building_id = bm.building_id
                  WHERE f.status = 'available' AND bm.manager_id = ? AND bm.is_active = 1
                  ORDER BY b.building_name, f.floor_number, f.flat_number";
        $result = execute_prepared_query($query, array($user_id), 'i');
    }
    
    $flats = $result ? fetch_all_rows($result) : array();
    echo json_encode(array('success' => true, 'flats' => $flats));
    exit();
}


function handle_search_tenants($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';
    
    if (empty($search_term)) {
        echo json_encode(array('success' => true, 'tenants' => array()));
        exit();
    }
    
    $search_param = '%' . $search_term . '%';
    
    $base_query = "SELECT DISTINCT u.user_id, u.username, u.email, up.full_name
                   FROM users u
                   JOIN user_profiles up ON u.user_id = up.user_id
                   WHERE u.user_type = 'tenant' 
                   AND (up.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    
    if ($user_type === 'owner') {
        $query = $base_query . " AND EXISTS (
                  SELECT 1 FROM flat_assignments fa
                  JOIN flats f ON fa.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  WHERE fa.tenant_id = u.user_id AND b.owner_id = ?
                 ) ORDER BY up.full_name LIMIT 20";
        $result = execute_prepared_query($query, array($search_param, $search_param, $search_param, $user_id), 'sssi');
    } else {
        $query = $base_query . " AND EXISTS (
                  SELECT 1 FROM flat_assignments fa
                  JOIN flats f ON fa.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  JOIN building_managers bm ON b.building_id = bm.building_id
                  WHERE fa.tenant_id = u.user_id AND bm.manager_id = ? AND bm.is_active = 1
                 ) ORDER BY up.full_name LIMIT 20";
        $result = execute_prepared_query($query, array($search_param, $search_param, $search_param, $user_id), 'sssi');
    }
    
    $tenants = $result ? fetch_all_rows($result) : array();
    echo json_encode(array('success' => true, 'tenants' => $tenants));
    exit();
}


function handle_get_buildings($user_id, $user_type) {
    if (!in_array($user_type, array('owner', 'manager'))) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if ($user_type === 'owner') {
        $query = "SELECT DISTINCT building_id, building_name 
                  FROM buildings 
                  WHERE owner_id = ? 
                  ORDER BY building_name";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        $query = "SELECT DISTINCT b.building_id, b.building_name 
                  FROM buildings b
                  JOIN building_managers bm ON b.building_id = bm.building_id
                  WHERE bm.manager_id = ? AND bm.is_active = 1
                  ORDER BY b.building_name";
        $result = execute_prepared_query($query, array($user_id), 'i');
    }
    
    $buildings = $result ? fetch_all_rows($result) : array();
    echo json_encode(array('success' => true, 'buildings' => $buildings));
    exit();
}
?>