<?php
// Property Model for SmartRent
// All property and building-related database operations
require_once 'database.php';

// Create new building
function create_building($owner_id, $building_name, $address, $total_floors, $total_flats = null) {
    // Validate inputs
    if (empty($building_name) || empty($address) || $total_floors <= 0) {
        return array('success' => false, 'message' => 'Invalid building data');
    }
    
    // Check if building name already exists for this owner
    $check_query = "SELECT building_id FROM buildings WHERE owner_id = ? AND building_name = ?";
    $check_result = execute_prepared_query($check_query, array($owner_id, $building_name), 'is');
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        return array('success' => false, 'message' => 'Building name already exists');
    }
    
    // Insert building
    $query = "INSERT INTO buildings (owner_id, building_name, address, total_floors, total_flats) 
              VALUES (?, ?, ?, ?, ?)";
    $params = array($owner_id, $building_name, $address, $total_floors, $total_flats);
    $types = 'issii';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        $building_id = get_last_insert_id();
        
        // Log building creation
        log_user_activity($owner_id, 'create', 'buildings', $building_id, null, 
                         array('building_name' => $building_name, 'total_floors' => $total_floors));
        
        return array('success' => true, 'message' => 'Building created successfully', 'building_id' => $building_id);
    } else {
        return array('success' => false, 'message' => 'Failed to create building');
    }
}

// Get buildings by owner
function get_buildings_by_owner($owner_id) {
    $query = "SELECT b.*, COUNT(f.flat_id) as actual_flats,
                     COUNT(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN 1 END) as occupied_flats
              FROM buildings b
              LEFT JOIN flats f ON b.building_id = f.building_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id
              WHERE b.owner_id = ?
              GROUP BY b.building_id
              ORDER BY b.created_at DESC";
    
    $result = execute_prepared_query($query, array($owner_id), 'i');
    
    return $result ? fetch_all_rows($result) : array();
}

// Get buildings managed by manager
function get_buildings_by_manager($manager_id) {
    $query = "SELECT b.*, bm.assigned_date, COUNT(f.flat_id) as actual_flats,
                     COUNT(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN 1 END) as occupied_flats
              FROM buildings b
              JOIN building_managers bm ON b.building_id = bm.building_id
              LEFT JOIN flats f ON b.building_id = f.building_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id
              WHERE bm.manager_id = ? AND bm.is_active = 1
              GROUP BY b.building_id
              ORDER BY b.building_name";
    
    $result = execute_prepared_query($query, array($manager_id), 'i');
    
    return $result ? fetch_all_rows($result) : array();
}

// Get single building details
function get_building_details($building_id, $user_id = null, $user_type = null) {
    $query = "SELECT b.*, up.full_name as owner_name, u.email as owner_email
              FROM buildings b
              JOIN users u ON b.owner_id = u.user_id
              JOIN user_profiles up ON b.owner_id = up.user_id
              WHERE b.building_id = ?";
    
    // Add access control based on user type
    if ($user_type === 'manager') {
        $query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                                WHERE bm.building_id = b.building_id 
                                AND bm.manager_id = ? AND bm.is_active = 1)";
        $params = array($building_id, $user_id);
        $types = 'ii';
    } elseif ($user_type === 'tenant') {
        $query .= " AND EXISTS (SELECT 1 FROM flats f 
                                JOIN flat_assignments fa ON f.flat_id = fa.flat_id
                                WHERE f.building_id = b.building_id 
                                AND fa.tenant_id = ? 
                                AND fa.status = 'confirmed' 
                                AND fa.actual_ended_at IS NULL)";
        $params = array($building_id, $user_id);
        $types = 'ii';
    } else {
        // Owner can see all their buildings
        $query .= " AND b.owner_id = ?";
        $params = array($building_id, $user_id);
        $types = 'ii';
    }
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_single_row($result) : null;
}

// Update building information
function update_building($building_id, $building_name, $address, $total_floors, $user_id) {
    // Check ownership or management rights
    $access_query = "SELECT b.building_id FROM buildings b
                     LEFT JOIN building_managers bm ON b.building_id = bm.building_id
                     WHERE b.building_id = ? 
                     AND (b.owner_id = ? OR (bm.manager_id = ? AND bm.is_active = 1))";
    
    $access_result = execute_prepared_query($access_query, array($building_id, $user_id, $user_id), 'iii');
    
    if (!$access_result || mysqli_num_rows($access_result) == 0) {
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // Update building
    $query = "UPDATE buildings 
              SET building_name = ?, address = ?, total_floors = ?
              WHERE building_id = ?";
    $params = array($building_name, $address, $total_floors, $building_id);
    $types = 'ssii';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        log_user_activity($user_id, 'update', 'buildings', $building_id, null,
                         array('building_name' => $building_name, 'total_floors' => $total_floors));
        
        return array('success' => true, 'message' => 'Building updated successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to update building');
    }
}

