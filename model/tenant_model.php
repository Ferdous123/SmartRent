<?php

require_once 'database.php';
function get_all_tenants($user_id, $us
er_type) {
    if ($user_type === 'owner') {
        $query = "SELECT DISTINCT u.user_id, u.username, u.email, up.full_name, 
                  up.profile_picture_url, uc.contact_number,
                  COUNT(DISTINCT fa.flat_id) as total_flats,
                  SUM(fa.advance_balance) as total_advance,
                  GROUP_CONCAT(DISTINCT CONCAT(b.building_name, ' - ', f.flat_number) SEPARATOR ', ') as flat_details,
                  MAX(fa.end_notice_sent_at) as has_end_notice,
                  MAX(fa.move_out_date) as nearest_move_out
                  FROM users u
                  JOIN user_profiles up ON u.user_id = up.user_id
                  LEFT JOIN user_contacts uc ON u.user_id = uc.user_id AND uc.contact_type = 'primary'
                  JOIN flat_assignments fa ON u.user_id = fa.tenant_id
                  JOIN flats f ON fa.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  WHERE u.user_type = 'tenant' 
                  AND fa.status = 'confirmed' 
                  AND fa.actual_ended_at IS NULL
                  AND b.owner_id = ?
                  GROUP BY u.user_id
                  ORDER BY up.full_name";
        
        $result = execute_prepared_query($query, array($user_id), 'i');
    } elseif ($user_type === 'manager') {
        $query = "SELECT DISTINCT u.user_id, u.username, u.email, up.full_name, 
                  up.profile_picture_url, uc.contact_number,
                  COUNT(DISTINCT fa.flat_id) as total_flats,
                  SUM(fa.advance_balance) as total_advance,
                  GROUP_CONCAT(DISTINCT CONCAT(b.building_name, ' - ', f.flat_number) SEPARATOR ', ') as flat_details,
                  MAX(fa.end_notice_sent_at) as has_end_notice,
                  MAX(fa.move_out_date) as nearest_move_out
                  FROM users u
                  JOIN user_profiles up ON u.user_id = up.user_id
                  LEFT JOIN user_contacts uc ON u.user_id = uc.user_id AND uc.contact_type = 'primary'
                  JOIN flat_assignments fa ON u.user_id = fa.tenant_id
                  JOIN flats f ON fa.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  JOIN building_managers bm ON b.building_id = bm.building_id
                  WHERE u.user_type = 'tenant' 
                  AND fa.status = 'confirmed' 
                  AND fa.actual_ended_at IS NULL
                  AND bm.manager_id = ? AND bm.is_active = 1
                  GROUP BY u.user_id
                  ORDER BY up.full_name";
        
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        return array();
    }
    
    return $result ? fetch_all_rows($result) : array();
}


function get_pending_assignments($user_id, $user_type) {
    $base_query = "SELECT fa.*, f.flat_number, f.floor_number, b.building_name,
                   u.username as tenant_username, up.full_name as tenant_name,
                   TIMESTAMPDIFF(SECOND, NOW(), fa.expires_at) as seconds_remaining
                   FROM flat_assignments fa
                   JOIN flats f ON fa.flat_id = f.flat_id
                   JOIN buildings b ON f.building_id = b.building_id
                   LEFT JOIN users u ON fa.tenant_id = u.user_id
                   LEFT JOIN user_profiles up ON fa.tenant_id = up.user_id
                   WHERE fa.status = 'pending' 
                   AND fa.expires_at > NOW()";
    
    if ($user_type === 'owner') {
        $query = $base_query . " AND b.owner_id = ? ORDER BY fa.expires_at ASC";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } elseif ($user_type === 'manager') {
        $query = $base_query . " AND EXISTS (SELECT 1 FROM building_managers bm 
                 WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)
                 ORDER BY fa.expires_at ASC";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        return array();
    }
    
    return $result ? fetch_all_rows($result) : array();
}


