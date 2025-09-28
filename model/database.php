<?php
// FIXED DATABASE.PHP - Main issues causing 500 errors
// Database Configuration for SmartRent

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'smartrent_db');

// Global database connection variable
$db_connection = null;

// Connect to database function
function connect_database() {
    global $db_connection;
    
    // Create connection
    $db_connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($db_connection->connect_error) {
        error_log("Database connection failed: " . $db_connection->connect_error);
        die("Connection failed: " . $db_connection->connect_error);
    }
    
    // Set charset to UTF-8 for Bengali support
    $db_connection->set_charset("utf8mb4");
    
    return $db_connection;
}

// Close database connection function
function close_database() {
    global $db_connection;
    
    if ($db_connection) {
        $db_connection->close();
        $db_connection = null;
    }
}

// Get database connection function
function get_database_connection() {
    global $db_connection;
    
    if (!$db_connection || $db_connection->ping() === false) {
        connect_database();
    }
    
    return $db_connection;
}

// Execute query with error handling
function execute_query($query) {
    $connection = get_database_connection();
    
    $result = $connection->query($query);
    
    if (!$result) {
        error_log("Database Query Error: " . $connection->error);
        error_log("Query: " . $query);
        return false;
    }
    
    return $result;
}

// FIXED: Execute prepared statement - this was causing main 500 errors
function execute_prepared_query($query, $params = array(), $types = '') {
    $connection = get_database_connection();
    
    $stmt = $connection->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare Error: " . $connection->error);
        error_log("Query: " . $query);
        return false;
    }
    
    // Bind parameters if provided
    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Bind Error: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // For SELECT queries, get the result
    $result = $stmt->get_result();
    $stmt->close();
    
    // Return result for SELECT queries, or TRUE for INSERT/UPDATE/DELETE
    return $result !== false ? $result : true;
}

// Sanitize input data
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Escape string for database
function escape_string($string) {
    $connection = get_database_connection();
    return $connection->real_escape_string($string);
}

// Get single row from result
function fetch_single_row($result) {
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Get all rows from result
function fetch_all_rows($result) {
    $rows = array();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

// Get last inserted ID
function get_last_insert_id() {
    $connection = get_database_connection();
    return $connection->insert_id;
}

// Get affected rows count
function get_affected_rows() {
    $connection = get_database_connection();
    return $connection->affected_rows;
}

// Begin transaction
function begin_transaction() {
    $connection = get_database_connection();
    $connection->autocommit(false);
    return $connection->begin_transaction();
}

// Commit transaction
function commit_transaction() {
    $connection = get_database_connection();
    $connection->commit();
    $connection->autocommit(true);
}

// Rollback transaction
function rollback_transaction() {
    $connection = get_database_connection();
    $connection->rollback();
    $connection->autocommit(true);
}

// FIXED: Generate unique transaction number - had syntax error
function generate_transaction_number() {
    $timestamp = date('YmdHis');
    $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $transaction_number = 'TXN-' . $timestamp . '-' . $random;
    
    // Check if it exists and regenerate if needed
    $counter = 0;
    while ($counter < 10) { // FIXED: was missing $ before counter
        $check_query = "SELECT transaction_number FROM payments WHERE transaction_number = ?";
        $result = execute_prepared_query($check_query, [$transaction_number], 's');
        
        if (!$result || $result->num_rows == 0) {
            break;
        }
        
        // Regenerate
        $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $transaction_number = 'TXN-' . $timestamp . '-' . $random;
        $counter++;
    }
    
    return $transaction_number;
}

// FIXED: Generate OTP code
function generate_otp_code() {
    $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Check if it exists and regenerate if needed
    $counter = 0;
    while ($counter < 10) {
        $check_query = "SELECT otp_code FROM flat_assignments WHERE otp_code = ?";
        $result = execute_prepared_query($check_query, [$otp_code], 's');
        
        if (!$result || $result->num_rows == 0) {
            break;
        }
        
        // Regenerate
        $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $counter++;
    }
    
    return $otp_code;
}

// Log user activity - FIXED parameter handling
function log_user_activity($user_id, $action_type, $target_table = null, $target_id = null, $old_values = null, $new_values = null) {
    try {
        $query = "INSERT INTO user_logs (user_id, action_type, target_table, target_id, old_values, new_values, ip_address, user_agent, session_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $session_id = session_id() ?? '';
        
        $old_json = $old_values ? json_encode($old_values) : null;
        $new_json = $new_values ? json_encode($new_values) : null;
        
        $params = [
            $user_id,
            $action_type,
            $target_table,
            $target_id,
            $old_json,
            $new_json,
            $ip_address,
            $user_agent,
            $session_id
        ];
        
        $types = 'issiissss';
        
        return execute_prepared_query($query, $params, $types);
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
        return false;
    }
}

// Update user activity tracking
function update_user_activity($user_id, $page_visited) {
    try {
        $query = "INSERT INTO user_activity (user_id, last_page_visited, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  last_page_visited = ?, 
                  ip_address = ?, 
                  user_agent = ?, 
                  last_activity = CURRENT_TIMESTAMP";
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $params = [$user_id, $page_visited, $ip_address, $user_agent, $page_visited, $ip_address, $user_agent];
        $types = 'issssss';
        
        return execute_prepared_query($query, $params, $types);
    } catch (Exception $e) {
        error_log("Update activity error: " . $e->getMessage());
        return false;
    }
}

// Test database connection
function test_database_connection() {
    try {
        $connection = get_database_connection();
        if ($connection && $connection->ping()) {
            return ['success' => true, 'message' => 'Database connection successful'];
        } else {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Initialize database connection when file is included
try {
    connect_database();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

// Register shutdown function to close connection
register_shutdown_function('close_database');

// =============================================================
// TEST REGISTRATION FUNCTION - Add this to test the fix
// =============================================================

// Test the registration system
function test_registration() {
    error_log("=== Testing Registration System ===");
    
    // Test database connection
    $db_test = test_database_connection();
    error_log("Database test: " . json_encode($db_test));
    
    if (!$db_test['success']) {
        return $db_test;
    }
    
    // Test user registration
    try {
        $test_result = register_user(
            'testuser_' . time(), 
            'test' . time() . '@example.com', 
            'testpass123', 
            'tenant', 
            'Test User',
            '1234567890'
        );
        
        error_log("Registration test result: " . json_encode($test_result));
        return $test_result;
        
    } catch (Exception $e) {
        error_log("Registration test error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Test failed: ' . $e->getMessage()];
    }
}

?>