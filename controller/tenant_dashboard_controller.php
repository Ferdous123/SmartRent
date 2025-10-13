<?php
// Safe error handling
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once 'auth_header.php';
    require_once '../model/database.php';
    require_once '../model/tenant_dashboard_model.php';
    
    if (file_exists('../model/payment_gateway_helper.php')) {
        require_once '../model/payment_gateway_helper.php';
    }
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Initialization error: ' . $e->getMessage()));
    exit();
}

ob_clean();
header('Content-Type: application/json');

// Check if user is tenant
if (!isset($current_user) || $current_user['user_type'] !== 'tenant') {
    echo json_encode(array('success' => false, 'message' => 'Access denied'));
    exit();
}

$user_id = $current_user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'get_dashboard_data':
                handle_get_dashboard_data($user_id);
                break;
                
            case 'check_pending_assignment':
                handle_check_pending_assignment($user_id);
                break;
                
            case 'confirm_assignment':
                handle_confirm_assignment($user_id);
                break;
                
            case 'simulate_payment':
                handle_simulate_payment($user_id);
                break;
                
            case 'verify_and_pay':
                handle_verify_and_pay($user_id);
                break;
                
            case 'record_payment':
                handle_record_payment($user_id);
                break;
                
            case 'get_flat_details':
                handle_get_flat_details($user_id);
                break;
                
            case 'get_payment_history':
                handle_get_payment_history($user_id);
                break;
                
            case 'request_move_out':
                handle_request_move_out($user_id);
                break;
                
            case 'get_notifications':
                handle_get_notifications($user_id);
                break;
                
            case 'mark_notification_read':
                handle_mark_notification_read($user_id);
                break;
                
            case 'mark_all_notifications_read':
                handle_mark_all_notifications_read($user_id);
                break;

            case 'claim_otp':
                handle_claim_otp($user_id);
                break;
                
            case 'get_all_my_flats':
                if (function_exists('get_all_tenant_flats')) {
                    $flats = get_all_tenant_flats($user_id);
                    echo json_encode(array('success' => true, 'flats' => $flats));
                } else {
                    echo json_encode(array('success' => false, 'message' => 'Function not available'));
                }
                break;
            case 'get_flat_full_details':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $result = get_tenant_flat_full_details($user_id, $flat_id);
                echo json_encode($result);
                break;

            case 'get_flat_payment_history':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $payments = get_flat_specific_payments($user_id, $flat_id);
                echo json_encode(array('success' => true, 'payments' => $payments));
                break;

            case 'get_flat_outstanding':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $dues = get_flat_specific_dues($user_id, $flat_id);
                echo json_encode(array('success' => true, 'dues' => $dues));
                break;

            case 'create_service_request':
                handle_create_service_request($user_id);
                break;

            case 'get_flat_service_requests':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $requests = get_flat_service_requests($user_id, $flat_id);
                echo json_encode(array('success' => true, 'requests' => $requests));
                break;

            case 'get_flat_expenses':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $expenses = get_flat_monthly_expenses($user_id, $flat_id);
                echo json_encode(array('success' => true, 'expenses' => $expenses));
                break;

            case 'get_meter_readings':
                $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
                $meters = get_flat_meter_readings($user_id, $flat_id);
                echo json_encode(array('success' => true, 'meters' => $meters));
                break;

            case 'request_move_out':
                handle_move_out_request($user_id);
                break;

            case 'cancel_move_out':
                handle_cancel_move_out($user_id);
                break;
            default:
                echo json_encode(array('success' => false, 'message' => 'Invalid action: ' . $action));
                break;
        }
    } catch (Exception $e) {
        error_log("Tenant dashboard error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Server error: ' . $e->getMessage()));
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    try {
        switch ($action) {
            case 'download_latest_slip':
                handle_download_latest_slip($user_id);
                break;
                
            case 'download_statement':
                handle_download_statement($user_id);
                break;
                
            default:
                echo json_encode(array('success' => false, 'message' => 'Invalid action'));
                break;
        }
    } catch (Exception $e) {
        error_log("Tenant dashboard error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Server error'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
}
exit();

// ============================================================================
// HANDLER FUNCTIONS
// ============================================================================

// Get complete dashboard data
function handle_get_dashboard_data($user_id) {
    try {
        // Check if function exists
        if (!function_exists('get_tenant_dashboard_data')) {
            echo json_encode(array(
                'success' => false, 
                'message' => 'Dashboard function not available'
            ));
            exit();
        }
        
        $data = get_tenant_dashboard_data($user_id);
        
        if ($data !== null) {
            echo json_encode(array('success' => true, 'data' => $data));
        } else {
            // Return empty data structure
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'has_assignment' => false,
                    'flat_info' => null,
                    'outstanding_dues' => 0,
                    'advance_balance' => 0,
                    'overdue_count' => 0,
                    'last_payment_amount' => 0,
                    'last_payment_date' => null,
                    'current_month' => null,
                    'recent_payments' => array(),
                    'active_service_requests' => 0
                )
            ));
        }
    } catch (Exception $e) {
        error_log("Dashboard data error: " . $e->getMessage());
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'has_assignment' => false,
                'flat_info' => null,
                'outstanding_dues' => 0,
                'advance_balance' => 0
            )
        ));
    }
    exit();
}