function get_tenant_details($tenant_id, $user_id, $user_type) {

    $query = "SELECT u.user_id, u.username, u.email, u.created_at,
              up.full_name, up.nid_number, up.permanent_address, up.profile_picture_url,
              uc.contact_number
              FROM users u
              JOIN user_profiles up ON u.user_id = up.user_id
              LEFT JOIN user_contacts uc ON u.user_id = uc.user_id AND uc.contact_type = 'primary'
              WHERE u.user_id = ? AND u.user_type = 'tenant'";
    
    $result = execute_prepared_query($query, array($tenant_id), 'i');
    
    if (!$result || $result->num_rows == 0) {
        return null;
    }
    
    $tenant = fetch_single_row($result);
    

    $flats_query = "SELECT fa.*, f.flat_number, f.floor_number, f.base_rent,
                    b.building_id, b.building_name, b.address,
                    TIMESTAMPDIFF(HOUR, NOW(), fa.end_notice_expires_at) as notice_hours_remaining
                    FROM flat_assignments fa
                    JOIN flats f ON fa.flat_id = f.flat_id
                    JOIN buildings b ON f.building_id = b.building_id
                    WHERE fa.tenant_id = ? 
                    AND fa.status = 'confirmed' 
                    AND fa.actual_ended_at IS NULL";
    
    if ($user_type === 'owner') {
        $flats_query .= " AND b.owner_id = ?";
        $flats_result = execute_prepared_query($flats_query, array($tenant_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $flats_query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                         WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)";
        $flats_result = execute_prepared_query($flats_query, array($tenant_id, $user_id), 'ii');
    } else {
        $flats_result = null;
    }
    
    $tenant['assigned_flats'] = $flats_result ? fetch_all_rows($flats_result) : array();
    

    $dues_query = "SELECT COALESCE(SUM(td.remaining_amount), 0) as total_outstanding
                   FROM tenant_dues td
                   JOIN flats f ON td.flat_id = f.flat_id
                   JOIN buildings b ON f.building_id = b.building_id
                   WHERE td.tenant_id = ?";
    
    if ($user_type === 'owner') {
        $dues_query .= " AND b.owner_id = ?";
        $dues_result = execute_prepared_query($dues_query, array($tenant_id, $user_id), 'ii');
    } elseif ($user_type === 'manager') {
        $dues_query .= " AND EXISTS (SELECT 1 FROM building_managers bm 
                        WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)";
        $dues_result = execute_prepared_query($dues_query, array($tenant_id, $user_id), 'ii');
    } else {
        $dues_result = null;
    }
    
    if ($dues_result && $dues_result->num_rows > 0) {
        $dues = fetch_single_row($dues_result);
        $tenant['total_outstanding'] = $dues['total_outstanding'];
    } else {
        $tenant['total_outstanding'] = 0;
    }
    
    return $tenant;
}


function generate_flat_otp($flat_id, $advance_amount, $assigned_by) {

    $check_query = "SELECT flat_id FROM flats WHERE flat_id = ? AND status = 'available'";
    $check_result = execute_prepared_query($check_query, array($flat_id), 'i');
    
    if (!$check_result || $check_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Flat is not available');
    }
    

    $otp_code = generate_otp_code();
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    

    $query = "INSERT INTO flat_assignments 
              (flat_id, otp_code, assigned_by, assignment_type, advance_amount, advance_balance, expires_at)
              VALUES (?, ?, ?, 'otp', ?, ?, ?)";
    
    $result = execute_prepared_query($query, 
        array($flat_id, $otp_code, $assigned_by, $advance_amount, $advance_amount, $expires_at),
        'isiids');
    
    if ($result) {
        log_user_activity($assigned_by, 'create', 'flat_assignments', get_last_insert_id(), null,
            array('flat_id' => $flat_id, 'otp_code' => $otp_code, 'advance_amount' => $advance_amount));
        
        return array(
            'success' => true, 
            'message' => 'OTP generated successfully',
            'otp_code' => $otp_code,
            'expires_at' => $expires_at
        );
    }
    
    return array('success' => false, 'message' => 'Failed to generate OTP');
}


