<?php

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
        case 'get_building_details':
            handle_get_building_details($user_id, $user_type);
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

        case 'get_first_flat_charges':
            handle_get_first_flat_charges($user_id, $user_type);
            break;

        case 'get_flat_charges':
            handle_get_flat_charges($user_id, $user_type);
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            break;
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
}
exit();

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
            error_log("Starting to create $total_flats flats for building $building_id");

            foreach ($flats_data as $flat) {
                $flat_number = $flat['flat_number'];
                $floor_number = $flat['floor_number'];
                
                error_log("Attempting to create flat: $flat_number on floor: $floor_number");
                
                $flat_result = create_flat($building_id, $flat_number, $floor_number, $user_id, null, null, 0.00);
                
                error_log("Flat creation result: " . json_encode($flat_result));
                
                if ($flat_result['success']) {
                    $flats_created++;
                    error_log("Flat created successfully. Total created: $flats_created");
                } else {
                    error_log("Flat creation FAILED: " . $flat_result['message']);
                }
            }

            error_log("Final count: $flats_created created out of $total_flats expected");

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

function handle_get_building_details($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $building = get_building_details($building_id, $user_id, $user_type);
    
    if ($building) {
        echo json_encode(array('success' => true, 'building' => $building));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Building not found'));
    }
    exit();
}


