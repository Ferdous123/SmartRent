<?php
// Notification Model for SmartRent
// All notification-related database operations
require_once 'database.php';

// Create new notification
function create_notification($user_id, $type, $title, $message, $related_entity = null, $related_id = null) {
    $query = "INSERT INTO notifications (user_id, type, title, message, related_entity, related_id) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($user_id, $type, $title, $message, $related_entity, $related_id);
    $types = 'issssi';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        return array('success' => true, 'notification_id' => get_last_insert_id());
    } else {
        return array('success' => false, 'message' => 'Failed to create notification');
    }
}

// Create bulk notifications for multiple users
function create_bulk_notifications($user_ids, $type, $title, $message, $related_entity = null, $related_id = null) {
    if (empty($user_ids) || !is_array($user_ids)) {
        return array('success' => false, 'message' => 'No user IDs provided');
    }
    
    begin_transaction();
    
    try {
        $success_count = 0;
        
        foreach ($user_ids as $user_id) {
            $result = create_notification($user_id, $type, $title, $message, $related_entity, $related_id);
            if ($result['success']) {
                $success_count++;
            }
        }
        
        commit_transaction();
        
        return array(
            'success' => true, 
            'message' => "Created $success_count notifications successfully",
            'count' => $success_count
        );
        
    } catch (Exception $e) {
        rollback_transaction();
        return array('success' => false, 'message' => 'Failed to create bulk notifications: ' . $e->getMessage());
    }
}

// Get notifications for user
function get_user_notifications($user_id, $limit = 10, $unread_only = false) {
    $query = "SELECT * FROM notifications WHERE user_id = ?";
    $params = array($user_id);
    $types = 'i';
    
    if ($unread_only) {
        $query .= " AND is_read = 0";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
    }
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_all_rows($result) : array();
}

// Get unread notification count
function get_unread_notification_count($user_id) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result)['count'];
    }
    
    return 0;
}

// Mark notification as read
function mark_notification_read($notification_id, $user_id = null) {
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?";
    $params = array($notification_id);
    $types = 'i';
    
    // Add user verification if provided
    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result && get_affected_rows() > 0) {
        return array('success' => true, 'message' => 'Notification marked as read');
    } else {
        return array('success' => false, 'message' => 'Notification not found or already read');
    }
}

// Mark all notifications as read for user
function mark_all_notifications_read($user_id) {
    $query = "UPDATE notifications SET is_read = 1, read_at = NOW() 
              WHERE user_id = ? AND is_read = 0";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result) {
        $affected_rows = get_affected_rows();
        return array(
            'success' => true, 
            'message' => "Marked $affected_rows notifications as read",
            'count' => $affected_rows
        );
    } else {
        return array('success' => false, 'message' => 'Failed to mark notifications as read');
    }
}

// Delete notification
function delete_notification($notification_id, $user_id = null) {
    $query = "DELETE FROM notifications WHERE notification_id = ?";
    $params = array($notification_id);
    $types = 'i';
    
    // Add user verification if provided
    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result && get_affected_rows() > 0) {
        return array('success' => true, 'message' => 'Notification deleted successfully');
    } else {
        return array('success' => false, 'message' => 'Notification not found');
    }
}

// Delete old notifications (cleanup)
function cleanup_old_notifications($days_old = 30) {
    $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $result = execute_prepared_query($query, array($days_old), 'i');
    
    if ($result) {
        $affected_rows = get_affected_rows();
        return array(
            'success' => true,
            'message' => "Cleaned up $affected_rows old notifications",
            'count' => $affected_rows
        );
    } else {
        return array('success' => false, 'message' => 'Failed to cleanup old notifications');
    }
}

// Notification helper functions for specific events

// Payment notification
function notify_payment_received($tenant_id, $amount, $transaction_number, $payment_type = 'rent') {
    $title = 'Payment Received';
    $message = "Your payment of ৳" . number_format($amount, 2) . " has been received successfully. Transaction: $transaction_number";
    
    return create_notification($tenant_id, 'payment', $title, $message, 'payment', null);
}

