<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth_header.php';
require_once '../model/database.php';
require_once '../model/property_model.php';

header('Content-Type: application/json');

$user_id = $current_user['user_id'];
$user_type = $current_user['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'create_building':
            handle_create_building($user_id, $user_type);
            break;
            
        case 'get_buildings':
            handle_get_buildings($user_id, $user_type);
            break;
            
        case 'update_building':
            handle_update_building($user_id, $user_type);
            break;
            
        case 'delete_building':
            handle_delete_building($user_id, $user_type);
            break;
            
        case 'get_flats':
            handle_get_flats($user_id, $user_type);
            break;
            
        case 'get_flat':
            handle_get_flat($user_id, $user_type);
            break;
            
        case 'add_flat':
            handle_add_flat($user_id, $user_type);
            break;
            
        case 'update_flat':
            handle_update_flat($user_id, $user_type);
            break;
            
        case 'delete_flat':
            handle_delete_flat($user_id, $user_type);
            break;
            
        case 'get_managers':
            handle_get_managers($user_id, $user_type);
            break;
            
        case 'get_available_managers':
            handle_get_available_managers($user_id, $user_type);
            break;
            
        case 'assign_manager':
            handle_assign_manager($user_id, $user_type);
            break;
            
        case 'remove_manager':
            handle_remove_manager($user_id, $user_type);
            break;

        case 'confirm_delete_building':
            handle_confirm_delete_building($user_id, $user_type);
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            break;
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
}
exit();