// Delete building (with protection checks)
function delete_building($building_id, $user_id) {
    // Only owner can delete buildings
    $ownership_query = "SELECT building_id FROM buildings WHERE building_id = ? AND owner_id = ?";
    $ownership_result = execute_prepared_query($ownership_query, array($building_id, $user_id), 'ii');
    
    if (!$ownership_result || mysqli_num_rows($ownership_result) == 0) {
        return array('success' => false, 'message' => 'Access denied - only building owner can delete');
    }
    
    // Check if building has active tenants
    $tenants_query = "SELECT COUNT(*) as active_tenants
                      FROM flat_assignments fa
                      JOIN flats f ON fa.flat_id = f.flat_id
                      WHERE f.building_id = ? 
                      AND fa.status = 'confirmed' 
                      AND fa.actual_ended_at IS NULL";
    
    $tenants_result = execute_prepared_query($tenants_query, array($building_id), 'i');
    $tenant_count = $tenants_result ? fetch_single_row($tenants_result)['active_tenants'] : 0;
    
    if ($tenant_count > 0) {
        return array(
            'success' => false, 
            'message' => 'Cannot delete building with active tenants. Please end all tenancies first.',
            'warning' => 'active_tenants'
        );
    }
    
    begin_transaction();
    
    try {
        // Get building name for logging
        $name_query = "SELECT building_name FROM buildings WHERE building_id = ?";
        $name_result = execute_prepared_query($name_query, array($building_id), 'i');
        $building_name = $name_result ? fetch_single_row($name_result)['building_name'] : 'Unknown';
        
        // Delete building (CASCADE will handle flats and related data)
        $delete_query = "DELETE FROM buildings WHERE building_id = ?";
        $delete_result = execute_prepared_query($delete_query, array($building_id), 'i');
        
        if (!$delete_result) {
            throw new Exception('Failed to delete building');
        }
        
        // Log deletion
        log_user_activity($user_id, 'delete', 'buildings', $building_id, 
                         array('building_name' => $building_name), null);
        
        commit_transaction();
        
        return array('success' => true, 'message' => 'Building deleted successfully');
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => 'Failed to delete building: ' . $e->getMessage());
    }
}

// Create flat in building
function create_flat($building_id, $flat_number, $floor_number, $user_id, $bedrooms = null, $bathrooms = null, $base_rent = 0.00) {
    // Check building access
    $access_query = "SELECT b.building_id FROM buildings b
                     LEFT JOIN building_managers bm ON b.building_id = bm.building_id
                     WHERE b.building_id = ? 
                     AND (b.owner_id = ? OR (bm.manager_id = ? AND bm.is_active = 1))";
    
    $access_result = execute_prepared_query($access_query, array($building_id, $user_id, $user_id), 'iii');
    
    if (!$access_result || mysqli_num_rows($access_result) == 0) {
        error_log("create_flat: Access denied for building_id=$building_id, user_id=$user_id");
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // Check if flat number already exists in this building
    $check_query = "SELECT flat_id FROM flats WHERE building_id = ? AND flat_number = ?";
    $check_result = execute_prepared_query($check_query, array($building_id, $flat_number), 'is');
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        error_log("create_flat: Flat $flat_number already exists in building $building_id");
        return array('success' => false, 'message' => 'Flat number already exists in this building');
    }
    
    // Handle NULL values - convert to 0 for binding
    $bedrooms_value = ($bedrooms === null) ? 0 : $bedrooms;
    $bathrooms_value = ($bathrooms === null) ? 0 : $bathrooms;
    
    // Insert flat WITH base_rent
    $query = "INSERT INTO flats (building_id, flat_number, floor_number, bedrooms, bathrooms, base_rent) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($building_id, $flat_number, $floor_number, $bedrooms_value, $bathrooms_value, $base_rent);
    $types = 'isiiid';
    
    error_log("create_flat: Attempting to create flat $flat_number in building $building_id");
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        $flat_id = get_last_insert_id();
        error_log("create_flat: SUCCESS - Created flat_id=$flat_id");
        
        // Update building's total flats count
        update_building_flats_count($building_id);
        
        // Log flat creation
        log_user_activity($user_id, 'create', 'flats', $flat_id, null,
                         array('flat_number' => $flat_number, 'building_id' => $building_id));
        
        return array('success' => true, 'message' => 'Flat created successfully', 'flat_id' => $flat_id);
    } else {
        error_log("create_flat: FAILED - Could not insert flat $flat_number");
        return array('success' => false, 'message' => 'Failed to create flat');
    }
}

