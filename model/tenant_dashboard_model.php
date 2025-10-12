<?php
// Tenant Dashboard Model
// All database operations for tenant dashboard

require_once 'database.php';

// Get complete dashboard data for tenant
function get_tenant_dashboard_data($user_id) {
    $data = array();
    
    // Get flat information
    $flat_query = "SELECT fa.assignment_id, fa.flat_id, fa.advance_balance, fa.confirmed_at,
                   f.flat_number, f.floor_number, f.base_rent,
                   b.building_id, b.building_name, b.address
                   FROM flat_assignments fa
                   JOIN flats f ON fa.flat_id = f.flat_id
                   JOIN buildings b ON f.building_id = b.building_id
                   WHERE fa.tenant_id = ? 
                   AND fa.status = 'confirmed' 
                   AND fa.actual_ended_at IS NULL
                   LIMIT 1";
    
    $flat_result = execute_prepared_query($flat_query, array($user_id), 'i');
    
    if ($flat_result && mysqli_num_rows($flat_result) > 0) {
        $data['flat_info'] = fetch_single_row($flat_result);
        $data['has_assignment'] = true;
        
        $flat_id = $data['flat_info']['flat_id'];
        
        // Get outstanding dues
        $dues_query = "SELECT COALESCE(SUM(remaining_amount), 0) as total_outstanding,
                       COUNT(*) as overdue_count
                       FROM tenant_dues
                       WHERE tenant_id = ? AND flat_id = ? AND remaining_amount > 0";
        
        $dues_result = execute_prepared_query($dues_query, array($user_id, $flat_id), 'ii');
        $dues = fetch_single_row($dues_result);
        
        $data['outstanding_dues'] = $dues['total_outstanding'];
        $data['overdue_count'] = $dues['overdue_count'];
        $data['advance_balance'] = $data['flat_info']['advance_balance'];
        
        // Get current month expense
        $current_month = date('Y-m-01');
        $current_query = "SELECT * FROM flat_expenses 
                          WHERE flat_id = ? AND billing_month = ?";
        $current_result = execute_prepared_query($current_query, array($flat_id, $current_month), 'is');
        
        if ($current_result && mysqli_num_rows($current_result) > 0) {
            $current_expense = fetch_single_row($current_result);
            
            // Get paid amount for this month
            $paid_query = "SELECT COALESCE(SUM(amount), 0) as paid
                           FROM payments
                           WHERE tenant_id = ? AND expense_id = ?";
            $paid_result = execute_prepared_query($paid_query, array($user_id, $current_expense['expense_id']), 'ii');
            $paid = fetch_single_row($paid_result);
            
            $data['current_month'] = array(
                'rent' => $current_expense['rent'],
                'utilities' => $current_expense['electric_bill'] + $current_expense['gas_bill'] + $current_expense['water_bill'],
                'service_charge' => $current_expense['service_charge'],
                'total' => $current_expense['total_amount'],
                'paid_amount' => $paid['paid'],
                'remaining' => $current_expense['total_amount'] - $paid['paid'],
                'due_date' => date('Y-m-15') // 15th of current month
            );
        } else {
            $data['current_month'] = null;
        }
        
        // Get last payment
        $last_payment_query = "SELECT amount, payment_date 
                               FROM payments
                               WHERE tenant_id = ? AND flat_id = ?
                               ORDER BY payment_date DESC
                               LIMIT 1";
        $last_payment_result = execute_prepared_query($last_payment_query, array($user_id, $flat_id), 'ii');
        
        if ($last_payment_result && mysqli_num_rows($last_payment_result) > 0) {
            $last_payment = fetch_single_row($last_payment_result);
            $data['last_payment_amount'] = $last_payment['amount'];
            $data['last_payment_date'] = $last_payment['payment_date'];
        } else {
            $data['last_payment_amount'] = 0;
            $data['last_payment_date'] = null;
        }
        
        // Get recent payments
        $recent_payments_query = "SELECT * FROM payments
                                  WHERE tenant_id = ? AND flat_id = ?
                                  ORDER BY payment_date DESC
                                  LIMIT 5";
        $recent_payments_result = execute_prepared_query($recent_payments_query, array($user_id, $flat_id), 'ii');
        $data['recent_payments'] = fetch_all_rows($recent_payments_result);
        
        // Get outstanding payments
        $outstanding_query = "SELECT td.*, fe.billing_month, fe.total_amount as total_due
                              FROM tenant_dues td
                              JOIN flat_expenses fe ON td.expense_id = fe.expense_id
                              WHERE td.tenant_id = ? AND td.flat_id = ? AND td.remaining_amount > 0
                              ORDER BY fe.billing_month DESC";
        $outstanding_result = execute_prepared_query($outstanding_query, array($user_id, $flat_id), 'ii');
        $data['outstanding_payments'] = fetch_all_rows($outstanding_result);
        
        // Get service requests
        $service_query = "SELECT * FROM service_requests
                          WHERE tenant_id = ? AND flat_id = ?
                          ORDER BY created_at DESC
                          LIMIT 5";
        $service_result = execute_prepared_query($service_query, array($user_id, $flat_id), 'ii');
        $data['service_requests'] = fetch_all_rows($service_result);
        $data['active_service_requests'] = count(array_filter($data['service_requests'], function($req) {
            return in_array($req['status'], array('pending', 'assigned', 'in_progress'));
        }));
        
        // Get recent activity
        $activity_query = "SELECT * FROM user_logs
                           WHERE user_id = ?
                           ORDER BY created_at DESC
                           LIMIT 10";
        $activity_result = execute_prepared_query($activity_query, array($user_id), 'i');
        $data['recent_activity'] = fetch_all_rows($activity_result);
        
    } else {
        $data['has_assignment'] = false;
        $data['flat_info'] = null;
        $data['outstanding_dues'] = 0;
        $data['advance_balance'] = 0;
        $data['overdue_count'] = 0;
    }
    
    return $data;
}

