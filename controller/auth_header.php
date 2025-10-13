<?php
if (basename($_SERVER['PHP_SELF']) == 'auth_header.php') {
    die('Direct access not permitted');
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../model/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: " . __DIR__ . "/../view/login.php?error=login_required");
    exit();
}

// Initialize session timeout if not set
if (!isset($_SESSION['session_timeout'])) {
    $_SESSION['session_timeout'] = time() + 60;
}

// Check session timeout
if (time() > $_SESSION['session_timeout']) {
    session_destroy();
    header("Location: " . __DIR__ . "/../view/login.php?error=session_expired");
    exit();
}

// Extend session based on stay logged in
if (isset($_SESSION['stay_logged_in']) && $_SESSION['stay_logged_in']) {
    $_SESSION['session_timeout'] = time() + (45 * 24 * 60 * 60); // 45 days
} else {
    $_SESSION['session_timeout'] = time() + 60; // 1 minute
}

// Update last activity
$_SESSION['last_activity'] = time();

// Get current user data
$current_user = array(
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Unknown',
    'user_type' => $_SESSION['user_type'] ?? 'tenant',
    'full_name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['email'] ?? '',
    'profile_picture_url' => $_SESSION['profile_picture_url'] ?? ''
);

// Get user preferences
$user_preferences = array(
    'theme_mode' => $_SESSION['theme_mode'] ?? 'light',
    'language_code' => $_SESSION['language_code'] ?? 'en',
    'nav_color' => $_SESSION['nav_color'] ?? '#667eea',
    'primary_bg_color' => $_SESSION['primary_bg_color'] ?? '#ffffff',
    'secondary_bg_color' => $_SESSION['secondary_bg_color'] ?? '#f5f7fa',
    'font_size' => $_SESSION['font_size'] ?? 'medium'
);
?>