// Get flats in building
function get_flats_by_building($building_id, $user_id, $user_type) {
    $query = "SELECT f.*,
              fdc.rent as monthly_rent,
              fa.assignment_id,
              fa.status as assignment_status,
              fa.tenant_id,
              up.full_name as tenant_name
              FROM flats f
              LEFT JOIN flat_default_charges fdc ON f.flat_id = fdc.flat_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id 
                  AND fa.status = 'confirmed' 
                  AND fa.actual_ended_at IS NULL
              LEFT JOIN user_profiles up ON fa.tenant_id = up.user_id
              WHERE f.building_id = ?
              ORDER BY f.floor_number, f.flat_number";
    
    $result = execute_prepared_query($query, array($building_id), 'i');
    return $result ? fetch_all_rows($result) : array();
}

// Get available flats in building
function get_available_flats($building_id = null, $future_date = null) {
    $query = "SELECT f.*, b.building_name, b.address
              FROM flats f
              JOIN buildings b ON f.building_id = b.building_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id 
                        AND fa.status = 'confirmed' 
                        AND fa.actual_ended_at IS NULL
              WHERE f.status = 'available'";
    
    $params = array();
    $types = '';
    
    if ($building_id) {
        $query .= " AND f.building_id = ?";
        $params[] = $building_id;
        $types .= 'i';
    }
    
    // Include flats that will be available in the future
    if ($future_date) {
        $query .= " OR (fa.move_out_date IS NOT NULL AND fa.move_out_date <= ?)";
        $params[] = $future_date;
        $types .= 's';
    } else {
        $query .= " AND fa.assignment_id IS NULL";
    }
    
    $query .= " ORDER BY b.building_name, f.floor_number, f.flat_number";
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_all_rows($result) : array();
}

// Update flat information
function update_flat($flat_id, $flat_number, $floor_number, $bedrooms, $bathrooms, $status, $user_id) {
    // Check access rights
    $access_query = "SELECT f.flat_id FROM flats f
                     JOIN buildings b ON f.building_id = b.building_id
                     LEFT JOIN building_managers bm ON b.building_id = bm.building_id
                     WHERE f.flat_id = ? 
                     AND (b.owner_id = ? OR (bm.manager_id = ? AND bm.is_active = 1))";
    
    $access_result = execute_prepared_query($access_query, array($flat_id, $user_id, $user_id), 'iii');
    
    if (!$access_result || mysqli_num_rows($access_result) == 0) {
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // Update flat (removed base_rent from here)
    $query = "UPDATE flats 
              SET flat_number = ?, floor_number = ?, bedrooms = ?, bathrooms = ?, status = ?
              WHERE flat_id = ?";
    $params = array($flat_number, $floor_number, $bedrooms, $bathrooms, $status, $flat_id);
    $types = 'siiisi';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        log_user_activity($user_id, 'update', 'flats', $flat_id, null,
                         array('flat_number' => $flat_number, 'status' => $status));
        
        return array('success' => true, 'message' => 'Flat updated successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to update flat');
    }
}