function handle_update_building($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    $building_name = isset($_POST['building_name']) ? trim($_POST['building_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $total_floors = isset($_POST['total_floors']) ? intval($_POST['total_floors']) : 0;
    

    $default_rent = isset($_POST['default_rent']) ? floatval($_POST['default_rent']) : 0;
    $default_gas_bill = isset($_POST['default_gas_bill']) ? floatval($_POST['default_gas_bill']) : 0;
    $default_water_bill = isset($_POST['default_water_bill']) ? floatval($_POST['default_water_bill']) : 0;
    $default_service_charge = isset($_POST['default_service_charge']) ? floatval($_POST['default_service_charge']) : 0;
    $default_cleaning_charge = isset($_POST['default_cleaning_charge']) ? floatval($_POST['default_cleaning_charge']) : 0;
    $default_miscellaneous = isset($_POST['default_miscellaneous']) ? floatval($_POST['default_miscellaneous']) : 0;
    

    $default_meter_type = isset($_POST['default_meter_type']) ? trim($_POST['default_meter_type']) : '';
    $default_per_unit_cost = isset($_POST['default_per_unit_cost']) && $_POST['default_per_unit_cost'] !== '' 
        ? floatval($_POST['default_per_unit_cost']) 
        : null;
    

    $access_query = "SELECT building_id FROM buildings WHERE building_id = ?";
    if ($user_type === 'owner') {
        $access_query .= " AND owner_id = ?";
        $result = execute_prepared_query($access_query, array($building_id, $user_id), 'ii');
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if (!$result || $result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    begin_transaction();
    
    try {

        $building_query = "UPDATE buildings 
                          SET building_name = ?, address = ?, total_floors = ?
                          WHERE building_id = ?";
        execute_prepared_query($building_query, 
            array($building_name, $address, $total_floors, $building_id), 
            'ssii');
        

        $flats_query = "SELECT flat_id FROM flats WHERE building_id = ?";
        $flats_result = execute_prepared_query($flats_query, array($building_id), 'i');
        
        $flats_updated = 0;
        
        if ($flats_result && $flats_result->num_rows > 0) {
            $flats = fetch_all_rows($flats_result);
            
            foreach ($flats as $flat) {
                $flat_id = $flat['flat_id'];
                

                if ($default_rent > 0) {

                    $update_flat_rent = "UPDATE flats SET base_rent = ? WHERE flat_id = ?";
                    execute_prepared_query($update_flat_rent, array($default_rent, $flat_id), 'di');
                    

                    $update_charges_rent = "INSERT INTO flat_default_charges (flat_id, rent) 
                                            VALUES (?, ?)
                                            ON DUPLICATE KEY UPDATE rent = VALUES(rent)";
                    execute_prepared_query($update_charges_rent, array($flat_id, $default_rent), 'id');
                }
                

                $charges_query = "INSERT INTO flat_default_charges 
                                 (flat_id, gas_bill, water_bill, service_charge, cleaning_charge, miscellaneous)
                                 VALUES (?, ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                 gas_bill = VALUES(gas_bill),
                                 water_bill = VALUES(water_bill),
                                 service_charge = VALUES(service_charge),
                                 cleaning_charge = VALUES(cleaning_charge),
                                 miscellaneous = VALUES(miscellaneous)";
                
                execute_prepared_query($charges_query, 
                    array($flat_id, $default_gas_bill, $default_water_bill, 
                          $default_service_charge, $default_cleaning_charge, $default_miscellaneous),
                    'iddddd');
                

                if (!empty($default_meter_type) && 
                    ($default_meter_type === 'electric_prepaid' || $default_meter_type === 'electric_postpaid')) {
                    

                    $delete_meter = "DELETE FROM flat_meters 
                                    WHERE flat_id = ? 
                                    AND (meter_type = 'electric_prepaid' OR meter_type = 'electric_postpaid')";
                    execute_prepared_query($delete_meter, array($flat_id), 'i');
                    
                    // Insert new meter
                    if ($default_per_unit_cost !== null && $default_per_unit_cost > 0) {
                        $meter_query = "INSERT INTO flat_meters 
                                       (flat_id, meter_type, per_unit_cost) 
                                       VALUES (?, ?, ?)";
                        execute_prepared_query($meter_query, 
                            array($flat_id, $default_meter_type, $default_per_unit_cost),
                            'isd');
                    } else {
                        $meter_query = "INSERT INTO flat_meters 
                                       (flat_id, meter_type) 
                                       VALUES (?, ?)";
                        execute_prepared_query($meter_query, 
                            array($flat_id, $default_meter_type),
                            'is');
                    }
                }
                
                $flats_updated++;
            }
        }
        
        commit_transaction();
        
        log_user_activity($user_id, 'update', 'buildings', $building_id, null,
                         array('building_name' => $building_name, 
                               'default_charges_applied' => true,
                               'flats_updated' => $flats_updated,
                               'default_rent' => $default_rent,
                               'default_meter_applied' => !empty($default_meter_type)));
        
        echo json_encode(array(
            'success' => true, 
            'message' => 'Building updated successfully. Default settings applied to ' . $flats_updated . ' flats.'
        ));
        exit();
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Building update error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Failed to update building: ' . $e->getMessage()));
        exit();
    }
}


function handle_delete_building($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can delete buildings'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    

    $info = get_building_deletion_info($building_id, $user_id);
    
    if (!$info['success']) {
        echo json_encode($info);
        exit();
    }
    
    echo json_encode($info);
    exit();
}


function get_building_deletion_info($building_id, $user_id) {

    $ownership_query = "SELECT building_name FROM buildings WHERE building_id = ? AND owner_id = ?";
    $ownership_result = execute_prepared_query($ownership_query, array($building_id, $user_id), 'ii');
    
    if (!$ownership_result || $ownership_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Access denied - only building owner can delete');
    }
    
    $building = fetch_single_row($ownership_result);
    

    $flats_query = "SELECT COUNT(*) as count FROM flats WHERE building_id = ?";
    $flats_result = execute_prepared_query($flats_query, array($building_id), 'i');
    $flats_count = $flats_result ? fetch_single_row($flats_result)['count'] : 0;
    

    $tenants_query = "SELECT COUNT(DISTINCT fa.tenant_id) as count 
                      FROM flat_assignments fa
                      JOIN flats f ON fa.flat_id = f.flat_id
                      WHERE f.building_id = ? 
                      AND fa.status = 'confirmed' 
                      AND fa.actual_ended_at IS NULL";
    $tenants_result = execute_prepared_query($tenants_query, array($building_id), 'i');
    $tenants_count = $tenants_result ? fetch_single_row($tenants_result)['count'] : 0;
    

    $assignments_query = "SELECT COUNT(*) as count 
                          FROM flat_assignments fa
                          JOIN flats f ON fa.flat_id = f.flat_id
                          WHERE f.building_id = ?";
    $assignments_result = execute_prepared_query($assignments_query, array($building_id), 'i');
    $assignments_count = $assignments_result ? fetch_single_row($assignments_result)['count'] : 0;
    

    $expenses_query = "SELECT COUNT(*) as count 
                       FROM flat_expenses fe
                       JOIN flats f ON fe.flat_id = f.flat_id
                       WHERE f.building_id = ?";
    $expenses_result = execute_prepared_query($expenses_query, array($building_id), 'i');
    $expenses_count = $expenses_result ? fetch_single_row($expenses_result)['count'] : 0;
    

    $managers_query = "SELECT COUNT(*) as count 
                       FROM building_managers 
                       WHERE building_id = ? AND is_active = 1";
    $managers_result = execute_prepared_query($managers_query, array($building_id), 'i');
    $managers_count = $managers_result ? fetch_single_row($managers_result)['count'] : 0;
    

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

function handle_confirm_delete_building($user_id, $user_type) {
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Only owners can delete buildings'));
        exit();
    }
    
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    

    $ownership_query = "SELECT building_name FROM buildings WHERE building_id = ? AND owner_id = ?";
    $ownership_result = execute_prepared_query($ownership_query, array($building_id, $user_id), 'ii');
    
    if (!$ownership_result || $ownership_result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    $building = fetch_single_row($ownership_result);
    
    begin_transaction();
    
    try {
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
        
        foreach ($flats as $flat) {
            $flat_id = $flat['flat_id'];
            
            $del_assignments = "DELETE FROM flat_assignments WHERE flat_id = ?";
            execute_prepared_query($del_assignments, array($flat_id), 'i');
            $deleted_count['assignments'] += get_affected_rows();
            
            $del_expenses = "DELETE FROM flat_expenses WHERE flat_id = ?";
            execute_prepared_query($del_expenses, array($flat_id), 'i');
            $deleted_count['expenses'] += get_affected_rows();
            

            $del_meters = "DELETE FROM flat_meters WHERE flat_id = ?";
            execute_prepared_query($del_meters, array($flat_id), 'i');
            $deleted_count['meters'] += get_affected_rows();
            
            $del_bookings = "DELETE FROM flat_bookings WHERE flat_id = ?";
            execute_prepared_query($del_bookings, array($flat_id), 'i');
            $deleted_count['bookings'] += get_affected_rows();
        }
        

        $del_flats = "DELETE FROM flats WHERE building_id = ?";
        execute_prepared_query($del_flats, array($building_id), 'i');
        $deleted_count['flats'] = get_affected_rows();
        
        $del_managers = "DELETE FROM building_managers WHERE building_id = ?";
        execute_prepared_query($del_managers, array($building_id), 'i');
        $deleted_count['managers'] = get_affected_rows();
        

        $del_building = "DELETE FROM buildings WHERE building_id = ?";
        $del_result = execute_prepared_query($del_building, array($building_id), 'i');
        
        if (!$del_result) {
            throw new Exception('Failed to delete building');
        }
        

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


function handle_get_flats($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $flats = get_flats_by_building($building_id, $user_id, $user_type);
    
    echo json_encode(array('success' => true, 'flats' => $flats));
    exit();
}


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
        

        $meters_query = "SELECT * FROM flat_meters WHERE flat_id = ?";
        $meters_result = execute_prepared_query($meters_query, array($flat_id), 'i');
        $meters = $meters_result ? fetch_all_rows($meters_result) : array();
        

        $charges_query = "SELECT * FROM flat_default_charges WHERE flat_id = ?";
        $charges_result = execute_prepared_query($charges_query, array($flat_id), 'i');
        $charges = ($charges_result && $charges_result->num_rows > 0) ? fetch_single_row($charges_result) : null;
        
        echo json_encode(array(
            'success' => true, 
            'flat' => $flat,
            'meters' => $meters,
            'default_charges' => $charges
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Flat not found'));
    }
    exit();
}

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

function handle_update_flat($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $flat_number = isset($_POST['flat_number']) ? trim($_POST['flat_number']) : '';
    $floor_number = isset($_POST['floor_number']) ? intval($_POST['floor_number']) : 0;
    $bedrooms = isset($_POST['bedrooms']) && $_POST['bedrooms'] !== '' ? intval($_POST['bedrooms']) : null;
    $bathrooms = isset($_POST['bathrooms']) && $_POST['bathrooms'] !== '' ? intval($_POST['bathrooms']) : null;
    
    $rent = isset($_POST['rent']) ? floatval($_POST['rent']) : 0;
    $gas_bill = isset($_POST['gas_bill']) ? floatval($_POST['gas_bill']) : 0;
    $water_bill = isset($_POST['water_bill']) ? floatval($_POST['water_bill']) : 0;
    $service_charge = isset($_POST['service_charge']) ? floatval($_POST['service_charge']) : 0;
    $cleaning_charge = isset($_POST['cleaning_charge']) ? floatval($_POST['cleaning_charge']) : 0;
    $miscellaneous = isset($_POST['miscellaneous']) ? floatval($_POST['miscellaneous']) : 0;
    
    $access_query = "SELECT f.flat_id, f.building_id FROM flats f 
                     JOIN buildings b ON f.building_id = b.building_id 
                     WHERE f.flat_id = ?";
    
    if ($user_type === 'owner') {
        $access_query .= " AND b.owner_id = ?";
        $access_result = execute_prepared_query($access_query, array($flat_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $access_query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                          WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)";
        $access_result = execute_prepared_query($access_query, array($flat_id, $user_id), 'ii');
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if (!$access_result || $access_result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    begin_transaction();
    
    try {
        $update_query = "UPDATE flats 
                        SET flat_number = ?, floor_number = ?, bedrooms = ?, bathrooms = ?
                        WHERE flat_id = ?";
        $update_result = execute_prepared_query($update_query, 
            array($flat_number, $floor_number, $bedrooms, $bathrooms, $flat_id), 
            'siiii');
        
        if (!$update_result) {
            throw new Exception('Failed to update flat');
        }
        
        $rent_query = "CALL update_flat_rent(?, ?)";
        $rent_result = execute_prepared_query($rent_query, array($flat_id, $rent), 'id');
        
        if (!$rent_result) {
            throw new Exception('Failed to update rent');
        }
        
        $charges_query = "INSERT INTO flat_default_charges 
                         (flat_id, rent, gas_bill, water_bill, service_charge, cleaning_charge, miscellaneous) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         gas_bill = VALUES(gas_bill),
                         water_bill = VALUES(water_bill),
                         service_charge = VALUES(service_charge),
                         cleaning_charge = VALUES(cleaning_charge),
                         miscellaneous = VALUES(miscellaneous)";
                         
        execute_prepared_query($charges_query, 
            array($flat_id, $rent, $gas_bill, $water_bill, $service_charge, $cleaning_charge, $miscellaneous),
            'idddddd');
        
        $delete_meters = "DELETE FROM flat_meters WHERE flat_id = ?";
        execute_prepared_query($delete_meters, array($flat_id), 'i');
        
        $electric_type = isset($_POST['electric_type']) ? $_POST['electric_type'] : '';
        if (!empty($electric_type) && ($electric_type === 'electric_prepaid' || $electric_type === 'electric_postpaid')) {
            $electric_number = isset($_POST['electric_number']) ? trim($_POST['electric_number']) : null;
            $electric_cost = isset($_POST['electric_cost']) && $_POST['electric_cost'] !== '' ? floatval($_POST['electric_cost']) : null;
            $electric_current = isset($_POST['electric_current']) && $_POST['electric_current'] !== '' ? floatval($_POST['electric_current']) : null;
            $electric_previous = isset($_POST['electric_previous']) && $_POST['electric_previous'] !== '' ? floatval($_POST['electric_previous']) : null;
            
            $meter_query = "INSERT INTO flat_meters 
                           (flat_id, meter_type, meter_number, current_reading, previous_reading, per_unit_cost) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            execute_prepared_query($meter_query, 
                array($flat_id, $electric_type, $electric_number, $electric_current, $electric_previous, $electric_cost),
                'issddd');
        }
        
        commit_transaction();
        
        log_user_activity($user_id, 'update', 'flats', $flat_id, null,
                         array('flat_number' => $flat_number, 'rent' => $rent));
        
        echo json_encode(array('success' => true, 'message' => 'Flat updated successfully'));
        exit();
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Flat update error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Failed to update flat: ' . $e->getMessage()));
        exit();
    }
}


function handle_delete_flat($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    
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

function handle_get_managers($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $managers = get_building_managers($building_id);
    
    echo json_encode(array('success' => true, 'managers' => $managers));
    exit();
}

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

function handle_get_first_flat_charges($user_id, $user_type) {
    $building_id = isset($_POST['building_id']) ? intval($_POST['building_id']) : 0;
    
    $access_query = "SELECT building_id FROM buildings WHERE building_id = ?";
    if ($user_type === 'owner') {
        $access_query .= " AND owner_id = ?";
        $result = execute_prepared_query($access_query, array($building_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $access_query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                          WHERE bm.building_id = ? AND bm.manager_id = ? AND bm.is_active = 1)";
        $result = execute_prepared_query($access_query, array($building_id, $user_id), 'ii');
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if (!$result || $result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    

    $query = "SELECT fdc.* FROM flat_default_charges fdc
              JOIN flats f ON fdc.flat_id = f.flat_id
              WHERE f.building_id = ?
              ORDER BY f.flat_id ASC
              LIMIT 1";
    $result = execute_prepared_query($query, array($building_id), 'i');
    $charges = $result && $result->num_rows > 0 ? fetch_single_row($result) : null;
    

    $meter_query = "SELECT fm.meter_type, fm.per_unit_cost 
                    FROM flat_meters fm
                    JOIN flats f ON fm.flat_id = f.flat_id
                    WHERE f.building_id = ?
                    AND (fm.meter_type = 'electric_prepaid' OR fm.meter_type = 'electric_postpaid')
                    ORDER BY f.flat_id ASC
                    LIMIT 1";
    $meter_result = execute_prepared_query($meter_query, array($building_id), 'i');
    $meter = $meter_result && $meter_result->num_rows > 0 ? fetch_single_row($meter_result) : null;
    
    echo json_encode(array(
        'success' => true, 
        'charges' => $charges,
        'meter' => $meter
    ));
    exit();
}


function handle_get_flat_charges($user_id, $user_type) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    

    $access_query = "SELECT f.flat_id FROM flats f
                     JOIN buildings b ON f.building_id = b.building_id
                     WHERE f.flat_id = ?";
    
    if ($user_type === 'owner') {
        $access_query .= " AND b.owner_id = ?";
        $result = execute_prepared_query($access_query, array($flat_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $access_query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                          WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)";
        $result = execute_prepared_query($access_query, array($flat_id, $user_id), 'ii');
    } else {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    
    if (!$result || $result->num_rows == 0) {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        exit();
    }
    

    $query = "SELECT * FROM flat_default_charges WHERE flat_id = ?";
    $result = execute_prepared_query($query, array($flat_id), 'i');
    $charges = $result && $result->num_rows > 0 ? fetch_single_row($result) : null;
    
    echo json_encode(array('success' => true, 'charges' => $charges));
    exit();
}
?>