// Flat assignment notification
function notify_flat_assignment($tenant_id, $flat_info, $advance_amount, $expires_at) {
    $title = 'Flat Assignment';
    $expires_readable = date('M d, Y H:i', strtotime($expires_at));
    $message = "You have been assigned to $flat_info. Please confirm with advance payment of ৳" . number_format($advance_amount, 2) . " by $expires_readable";
    
    return create_notification($tenant_id, 'assignment', $title, $message, 'assignment', null);
}

// OTP notification
function notify_otp_generated($tenant_id, $otp_code, $flat_info, $expires_at) {
    $title = 'Flat Assignment OTP';
    $expires_readable = date('M d, Y H:i', strtotime($expires_at));
    $message = "Your OTP for $flat_info is: $otp_code. Valid until $expires_readable";
    
    return create_notification($tenant_id, 'info', $title, $message, 'otp', null);
}

// Service request notification
function notify_service_request_update($tenant_id, $request_type, $status, $assigned_to = null) {
    $title = 'Service Request Update';
    $message = "Your $request_type request has been " . str_replace('_', ' ', $status);
    
    if ($assigned_to) {
        $message .= " and assigned to $assigned_to";
    }
    
    return create_notification($tenant_id, 'info', $title, $message, 'service_request', null);
}

// Move out notification
function notify_move_out_request($manager_id, $tenant_name, $flat_info, $move_out_date) {
    $title = 'Move Out Request';
    $message = "$tenant_name has requested to move out from $flat_info on " . date('M d, Y', strtotime($move_out_date));
    
    return create_notification($manager_id, 'move_out', $title, $message, 'move_out', null);
}

// Overdue payment notification
function notify_overdue_payment($tenant_id, $amount, $days_overdue, $flat_info) {
    $title = 'Payment Overdue';
    $message = "Your payment of ৳" . number_format($amount, 2) . " for $flat_info is $days_overdue days overdue. Please make payment immediately.";
    
    return create_notification($tenant_id, 'warning', $title, $message, 'payment', null);
}

// Upcoming payment due notification
function notify_payment_due_soon($tenant_id, $amount, $due_date, $flat_info) {
    $title = 'Payment Due Soon';
    $due_readable = date('M d, Y', strtotime($due_date));
    $message = "Your payment of ৳" . number_format($amount, 2) . " for $flat_info is due on $due_readable";
    
    return create_notification($tenant_id, 'info', $title, $message, 'payment', null);
}

// Building notice notification
function notify_building_notice($building_id, $title, $message, $sender_id) {
    // Get all tenants in the building
    $tenant_query = "SELECT DISTINCT fa.tenant_id
                     FROM flat_assignments fa
                     JOIN flats f ON fa.flat_id = f.flat_id
                     WHERE f.building_id = ? 
                     AND fa.status = 'confirmed' 
                     AND fa.actual_ended_at IS NULL";
    
    $tenant_result = execute_prepared_query($tenant_query, array($building_id), 'i');
    
    if ($tenant_result) {
        $tenant_ids = array();
        while ($row = fetch_single_row($tenant_result)) {
            $tenant_ids[] = $row['tenant_id'];
        }
        
        if (!empty($tenant_ids)) {
            return create_bulk_notifications($tenant_ids, 'info', $title, $message, 'building_notice', $building_id);
        }
    }
    
    return array('success' => false, 'message' => 'No tenants found in building');
}

// Manager notification for new tenant
function notify_manager_new_tenant($manager_id, $tenant_name, $flat_info) {
    $title = 'New Tenant Assigned';
    $message = "$tenant_name has been assigned to $flat_info";
    
    return create_notification($manager_id, 'info', $title, $message, 'tenant_assignment', null);
}

// Owner notification for new manager
function notify_owner_new_manager($owner_id, $manager_name, $building_name) {
    $title = 'New Manager Assigned';
    $message = "$manager_name has been assigned as manager for $building_name";
    
    return create_notification($owner_id, 'info', $title, $message, 'manager_assignment', null);
}

// System maintenance notification
function notify_system_maintenance($user_ids, $maintenance_date, $duration) {
    $title = 'Scheduled Maintenance';
    $maintenance_readable = date('M d, Y H:i', strtotime($maintenance_date));
    $message = "System maintenance is scheduled for $maintenance_readable. Expected duration: $duration. The system may be temporarily unavailable.";
    
    return create_bulk_notifications($user_ids, 'warning', $title, $message, 'maintenance', null);
}

