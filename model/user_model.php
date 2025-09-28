<?php
// User Model for SmartRent
// All user-related database operations
require_once 'database.php';

// Register new user
if (!function_exists('register_user')) {
    function register_user($username, $email, $password, $user_type, $full_name, $phone_number = null) {
    // Check if username already exists
    if (check_username_exists($username)) {
        return array('success' => false, 'message' => 'Username already exists');
    }
    
    // Check if email already exists
    if (check_email_exists($email)) {
        return array('success' => false, 'message' => 'Email already exists');
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Begin transaction
    begin_transaction();
    
    try {
        // Insert into users table
        $user_query = "INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)";
        $user_params = array($username, $email, $password_hash, $user_type);
        $user_types = 'ssss';
        
        $user_result = execute_prepared_query($user_query, $user_params, $user_types);
        
        if (!$user_result) {
            throw new Exception('Failed to create user account');
        }
        
        $user_id = get_last_insert_id();
        
        // Insert into user_profiles table
        $profile_query = "INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)";
        $profile_params = array($user_id, $full_name);
        $profile_types = 'is';
        
        $profile_result = execute_prepared_query($profile_query, $profile_params, $profile_types);
        
        if (!$profile_result) {
            throw new Exception('Failed to create user profile');
        }
        
        // Insert phone number if provided
        if ($phone_number) {
            $contact_query = "INSERT INTO user_contacts (user_id, contact_number, contact_type) VALUES (?, ?, 'primary')";
            $contact_params = array($user_id, $phone_number);
            $contact_types = 'is';
            
            execute_prepared_query($contact_query, $contact_params, $contact_types);
        }
        
        // Insert default preferences
        $pref_query = "INSERT INTO user_preferences (user_id) VALUES (?)";
        $pref_params = array($user_id);
        $pref_types = 'i';
        
        execute_prepared_query($pref_query, $pref_params, $pref_types);
        
        // Log registration
        log_user_activity($user_id, 'create', 'users', $user_id, null, array('action' => 'user_registration'));
        
        commit_transaction();
        
        return array('success' => true, 'message' => 'Registration successful', 'user_id' => $user_id);
        
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Registration Error: " . $e->getMessage());
        return array('success' => false, 'message' => 'Registration failed. Please try again.');
    }
}
}

// Login user
function login_user($username, $password) {
    $query = "SELECT u.user_id, u.username, u.email, u.password_hash, u.user_type, u.is_active, 
                     up.full_name, up.profile_picture_url 
              FROM users u 
              LEFT JOIN user_profiles up ON u.user_id = up.user_id 
              WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1";
    
    $params = array($username, $username);
    $types = 'ss';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = fetch_single_row($result);
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Update last login time
            $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $update_params = array($user['user_id']);
            $update_types = 'i';
            execute_prepared_query($update_query, $update_params, $update_types);
            
            // Log login activity
            log_user_activity($user['user_id'], 'login', 'users', $user['user_id']);
            
            return array('success' => true, 'user' => $user);
        } else {
            return array('success' => false, 'message' => 'Invalid password');
        }
    } else {
        return array('success' => false, 'message' => 'User not found or account inactive');
    }
}

// Check if username exists
function check_username_exists($username) {
    $query = "SELECT user_id FROM users WHERE username = ?";
    $params = array($username);
    $types = 's';
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result && mysqli_num_rows($result) > 0;
}

// Check if email exists
function check_email_exists($email) {
    $query = "SELECT user_id FROM users WHERE email = ?";
    $params = array($email);
    $types = 's';
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result && mysqli_num_rows($result) > 0;
}

// Get user by ID
function get_user_by_id($user_id) {
    $query = "SELECT u.user_id, u.username, u.email, u.user_type, u.is_active, u.last_login, u.created_at,
                     up.full_name, up.nid_number, up.permanent_address, up.profile_picture_url,
                     uc.contact_number
              FROM users u
              LEFT JOIN user_profiles up ON u.user_id = up.user_id
              LEFT JOIN user_contacts uc ON u.user_id = uc.user_id AND uc.contact_type = 'primary'
              WHERE u.user_id = ? AND u.is_active = 1";
    
    $result = execute_prepared_query($query, array($user_id), 'i');
    return $result ? fetch_single_row($result) : null;
}