// Add function to update flat default charges
function update_flat_default_charges($flat_id, $charges, $user_id) {
    // Check access rights
    $access_query = "SELECT f.flat_id FROM flats f
                     JOIN buildings b ON f.building_id = b.building_id
                     LEFT JOIN building_managers bm ON b.building_id = bm.building_id
                     WHERE f.flat_id = ? 
                     AND (b.owner_id = ? OR (bm.manager_id = ? AND bm.is_active = 1))";
    
    $access_result = execute_prepared_query($access_query, array($flat_id, $user_id, $user_id), 'iii');
    
    if (!$access_result || mysqli_num_rows($access_result) == 0) {
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // If rent is being updated, use the stored procedure
    if (isset($charges['rent'])) {
        $query = "CALL update_flat_rent(?, ?)";
        $result = execute_prepared_query($query, array($flat_id, $charges['rent']), 'id');
        
        if (!$result) {
            return array('success' => false, 'message' => 'Failed to update rent');
        }
        
        unset($charges['rent']); // Remove from array since already updated
    }
    
    // Update other charges
    if (!empty($charges)) {
        // First check if record exists
        $check_query = "SELECT flat_id FROM flat_default_charges WHERE flat_id = ?";
        $check_result = execute_prepared_query($check_query, array($flat_id), 'i');
        
        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            // Insert new record
            $query = "INSERT INTO flat_default_charges (flat_id, gas_bill, water_bill, service_charge, cleaning_charge, miscellaneous) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $params = array(
                $flat_id,
                isset($charges['gas_bill']) ? $charges['gas_bill'] : 0,
                isset($charges['water_bill']) ? $charges['water_bill'] : 0,
                isset($charges['service_charge']) ? $charges['service_charge'] : 0,
                isset($charges['cleaning_charge']) ? $charges['cleaning_charge'] : 0,
                isset($charges['miscellaneous']) ? $charges['miscellaneous'] : 0
            );
            $types = 'iddddd';
        } else {
            // Update existing record
            $set_parts = array();
            $params = array();
            $types = '';
            
            foreach ($charges as $field => $value) {
                $set_parts[] = "$field = ?";
                $params[] = $value;
                $types .= 'd';
            }
            
            $params[] = $flat_id;
            $types .= 'i';
            
            $query = "UPDATE flat_default_charges SET " . implode(', ', $set_parts) . " WHERE flat_id = ?";
        }
        
        $result = execute_prepared_query($query, $params, $types);
        
        if (!$result) {
            return array('success' => false, 'message' => 'Failed to update charges');
        }
    }
    
    log_user_activity($user_id, 'update', 'flat_default_charges', $flat_id, null, $charges);
    
    return array('success' => true, 'message' => 'Charges updated successfully');
}


