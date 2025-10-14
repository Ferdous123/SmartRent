<?php

require_once '../controller/auth_header.php';


if (!in_array($current_user['user_type'], ['owner', 'manager'])) {
    header("Location: ../controller/dashboard_controller.php?error=access_denied");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code'] ?? 'en'; ?>">
<head>
    <title>Buildings Management - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../view/css/dashboard.css">
    <link rel="stylesheet" href="../view/css/building.css">
    <link rel="stylesheet" href="../view/css/buildings-page.css">
    <link rel="stylesheet" href="../view/css/add-building-modal.css">
    <style>
        :root {
            --nav-color: <?php echo $user_preferences['nav_color'] ?? '#667eea'; ?>;
            --primary-bg: <?php echo $user_preferences['primary_bg_color'] ?? '#ffffff'; ?>;
            --secondary-bg: <?php echo $user_preferences['secondary_bg_color'] ?? '#f5f7fa'; ?>;
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: var(--secondary-bg);
        }
    </style>
</head>
<body>
    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="../controller/dashboard_controller.php" class="nav-link">Dashboard</a>
                    <a href="../view/buildings.php" class="nav-link active">Buildings</a>
                    <a href="../view/tenants.php" class="nav-link">Tenants</a>
                    <a href="#payments" class="nav-link">Payments</a>
                </nav>
            </div>
            
            <div class="nav-right">
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            <?php if (!empty($current_user['profile_picture_url'])): ?>
                                <img src="<?php echo htmlspecialchars($current_user['profile_picture_url']); ?>" alt="Profile">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-menu" id="userMenu" style="display: none;">
                        <a href="../controller/profile_controller.php">Profile Settings</a>
                        <a href="#backup">Backup Data</a>
                        <a href="#logs">Activity Logs</a>
                        <a href="../controller/working_login.php?action=logout" style="color: #dc3545;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="buildings-main">
        <div class="page-header">
            <div class="header-content">
                <h1>Buildings Management</h1>
                <p>Manage your properties, flats, tenants, and managers</p>
            </div>
            <div class="header-actions">
                <?php if ($current_user['user_type'] === 'owner'): ?>
                <button class="btn-primary" onclick="showAddBuildingModal()">
                    <span class="btn-icon">+</span>
                    Add Building
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="buildings-container" id="buildingsContainer">
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Loading buildings...</p>
            </div>
        </div>
    </main>

    <?php include 'modals/add_building_modal.php'; ?>
    <?php include 'modals/edit_building_modal.php'; ?>
    <?php include 'modals/add_flat_modal.php'; ?>
    <?php include 'modals/edit_flat_modal.php'; ?>
    <?php include 'modals/assign_manager_modal.php'; ?>

    <div id="confirmDialog" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirm Action</h3>
                <button class="modal-close" onclick="closeConfirmDialog()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeConfirmDialog()">Cancel</button>
                <button class="btn-danger" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>

    <div id="messageContainer"></div>

    <script src="../view/js/global-session.js"></script>
    <script src="../view/js/building.js"></script>
    <script src="../view/js/buildings-page.js"></script>
</body>
</html>