function assign_tenant_direct($flat_id, $tenant_id, $advance_amount, $assigned_by, $auto_confirm = true) {

    $check_query = "SELECT flat_id FROM flats WHERE flat_id = ? AND status = 'available'";
    $check_result = execute_prepared_query($check_query, array($flat_id), 'i');
    
    if (!$check_result || $check_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Flat is not available');
    }
    

    $tenant_query = "SELECT user_id FROM users WHERE user_id = ? AND user_type = 'tenant'";
    $tenant_result = execute_prepared_query($tenant_query, array($tenant_id), 'i');
    
    if (!$tenant_result || $tenant_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Tenant not found');
    }
    
    begin_transaction();
    
    try {
        if ($auto_confirm) {

            $query = "INSERT INTO flat_assignments 
                      (flat_id, tenant_id, assigned_by, assignment_type, advance_amount, advance_balance, status, confirmed_at)
                      VALUES (?, ?, ?, 'direct', ?, ?, 'confirmed', NOW())";
            
            $result = execute_prepared_query($query,
                array($flat_id, $tenant_id, $assigned_by, $advance_amount, $advance_amount),
                'iiidd');
            
            if ($result) {
                $assignment_id = get_last_insert_id();
                

                $update_flat = "UPDATE flats SET status = 'occupied' WHERE flat_id = ?";
                execute_prepared_query($update_flat, array($flat_id), 'i');
                
                commit_transaction();
                

                create_tenant_notification($tenant_id, 'assignment', 
                    'Flat Assignment Confirmed', 
                    'You have been assigned to a flat. Check your dashboard for details.',
                    'flat_assignments', $assignment_id);
                
                log_user_activity($assigned_by, 'assign', 'flat_assignments', $assignment_id, null,
                    array('flat_id' => $flat_id, 'tenant_id' => $tenant_id, 'advance_amount' => $advance_amount, 'auto_confirmed' => true));
                
                return array('success' => true, 'message' => 'Tenant assigned and confirmed successfully!');
            }
        } else {

            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $query = "INSERT INTO flat_assignments 
                      (flat_id, tenant_id, assigned_by, assignment_type, advance_amount, advance_balance, expires_at)
                      VALUES (?, ?, ?, 'direct', ?, ?, ?)";
            
            $result = execute_prepared_query($query,
                array($flat_id, $tenant_id, $assigned_by, $advance_amount, $advance_amount, $expires_at),
                'iiiids');
            
            if ($result) {
                $assignment_id = get_last_insert_id();
                
                commit_transaction();
                

                create_tenant_notification($tenant_id, 'assignment', 
                    'Flat Assignment Pending', 
                    'You have been assigned a flat. Please confirm within 24 hours by providing advance payment transaction number.',
                    'flat_assignments', $assignment_id);
                
                log_user_activity($assigned_by, 'assign', 'flat_assignments', $assignment_id, null,
                    array('flat_id' => $flat_id, 'tenant_id' => $tenant_id, 'advance_amount' => $advance_amount));
                
                return array('success' => true, 'message' => 'Tenant assigned successfully. Awaiting confirmation.');
            }
        }
        
        throw new Exception('Failed to create assignment');
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => $e->getMessage());
    }
}


function generate_tenant_credentials($flat_id, $advance_amount, $assigned_by) {

    $check_query = "SELECT flat_id FROM flats WHERE flat_id = ? AND status = 'available'";
    $check_result = execute_prepared_query($check_query, array($flat_id), 'i');
    
    if (!$check_result || $check_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Flat is not available');
    }
    
    begin_transaction();
    
    try {

        $username = 'TEN' . time() . rand(100, 999);
        $password = 'PASS' . rand(1000, 9999);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        

        $user_query = "INSERT INTO users (username, password_hash, user_type, is_temporary) 
                       VALUES (?, ?, 'tenant', 1)";
        $user_result = execute_prepared_query($user_query, array($username, $password_hash), 'ss');
        
        if (!$user_result) {
            throw new Exception('Failed to create tenant user');
        }
        
        $tenant_id = get_last_insert_id();
        

        $profile_query = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, 'Temporary Tenant')";
        execute_prepared_query($profile_query, array($tenant_id), 'i');
        
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        

        $assign_query = "INSERT INTO flat_assignments 
                        (flat_id, tenant_id, assigned_by, assignment_type, advance_amount, advance_balance, status, confirmed_at)
                        VALUES (?, ?, ?, 'generated', ?, ?, 'confirmed', NOW())";

        $assign_result = execute_prepared_query($assign_query,
            array($flat_id, $tenant_id, $assigned_by, $advance_amount, $advance_amount),
            'iiidd');

        if (!$assign_result) {
            throw new Exception('Failed to create assignment');
        }


        $update_flat = "UPDATE flats SET status = 'occupied' WHERE flat_id = ?";
        execute_prepared_query($update_flat, array($flat_id), 'i');
        
        if (!$assign_result) {
            throw new Exception('Failed to create assignment');
        }
        
        commit_transaction();
        
        log_user_activity($assigned_by, 'create', 'users', $tenant_id, null,
            array('type' => 'generated_tenant', 'flat_id' => $flat_id));
        
        return array(
            'success' => true,
            'message' => 'Tenant credentials generated successfully',
            'username' => $username,
            'password' => $password,
            'tenant_id' => $tenant_id
        );
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => $e->getMessage());
    }
}


