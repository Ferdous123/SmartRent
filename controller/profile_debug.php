<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing profile include...<br>";

session_start();
require_once '../model/database.php';
require_once '../model/user_model.php';
require_once 'session_controller.php';

if (!is_user_logged_in()) {
    die("Not logged in");
}

$current_user = get_logged_in_user();
$user_profile = get_user_by_id($current_user['user_id']);
$user_preferences = get_user_preferences($current_user['user_id']);

echo "Variables set successfully<br>";
echo "About to include profile.php...<br>";

include '../view/profile.php';
?>