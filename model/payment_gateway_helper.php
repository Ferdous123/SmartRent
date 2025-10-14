<?php
require_once 'database.php';


function simulate_payment($amount, $method = 'bKash') {
    $prefix = array('bKash' => 'BKA', 'Nagad' => 'NAG', 'Rocket' => 'RKT');
    $pre = isset($prefix[$method]) ? $prefix[$method] : 'TXN';
    
    $transaction_id = $pre . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    

    $query = "INSERT INTO payment_gateway_sim (transaction_id, amount, payment_method) 
              VALUES (?, ?, ?)";
    
    $result = execute_prepared_query($query, array($transaction_id, $amount, $method), 'sds');
    
    if ($result) {
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'method' => $method
        );
    }
    
    return array('success' => false);
}


function verify_transaction($transaction_id) {
    $query = "SELECT * FROM payment_gateway_sim WHERE transaction_id = ?";
    $result = execute_prepared_query($query, array($transaction_id), 's');
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result);
    }
    return null;
}


function is_transaction_used($transaction_id) {
    $query = "SELECT is_used FROM payment_gateway_sim WHERE transaction_id = ?";
    $result = execute_prepared_query($query, array($transaction_id), 's');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = fetch_single_row($result);
        return $row['is_used'] == 1;
    }
    return false;
}


function mark_transaction_used($transaction_id) {
    $query = "UPDATE payment_gateway_sim SET is_used = 1 WHERE transaction_id = ?";
    return execute_prepared_query($query, array($transaction_id), 's');
}


function get_total_paid_for_assignment($assignment_id) {
    $query = "SELECT COALESCE(SUM(amount), 0) as total 
              FROM payments 
              WHERE assignment_id = ? AND payment_type = 'advance'";
    
    $result = execute_prepared_query($query, array($assignment_id), 'i');
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = fetch_single_row($result);
        return floatval($row['total']);
    }
    return 0.0;
}
?>