// Get notification statistics
function get_notification_stats($user_id = null) {
    if ($user_id) {
        // Stats for specific user
        $query = "SELECT 
                    COUNT(*) as total_notifications,
                    COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count,
                    COUNT(CASE WHEN type = 'payment' THEN 1 END) as payment_notifications,
                    COUNT(CASE WHEN type = 'warning' THEN 1 END) as warning_notifications,
                    COUNT(CASE WHEN type = 'info' THEN 1 END) as info_notifications,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_count
                  FROM notifications 
                  WHERE user_id = ?";
        
        $result = execute_prepared_query($query, array($user_id), 'i');
    } else {
        // System-wide stats
        $query = "SELECT 
                    COUNT(*) as total_notifications,
                    COUNT(DISTINCT user_id) as total_recipients,
                    COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count,
                    COUNT(CASE WHEN type = 'payment' THEN 1 END) as payment_notifications,
                    COUNT(CASE WHEN type = 'warning' THEN 1 END) as warning_notifications,
                    COUNT(CASE WHEN type = 'info' THEN 1 END) as info_notifications,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_count
                  FROM notifications";
        
        $result = execute_query($query);
    }
    
    return $result ? fetch_single_row($result) : null;
}

// Search notifications
function search_notifications($user_id, $search_term, $type = null, $limit = 20) {
    $search_term = '%' . $search_term . '%';
    
    $query = "SELECT * FROM notifications 
              WHERE user_id = ? 
              AND (title LIKE ? OR message LIKE ?)";
    $params = array($user_id, $search_term, $search_term);
    $types = 'iss';
    
    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $query .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_all_rows($result) : array();
}

// Get notifications by type
function get_notifications_by_type($user_id, $type, $limit = 10) {
    $query = "SELECT * FROM notifications 
              WHERE user_id = ? AND type = ?
              ORDER BY created_at DESC LIMIT ?";
    
    $result = execute_prepared_query($query, array($user_id, $type, $limit), 'isi');
    
    return $result ? fetch_all_rows($result) : array();
}

// Update notification (for editing)
function update_notification($notification_id, $title, $message, $user_id = null) {
    $query = "UPDATE notifications 
              SET title = ?, message = ?
              WHERE notification_id = ?";
    $params = array($title, $message, $notification_id);
    $types = 'ssi';
    
    // Add user verification if provided
    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result && get_affected_rows() > 0) {
        return array('success' => true, 'message' => 'Notification updated successfully');
    } else {
        return array('success' => false, 'message' => 'Notification not found or no changes made');
    }
}

// Send immediate email notification (if email system is integrated)
function send_email_notification($user_id, $subject, $message, $priority = 'normal') {
    // Get user email
    $user_query = "SELECT u.email, up.full_name 
                   FROM users u
                   JOIN user_profiles up ON u.user_id = up.user_id
                   WHERE u.user_id = ? AND u.is_active = 1";
    
    $user_result = execute_prepared_query($user_query, array($user_id), 'i');
    
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_data = fetch_single_row($user_result);
        
        // Here you would integrate with your email service
        // For now, we'll just log the email attempt
        error_log("Email Notification: To: {$user_data['email']}, Subject: $subject");
        
        return array(
            'success' => true, 
            'message' => 'Email notification queued',
            'recipient' => $user_data['email']
        );
    } else {
        return array('success' => false, 'message' => 'User not found or inactive');
    }
}

// Get recent notifications for dashboard
function get_recent_notifications_for_dashboard($user_id, $limit = 5) {
    $query = "SELECT notification_id, type, title, message, is_read, created_at,
                     CASE 
                         WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'just_now'
                         WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'today'
                         WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'this_week'
                         ELSE 'older'
                     END as time_category
              FROM notifications 
              WHERE user_id = ?
              ORDER BY created_at DESC 
              LIMIT ?";
    
    $result = execute_prepared_query($query, array($user_id, $limit), 'ii');
    
    return $result ? fetch_all_rows($result) : array();
}
?>