// Update user profile
function update_user_profile($user_id, $full_name, $nid_number = null, $permanent_address = null) {
    $query = "UPDATE user_profiles SET full_name = ?, nid_number = ?, permanent_address = ? WHERE user_id = ?";
    $params = array($full_name, $nid_number, $permanent_address, $user_id);
    $types = 'sssi';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        // Log profile update
        log_user_activity($user_id, 'update', 'user_profiles', $user_id, null, array('full_name' => $full_name));
        return true;
    }
    
    return false;
}

// Update profile picture
function update_profile_picture($user_id, $picture_url) {
    $query = "UPDATE user_profiles SET profile_picture_url = ? WHERE user_id = ?";
    $result = execute_prepared_query($query, array($picture_url, $user_id), 'si');
    
    if ($result) {
        log_user_activity($user_id, 'update', 'user_profiles', $user_id, null, array('profile_picture' => 'updated'));
        return true;
    }
    
    return false;
}

// Change password
function change_user_password($user_id, $old_password, $new_password) {
    // First verify old password
    $query = "SELECT password_hash FROM users WHERE user_id = ?";
    $params = array($user_id);
    $types = 'i';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return array('success' => false, 'message' => 'User not found');
    }
    
    $user = fetch_single_row($result);
    
    if (!password_verify($old_password, $user['password_hash'])) {
        return array('success' => false, 'message' => 'Current password is incorrect');
    }
    
    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
    $update_params = array($new_password_hash, $user_id);
    $update_types = 'si';
    
    $update_result = execute_prepared_query($update_query, $update_params, $update_types);
    
    if ($update_result) {
        log_user_activity($user_id, 'update', 'users', $user_id, null, array('action' => 'password_change'));
        return array('success' => true, 'message' => 'Password changed successfully');
    }
    
    return array('success' => false, 'message' => 'Failed to change password');
}

// Reset password (for forgot password)
function reset_user_password($email, $new_password) {
    // Check if email exists
    $query = "SELECT user_id FROM users WHERE email = ? AND is_active = 1";
    $params = array($email);
    $types = 's';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return array('success' => false, 'message' => 'Email not found');
    }
    
    $user = fetch_single_row($result);
    $user_id = $user['user_id'];
    
    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
    $update_params = array($new_password_hash, $user_id);
    $update_types = 'si';
    
    $update_result = execute_prepared_query($update_query, $update_params, $update_types);
    
    if ($update_result) {
        log_user_activity($user_id, 'update', 'users', $user_id, null, array('action' => 'password_reset'));
        return array('success' => true, 'message' => 'Password reset successfully');
    }
    
    return array('success' => false, 'message' => 'Failed to reset password');
}

// Add/Update contact number
function update_user_contact($user_id, $contact_number, $contact_type = 'primary') {
    // Check if contact already exists
    $check_query = "SELECT contact_id FROM user_contacts WHERE user_id = ? AND contact_type = ?";
    $check_params = array($user_id, $contact_type);
    $check_types = 'is';
    
    $check_result = execute_prepared_query($check_query, $check_params, $check_types);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        // Update existing contact
        $update_query = "UPDATE user_contacts SET contact_number = ? WHERE user_id = ? AND contact_type = ?";
        $update_params = array($contact_number, $user_id, $contact_type);
        $update_types = 'sis';
        
        $result = execute_prepared_query($update_query, $update_params, $update_types);
    } else {
        // Insert new contact
        $insert_query = "INSERT INTO user_contacts (user_id, contact_number, contact_type) VALUES (?, ?, ?)";
        $insert_params = array($user_id, $contact_number, $contact_type);
        $insert_types = 'iss';
        
        $result = execute_prepared_query($insert_query, $insert_params, $insert_types);
    }
    
    if ($result) {
        log_user_activity($user_id, 'update', 'user_contacts', $user_id, null, array('contact_number' => $contact_number));
        return true;
    }
    
    return false;
}

// Get user preferences
function get_user_preferences($user_id) {
    $query = "SELECT * FROM user_preferences WHERE user_id = ?";
    $params = array($user_id);
    $types = 'i';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_single_row($result);
    }
    
    // Return default preferences if not found
    return array(
        'theme_mode' => 'light',
        'language_code' => 'en',
        'nav_color' => '#667eea',
        'primary_bg_color' => '#ffffff',
        'secondary_bg_color' => '#f5f7fa',
        'font_size' => 'medium'
    );
}