// Handle building creation (already exists - keep it)
function handle_create_building($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $building_name = isset($_POST['building_name']) ? trim($_POST['building_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $total_floors = isset($_POST['total_floors']) ? intval($_POST['total_floors']) : 0;
    $flats_data_json = isset($_POST['flats_data']) ? $_POST['flats_data'] : '';
    
    if (empty($building_name) || empty($address) || $total_floors < 1) {
        echo json_encode(array('success' => false, 'message' => 'Invalid building data'));
        exit();
    }
    
    $flats_data = json_decode($flats_data_json, true);
    if (!$flats_data || !is_array($flats_data)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid flats data'));
        exit();
    }
    
    $total_flats = count($flats_data);
    
    begin_transaction();
    
    try {
        $building_result = create_building($user_id, $building_name, $address, $total_floors, $total_flats);
        
        if (!$building_result['success']) {
            throw new Exception($building_result['message']);
        }
        
        $building_id = $building_result['building_id'];
        
        $flats_created = 0;
        foreach ($flats_data as $flat) {
            $flat_number = $flat['flat_number'];
            $floor_number = $flat['floor_number'];
            
            $flat_result = create_flat($flat['building_id'], $flat_number, $floor_number, $user_id, null, null, 0.00);
            
            if ($flat_result['success']) {
                $flats_created++;
            }
        }
        
        if ($flats_created !== $total_flats) {
            throw new Exception("Only $flats_created out of $total_flats flats were created");
        }
        
        commit_transaction();
        
        log_user_activity($user_id, 'create', 'buildings', $building_id, null, array(
            'building_name' => $building_name,
            'total_floors' => $total_floors,
            'total_flats' => $total_flats
        ));
        
        echo json_encode(array(
            'success' => true, 
            'message' => 'Building created successfully with ' . $flats_created . ' flats',
            'building_id' => $building_id
        ));
        exit();
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Building creation error: " . $e->getMessage());
        
        echo json_encode(array(
            'success' => false, 
            'message' => 'Failed to create building: ' . $e->getMessage()
        ));
        exit();
    }
}

// Get all buildings
function handle_get_buildings($user_id, $user_type) {
    if ($user_type === 'owner') {
        $buildings = get_buildings_by_owner($user_id);
    } elseif ($user_type === 'manager') {
        $buildings = get_buildings_by_manager($user_id);
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    echo json_encode(array('success' => true, 'buildings' => $buildings));
    exit();
}

// Update building
function handle_update_building($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    $building_name = isset($_POST['building_name']) ? trim($_POST['building_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $total_floors = isset($_POST['total_floors']) ? intval($_POST['total_floors']) : 0;
    
    if (empty($building_name) || empty($address) || $total_floors < 1) {
        echo json_encode(array('success' => false, 'message' => 'Invalid building data'));
        exit();
    }
    
    $result = update_building($building_id, $building_name, $address, $total_floors, $user_id);
    echo json_encode($result);
    exit();
}

// Delete building with detailed check
function handle_delete_building($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can delete buildings'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    // Get detailed building info for warning
    $info = get_building_deletion_info($building_id, $user_id);
    
    if (!$info['success']) {
        echo json_encode($info);
        exit();
    }
    
    echo json_encode($info);
    exit();
}

// Get deletion info
function get_building_deletion_info($building_id, $user_id) {
    // Check ownership
    $ownership_query = "SELECT building_name FROM buildings WHERE building_id = ? AND owner_id = ?";
    $ownership_result = execute_prepared_query($ownership_query, array($building_id, $user_id), 'ii');
    
    if (!$ownership_result || $ownership_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Access denied - only building owner can delete');
    }
    
    $building = fetch_single_row($ownership_result);
    
    // Count flats
    $flats_query = "SELECT COUNT(*) as count FROM flats WHERE building_id = ?";
    $flats_result = execute_prepared_query($flats_query, array($building_id), 'i');
    $flats_count = $flats_result ? fetch_single_row($flats_result)['count'] : 0;
    
    // Count active tenants
    $tenants_query = "SELECT COUNT(DISTINCT fa.tenant_id) as count 
                      FROM flat_assignments fa
                      JOIN flats f ON fa.flat_id = f.flat_id
                      WHERE f.building_id = ? 
                      AND fa.status = 'confirmed' 
                      AND fa.actual_ended_at IS NULL";
    $tenants_result = execute_prepared_query($tenants_query, array($building_id), 'i');
    $tenants_count = $tenants_result ? fetch_single_row($tenants_result)['count'] : 0;
    
    // Count total assignments (including past)
    $assignments_query = "SELECT COUNT(*) as count 
                          FROM flat_assignments fa
                          JOIN flats f ON fa.flat_id = f.flat_id
                          WHERE f.building_id = ?";
    $assignments_result = execute_prepared_query($assignments_query, array($building_id), 'i');
    $assignments_count = $assignments_result ? fetch_single_row($assignments_result)['count'] : 0;
    
    // Count expenses/payments
    $expenses_query = "SELECT COUNT(*) as count 
                       FROM flat_expenses fe
                       JOIN flats f ON fe.flat_id = f.flat_id
                       WHERE f.building_id = ?";
    $expenses_result = execute_prepared_query($expenses_query, array($building_id), 'i');
    $expenses_count = $expenses_result ? fetch_single_row($expenses_result)['count'] : 0;
    
    // Count managers
    $managers_query = "SELECT COUNT(*) as count 
                       FROM building_managers 
                       WHERE building_id = ? AND is_active = 1";
    $managers_result = execute_prepared_query($managers_query, array($building_id), 'i');
    $managers_count = $managers_result ? fetch_single_row($managers_result)['count'] : 0;
    
    // Count meters
    $meters_query = "SELECT COUNT(*) as count 
                     FROM flat_meters fm
                     JOIN flats f ON fm.flat_id = f.flat_id
                     WHERE f.building_id = ?";
    $meters_result = execute_prepared_query($meters_query, array($building_id), 'i');
    $meters_count = $meters_result ? fetch_single_row($meters_result)['count'] : 0;
    
    return array(
        'success' => true,
        'action' => 'confirm_delete',
        'building_name' => $building['building_name'],
        'counts' => array(
            'flats' => $flats_count,
            'active_tenants' => $tenants_count,
            'assignments' => $assignments_count,
            'expenses' => $expenses_count,
            'managers' => $managers_count,
            'meters' => $meters_count
        )
    );
}

// Add new action for confirmed deletion
function handle_confirm_delete_building($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can delete buildings'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    // Verify ownership
    $ownership_query = "SELECT building_name FROM buildings WHERE building_id = ? AND owner_id = ?";
    $ownership_result = execute_prepared_query($ownership_query, array($building_id, $user_id), 'ii');
    
    if (!$ownership_result || $ownership_result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $building = fetch_single_row($ownership_result);
    
    begin_transaction();
    
    try {
        // Step 1: Get all flats in this building
        $flats_query = "SELECT flat_id FROM flats WHERE building_id = ?";
        $flats_result = execute_prepared_query($flats_query, array($building_id), 'i');
        $flats = fetch_all_rows($flats_result);
        
        $deleted_count = array(
            'assignments' => 0,
            'expenses' => 0,
            'meters' => 0,
            'bookings' => 0,
            'flats' => 0
        );
        
        // Step 2: Delete data for each flat
        foreach ($flats as $flat) {
            $flat_id = $flat['flat_id'];
            
            // Delete flat_assignments
            $del_assignments = "DELETE FROM flat_assignments WHERE flat_id = ?";
            execute_prepared_query($del_assignments, array($flat_id), 'i');
            $deleted_count['assignments'] += get_affected_rows();
            
            // Delete flat_expenses
            $del_expenses = "DELETE FROM flat_expenses WHERE flat_id = ?";
            execute_prepared_query($del_expenses, array($flat_id), 'i');
            $deleted_count['expenses'] += get_affected_rows();
            
            // Delete flat_meters (CASCADE should work, but let's be explicit)
            $del_meters = "DELETE FROM flat_meters WHERE flat_id = ?";
            execute_prepared_query($del_meters, array($flat_id), 'i');
            $deleted_count['meters'] += get_affected_rows();
            
            // Delete flat_bookings (CASCADE should work)
            $del_bookings = "DELETE FROM flat_bookings WHERE flat_id = ?";
            execute_prepared_query($del_bookings, array($flat_id), 'i');
            $deleted_count['bookings'] += get_affected_rows();
        }
        
        // Step 3: Delete all flats
        $del_flats = "DELETE FROM flats WHERE building_id = ?";
        execute_prepared_query($del_flats, array($building_id), 'i');
        $deleted_count['flats'] = get_affected_rows();
        
        // Step 4: Deactivate building managers (CASCADE should work, but let's be explicit)
        $del_managers = "DELETE FROM building_managers WHERE building_id = ?";
        execute_prepared_query($del_managers, array($building_id), 'i');
        $deleted_count['managers'] = get_affected_rows();
        
        // Step 5: Finally delete the building
        $del_building = "DELETE FROM buildings WHERE building_id = ?";
        $del_result = execute_prepared_query($del_building, array($building_id), 'i');
        
        if (!$del_result) {
            throw new Exception('Failed to delete building');
        }
        
        // Log deletion
        log_user_activity($user_id, 'delete', 'buildings', $building_id, 
                         array('building_name' => $building['building_name']), 
                         $deleted_count);
        
        commit_transaction();
        
        $message = 'Building deleted successfully. Removed: ' . 
                   $deleted_count['flats'] . ' flats, ' .
                   $deleted_count['assignments'] . ' assignments, ' .
                   $deleted_count['expenses'] . ' expense records';
        
        echo json_encode(array('success' => true, 'message' => $message));
        exit();
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Building deletion error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Failed to delete building: ' . $e->getMessage()));
        exit();
    }
}

// Get flats for a building
function handle_get_flats($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $flats = get_flats_by_building($building_id, $user_id, $user_type);
    
    echo json_encode(array('success' => true, 'flats' => $flats));
    exit();
}

// Get single flat
function handle_get_flat($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    
    $query = "SELECT f.* FROM flats f 
              JOIN buildings b ON f.building_id = b.building_id 
              WHERE f.flat_id = ?";
    
    if ($user_type === 'owner') {
        $query .= " AND b.owner_id = ?";
        $result = execute_prepared_query($query, array($flat_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                    WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)";
        $result = execute_prepared_query($query, array($flat_id, $user_id), 'ii');
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if ($result && $result->num_rows > 0) {
        $flat = fetch_single_row($result);
        echo json_encode(array('success' => true, 'flat' => $flat));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Flat not found'));
    }
    exit();
}

// Add flat
function handle_add_flat($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    $flat_number = isset($_POST['flat_number']) ? trim($_POST['flat_number']) : '';
    $floor_number = isset($_POST['floor_number']) ? intval($_POST['floor_number']) : 0;
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : null;
    $bathrooms = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : null;
    $base_rent = isset($_POST['base_rent']) ? floatval($_POST['base_rent']) : 0;
    
    $result = create_flat($building_id, $flat_number, $floor_number, $user_id, $bedrooms, $bathrooms, $base_rent);
    echo json_encode($result);
    exit();
}

// Update flat
function handle_update_flat($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $flat_number = isset($_POST['flat_number']) ? trim($_POST['flat_number']) : '';
    $floor_number = isset($_POST['floor_number']) ? intval($_POST['floor_number']) : 0;
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : null;
    $bathrooms = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : null;
    $base_rent = isset($_POST['base_rent']) ? floatval($_POST['base_rent']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'available';
    
    $result = update_flat($flat_id, $flat_number, $floor_number, $bedrooms, $bathrooms, $base_rent, $status, $user_id);
    echo json_encode($result);
    exit();
}

// Delete flat
function handle_delete_flat($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    
    // Check access
    $access_query = "SELECT f.flat_id FROM flats f 
                     JOIN buildings b ON f.building_id = b.building_id 
                     WHERE f.flat_id = ? AND b.owner_id = ?";
    $access_result = execute_prepared_query($access_query, array($flat_id, $user_id), 'ii');
    
    if (!$access_result || $access_result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $delete_query = "DELETE FROM flats WHERE flat_id = ?";
    $result = execute_prepared_query($delete_query, array($flat_id), 'i');
    
    if ($result) {
        echo json_encode(array('success' => true, 'message' => 'Flat deleted successfully'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to delete flat'));
    }
    exit();
}

// Get managers for a building
function handle_get_managers($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $managers = get_building_managers($building_id);
    
    echo json_encode(array('success' => true, 'managers' => $managers));
    exit();
}

// Get available managers
function handle_get_available_managers($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $query = "SELECT u.user_id, up.full_name, u.email 
              FROM users u 
              JOIN user_profiles up ON u.user_id = up.user_id 
              WHERE u.user_type = 'manager' AND u.is_active = 1";
    
    $result = execute_prepared_query($query, array(), '');
    $managers = $result ? fetch_all_rows($result) : array();
    
    echo json_encode(array('success' => true, 'managers' => $managers));
    exit();
}

// Assign manager
function handle_assign_manager($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can assign managers'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    $manager_id = isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0;
    
    $result = assign_manager_to_building($building_id, $manager_id, $user_id);
    echo json_encode($result);
    exit();
}

// Remove manager
function handle_remove_manager($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can remove managers'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    $manager_id = isset($_POST['manager_id']) ? intval($_POST['manager_id']) : 0;
    
    $result = remove_manager_from_building($building_id, $manager_id, $user_id);
    echo json_encode($result);
    exit();
}
?>