// Get pending assignment for tenant
function get_pending_assignment_for_tenant($user_id) {
    $query = "SELECT fa.*, f.flat_number, f.floor_number, b.building_name,
              TIMESTAMPDIFF(SECOND, NOW(), fa.expires_at) as seconds_remaining
              FROM flat_assignments fa
              JOIN flats f ON fa.flat_id = f.flat_id
              JOIN buildings b ON f.building_id = b.building_id
              WHERE fa.tenant_id = ? 
              AND fa.status = 'pending'
              AND fa.expires_at > NOW()
              LIMIT 1";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result);
    }
    
    return null;
}

// Confirm flat assignment with transaction verification
function confirm_flat_assignment($assignment_id, $user_id, $transaction_number) {
    // Get assignment details
    $query = "SELECT fa.*, f.flat_number, b.building_name 
              FROM flat_assignments fa
              JOIN flats f ON fa.flat_id = f.flat_id
              JOIN buildings b ON f.building_id = b.building_id
              WHERE fa.assignment_id = ? AND fa.tenant_id = ? AND fa.status = 'pending'";
    $result = execute_prepared_query($query, array($assignment_id, $user_id), 'ii');
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return array('success' => false, 'message' => 'Assignment not found or already confirmed');
    }
    
    $assignment = fetch_single_row($result);
    
    // Check if expired
    if (strtotime($assignment['expires_at']) < time()) {
        return array('success' => false, 'message' => 'Assignment has expired');
    }
    
    // CRITICAL: Check if transaction number already used
    $check_used = "SELECT verification_id FROM transaction_verifications WHERE transaction_number = ?";
    $used_result = execute_prepared_query($check_used, array($transaction_number), 's');
    
    if ($used_result && mysqli_num_rows($used_result) > 0) {
        return array('success' => false, 'message' => 'This transaction number has already been used');
    }
    
    // TODO: Here you would normally verify the transaction with payment gateway/bank
    // For now, we'll assume the transaction is valid
    // In real implementation, you'd call payment API to verify:
    // - Transaction exists
    // - Amount matches expected amount
    // - Transaction is successful
    
    $expected_amount = $assignment['advance_amount'];
    $verified_amount = $expected_amount; // In real system, get this from payment API
    
    // Check if amount matches (with small tolerance for fees)
    $tolerance = 50; // Allow 50 Taka difference for transaction fees
    if (abs($verified_amount - $expected_amount) > $tolerance) {
        return array(
            'success' => false, 
            'message' => 'Payment amount does not match. Expected: ৳' . number_format($expected_amount, 2) . 
                        ', Found: ৳' . number_format($verified_amount, 2)
        );
    }
    
    begin_transaction();
    
    try {
        // Record transaction verification (BEFORE confirming assignment)
        $verify_query = "INSERT INTO transaction_verifications 
                        (transaction_number, assignment_id, tenant_id, expected_amount, verified_amount, 
                         payment_method, verification_status, notes)
                        VALUES (?, ?, ?, ?, ?, 'advance_payment', 'verified', 'Assignment confirmation')";
        
        $verify_result = execute_prepared_query($verify_query, 
            array($transaction_number, $assignment_id, $user_id, $expected_amount, $verified_amount),
            'siidд');
        
        if (!$verify_result) {
            throw new Exception('Failed to record transaction verification');
        }
        
        // Update assignment to confirmed
        $update_query = "UPDATE flat_assignments 
                         SET status = 'confirmed', 
                             confirmed_at = NOW(),
                             payment_transaction = ?
                         WHERE assignment_id = ?";
        execute_prepared_query($update_query, array($transaction_number, $assignment_id), 'si');
        
        // Update flat status to occupied
        $flat_query = "UPDATE flats SET status = 'occupied' WHERE flat_id = ?";
        execute_prepared_query($flat_query, array($assignment['flat_id']), 'i');
        
        // Create initial payment record in payments table
        $payment_query = "INSERT INTO payments 
                         (transaction_number, tenant_id, flat_id, amount, method, payment_type, 
                          payment_date, remarks, is_verified)
                         VALUES (?, ?, ?, ?, 'bank_transfer', 'advance', NOW(), 
                                'Initial advance payment for flat assignment', 1)";
        execute_prepared_query($payment_query,
            array($transaction_number, $user_id, $assignment['flat_id'], $verified_amount),
            'siid');
        
        // Create notification
        create_tenant_notification($user_id, 'assignment', 
            'Assignment Confirmed',
            'Your flat assignment for ' . $assignment['building_name'] . ' - ' . 
            $assignment['flat_number'] . ' has been confirmed. Welcome to your new home!',
            'flat_assignments', $assignment_id);
        
        // Log activity
        log_user_activity($user_id, 'update', 'flat_assignments', $assignment_id, null,
            array('action' => 'assignment_confirmed', 'transaction' => $transaction_number, 
                  'verified_amount' => $verified_amount));
        
        commit_transaction();
        
        return array('success' => true, 'message' => 'Assignment confirmed successfully! Welcome home!');
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Assignment confirmation error: " . $e->getMessage());
        return array('success' => false, 'message' => 'Failed to confirm assignment: ' . $e->getMessage());
    }
}