// Update user preferences
function update_user_preferences($user_id, $preferences) {
    $query = "UPDATE user_preferences SET 
                theme_mode = ?, 
                language_code = ?, 
                nav_color = ?, 
                primary_bg_color = ?, 
                secondary_bg_color = ?, 
                font_size = ?,
                custom_colors = ? 
              WHERE user_id = ?";
    
    $custom_colors_json = isset($preferences['custom_colors']) ? json_encode($preferences['custom_colors']) : null;
    
    $params = array(
        $preferences['theme_mode'] ?? 'light',
        $preferences['language_code'] ?? 'en',
        $preferences['nav_color'] ?? '#667eea',
        $preferences['primary_bg_color'] ?? '#ffffff',
        $preferences['secondary_bg_color'] ?? '#f5f7fa',
        $preferences['font_size'] ?? 'medium',
        $custom_colors_json,
        $user_id
    );
    
    $types = 'sssssssi';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        log_user_activity($user_id, 'update', 'user_preferences', $user_id, null, $preferences);
        return true;
    }
    
    return false;
}

// Update stay logged in preference
function update_stay_logged_in($user_id, $stay_logged_in) {
    $query = "UPDATE users SET stay_logged_in = ? WHERE user_id = ?";
    $params = array($stay_logged_in ? 1 : 0, $user_id);
    $types = 'ii';
    
    return execute_prepared_query($query, $params, $types);
}

// Get Google Authenticator settings
function get_user_authenticator($user_id) {
    $query = "SELECT * FROM user_authenticator WHERE user_id = ?";
    $params = array($user_id);
    $types = 'i';
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_single_row($result) : null;
}

// Setup Google Authenticator
function setup_user_authenticator($user_id, $secret_key, $qr_code_url, $backup_codes) {
    $query = "INSERT INTO user_authenticator (user_id, secret_key, qr_code_url, backup_codes, is_enabled) 
              VALUES (?, ?, ?, ?, 1)
              ON DUPLICATE KEY UPDATE 
              secret_key = ?, qr_code_url = ?, backup_codes = ?, is_enabled = 1";
    
    $backup_codes_json = json_encode($backup_codes);
    
    $params = array($user_id, $secret_key, $qr_code_url, $backup_codes_json, $secret_key, $qr_code_url, $backup_codes_json);
    $types = 'isssiss';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        log_user_activity($user_id, 'update', 'user_authenticator', $user_id, null, array('action' => '2fa_setup'));
        return true;
    }
    
    return false;
}

// Deactivate user account
function deactivate_user($user_id) {
    $query = "UPDATE users SET is_active = 0 WHERE user_id = ?";
    $params = array($user_id);
    $types = 'i';
    
    $result = execute_prepared_query($query, $params, $types);
    
    if ($result) {
        log_user_activity($user_id, 'update', 'users', $user_id, null, array('action' => 'account_deactivated'));
        return true;
    }
    
    return false;
}

// Get user activity logs
function get_user_activity_logs($user_id, $limit = 10) {
    $query = "SELECT * FROM user_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $params = array($user_id, $limit);
    $types = 'ii';
    
    $result = execute_prepared_query($query, $params, $types);
    
    return $result ? fetch_all_rows($result) : array();
}

// Check if user has permission for action
function check_user_permission($user_id, $action, $target_user_type = null) {
    $user = get_user_by_id($user_id);
    
    if (!$user || !$user['is_active']) {
        return false;
    }
    
    $user_type = $user['user_type'];
    
    // Owner has all permissions
    if ($user_type === 'owner') {
        return true;
    }
    
    // Manager permissions
    if ($user_type === 'manager') {
        $manager_actions = array('create_tenant', 'update_tenant', 'assign_flat', 'manage_payments', 'send_messages');
        return in_array($action, $manager_actions);
    }
    
    // Tenant permissions
    if ($user_type === 'tenant') {
        $tenant_actions = array('update_profile', 'view_payments', 'send_messages', 'service_request');
        return in_array($action, $tenant_actions);
    }
    
    return false;
}
?>