// Check for pending assignment
function handle_check_pending_assignment($user_id) {
    try {
        if (function_exists('get_pending_assignment_for_tenant')) {
            $assignment = get_pending_assignment_for_tenant($user_id);
            
            if ($assignment) {
                echo json_encode(array(
                    'success' => true, 
                    'pending_assignment' => $assignment
                ));
            } else {
                echo json_encode(array('success' => true, 'pending_assignment' => null));
            }
        } else {
            echo json_encode(array('success' => true, 'pending_assignment' => null));
        }
    } catch (Exception $e) {
        error_log("Pending assignment error: " . $e->getMessage());
        echo json_encode(array('success' => true, 'pending_assignment' => null));
    }
    exit();
}

// Confirm assignment
function handle_confirm_assignment($user_id) {
    try {
        $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $transaction_number = isset($_POST['transaction_number']) ? trim($_POST['transaction_number']) : '';
        
        if (empty($transaction_number)) {
            echo json_encode(array('success' => false, 'message' => 'Transaction number is required'));
            exit();
        }
        
        if (strlen($transaction_number) < 5) {
            echo json_encode(array('success' => false, 'message' => 'Invalid transaction number'));
            exit();
        }
        
        if (function_exists('confirm_flat_assignment')) {
            $result = confirm_flat_assignment($assignment_id, $user_id, $transaction_number);
            echo json_encode($result);
        } else {
            echo json_encode(array('success' => false, 'message' => 'Function not available'));
        }
    } catch (Exception $e) {
        error_log("Confirm assignment error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Error confirming assignment'));
    }
    exit();
}

// Record payment
function handle_record_payment($user_id) {
    try {
        $payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
        $transaction_number = isset($_POST['transaction_number']) ? trim($_POST['transaction_number']) : '';
        $payment_date = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : '';
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
        
        if (empty($payment_type) || $amount <= 0 || empty($method) || empty($transaction_number)) {
            echo json_encode(array('success' => false, 'message' => 'All fields are required'));
            exit();
        }
        
        if (function_exists('record_tenant_payment')) {
            $result = record_tenant_payment($user_id, $payment_type, $amount, $method, $transaction_number, $payment_date, $remarks);
            echo json_encode($result);
        } else {
            echo json_encode(array('success' => false, 'message' => 'Function not available'));
        }
    } catch (Exception $e) {
        error_log("Record payment error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Error recording payment'));
    }
    exit();
}

// Get flat details
function handle_get_flat_details($user_id) {
    try {
        if (function_exists('get_tenant_flat_details')) {
            $flat_details = get_tenant_flat_details($user_id);
            
            if ($flat_details) {
                echo json_encode(array('success' => true, 'flat_details' => $flat_details));
            } else {
                echo json_encode(array('success' => false, 'message' => 'No flat assigned'));
            }
        } else {
            echo json_encode(array('success' => false, 'message' => 'Function not available'));
        }
    } catch (Exception $e) {
        error_log("Flat details error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Error loading flat details'));
    }
    exit();
}

// Get payment history
function handle_get_payment_history($user_id) {
    try {
        if (function_exists('get_tenant_payment_history')) {
            $payments = get_tenant_payment_history($user_id);
            echo json_encode(array('success' => true, 'payments' => $payments));
        } else {
            echo json_encode(array('success' => true, 'payments' => array()));
        }
    } catch (Exception $e) {
        error_log("Payment history error: " . $e->getMessage());
        echo json_encode(array('success' => true, 'payments' => array()));
    }
    exit();
}

// Request move out
function handle_request_move_out($user_id) {
    try {
        $move_out_date = isset($_POST['move_out_date']) ? trim($_POST['move_out_date']) : '';
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        
        if (empty($move_out_date)) {
            echo json_encode(array('success' => false, 'message' => 'Move out date is required'));
            exit();
        }
        
        if (function_exists('request_tenant_move_out')) {
            $result = request_tenant_move_out($user_id, $move_out_date, $reason);
            echo json_encode($result);
        } else {
            echo json_encode(array('success' => false, 'message' => 'Function not available'));
        }
    } catch (Exception $e) {
        error_log("Move out error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Error processing request'));
    }
    exit();
}

// Get notifications
function handle_get_notifications($user_id) {
    try {
        if (function_exists('get_tenant_notifications')) {
            $notifications = get_tenant_notifications($user_id);
            $unread_count = function_exists('get_unread_notification_count') 
                ? get_unread_notification_count($user_id) 
                : 0;
            
            echo json_encode(array(
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ));
        } else {
            echo json_encode(array(
                'success' => true,
                'notifications' => array(),
                'unread_count' => 0
            ));
        }
    } catch (Exception $e) {
        error_log("Notifications error: " . $e->getMessage());
        echo json_encode(array(
            'success' => true,
            'notifications' => array(),
            'unread_count' => 0
        ));
    }
    exit();
}

// Mark notification as read
function handle_mark_notification_read($user_id) {
    try {
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if (function_exists('mark_notification_as_read')) {
            $result = mark_notification_as_read($notification_id, $user_id);
            echo json_encode(array('success' => $result));
        } else {
            echo json_encode(array('success' => false));
        }
    } catch (Exception $e) {
        error_log("Mark notification error: " . $e->getMessage());
        echo json_encode(array('success' => false));
    }
    exit();
}

// Mark all notifications as read
function handle_mark_all_notifications_read($user_id) {
    try {
        if (function_exists('mark_all_notifications_as_read')) {
            $result = mark_all_notifications_as_read($user_id);
            echo json_encode(array('success' => $result));
        } else {
            echo json_encode(array('success' => false));
        }
    } catch (Exception $e) {
        error_log("Mark all notifications error: " . $e->getMessage());
        echo json_encode(array('success' => false));
    }
    exit();
}

// Simulate payment gateway
function handle_simulate_payment($user_id) {
    try {
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'bKash';
        
        if ($amount <= 0) {
            echo json_encode(array('success' => false, 'message' => 'Invalid amount'));
            exit();
        }
        
        if (function_exists('simulate_payment')) {
            $result = simulate_payment($amount, $method);
            echo json_encode($result);
        } else {
            echo json_encode(array('success' => false, 'message' => 'Payment simulation not available'));
        }
    } catch (Exception $e) {
        error_log("Simulate payment error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Payment simulation failed'));
    }
    exit();
}

// Verify and pay

function handle_verify_and_pay($user_id) {
    try {
        $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if (!$assignment_id || !$transaction_id || $amount <= 0) {
            echo json_encode(array('success' => false, 'message' => 'Invalid input'));
            exit();
        }
        
        // Verify transaction in gateway
        $gateway_txn = verify_transaction($transaction_id);
        if (!$gateway_txn) {
            echo json_encode(array('success' => false, 'message' => 'Transaction not found'));
            exit();
        }
        
        // Check if already used
        if (is_transaction_used($transaction_id)) {
            echo json_encode(array('success' => false, 'message' => 'Transaction already used'));
            exit();
        }
        
        // Get assignment
        $query = "SELECT * FROM flat_assignments WHERE assignment_id = ? AND tenant_id = ? AND status = 'pending'";
        $result = execute_prepared_query($query, array($assignment_id, $user_id), 'ii');
        
        if (!$result || mysqli_num_rows($result) == 0) {
            echo json_encode(array('success' => false, 'message' => 'Assignment not found'));
            exit();
        }
        
        $assignment = fetch_single_row($result);
        $total_paid = get_total_paid_for_assignment($assignment_id);
        $remaining = $assignment['advance_amount'] - $total_paid;
        
        begin_transaction();
        
        // Mark transaction as used
        mark_transaction_used($transaction_id);
        
        // Create payment record
        $payment_query = "INSERT INTO payments 
                         (transaction_number, tenant_id, flat_id, assignment_id, amount, 
                          method, payment_type, payment_date, is_verified)
                         VALUES (?, ?, ?, ?, ?, 'bank_transfer', 'advance', NOW(), 1)";
        execute_prepared_query($payment_query, 
            array($transaction_id, $user_id, $assignment['flat_id'], $assignment_id, $amount),
            'siiid');
        
        $new_total_paid = $total_paid + $amount;
        $new_remaining = $assignment['advance_amount'] - $new_total_paid;
        
        if ($new_remaining <= 0) {
            // Full payment - confirm assignment
            $update = "UPDATE flat_assignments 
                      SET status = 'confirmed', 
                          confirmed_at = NOW(), 
                          advance_balance = ?,
                          payment_transaction = ? 
                      WHERE assignment_id = ?";
            execute_prepared_query($update, array($assignment['advance_amount'], $transaction_id, $assignment_id), 'dsi');
            
            $flat_update = "UPDATE flats SET status = 'occupied' WHERE flat_id = ?";
            execute_prepared_query($flat_update, array($assignment['flat_id']), 'i');
            
            commit_transaction();
            
            echo json_encode(array(
                'success' => true, 
                'status' => 'confirmed', 
                'message' => 'Full payment verified! Flat confirmed.',
                'total_paid' => $new_total_paid
            ));
        } else {
            // Partial payment
            commit_transaction();
            
            echo json_encode(array(
                'success' => true, 
                'status' => 'partial', 
                'message' => 'Partial payment recorded', 
                'remaining' => $new_remaining,
                'total_paid' => $new_total_paid
            ));
        }
        
    } catch (Exception $e) {
        rollback_transaction();
        echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
    }
    exit();
}

// Download latest slip
function handle_download_latest_slip($user_id) {
    try {
        if (function_exists('get_latest_slip_data')) {
            $slip_data = get_latest_slip_data($user_id);
            
            if (!$slip_data) {
                header('Location: ../view/dashboard_tenant.php?error=no_slip');
                exit();
            }
            
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="rent_slip_' . date('Y-m') . '.html"');
            
            echo generate_slip_html($slip_data);
        } else {
            header('Location: ../view/dashboard_tenant.php?error=function_not_available');
        }
    } catch (Exception $e) {
        error_log("Download slip error: " . $e->getMessage());
        header('Location: ../view/dashboard_tenant.php?error=download_failed');
    }
    exit();
}

// Download statement
function handle_download_statement($user_id) {
    try {
        if (function_exists('get_tenant_payment_history')) {
            $payments = get_tenant_payment_history($user_id);
            
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="payment_statement_' . date('Y-m-d') . '.html"');
            
            echo generate_statement_html($payments, $user_id);
        } else {
            header('Location: ../view/dashboard_tenant.php?error=function_not_available');
        }
    } catch (Exception $e) {
        error_log("Download statement error: " . $e->getMessage());
        header('Location: ../view/dashboard_tenant.php?error=download_failed');
    }
    exit();
}

// Generate slip HTML
function generate_slip_html($slip_data) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Rent Slip</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .slip { border: 2px solid #333; padding: 20px; max-width: 600px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
            .row { display: flex; justify-content: space-between; margin: 10px 0; }
            .total { font-weight: bold; font-size: 18px; margin-top: 20px; padding-top: 10px; border-top: 2px solid #333; }
        </style>
    </head>
    <body>
        <div class="slip">
            <div class="header">
                <h2>RENT SLIP</h2>
                <p>' . htmlspecialchars($slip_data['building_name']) . '</p>
                <p>Flat: ' . htmlspecialchars($slip_data['flat_number']) . '</p>
            </div>
            <div class="row"><span>Tenant Name:</span><span>' . htmlspecialchars($slip_data['tenant_name']) . '</span></div>
            <div class="row"><span>Month:</span><span>' . date('F Y', strtotime($slip_data['billing_month'])) . '</span></div>
            <hr>
            <div class="row"><span>Rent:</span><span>৳' . number_format($slip_data['rent'], 2) . '</span></div>
            <div class="row total"><span>Total Amount:</span><span>৳' . number_format($slip_data['total_amount'], 2) . '</span></div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Claim flat with OTP
function handle_claim_otp($user_id) {
    try {
        $otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
        
        if (empty($otp_code) || strlen($otp_code) !== 6) {
            echo json_encode(array('success' => false, 'message' => 'Invalid OTP code'));
            exit();
        }
        
        // Check if user already has a pending assignment
        $check_query = "SELECT assignment_id FROM flat_assignments 
                       WHERE tenant_id = ? AND status = 'pending'";
        $check_result = execute_prepared_query($check_query, array($user_id), 'i');
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            echo json_encode(array('success' => false, 'message' => 'You already have a pending assignment'));
            exit();
        }
        
        // Find OTP assignment
        $query = "SELECT fa.*, f.flat_number, f.floor_number, b.building_name, b.address
                  FROM flat_assignments fa
                  JOIN flats f ON fa.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  WHERE fa.otp_code = ? 
                  AND fa.tenant_id IS NULL 
                  AND fa.status = 'pending'
                  AND fa.expires_at > NOW()";
        
        $result = execute_prepared_query($query, array($otp_code), 's');
        
        if (!$result || mysqli_num_rows($result) == 0) {
            echo json_encode(array('success' => false, 'message' => 'Invalid or expired OTP code'));
            exit();
        }
        
        $assignment = fetch_single_row($result);
        
        // Claim the assignment
        begin_transaction();
        
        $update_query = "UPDATE flat_assignments 
                        SET tenant_id = ? 
                        WHERE assignment_id = ?";
        
        $update_result = execute_prepared_query($update_query, 
            array($user_id, $assignment['assignment_id']), 
            'ii');
        
        if (!$update_result) {
            rollback_transaction();
            echo json_encode(array('success' => false, 'message' => 'Database error updating assignment'));
            exit();
        }
        
        // Verify the update
        $verify_query = "SELECT tenant_id FROM flat_assignments WHERE assignment_id = ?";
        $verify_result = execute_prepared_query($verify_query, array($assignment['assignment_id']), 'i');
        $verify_data = fetch_single_row($verify_result);
        
        if ($verify_data['tenant_id'] != $user_id) {
            rollback_transaction();
            echo json_encode(array('success' => false, 'message' => 'Failed to claim - verification failed'));
            exit();
        }
        
        commit_transaction();
        
        // Create notification
        if (function_exists('create_tenant_notification')) {
            create_tenant_notification($user_id, 'assignment', 
                'Flat Claimed Successfully',
                'You have claimed ' . $assignment['building_name'] . ' - ' . 
                $assignment['flat_number'] . '. Please complete payment within 24 hours.',
                'flat_assignments', $assignment['assignment_id']);
        }
        
        // Log activity
        log_user_activity($user_id, 'assign', 'flat_assignments', $assignment['assignment_id'], null,
            array('action' => 'otp_claimed', 'otp_code' => $otp_code));
        
        echo json_encode(array(
            'success' => true, 
            'message' => 'Flat claimed successfully! Please complete your advance payment.',
            'assignment' => array(
                'building_name' => $assignment['building_name'],
                'flat_number' => $assignment['flat_number'],
                'advance_amount' => $assignment['advance_amount']
            )
        ));
        
    } catch (Exception $e) {
        if (function_exists('rollback_transaction')) {
            rollback_transaction();
        }
        error_log("OTP claim error: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Error claiming flat: ' . $e->getMessage()));
    }
    exit();
}

// Generate statement HTML
function generate_statement_html($payments, $user_id) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Statement</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #667eea; color: white; }
        </style>
    </head>
    <body>
        <h2>Payment Statement</h2>
        <p><strong>Generated:</strong> ' . date('F d, Y') . '</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($payments as $payment) {
        $html .= '<tr>
                    <td>' . date('M d, Y', strtotime($payment['payment_date'])) . '</td>
                    <td>' . htmlspecialchars($payment['payment_type']) . '</td>
                    <td>৳' . number_format($payment['amount'], 2) . '</td>
                    <td>' . htmlspecialchars($payment['method']) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody>
        </table>
    </body>
    </html>';
    
    return $html;
}
?>