// Record tenant payment
function record_tenant_payment($user_id, $payment_type, $amount, $method, $transaction_number, $payment_date, $remarks) {
    // Get tenant's flat
    $flat_query = "SELECT flat_id FROM flat_assignments 
                   WHERE tenant_id = ? AND status = 'confirmed' AND actual_ended_at IS NULL
                   LIMIT 1";
    $flat_result = execute_prepared_query($flat_query, array($user_id), 'i');
    
    if (!$flat_result || mysqli_num_rows($flat_result) == 0) {
        return array('success' => false, 'message' => 'No active flat assignment');
    }
    
    $flat = fetch_single_row($flat_result);
    $flat_id = $flat['flat_id'];
    
    // Insert payment
    $payment_query = "INSERT INTO payments 
                      (transaction_number, tenant_id, flat_id, amount, method, payment_type, payment_date, remarks, is_verified)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    
    $result = execute_prepared_query($payment_query,
        array($transaction_number, $user_id, $flat_id, $amount, $method, $payment_type, $payment_date, $remarks),
        'siidssss');
    
    if ($result) {
        // Create notification
        create_tenant_notification($user_id, 'payment',
            'Payment Submitted',
            'Your payment of ৳' . number_format($amount, 2) . ' has been submitted for verification.',
            'payments', get_last_insert_id());
        
        // Log activity
        log_user_activity($user_id, 'payment', 'payments', get_last_insert_id(), null,
            array('amount' => $amount, 'type' => $payment_type));
        
        return array('success' => true, 'message' => 'Payment submitted successfully. Awaiting verification.');
    }
    
    return array('success' => false, 'message' => 'Failed to record payment');
}

// Get tenant flat details
function get_tenant_flat_details($user_id) {
    $query = "SELECT fa.*, f.*, b.building_name, b.address
              FROM flat_assignments fa
              JOIN flats f ON fa.flat_id = f.flat_id
              JOIN buildings b ON f.building_id = b.building_id
              WHERE fa.tenant_id = ? AND fa.status = 'confirmed' AND fa.actual_ended_at IS NULL
              LIMIT 1";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result);
    }
    
    return null;
}

