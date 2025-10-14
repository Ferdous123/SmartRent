<?php

require_once 'database.php';
require_once '../lib/GoogleAuthenticator.php';


function setup_user_2fa($user_id, $email, $full_name) {
    $ga = new PHPGangsta_GoogleAuthenticator();
    

    $secret = $ga->createSecret();
    

    $issuer = 'SmartRent';
    $account_name = $email;
    

    $qr_url = $ga->getQRCodeGoogleUrl($account_name, $secret, $issuer);
    

    $backup_codes = generate_backup_codes();
    $backup_codes_json = json_encode($backup_codes);
    

    $query = "INSERT INTO user_authenticator (user_id, secret_key, backup_codes, is_enabled, created_at) 
              VALUES (?, ?, ?, 0, NOW())
              ON DUPLICATE KEY UPDATE 
              secret_key = VALUES(secret_key), 
              backup_codes = VALUES(backup_codes),
              is_enabled = 0,
              created_at = NOW()";
    
    $result = execute_prepared_query($query, array($user_id, $secret, $backup_codes_json), 'iss');
    
    if ($result) {
        $response = array(
            'success' => true,
            'message' => '2FA setup initiated',
            'qr_url' => $qr_url,
            'secret' => $secret,
            'backup_codes' => $backup_codes
        );
        return $response;
    } else {
        $response = array('success' => false, 'message' => 'Failed to setup 2FA');
        return $response;
    }
}


function verify_and_enable_2fa($user_id, $verification_code) {
    try {

        $query = "SELECT secret_key FROM user_authenticator WHERE user_id = ? AND is_enabled = 0";
        $result = execute_prepared_query($query, array($user_id), 'i');
        
        if (!$result || $result->num_rows === 0) {
            return array('success' => false, 'message' => 'No pending 2FA setup found');
        }
        
        $auth_data = fetch_single_row($result);
        $secret = $auth_data['secret_key'];
        

        $ga = new PHPGangsta_GoogleAuthenticator();
        $is_valid = $ga->verifyCode($secret, $verification_code, 2);
        
        if ($is_valid) {

            $update_query = "UPDATE user_authenticator SET is_enabled = 1, last_used = NOW() WHERE user_id = ?";
            $update_result = execute_prepared_query($update_query, array($user_id), 'i');
            
            if ($update_result) {
                log_user_activity($user_id, 'enable_2fa', 'user_authenticator', $user_id, null,
                                 array('action' => '2fa_enabled', 'method' => 'google_authenticator'));
                
                return array(
                    'success' => true,
                    'message' => '2FA enabled successfully! Save your backup codes.'
                );
            } else {
                return array('success' => false, 'message' => 'Failed to enable 2FA');
            }
        } else {
            return array('success' => false, 'message' => 'Invalid verification code');
        }
        
    } catch (Exception $e) {
        error_log("2FA Verification Error: " . $e->getMessage());
        return array('success' => false, 'message' => 'Verification failed');
    }
}


function verify_2fa_code($user_id, $code) {

    $query = "SELECT secret_key FROM user_authenticator WHERE user_id = ? AND is_enabled = 1";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if (!$result) {
        $response = array('success' => false, 'message' => '2FA not enabled');
        return $response;
    }
    
    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0) {
        $response = array('success' => false, 'message' => '2FA not enabled');
        return $response;
    }
    
    $auth_data = fetch_single_row($result);
    $secret = $auth_data['secret_key'];
    

    $ga = new PHPGangsta_GoogleAuthenticator();
    $is_valid = $ga->verifyCode($secret, $code, 2);
    
    if ($is_valid) {

        $activity_data = array('action' => '2fa_verified', 'success' => true);
        log_user_activity($user_id, 'verify_2fa', 'user_authenticator', $user_id, null, $activity_data);
        
        $response = array('success' => true, 'message' => '2FA verified');
        return $response;
    } else {

        $activity_data = array('action' => '2fa_verification_failed', 'success' => false);
        log_user_activity($user_id, 'verify_2fa', 'user_authenticator', $user_id, null, $activity_data);
        
        $response = array('success' => false, 'message' => 'Invalid 2FA code');
        return $response;
    }
}


function disable_2fa($user_id, $verification_code) {
    try {

        $verify_result = verify_2fa_code($user_id, $verification_code);
        
        if (!$verify_result['success']) {
            return array('success' => false, 'message' => 'Invalid 2FA code');
        }
        

        $query = "UPDATE user_authenticator SET is_enabled = 0, last_used = NOW() WHERE user_id = ?";
        $result = execute_prepared_query($query, array($user_id), 'i');
        
        if ($result) {
            log_user_activity($user_id, 'disable_2fa', 'user_authenticator', $user_id, null,
                             array('action' => '2fa_disabled'));
            
            return array('success' => true, 'message' => '2FA disabled successfully');
        } else {
            return array('success' => false, 'message' => 'Failed to disable 2FA');
        }
        
    } catch (Exception $e) {
        error_log("2FA Disable Error: " . $e->getMessage());
        return array('success' => false, 'message' => 'Failed to disable 2FA');
    }
}