// Add this new function to property_model.php
function update_flat_rent($flat_id, $new_rent, $user_id) {
    // Check access rights
    $access_query = "SELECT f.flat_id FROM flats f
                     JOIN buildings b ON f.building_id = b.building_id
                     LEFT JOIN building_managers bm ON b.building_id = bm.building_id
                     WHERE f.flat_id = ? 
                     AND (b.owner_id = ? OR (bm.manager_id = ? AND bm.is_active = 1))";
    
    $access_result = execute_prepared_query($access_query, array($flat_id, $user_id, $user_id), 'iii');
    
    if (!$access_result || mysqli_num_rows($access_result) == 0) {
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // Call the stored procedure
    $query = "CALL update_flat_rent(?, ?)";
    $result = execute_prepared_query($query, array($flat_id, $new_rent), 'id');
    
    if ($result) {
        log_user_activity($user_id, 'update', 'flats', $flat_id, null,
                         array('action' => 'rent_updated', 'new_rent' => $new_rent));
        
        return array('success' => true, 'message' => 'Rent updated successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to update rent');
    }
}

// Assign manager to building
function assign_manager_to_building($building_id, $manager_id, $owner_id) {
    // Verify owner owns the building
    $owner_query = "SELECT building_id FROM buildings WHERE building_id = ? AND owner_id = ?";
    $owner_result = execute_prepared_query($owner_query, array($building_id, $owner_id), 'ii');
    
    if (!$owner_result || mysqli_num_rows($owner_result) == 0) {
        return array('success' => false, 'message' => 'Access denied - you do not own this building');
    }
    
    // Verify manager exists and is active
    $manager_query = "SELECT user_id FROM users WHERE user_id = ? AND user_type = 'manager' AND is_active = 1";
    $manager_result = execute_prepared_query($manager_query, array($manager_id), 'i');
    
    if (!$manager_result || mysqli_num_rows($manager_result) == 0) {
        return array('success' => false, 'message' => 'Manager not found or inactive');
    }
    
    // Check if assignment already exists
    $existing_query = "SELECT bm_id FROM building_managers 
                       WHERE building_id = ? AND manager_id = ? AND is_active = 1";
    $existing_result = execute_prepared_query($existing_query, array($building_id, $manager_id), 'ii');
    
    if ($existing_result && mysqli_num_rows($existing_result) > 0) {
        return array('success' => false, 'message' => 'Manager is already assigned to this building');
    }
    
    // Create assignment
    $query = "INSERT INTO building_managers (building_id, manager_id, assigned_date) 
              VALUES (?, ?, CURDATE())";
    $result = execute_prepared_query($query, array($building_id, $manager_id), 'ii');
    
    if ($result) {
        log_user_activity($owner_id, 'assign', 'building_managers', get_last_insert_id(), null,
                         array('building_id' => $building_id, 'manager_id' => $manager_id));
        
        return array('success' => true, 'message' => 'Manager assigned successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to assign manager');
    }
}

// Remove manager from building
function remove_manager_from_building($building_id, $manager_id, $owner_id) {
    // Verify ownership
    $owner_query = "SELECT building_id FROM buildings WHERE building_id = ? AND owner_id = ?";
    $owner_result = execute_prepared_query($owner_query, array($building_id, $owner_id), 'ii');
    
    if (!$owner_result || mysqli_num_rows($owner_result) == 0) {
        return array('success' => false, 'message' => 'Access denied');
    }
    
    // Deactivate assignment
    $query = "UPDATE building_managers 
              SET is_active = 0 
              WHERE building_id = ? AND manager_id = ?";
    $result = execute_prepared_query($query, array($building_id, $manager_id), 'ii');
    
    if ($result && get_affected_rows() > 0) {
        log_user_activity($owner_id, 'update', 'building_managers', null, null,
                         array('action' => 'removed_manager', 'building_id' => $building_id, 'manager_id' => $manager_id));
        
        return array('success' => true, 'message' => 'Manager removed successfully');
    } else {
        return array('success' => false, 'message' => 'Manager assignment not found');
    }
}

// Update building flats count
function update_building_flats_count($building_id) {
    $query = "UPDATE buildings 
              SET total_flats = (SELECT COUNT(*) FROM flats WHERE building_id = ?)
              WHERE building_id = ?";
    
    return execute_prepared_query($query, array($building_id, $building_id), 'ii');
}

// Get building statistics
function get_building_statistics($building_id) {
    $query = "SELECT 
                COUNT(f.flat_id) as total_flats,
                COUNT(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN 1 END) as occupied_flats,
                COUNT(CASE WHEN f.status = 'available' AND fa.assignment_id IS NULL THEN 1 END) as available_flats,
                COUNT(CASE WHEN f.status = 'maintenance' THEN 1 END) as maintenance_flats,
                COALESCE(SUM(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN f.base_rent END), 0) as monthly_rent_potential,
                COUNT(DISTINCT bm.manager_id) as assigned_managers
              FROM flats f
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id 
                        AND fa.status = 'confirmed' 
                        AND fa.actual_ended_at IS NULL
              LEFT JOIN buildings b ON f.building_id = b.building_id
              LEFT JOIN building_managers bm ON b.building_id = bm.building_id AND bm.is_active = 1
              WHERE f.building_id = ?";
    
    $result = execute_prepared_query($query, array($building_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $stats = fetch_single_row($result);
        
        // Calculate occupancy rate
        if ($stats['total_flats'] > 0) {
            $stats['occupancy_rate'] = round(($stats['occupied_flats'] / $stats['total_flats']) * 100, 1);
        } else {
            $stats['occupancy_rate'] = 0;
        }
        
        return $stats;
    }
    
    return null;
}

// Search buildings
function search_buildings($search_term, $user_id, $user_type) {
    $search_term = '%' . $search_term . '%';
    
    $query = "SELECT b.*, COUNT(f.flat_id) as total_flats,
                     COUNT(CASE WHEN fa.status = 'confirmed' AND fa.actual_ended_at IS NULL THEN 1 END) as occupied_flats
              FROM buildings b
              LEFT JOIN flats f ON b.building_id = f.building_id
              LEFT JOIN flat_assignments fa ON f.flat_id = fa.flat_id
              WHERE (b.building_name LIKE ? OR b.address LIKE ?)";
    
    $params = array($search_term, $search_term);
    $types = 'ss';
    
    // Add user-specific filters
    if ($user_type === 'owner') {
        $query .= " AND b.owner_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    } elseif ($user_type === 'manager') {
        $query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                                WHERE bm.building_id = b.building_id 
                                AND bm.manager_id = ? AND bm.is_active = 1)";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    $query .= " GROUP BY b.building_id ORDER BY b.building_name LIMIT 20";
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_all_rows($result) : array();
}

// Get building managers
function get_building_managers($building_id) {
    $query = "SELECT bm.*, up.full_name as manager_name, u.email as manager_email
              FROM building_managers bm
              JOIN users u ON bm.manager_id = u.user_id
              JOIN user_profiles up ON bm.manager_id = up.user_id
              WHERE bm.building_id = ? AND bm.is_active = 1
              ORDER BY bm.assigned_date DESC";
    
    $result = execute_prepared_query($query, array($building_id), 'i');
    
    return $result ? fetch_all_rows($result) : array();
}
?>