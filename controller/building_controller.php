<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../model/database.php';
require_once '../model/property_model.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Not logged in'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'create_building':
            handle_create_building($user_id, $user_type);
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            break;
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
}

// Handle building creation
function handle_create_building($user_id, $user_type) {
    // Only owners can create buildings
    if ($user_type !== 'owner') {
        echo json_encode(array('success' => false, 'message' => 'Access denied'));
        return;
    }
    
    // Validate input
    $building_name = isset($_POST['building_name']) ? trim($_POST['building_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $total_floors = isset($_POST['total_floors']) ? intval($_POST['total_floors']) : 0;
    $flats_data_json = isset($_POST['flats_data']) ? $_POST['flats_data'] : '';
    
    // Validation
    if (empty($building_name)) {
        echo json_encode(array('success' => false, 'message' => 'Building name is required'));
        return;
    }
    
    if (empty($address)) {
        echo json_encode(array('success' => false, 'message' => 'Address is required'));
        return;
    }
    
    if ($total_floors < 1 || $total_floors > 50) {
        echo json_encode(array('success' => false, 'message' => 'Invalid number of floors'));
        return;
    }
    
    // Parse flats data
    $flats_data = json_decode($flats_data_json, true);
    if (!$flats_data || !is_array($flats_data)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid flats data'));
        return;
    }
    
    // Calculate total flats
    $total_flats = count($flats_data);
    
    // Begin transaction
    begin_transaction();
    
    try {
        // Create building
        $building_result = create_building($user_id, $building_name, $address, $total_floors, $total_flats);
        
        if (!$building_result['success']) {
            throw new Exception($building_result['message']);
        }
        
        $building_id = $building_result['building_id'];
        
        // Create flats
        $flats_created = 0;
        foreach ($flats_data as $flat) {
            $flat_number = $flat['flat_number'];
            $floor_number = $flat['floor_number'];
            
            $flat_result = create_flat($building_id, $flat_number, $floor_number, null, null, 0.00, $user_id);
            
            if ($flat_result['success']) {
                $flats_created++;
            }
        }
        
        // Check if all flats were created
        if ($flats_created !== $total_flats) {
            throw new Exception('Some flats could not be created');
        }
        
        // Commit transaction
        commit_transaction();
        
        // Log activity
        log_user_activity($user_id, 'create', 'buildings', $building_id, null, array(
            'building_name' => $building_name,
            'total_floors' => $total_floors,
            'total_flats' => $total_flats)
        );
        
        echo json_encode(array(
            'success' => true, 
            'message' => 'Building created successfully with ' . $flats_created . ' flats',
            'building_id' => $building_id
        ));
        
    } catch (Exception $e) {
        // Rollback on error
        rollback_transaction();
        error_log("Building creation error: " . $e->getMessage());
        
        echo json_encode(array(
            'success' => false, 
            'message' => 'Failed to create building: ' . $e->getMessage()
        ));
    }
}
?>