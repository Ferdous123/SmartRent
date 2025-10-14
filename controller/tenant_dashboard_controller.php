<?php
ob_start();

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
                
            case 'get_notifications':
                handle_get_notifications($user_id);
                break;
                
            case 'mark_notification_read':
                handle_mark_notification_read($user_id);
                break;
                
            case 'mark_all_notifications_read':
                handle_mark_all_notifications_read($user_id);
                break;

            case 'download_receipt':
                handle_download_receipt($user_id);
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

            case 'get_flat_full_details':
                handle_get_flat_full_details($user_id);
                break;

            case 'get_flat_payment_history':
                handle_get_flat_payment_history($user_id);
                break;

            case 'get_flat_outstanding':
                handle_get_flat_outstanding($user_id);
                break;

            case 'create_service_request':
                handle_create_service_request($user_id);
                break;

            case 'cancel_service_request':
                handle_cancel_service_request($user_id);
                break;

            case 'get_flat_service_requests':
                handle_get_flat_service_requests($user_id);
                break;

            case 'get_flat_expenses':
                handle_get_flat_expenses($user_id);
                break;

            case 'get_expense_months':
                handle_get_expense_months($user_id);
                break;

            case 'get_meter_readings':
                handle_get_meter_readings($user_id);
                break;

            case 'request_move_out':
                handle_request_move_out($user_id);
                break;

            case 'cancel_move_out':
                handle_cancel_move_out($user_id);
                break;

            case 'pay_outstanding':
                handle_pay_outstanding($user_id);
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