function get_2fa_status($user_id) {
    $query = "SELECT is_enabled, created_at FROM user_authenticator WHERE user_id = ?";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result && $result->num_rows > 0) {
        $data = fetch_single_row($result);
        return array(
            'is_enabled' => (bool)$data['is_enabled'],
            'created_at' => $data['created_at']
        );
    }
    
    return array(
        'is_enabled' => false,
        'created_at' => null
    );
}


function verify_backup_code($user_id, $backup_code, $purpose = 'login') {

    $query = "SELECT backup_codes FROM user_authenticator WHERE user_id = ? AND is_enabled = 1";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if (!$result) {
        $response = array('success' => false, 'message' => '2FA not enabled');
        return $response;
    }
    
    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0) {
        $response = array('success' => false, 'message' => '2FA not enabled');
        return $response;
    }
    
    $auth_data = fetch_single_row($result);
    $backup_codes = json_decode($auth_data['backup_codes'], true);
    
    if (!$backup_codes) {
        $response = array('success' => false, 'message' => 'No backup codes available');
        return $response;
    }
    

    $code_key = find_backup_code($backup_codes, $backup_code);
    
    if ($code_key === false) {
        $response = array('success' => false, 'message' => 'Invalid backup code');
        return $response;
    }
    
    $code_used = $backup_codes[$code_key]['used'];
    if ($code_used) {
        $response = array('success' => false, 'message' => 'Backup code already used');
        return $response;
    }
    

    $backup_codes[$code_key]['used'] = true;
    $backup_codes[$code_key]['used_at'] = date('Y-m-d H:i:s');
    $backup_codes[$code_key]['used_for'] = $purpose;
    

    $backup_codes_json = json_encode($backup_codes);
    $update_query = "UPDATE user_authenticator SET backup_codes = ? WHERE user_id = ?";
    $update_result = execute_prepared_query($update_query, array($backup_codes_json, $user_id), 'si');
    
    if ($update_result) {

        $activity_data = array('action' => 'backup_code_used', 'purpose' => $purpose);
        log_user_activity($user_id, 'use_backup_code', 'user_authenticator', $user_id, null, $activity_data);
        
        $response = array('success' => true, 'message' => 'Backup code verified');
        return $response;
    } else {
        $response = array('success' => false, 'message' => 'Failed to update backup codes');
        return $response;
    }
}


function generate_backup_codes($count = 8) {
    $codes = array();
    
    for ($i = 0; $i < $count; $i++) {
        $random_bytes = random_bytes(16);
        $md5_hash = md5($random_bytes);
        $code = strtoupper(substr($md5_hash, 0, 8));
        
        $code_data = array(
            'code' => $code,
            'used' => false,
            'generated_at' => date('Y-m-d H:i:s')
        );
        
        $codes[] = $code_data;
    }
    
    return $codes;
}


function get_masked_backup_codes($user_id) {
    $query = "SELECT backup_codes FROM user_authenticator WHERE user_id = ? AND is_enabled = 1";
    $result = execute_prepared_query($query, array($user_id), 'i');
    
    if ($result) {
        $num_rows = mysqli_num_rows($result);
        if ($num_rows > 0) {
            $auth_data = fetch_single_row($result);
            $backup_codes = json_decode($auth_data['backup_codes'], true);
            
            if ($backup_codes) {
                $masked_codes = array();
                
                for ($i = 0; $i < count($backup_codes); $i++) {
                    $code_data = $backup_codes[$i];
                    $is_used = $code_data['used'];
                    
                    if ($is_used) {
                        $masked_code = '****' . substr($code_data['code'], -2);
                    } else {
                        $masked_code = $code_data['code'];
                    }
                    
                    $masked_codes[] = array(
                        'code' => $masked_code,
                        'used' => $is_used
                    );
                }
                
                return $masked_codes;
            }
        }
    }
    
    return array();
}


function user_has_2fa_enabled($user_id) {
    $status = get_2fa_status($user_id);
    $is_enabled = $status['is_enabled'];
    return $is_enabled;
}


function regenerate_backup_codes($user_id) {
    $new_codes = generate_backup_codes();
    $codes_json = json_encode($new_codes);
    
    $query = "UPDATE user_authenticator SET backup_codes = ?, backup_codes_generated_at = NOW() WHERE user_id = ? AND is_enabled = 1";
    $result = execute_prepared_query($query, array($codes_json, $user_id), 'si');
    
    if ($result) {
        $activity_data = array('action' => 'backup_codes_regenerated');
        log_user_activity($user_id, 'regenerate_backup_codes', 'user_authenticator', $user_id, null, $activity_data);
        
        $response = array(
            'success' => true,
            'backup_codes' => $new_codes
        );
        return $response;
    }
    
    $response = array('success' => false, 'message' => 'Failed to regenerate backup codes');
    return $response;
}


function find_backup_code($backup_codes, $search_code) {
    for ($i = 0; $i < count($backup_codes); $i++) {
        $code_data = $backup_codes[$i];
        $code = $code_data['code'];
        if ($code === $search_code) {
            return $i;
        }
    }
    return false;
}

?>