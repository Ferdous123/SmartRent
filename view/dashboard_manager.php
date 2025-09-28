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
if ($current_user['user_type'] !== 'manager') {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code'] ?? 'en'; ?>">
<head>
    <title><?php echo ucfirst($current_user['user_type']); ?> Dashboard - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        /* Complete Dashboard CSS */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --nav-color: <?php echo $user_preferences['nav_color'] ?? '#667eea'; ?>;
            --primary-bg: <?php echo $user_preferences['primary_bg_color'] ?? '#ffffff'; ?>;
            --secondary-bg: <?php echo $user_preferences['secondary_bg_color'] ?? '#f5f7fa'; ?>;
            --font-size: <?php echo ($user_preferences['font_size'] ?? 'medium') === 'small' ? '14px' : (($user_preferences['font_size'] ?? 'medium') === 'large' ? '18px' : '16px'); ?>;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--secondary-bg);
            font-size: var(--font-size);
            line-height: 1.6;
        }
        
        /* Navigation */
        .dashboard-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            height: 70px;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .logo h2 {
            color: var(--nav-color);
            font-weight: 700;
        }
        
        .main-nav {
            display: flex;
            gap: 1rem;
        }
        
        .nav-link {
            color: #666;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--nav-color);
            color: white;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* User dropdown */
        .user-dropdown {
            position: relative;
        }
        
        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .user-btn:hover {
            background: #f5f7fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--nav-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .dropdown-arrow {
            color: #666;
            font-size: 12px;
        }
        
        /* Main content */
        .dashboard-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header-content h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header-content p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-primary, .btn-secondary {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--nav-color);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--nav-color);
            border: 2px solid var(--nav-color);
        }
        
        .btn-secondary:hover {
            background: var(--nav-color);
            color: white;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--nav-color);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .stat-content p {
            color: #666;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stat-detail {
            color: #999;
            font-size: 0.9rem;
        }
        
        /* Content grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            color: #333;
            font-weight: 600;
        }
        
        .view-all-btn {
            color: var(--nav-color);
            text-decoration: none;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        /* Quick actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }
        
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .quick-action-btn:hover {
            background: var(--nav-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .action-icon {
            font-size: 1.5rem;
        }
        
        /* Activities */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .activity-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 50%;
        }
        
        .activity-content p {
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.9rem;
        }
        
        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
            color: #666;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
            }
            
            .main-nav {
                display: none;
            }
            
            .dashboard-main {
                padding: 1rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                    <a href="#dashboard" class="nav-link active">Dashboard</a>
                    <?php if ($current_user['user_type'] === 'manager'): ?>
                        <a href="#buildings" class="nav-link">My Buildings</a>
                        <a href="#tenants" class="nav-link">Tenants</a>
                        <a href="#maintenance" class="nav-link">Maintenance</a>
                    <?php elseif ($current_user['user_type'] === 'owner'): ?>
                        <a href="#buildings" class="nav-link">Buildings</a>
                        <a href="#managers" class="nav-link">Managers</a>
                        <a href="#reports" class="nav-link">Reports</a>
                    <?php else: ?>
                        <a href="#profile" class="nav-link">Profile</a>
                        <a href="#requests" class="nav-link">Service Requests</a>
                    <?php endif; ?>
                    <a href="#payments" class="nav-link">Payments</a>
                </nav>
            </div>
            
            <div class="nav-right">
                <!-- User Menu -->
                <div class="user-dropdown">
                    <button class="user-btn" onclick="toggleUserMenu()">
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
                        <a href="../controller/dashboard_controller.php?redirect=profile">Profile Settings</a>
                        <a href="#preferences">Preferences</a>
                        <hr>
                        <a href="../controller/working_login.php?action=logout">Logout</a>
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
                <h1>Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                <p><?php echo ucfirst($current_user['user_type']); ?> Dashboard - <?php 
                    if ($current_user['user_type'] === 'manager') {
                        echo 'Manage your assigned properties';
                    } elseif ($current_user['user_type'] === 'owner') {
                        echo 'Oversee your property portfolio';
                    } else {
                        echo 'Your rental information and services';
                    }
                ?></p>
            </div>
            <div class="header-actions">
                <?php if ($current_user['user_type'] === 'manager'): ?>
                    <button class="btn-primary" onclick="assignTenant()">
                        <span class="btn-icon">👤</span>
                        Assign Tenant
                    </button>
                    <button class="btn-secondary" onclick="viewServiceRequests()">
                        <span class="btn-icon">🔧</span>
                        Service Requests
                    </button>
                <?php elseif ($current_user['user_type'] === 'owner'): ?>
                    <button class="btn-primary" onclick="addBuilding()">
                        <span class="btn-icon">🏢</span>
                        Add Building
                    </button>
                    <button class="btn-secondary" onclick="viewReports()">
                        <span class="btn-icon">📊</span>
                        View Reports
                    </button>
                <?php else: ?>
                    <button class="btn-primary" onclick="makePayment()">
                        <span class="btn-icon">💳</span>
                        Make Payment
                    </button>
                    <button class="btn-secondary" onclick="createServiceRequest()">
                        <span class="btn-icon">🔧</span>
                        Service Request
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <?php if ($current_user['user_type'] === 'manager'): ?>
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-content">
                        <h3 id="managedBuildings">--</h3>
                        <p>Managed Buildings</p>
                        <div class="stat-detail">Buildings under your management</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏠</div>
                    <div class="stat-content">
                        <h3 id="totalFlats">--</h3>
                        <p>Total Flats</p>
                        <div class="stat-detail">Flats across all buildings</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3 id="totalTenants">--</h3>
                        <p>Active Tenants</p>
                        <div class="stat-detail">Tenants under your management</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-content">
                        <h3 id="pendingRequests">--</h3>
                        <p>Pending Requests</p>
                        <div class="stat-detail">Service requests awaiting action</div>
                    </div>
                </div>
            <?php elseif ($current_user['user_type'] === 'owner'): ?>
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-content">
                        <h3 id="totalBuildings">--</h3>
                        <p>Total Buildings</p>
                        <div class="stat-detail">Properties in your portfolio</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3 id="monthlyRevenue">--</h3>
                        <p>Monthly Revenue</p>
                        <div class="stat-detail">Current month collections</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="occupancyRate">--</h3>
                        <p>Occupancy Rate</p>
                        <div class="stat-detail">Percentage of occupied flats</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3 id="totalTenants">--</h3>
                        <p>Total Tenants</p>
                        <div class="stat-detail">Active tenants across properties</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon">🏠</div>
                    <div class="stat-content">
                        <h3 id="flatInfo">--</h3>
                        <p>Current Flat</p>
                        <div class="stat-detail">Your assigned accommodation</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💳</div>
                    <div class="stat-content">
                        <h3 id="outstandingDues">--</h3>
                        <p>Outstanding Dues</p>
                        <div class="stat-detail">Amount pending payment</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3 id="lastPayment">--</h3>
                        <p>Last Payment</p>
                        <div class="stat-detail">Most recent payment date</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-content">
                        <h3 id="activeRequests">--</h3>
                        <p>Active Requests</p>
                        <div class="stat-detail">Pending service requests</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Area -->
        <div class="content-grid">
            <!-- Recent Activity Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Activity</h3>
                    <button class="view-all-btn">View All</button>
                </div>
                <div class="card-content">
                    <div class="activity-list" id="activityList">
                        <div class="loading">Loading activities...</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <?php if ($current_user['user_type'] === 'manager'): ?>
                            <button class="quick-action-btn" onclick="assignTenant()">
                                <span class="action-icon">👤</span>
                                <span>Assign Tenant</span>
                            </button>
                            <button class="quick-action-btn" onclick="generateOTP()">
                                <span class="action-icon">🔑</span>
                                <span>Generate OTP</span>
                            </button>
                            <button class="quick-action-btn" onclick="recordPayment()">
                                <span class="action-icon">💳</span>
                                <span>Record Payment</span>
                            </button>
                            <button class="quick-action-btn" onclick="handleServiceRequest()">
                                <span class="action-icon">🔧</span>
                                <span>Handle Request</span>
                            </button>
                        <?php elseif ($current_user['user_type'] === 'owner'): ?>
                            <button class="quick-action-btn" onclick="addBuilding()">
                                <span class="action-icon">🏢</span>
                                <span>Add Building</span>
                            </button>
                            <button class="quick-action-btn" onclick="assignManager()">
                                <span class="action-icon">👨‍💼</span>
                                <span>Assign Manager</span>
                            </button>
                            <button class="quick-action-btn" onclick="viewReports()">
                                <span class="action-icon">📊</span>
                                <span>View Reports</span>
                            </button>
                            <button class="quick-action-btn" onclick="backupData()">
                                <span class="action-icon">💾</span>
                                <span>Backup Data</span>
                            </button>
                        <?php else: ?>
                            <button class="quick-action-btn" onclick="makePayment()">
                                <span class="action-icon">💳</span>
                                <span>Make Payment</span>
                            </button>
                            <button class="quick-action-btn" onclick="createServiceRequest()">
                                <span class="action-icon">🔧</span>
                                <span>Service Request</span>
                            </button>
                            <button class="quick-action-btn" onclick="downloadReceipt()">
                                <span class="action-icon">🧾</span>
                                <span>Download Receipt</span>
                            </button>
                            <button class="quick-action-btn" onclick="updateProfile()">
                                <span class="action-icon">⚙️</span>
                                <span>Update Profile</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Dashboard JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            loadRecentActivity();
        });

        function loadDashboardStats() {
            fetch('../controller/dashboard_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_dashboard_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            })
            .catch(error => console.error('Error loading stats:', error));
        }

        function updateStatsDisplay(stats) {
            const userType = '<?php echo $current_user['user_type']; ?>';
            
            if (userType === 'manager') {
                document.getElementById('managedBuildings').textContent = stats.managed_buildings || '0';
                document.getElementById('totalFlats').textContent = stats.total_flats || '0';
                document.getElementById('totalTenants').textContent = stats.total_tenants || '0';
                document.getElementById('pendingRequests').textContent = stats.pending_service_requests || '0';
            } else if (userType === 'owner') {
                document.getElementById('totalBuildings').textContent = stats.total_buildings || '0';
                document.getElementById('monthlyRevenue').textContent = '৳' + (stats.monthly_revenue || '0');
                document.getElementById('occupancyRate').textContent = (stats.occupancy_rate || '0') + '%';
                document.getElementById('totalTenants').textContent = stats.total_tenants || '0';
            } else {
                document.getElementById('flatInfo').textContent = stats.flat_info || 'Not Assigned';
                document.getElementById('outstandingDues').textContent = '৳' + (stats.outstanding_dues || '0');
                document.getElementById('lastPayment').textContent = stats.last_payment_date || 'No payments';
                document.getElementById('activeRequests').textContent = stats.active_service_requests || '0';
            }
        }

        function loadRecentActivity() {
            setTimeout(() => {
                const activityList = document.getElementById('activityList');
                activityList.innerHTML = `
                    <div class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <p><strong>Dashboard Loaded</strong></p>
                            <p>Welcome to your SmartRent dashboard</p>
                            <span class="activity-time">Just now</span>
                        </div>
                    </div>
                `;
            }, 1000);
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userDropdown = document.querySelector('.user-dropdown');
            const userMenu = document.getElementById('userMenu');
            
            if (!userDropdown.contains(event.target)) {
                userMenu.style.display = 'none';
            }
        });

        // Quick action functions (placeholders)
        function assignTenant() { alert('Assign Tenant feature coming soon!'); }
        function generateOTP() { alert('Generate OTP feature coming soon!'); }
        function recordPayment() { alert('Record Payment feature coming soon!'); }
        function handleServiceRequest() { alert('Handle Service Request feature coming soon!'); }
        function addBuilding() { alert('Add Building feature coming soon!'); }
        function assignManager() { alert('Assign Manager feature coming soon!'); }
        function viewReports() { alert('View Reports feature coming soon!'); }
        function backupData() { alert('Backup Data feature coming soon!'); }
        function makePayment() { alert('Make Payment feature coming soon!'); }
        function createServiceRequest() { alert('Create Service Request feature coming soon!'); }
        function downloadReceipt() { alert('Download Receipt feature coming soon!'); }
        function updateProfile() { alert('Update Profile feature coming soon!'); }
        function viewServiceRequests() { alert('Service Requests feature coming soon!'); }
    </script>
</body>
</html>