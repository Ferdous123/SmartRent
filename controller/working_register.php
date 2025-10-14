<?php
session_start();
require_once '../model/database.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    

    $errors = array();
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($full_name) || strlen($full_name) < 2) {
        $errors[] = 'Full name must be at least 2 characters';
    }
    
    if (empty($user_type)) {
        $errors[] = 'Please select a user type';
    }
    

    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false,
            'message' => implode(', ', $errors)
        ));
        exit();
    }
    

    $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_result = execute_prepared_query($check_query, array($username, $email), 'ss');
    
    if ($check_result && $check_result->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false,
            'message' => 'Username or email already exists',
            'field_errors' => array(
                'username' => 'This username is already taken',
                'email' => 'This email is already registered'
            )
        ));
        exit();
    }
    

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    

begin_transaction();

try {

    $insert_query = "INSERT INTO users (username, email, password_hash, user_type, is_active, created_at) 
                     VALUES (?, ?, ?, ?, 1, NOW())";
    $insert_params = array($username, $email, $password_hash, $user_type);
    $insert_types = 'ssss';
    
    $insert_result = execute_prepared_query($insert_query, $insert_params, $insert_types);
    
    if (!$insert_result) {
        throw new Exception('Failed to create user');
    }
    
    $user_id = get_last_insert_id();
    

    $profile_query = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
    $profile_result = execute_prepared_query($profile_query, array($user_id, $full_name), 'is');
    
    if (!$profile_result) {
        throw new Exception('Failed to create user profile');
    }
    

    if (!empty($phone_number)) {
        $contact_query = "INSERT INTO user_contacts (user_id, contact_number, contact_type) VALUES (?, ?, 'primary')";
        execute_prepared_query($contact_query, array($user_id, $phone_number), 'is');
    }
    
    commit_transaction();
    
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'message' => 'Registration successful! Please login to continue.'
    ));
    exit();
    
} catch (Exception $e) {
    rollback_transaction();
    error_log("Registration error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'message' => 'Failed to create account. Please try again.'
    ));
    exit();
}
    
} else {

    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']
    ));
    exit();
}
?>