<?php

if (!isset($current_user)) {
    require_once '../controller/session_controller.php';
    

    if (!is_user_logged_in()) {
        header("Location: ../view/login.php");
        exit();
    }
    

    $current_user = get_logged_in_user();
}


if ($current_user['user_type'] !== 'owner') {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code'] ?? 'en'; ?>">
<head>
    <title>Owner Dashboard - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../view/css/dashboard.css">
    <link rel="stylesheet" href="../view/css/building.css">
    <link rel="stylesheet" href="../view/css/add-building-modal.css">
    <style>

        :root {
            --nav-color: <?php echo $user_preferences['nav_color'] ?? '#667eea'; ?>;
            --primary-bg: <?php echo $user_preferences['primary_bg_color'] ?? '#ffffff'; ?>;
            --secondary-bg: <?php echo $user_preferences['secondary_bg_color'] ?? '#f5f7fa'; ?>;
            --font-size: <?php echo ($user_preferences['font_size'] ?? 'medium') === 'small' ? '14px' : (($user_preferences['font_size'] ?? 'medium') === 'large' ? '18px' : '16px'); ?>;
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: var(--primary-bg);
            font-size: var(--font-size);
        }
    </style>
</head>
<body class="<?php echo $user_preferences['theme_mode'] ?? 'light'; ?>-theme">

    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="../controller/dashboard_controller.php" class="nav-link">Dashboard</a>
                    <a href="../view/buildings.php" class="nav-link">Buildings</a>
                    <a href="../view/tenants.php" class="nav-link">Tenants</a>
                    <a href="#payments" class="nav-link">Payments</a>
                    <a href="#reports" class="nav-link">Reports</a>
                </nav>
            </div>
            
            <div class="nav-right">

                <div class="notifications-dropdown">
                    <button class="notification-btn" id="notificationBtn">
                        <span class="notification-icon">🔔</span>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
                    <div class="notifications-panel" id="notificationsPanel" style="display: none;">
                        <div class="notifications-header">
                            <h4>Notifications</h4>
                            <button class="mark-all-read" onclick="markAllNotificationsRead()">Mark All Read</button>
                        </div>
                        <div class="notifications-list" id="notificationsList">

                        </div>
                    </div>
                </div>


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


    <main class="dashboard-main">

        <div class="dashboard-header">
            <div class="header-content">
                <h1>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                <p>Manage your properties efficiently</p>
            </div>
            <div class="header-actions">
                <button class="btn-primary" onclick="showAddBuildingModal()">
                    <span class="btn-icon">🏢</span>
                    Add Building
                </button>
                <button class="btn-secondary" onclick="showReportsModal()">
                    <span class="btn-icon">📊</span>
                    Generate Report
                </button>
            </div>
        </div>


        <div class="stats-grid">
            <div class="stat-card buildings-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h3 id="totalBuildings">--</h3>
                    <p>Total Buildings</p>
                    <div class="stat-trend">
                        <span class="trend-indicator positive"></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <div class="stat-card flats-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-content">
                    <h3 id="totalFlats">--</h3>
                    <p>Total Flats</p>
                    <div class="stat-detail">
                        <span id="occupiedFlats">--</span> occupied • <span id="availableFlats">--</span> available
                    </div>
                </div>
            </div>

            <div class="stat-card tenants-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3 id="totalTenants">--</h3>
                    <p>Active Tenants</p>
                    <div class="stat-detail">
                        <span id="occupancyRate">--%</span> occupancy rate
                    </div>
                </div>
            </div>

            <div class="stat-card revenue-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3 id="monthlyRevenue">৳--</h3>
                    <p>This Month Revenue</p>
                    <div class="stat-trend">
                        <span class="trend-indicator positive"></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <div class="stat-card outstanding-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3 id="outstandingCount">--</h3>
                    <p>Outstanding Payments</p>
                    <div class="stat-detail">
                        <span id="outstandingAmount">৳--</span> total amount
                    </div>
                </div>
            </div>
        </div>


        <div class="content-grid">

            <div class="card quick-actions-card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="showAddBuildingModal()">
                            <span class="action-icon">🏢</span>
                            <span>Add Building</span>
                        </button>
                        <button class="quick-action-btn" onclick="goToAddTenant()">
                            <span class="action-icon">👤</span>
                            <span>Add Tenant</span>
                        </button>
                        <button class="quick-action-btn" onclick="showAddManagerModal()">
                            <span class="action-icon">⚡</span>
                            <span>Add Manager</span>
                        </button>
                        <button class="quick-action-btn" onclick="showPaymentModal()">
                            <span class="action-icon">💳</span>
                            <span>Record Payment</span>
                        </button>
                        <button class="quick-action-btn" onclick="generateSlips()">
                            <span class="action-icon">🧾</span>
                            <span>Generate Slips</span>
                        </button>
                        <button class="quick-action-btn" onclick="sendNotice()">
                            <span class="action-icon">📢</span>
                            <span>Send Notice</span>
                        </button>
                        <button class="quick-action-btn" onclick="backupDatabase()">
                            <span class="action-icon">💾</span>
                            <span>Backup Data</span>
                        </button>
                        <button class="quick-action-btn" onclick="viewReports()">
                            <span class="action-icon">📊</span>
                            <span>View Reports</span>
                        </button>
                    </div>
                </div>
            </div>


            <div class="card actions-needed-card">
                <div class="card-header">
                    <h3>⚠️ Actions Needed</h3>
                    <button class="view-all-btn" onclick="window.location.href='../view/tenants.php?tab=pending'">View All</button>
                </div>
                <div class="card-content">
                    <div id="actionsNeededList" class="actions-list">
                        <p style="text-align: center; color: #999;">Loading...</p>
                    </div>
                </div>
            </div>

            <div class="card buildings-overview-card">
                <div class="card-header">
                    <h3>Buildings Overview</h3>
                    <button class="view-all-btn" onclick="window.location.href='../view/buildings.php'">Manage Buildings</button>
                </div>
                <div class="card-content">
                    <div class="buildings-list" id="buildingsList">
                        <div class="empty-state">
                            <p>Loading buildings...</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>


    <div id="twoFactorModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Setup Two-Factor Authentication</h3>
                <button class="modal-close" onclick="closeTwoFactorModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="setup-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Install Google Authenticator</h4>
                            <p>Download Google Authenticator app on your phone</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Scan QR Code</h4>
                            <div class="qr-code-placeholder">
                                <p>QR Code will appear here</p>
                            </div>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Verify Setup</h4>
                            <input type="text" placeholder="Enter 6-digit code" class="verification-input">
                            <button class="btn-primary" onclick="verify2FA()">Verify & Enable</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Loading...</p>
    </div>


    <div id="messageContainer" class="message-container"></div>


    <?php include '../view/modals/add_building_modal.php'; ?>

    <script src="../view/js/global-session.js"></script>
    <script src="../view/js/dashboard.js"></script>
    <script src="../view/js/building.js"></script>
</body>
</body>
</html>