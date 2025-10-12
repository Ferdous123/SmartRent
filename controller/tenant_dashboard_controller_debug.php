<?php
// Temporary debug version - DELETE after fixing
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Starting<br>";

try {
    require_once 'auth_header.php';
    echo "Step 2: Auth loaded<br>";
    
    require_once '../model/database.php';
    echo "Step 3: Database loaded<br>";
    
    require_once '../model/tenant_dashboard_model.php';
    echo "Step 4: Dashboard model loaded<br>";
    
    if (file_exists('../model/payment_gateway_helper.php')) {
        require_once '../model/payment_gateway_helper.php';
        echo "Step 5: Payment gateway loaded<br>";
    } else {
        echo "Step 5: Payment gateway NOT FOUND<br>";
    }
    
    echo "Step 6: User ID = " . $current_user['user_id'] . "<br>";
    echo "Step 7: User type = " . $current_user['user_type'] . "<br>";
    
    if ($current_user['user_type'] !== 'tenant') {
        echo "ERROR: Not a tenant!<br>";
    } else {
        echo "Step 8: All checks passed!<br>";
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>