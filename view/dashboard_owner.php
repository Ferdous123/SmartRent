<?php
// Don't start session - already started by dashboard_controller.php
if (!isset($current_user)) {
    require_once '../controller/session_controller.php';
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        header("Location: ../view/login.php");
        exit();
    }
    
    // Get current user
    $current_user = get_logged_in_user();
}

// Check if user has correct role for this dashboard
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
        /* Inline CSS for immediate loading with user preferences */
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
    <!-- Navigation Header -->
    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="../controller/dashboard_controller.php" class="nav-link">Dashboard</a>
                    <a href="../view/buildings.php" class="nav-link">Buildings</a>
                    <a href="#tenants" class="nav-link">Tenants</a>
                    <a href="#payments" class="nav-link">Payments</a>
                    <a href="#reports" class="nav-link">Reports</a>
                </nav>
            </div>
            
            <div class="nav-right">
                <!-- Notifications -->
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
                            <!-- Notifications will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
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

    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Dashboard Header -->
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

        <!-- Statistics Cards -->
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

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Quick Actions -->
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
                        <button class="quick-action-btn" onclick="showAddTenantModal()">
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

            <!-- Recent Activity -->
            <div class="card activity-card">
                <div class="card-header">
                    <h3>Recent Activity</h3>
                    <button class="view-all-btn">View All</button>
                </div>
                <div class="card-content">
                    <div class="activity-list" id="activityList">
                        <div class="activity-item">
                            <div class="activity-icon">💰</div>
                            <div class="activity-content">
                                <p><strong>Payment Received</strong></p>
                                <p>Tenant Ahmad paid ৳25,000 for Flat 3A</p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">👤</div>
                            <div class="activity-content">
                                <p><strong>New Tenant Added</strong></p>
                                <p>Sarah Rahman assigned to Flat 2B</p>
                                <span class="activity-time">5 hours ago</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">🔧</div>
                            <div class="activity-content">
                                <p><strong>Maintenance Request</strong></p>
                                <p>Plumbing issue in Building A, Flat 1C</p>
                                <span class="activity-time">1 day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Payments -->
            <div class="card outstanding-payments-card">
                <div class="card-header">
                    <h3>Outstanding Payments</h3>
                    <button class="view-all-btn">View All</button>
                </div>
                <div class="card-content">
                    <div class="outstanding-list" id="outstandingList">
                        <div class="outstanding-item overdue">
                            <div class="tenant-info">
                                <strong>Mohammad Ali</strong>
                                <span>Building A - Flat 2A</span>
                            </div>
                            <div class="amount-info">
                                <span class="amount">৳28,000</span>
                                <span class="days-overdue">15 days overdue</span>
                            </div>
                            <div class="actions">
                                <button class="btn-small btn-warning" onclick="sendReminder()">Remind</button>
                                <button class="btn-small btn-primary" onclick="recordPayment()">Record Payment</button>
                            </div>
                        </div>
                        <div class="outstanding-item due-soon">
                            <div class="tenant-info">
                                <strong>Fatima Khan</strong>
                                <span>Building B - Flat 1B</span>
                            </div>
                            <div class="amount-info">
                                <span class="amount">৳22,000</span>
                                <span class="due-date">Due in 3 days</span>
                            </div>
                            <div class="actions">
                                <button class="btn-small btn-secondary" onclick="sendReminder()">Remind</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buildings Overview -->
            <div class="card buildings-overview-card">
                <div class="card-header">
                    <h3>Buildings Overview</h3>
                    <button class="view-all-btn">Manage Buildings</button>
                </div>
                <div class="card-content">
                    <div class="buildings-list" id="buildingsList">
                        <div class="building-item">
                            <div class="building-info">
                                <h4>Green Valley Apartments</h4>
                                <p>123 Main Street, Dhaka</p>
                            </div>
                            <div class="building-stats">
                                <span class="stat">12/15 occupied</span>
                                <span class="occupancy-bar">
                                    <span class="occupancy-fill" style="width: 80%;"></span>
                                </span>
                            </div>
                            <div class="building-actions">
                                <button class="btn-small" onclick="viewBuilding(1)">View</button>
                                <button class="btn-small" onclick="manageBuilding(1)">Manage</button>
                            </div>
                        </div>
                        <div class="building-item">
                            <div class="building-info">
                                <h4>Sunset Towers</h4>
                                <p>456 Park Avenue, Dhaka</p>
                            </div>
                            <div class="building-stats">
                                <span class="stat">8/10 occupied</span>
                                <span class="occupancy-bar">
                                    <span class="occupancy-fill" style="width: 80%;"></span>
                                </span>
                            </div>
                            <div class="building-actions">
                                <button class="btn-small" onclick="viewBuilding(2)">View</button>
                                <button class="btn-small" onclick="manageBuilding(2)">Manage</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 2FA Setup Modal -->
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

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Loading...</p>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="message-container"></div>

    <!-- Include Add Building Modal -->
    <?php include '../view/modals/add_building_modal.php'; ?>
    <!-- Scripts -->
    <script src="../view/js/global-session.js"></script>
    <script src="../view/js/dashboard.js"></script>
    <script src="../view/js/building.js"></script>
</body>
</body>
</html>