// Get tenant payment history
function get_tenant_payment_history($user_id) {
    $query = "SELECT * FROM payments
              WHERE tenant_id = ?
              ORDER BY payment_date DESC";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    return fetch_all_rows($result);
}

// Request move out
function request_tenant_move_out($user_id, $move_out_date, $reason) {
    // Get active assignment
    $query = "SELECT assignment_id, flat_id FROM flat_assignments
              WHERE tenant_id = ? AND status = 'confirmed' AND actual_ended_at IS NULL
              LIMIT 1";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return array('success' => false, 'message' => 'No active assignment found');
    }
    
    $assignment = fetch_single_row($result);
    
    // Update assignment
    $update_query = "UPDATE flat_assignments 
                     SET move_out_date = ?, move_out_requested_at = NOW()
                     WHERE assignment_id = ?";
    
    $update_result = execute_prepared_query($update_query, array($move_out_date, $assignment['assignment_id']), 'si');
    
    if ($update_result) {
        // Create notification for owner/manager
        $notify_query = "SELECT b.owner_id FROM flats f 
                         JOIN buildings b ON f.building_id = b.building_id
                         WHERE f.flat_id = ?";
        $notify_result = execute_prepared_query($notify_query, array($assignment['flat_id']), 'i');
        $owner = fetch_single_row($notify_result);
        
        create_tenant_notification($owner['owner_id'], 'move_out',
            'Move Out Request',
            'Tenant has requested to move out on ' . $move_out_date,
            'flat_assignments', $assignment['assignment_id']);
        
        // Log activity
        log_user_activity($user_id, 'move_out_request', 'flat_assignments', $assignment['assignment_id'], null,
            array('move_out_date' => $move_out_date, 'reason' => $reason));
        
        return array('success' => true, 'message' => 'Move out request submitted successfully');
    }
    
    return array('success' => false, 'message' => 'Failed to submit request');
}

// Get tenant notifications
function get_tenant_notifications($user_id) {
    $query = "SELECT * FROM notifications
              WHERE user_id = ?
              ORDER BY created_at DESC
              LIMIT 20";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    return fetch_all_rows($result);
}

// Get unread notification count
function get_unread_notification_count($user_id) {
    $query = "SELECT COUNT(*) as count FROM notifications
              WHERE user_id = ? AND is_read = 0";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    $row = fetch_single_row($result);
    
    return $row['count'];
}

// Mark notification as read
function mark_notification_as_read($notification_id, $user_id) {
    $query = "UPDATE notifications 
              SET is_read = 1, read_at = NOW()
              WHERE notification_id = ? AND user_id = ?";
    
    return execute_prepared_query($query, array($notification_id, $user_id), 'ii');
}

// Mark all notifications as read (continued)
function mark_all_notifications_as_read($user_id) {
    $query = "UPDATE notifications 
              SET is_read = 1, read_at = NOW()
              WHERE user_id = ? AND is_read = 0";
    
    return execute_prepared_query($query, array($user_id), 'i');
}

// Get latest slip data
function get_latest_slip_data($user_id) {
    $query = "SELECT fe.*, f.flat_number, b.building_name, up.full_name as tenant_name
              FROM flat_expenses fe
              JOIN flats f ON fe.flat_id = f.flat_id
              JOIN buildings b ON f.building_id = b.building_id
              JOIN flat_assignments fa ON fa.flat_id = f.flat_id AND fa.tenant_id = ?
              JOIN user_profiles up ON up.user_id = ?
              WHERE fa.status = 'confirmed' AND fa.actual_ended_at IS NULL
              ORDER BY fe.billing_month DESC
              LIMIT 1";
    
    $result = execute_prepared_query($query, array($user_id, $user_id), 'ii');
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result);
    }
    
    return null;
}

// Create notification (if not already defined in tenant_model.php)
function create_tenant_notification($user_id, $type, $title, $message, $related_entity = null, $related_id = null) {
    $query = "INSERT INTO notifications (user_id, type, title, message, related_entity, related_id)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    return execute_prepared_query($query, 
        array($user_id, $type, $title, $message, $related_entity, $related_id),
        'issssi');
}

?>