function move_tenant_to_flat($current_assignment_id, $new_flat_id, $transfer_advance, $additional_advance, $moved_by) {

    $current_query = "SELECT * FROM flat_assignments WHERE assignment_id = ? AND status = 'confirmed'";
    $current_result = execute_prepared_query($current_query, array($current_assignment_id), 'i');
    
    if (!$current_result || $current_result->num_rows == 0) {
        return array('success' => false, 'message' => 'Current assignment not found');
    }
    
    $current = fetch_single_row($current_result);
    

    $check_query = "SELECT flat_id FROM flats WHERE flat_id = ? AND status = 'available'";
    $check_result = execute_prepared_query($check_query, array($new_flat_id), 'i');
    
    if (!$check_result || $check_result->num_rows == 0) {
        return array('success' => false, 'message' => 'New flat is not available');
    }
    
    begin_transaction();
    
    try {

        $end_query = "UPDATE flat_assignments SET actual_ended_at = NOW(), status = 'ended' WHERE assignment_id = ?";
        execute_prepared_query($end_query, array($current_assignment_id), 'i');
        

        $update_old_flat = "UPDATE flats SET status = 'available' WHERE flat_id = ?";
        execute_prepared_query($update_old_flat, array($current['flat_id']), 'i');
        

        $new_advance_balance = $transfer_advance + $additional_advance;
        

        $new_query = "INSERT INTO flat_assignments 
                      (flat_id, tenant_id, assigned_by, assignment_type, advance_amount, advance_balance, status, confirmed_at)
                      VALUES (?, ?, ?, 'direct', ?, ?, 'confirmed', NOW())";
        
        $new_result = execute_prepared_query($new_query,
            array($new_flat_id, $current['tenant_id'], $moved_by, $new_advance_balance, $new_advance_balance),
            'iiidd');
        
        if (!$new_result) {
            throw new Exception('Failed to create new assignment');
        }
        

        $update_new_flat = "UPDATE flats SET status = 'occupied' WHERE flat_id = ?";
        execute_prepared_query($update_new_flat, array($new_flat_id), 'i');
        
        commit_transaction();
        
        log_user_activity($moved_by, 'update', 'flat_assignments', get_last_insert_id(), 
            array('old_flat' => $current['flat_id']),
            array('new_flat' => $new_flat_id, 'transferred_advance' => $transfer_advance));
        
        return array('success' => true, 'message' => 'Tenant moved successfully');
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => $e->getMessage());
    }
}


function send_end_tenancy_notice($assignment_id, $sent_by) {

    $query = "SELECT fa.*, f.flat_number, b.building_name, u.email, up.full_name
              FROM flat_assignments fa
              JOIN flats f ON fa.flat_id = f.flat_id
              JOIN buildings b ON f.building_id = b.building_id
              JOIN users u ON fa.tenant_id = u.user_id
              JOIN user_profiles up ON fa.tenant_id = up.user_id
              WHERE fa.assignment_id = ? AND fa.status = 'confirmed'";
    
    $result = execute_prepared_query($query, array($assignment_id), 'i');
    
    if (!$result || $result->num_rows == 0) {
        return array('success' => false, 'message' => 'Assignment not found');
    }
    
    $assignment = fetch_single_row($result);
    
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Update assignment with end notice
    $update_query = "UPDATE flat_assignments 
                     SET end_notice_sent_at = NOW(), 
                         end_notice_expires_at = ?, 
                         tenant_response = 'pending',
                         end_notice_sent_by = ?
                     WHERE assignment_id = ?";
    
    $update_result = execute_prepared_query($update_query, array($expires_at, $sent_by, $assignment_id), 'sii');
    
    if ($update_result) {
        // Create notification
        create_tenant_notification($assignment['tenant_id'], 'move_out',
            'End Tenancy Notice',
            'Your tenancy for ' . $assignment['building_name'] . ' - ' . $assignment['flat_number'] . ' will be ended. Please respond within 24 hours.',
            'flat_assignments', $assignment_id);
        
        log_user_activity($sent_by, 'update', 'flat_assignments', $assignment_id, null,
            array('action' => 'end_notice_sent'));
        
        return array('success' => true, 'message' => 'End tenancy notice sent successfully');
    }
    
    return array('success' => false, 'message' => 'Failed to send notice');
}

// Cancel end tenancy notice
function cancel_end_notice($assignment_id, $cancelled_by) {
    $query = "UPDATE flat_assignments 
              SET end_notice_sent_at = NULL,
                  end_notice_expires_at = NULL,
                  tenant_response = NULL,
                  tenant_response_at = NULL,
                  end_notice_sent_by = NULL
              WHERE assignment_id = ?";
    
    $result = execute_prepared_query($query, array($assignment_id), 'i');
    
    if ($result) {
        log_user_activity($cancelled_by, 'update', 'flat_assignments', $assignment_id, null,
            array('action' => 'end_notice_cancelled'));
        
        return array('success' => true, 'message' => 'End notice cancelled');
    }
    
    return array('success' => false, 'message' => 'Failed to cancel notice');
}