function handle_get_dashboard_data($user_id) {
    try {

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



function handle_verify_and_pay($user_id) {
    try {
        $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if (!$assignment_id || !$transaction_id || $amount <= 0) {
            echo json_encode(array('success' => false, 'message' => 'Invalid input'));
            exit();
        }
        

        $gateway_txn = verify_transaction($transaction_id);
        if (!$gateway_txn) {
            echo json_encode(array('success' => false, 'message' => 'Transaction not found'));
            exit();
        }
        

        if (is_transaction_used($transaction_id)) {
            echo json_encode(array('success' => false, 'message' => 'Transaction already used'));
            exit();
        }
        

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
        

        mark_transaction_used($transaction_id);
        

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


function handle_claim_otp($user_id) {
    try {
        $otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
        
        if (empty($otp_code) || strlen($otp_code) !== 6) {
            echo json_encode(array('success' => false, 'message' => 'Invalid OTP code'));
            exit();
        }
        

        $check_query = "SELECT assignment_id FROM flat_assignments 
                       WHERE tenant_id = ? AND status = 'pending'";
        $check_result = execute_prepared_query($check_query, array($user_id), 'i');
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            echo json_encode(array('success' => false, 'message' => 'You already have a pending assignment'));
            exit();
        }
        

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
        

        $verify_query = "SELECT tenant_id FROM flat_assignments WHERE assignment_id = ?";
        $verify_result = execute_prepared_query($verify_query, array($assignment['assignment_id']), 'i');
        $verify_data = fetch_single_row($verify_result);
        
        if ($verify_data['tenant_id'] != $user_id) {
            rollback_transaction();
            echo json_encode(array('success' => false, 'message' => 'Failed to claim - verification failed'));
            exit();
        }
        
        commit_transaction();
        

        if (function_exists('create_tenant_notification')) {
            create_tenant_notification($user_id, 'assignment', 
                'Flat Claimed Successfully',
                'You have claimed ' . $assignment['building_name'] . ' - ' . 
                $assignment['flat_number'] . '. Please complete payment within 24 hours.',
                'flat_assignments', $assignment['assignment_id']);
        }
        

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

function handle_get_flat_full_details($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $result = get_tenant_flat_full_details($user_id, $flat_id);
    echo json_encode($result);
    exit();
}

function handle_get_flat_payment_history($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $payments = get_flat_specific_payments($user_id, $flat_id);
    echo json_encode(array('success' => true, 'payments' => $payments));
    exit();
}

function handle_get_flat_outstanding($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $dues = get_flat_specific_dues($user_id, $flat_id);
    echo json_encode(array('success' => true, 'dues' => $dues));
    exit();
}

function handle_create_service_request($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $request_type = isset($_POST['request_type']) ? trim($_POST['request_type']) : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'medium';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    $attachments = array();
    if (isset($_FILES['attachments'])) {
        $files = $_FILES['attachments'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $attachments[] = array(
                    'tmp_name' => $files['tmp_name'][$i],
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i]
                );
            }
        }
    }
    
    $result = create_service_request($user_id, $flat_id, $request_type, $priority, $description, null);
    
    if ($result['success'] && !empty($attachments)) {
        $request_id = $result['request_id'];
        $upload_dir = '../uploads/service_requests/' . $request_id . '/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $uploaded_files = array();
        foreach ($attachments as $file) {
            $filename = time() . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $uploaded_files[] = 'uploads/service_requests/' . $request_id . '/' . $filename;
            }
        }
        
        if (!empty($uploaded_files)) {
            $update_query = "UPDATE service_requests SET attachments = ? WHERE request_id = ?";
            execute_prepared_query($update_query, array(json_encode($uploaded_files), $request_id), 'si');
        }
    }
    
    echo json_encode($result);
    exit();
}

function handle_cancel_service_request($user_id) {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $result = cancel_service_request($user_id, $request_id);
    echo json_encode($result);
    exit();
}

function handle_get_flat_service_requests($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $requests = get_flat_service_requests($user_id, $flat_id);
    echo json_encode(array('success' => true, 'requests' => $requests));
    exit();
}

function handle_get_flat_expenses($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $expenses = get_flat_monthly_expenses($user_id, $flat_id);
    echo json_encode(array('success' => true, 'expenses' => $expenses));
    exit();
}

function handle_get_expense_months($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $months = get_flat_expense_months($user_id, $flat_id);
    echo json_encode(array('success' => true, 'months' => $months));
    exit();
}

function handle_get_meter_readings($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $meters = get_flat_meter_readings($user_id, $flat_id);
    echo json_encode(array('success' => true, 'meters' => $meters));
    exit();
}

function handle_request_move_out($user_id) {
    $flat_id = isset($_POST['flat_id']) ? intval($_POST['flat_id']) : 0;
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $move_out_date = isset($_POST['move_out_date']) ? trim($_POST['move_out_date']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    $result = request_flat_move_out($user_id, $flat_id, $assignment_id, $move_out_date, $reason);
    echo json_encode($result);
    exit();
}

function handle_cancel_move_out($user_id) {
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    $result = cancel_flat_move_out($user_id, $assignment_id);
    echo json_encode($result);
    exit();
}

function handle_pay_outstanding($user_id) {
    $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    $query = "SELECT fe.*, td.remaining_amount 
              FROM flat_expenses fe
              JOIN tenant_dues td ON fe.expense_id = td.expense_id
              WHERE fe.expense_id = ? AND td.tenant_id = ?";
    
    $result = execute_prepared_query($query, array($expense_id, $user_id), 'ii');
    
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(array('success' => false, 'message' => 'Expense not found'));
        exit();
    }
    
    $expense = fetch_single_row($result);
    
    echo json_encode(array(
        'success' => true, 
        'expense' => $expense,
        'suggested_amount' => $expense['remaining_amount']
    ));
    exit();
}

function handle_download_receipt($user_id) {
    try {
        $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
        
        if (!$payment_id) {
            header('Location: ../view/dashboard_tenant.php?error=invalid_payment');
            exit();
        }
        
        $query = "SELECT p.*, f.flat_number, b.building_name, b.address, up.full_name as tenant_name
                  FROM payments p
                  JOIN flats f ON p.flat_id = f.flat_id
                  JOIN buildings b ON f.building_id = b.building_id
                  JOIN user_profiles up ON up.user_id = p.tenant_id
                  WHERE p.payment_id = ? AND p.tenant_id = ?";
        
        $result = execute_prepared_query($query, array($payment_id, $user_id), 'ii');
        
        if (!$result || mysqli_num_rows($result) == 0) {
            header('Location: ../view/dashboard_tenant.php?error=payment_not_found');
            exit();
        }
        
        $payment = fetch_single_row($result);
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="receipt_' . $payment_id . '.html"');
        
        echo generate_receipt_html($payment);
        
    } catch (Exception $e) {
        error_log("Download receipt error: " . $e->getMessage());
        header('Location: ../view/dashboard_tenant.php?error=download_failed');
    }
    exit();
}

function generate_receipt_html($payment) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Receipt - ' . $payment['payment_id'] . '</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
            .receipt { border: 2px solid #333; padding: 30px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { margin: 0; color: #667eea; }
            .row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; }
            .row.highlight { background: #f5f7fa; padding: 12px; border-radius: 4px; }
            .label { font-weight: bold; color: #666; }
            .value { color: #333; }
            .total { font-size: 24px; font-weight: bold; margin-top: 20px; padding-top: 20px; border-top: 2px solid #333; text-align: center; color: #667eea; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc; text-align: center; color: #999; font-size: 12px; }
            .status { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
            .status.verified { background: #28a745; color: white; }
            .status.pending { background: #ffc107; color: #333; }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <h1>PAYMENT RECEIPT</h1>
                <p style="margin: 5px 0; color: #666;">Receipt #' . str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) . '</p>
            </div>
            
            <div class="row highlight">
                <span class="label">Tenant Name:</span>
                <span class="value">' . htmlspecialchars($payment['tenant_name']) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Property:</span>
                <span class="value">' . htmlspecialchars($payment['building_name']) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Flat:</span>
                <span class="value">' . htmlspecialchars($payment['flat_number']) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Address:</span>
                <span class="value">' . htmlspecialchars($payment['address']) . '</span>
            </div>
            
            <div class="row highlight">
                <span class="label">Payment Date:</span>
                <span class="value">' . date('F d, Y', strtotime($payment['payment_date'])) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Payment Type:</span>
                <span class="value">' . ucwords(str_replace('_', ' ', $payment['payment_type'])) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Payment Method:</span>
                <span class="value">' . ucwords(str_replace('_', ' ', $payment['method'])) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Transaction Number:</span>
                <span class="value">' . htmlspecialchars($payment['transaction_number']) . '</span>
            </div>
            
            <div class="row">
                <span class="label">Status:</span>
                <span class="value"><span class="status ' . ($payment['is_verified'] ? 'verified' : 'pending') . '">' . ($payment['is_verified'] ? 'VERIFIED' : 'PENDING') . '</span></span>
            </div>
            
            <div class="total">
                Amount Paid: ৳' . number_format($payment['amount'], 2) . '
            </div>
            
            <div class="footer">
                <p>This is a computer-generated receipt and does not require a signature.</p>
                <p>Generated on ' . date('F d, Y h:i A') . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

?>