// Process end tenancy (after confirmation or timeout)
function process_end_tenancy($assignment_id, $processed_by) {
    $query = "SELECT * FROM flat_assignments WHERE assignment_id = ?";
    $result = execute_prepared_query($query, array($assignment_id), 'i');
    
    if (!$result || $result->num_rows == 0) {
        return array('success' => false, 'message' => 'Assignment not found');
    }
    
    $assignment = fetch_single_row($result);
    
    begin_transaction();
    
    try {
        // End assignment
        $update_query = "UPDATE flat_assignments 
                         SET actual_ended_at = NOW(), 
                             status = 'ended'
                         WHERE assignment_id = ?";
        execute_prepared_query($update_query, array($assignment_id), 'i');
        
        // Update flat status
        $flat_query = "UPDATE flats SET status = 'available' WHERE flat_id = ?";
        execute_prepared_query($flat_query, array($assignment['flat_id']), 'i');
        
        commit_transaction();
        
        log_user_activity($processed_by, 'update', 'flat_assignments', $assignment_id, null,
            array('action' => 'tenancy_ended'));
        
        return array('success' => true, 'message' => 'Tenancy ended successfully');
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => $e->getMessage());
    }
}

// Create notification for tenant
function create_tenant_notification($user_id, $type, $title, $message, $related_entity = null, $related_id = null) {
    $query = "INSERT INTO notifications (user_id, type, title, message, related_entity, related_id)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    return execute_prepared_query($query, 
        array($user_id, $type, $title, $message, $related_entity, $related_id),
        'issssi');
}

// Get tenants with outstanding payments
function get_tenants_with_outstanding($user_id, $user_type) {
    $base_query = "SELECT DISTINCT u.user_id, up.full_name, uc.contact_number,
                   SUM(td.remaining_amount) as total_outstanding,
                   COUNT(DISTINCT td.due_id) as overdue_count,
                   MIN(td.due_date) as earliest_due_date,
                   DATEDIFF(CURDATE(), MIN(td.due_date)) as days_overdue
                   FROM users u
                   JOIN user_profiles up ON u.user_id = up.user_id
                   LEFT JOIN user_contacts uc ON u.user_id = uc.user_id AND uc.contact_type = 'primary'
                   JOIN tenant_dues td ON u.user_id = td.tenant_id
                   JOIN flats f ON td.flat_id = f.flat_id
                   JOIN buildings b ON f.building_id = b.building_id
                   WHERE u.user_type = 'tenant' 
                   AND td.remaining_amount > 0";
    
    if ($user_type === 'owner') {
        $query = $base_query . " AND b.owner_id = ? GROUP BY u.user_id ORDER BY days_overdue DESC";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } elseif ($user_type === 'manager') {
        $query = $base_query . " AND EXISTS (SELECT 1 FROM building_managers bm 
                 WHERE bm.building_id = b.building_id AND bm.manager_id = ? AND bm.is_active = 1)
                 GROUP BY u.user_id ORDER BY days_overdue DESC";
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        return array();
    }
    
    return $result ? fetch_all_rows($result) : array();
}

// Update tenant profile
function update_tenant_profile($tenant_id, $full_name, $nid_number, $permanent_address, $contact_number, $updated_by) {
    begin_transaction();
    
    try {
        // Update profile
        $profile_query = "UPDATE user_profiles 
                          SET full_name = ?, nid_number = ?, permanent_address = ?
                          WHERE user_id = ?";
        execute_prepared_query($profile_query, array($full_name, $nid_number, $permanent_address, $tenant_id), 'sssi');
        
        // Update contact
        if ($contact_number) {
            $contact_query = "INSERT INTO user_contacts (user_id, contact_number, contact_type)
                              VALUES (?, ?, 'primary')
                              ON DUPLICATE KEY UPDATE contact_number = VALUES(contact_number)";
            execute_prepared_query($contact_query, array($tenant_id, $contact_number), 'is');
        }
        
        commit_transaction();
        
        log_user_activity($updated_by, 'update', 'user_profiles', $tenant_id, null,
            array('full_name' => $full_name));
        
        return array('success' => true, 'message' => 'Tenant profile updated successfully');
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => $e->getMessage());
    }
}
?>