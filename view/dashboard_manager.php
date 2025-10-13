<?php
// Don't start session - already started by dashboard_controller.php
if (!isset($current_user)) {
    require_once '../controller/session_controller.php';
    
    if (!is_user_logged_in()) {
        header("Location: ../view/login.php");
        exit();
    }
    
    $current_user = get_logged_in_user();
}

if ($current_user['user_type'] !== 'manager') {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}

// Initialize user preferences with defaults if not set
if (!isset($user_preferences)) {
    $user_preferences = [
        'language_code' => 'en',
        'nav_color' => '#667eea',
        'primary_bg_color' => '#ffffff',
        'secondary_bg_color' => '#f5f7fa',
        'font_size' => 'medium',
        'theme_mode' => 'light'
    ];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($current_user['user_type']); ?> Dashboard - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --nav-color: <?php echo $user_preferences['nav_color']; ?>;
            --primary-bg: <?php echo $user_preferences['primary_bg_color']; ?>;
            --secondary-bg: <?php echo $user_preferences['secondary_bg_color']; ?>;
            --font-size: <?php echo $user_preferences['font_size'] === 'small' ? '14px' : ($user_preferences['font_size'] === 'large' ? '18px' : '16px'); ?>;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--secondary-bg);
            font-size: var(--font-size);
            line-height: 1.6;
        }
        
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
        
        .user-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            margin-top: 0.5rem;
            min-width: 200px;
            z-index: 1001;
        }
        
        .user-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .user-menu a:hover {
            background: #f5f7fa;
        }
        
        .user-menu hr {
            margin: 0.5rem 0;
            border: none;
            border-top: 1px solid #e0e0e0;
        }
        
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
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
            color: #666;
        }
        
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
<body class="<?php echo $user_preferences['theme_mode']; ?>-theme">
    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="#dashboard" class="nav-link active">Dashboard</a>
                    <a href="#buildings" class="nav-link">My Buildings</a>
                    <a href="../view/tenants.php" class="nav-link">Tenants</a>
                    <a href="#maintenance" class="nav-link">Maintenance</a>
                    <a href="#payments" class="nav-link">Payments</a>
                </nav>
            </div>
            
            <div class="nav-right">
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            <?php if (!empty($current_user['profile_picture_url']) && file_exists('../' . $current_user['profile_picture_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($current_user['profile_picture_url']); ?>" alt="Profile">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <a href="../controller/dashboard_controller.php?redirect=profile">Profile Settings</a>
                        <hr>
                        <a href="../controller/working_login.php?action=logout" style="color: #dc3545;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                <p>Manage your assigned properties</p>
            </div>
            <div class="header-actions">
                <button class="btn-primary" onclick="assignTenant()">
                    <span class="btn-icon">👤</span>
                    Assign Tenant
                </button>
                <button class="btn-secondary" onclick="viewServiceRequests()">
                    <span class="btn-icon">🔧</span>
                    Service Requests
                </button>
            </div>
        </div>

        <div class="stats-grid">
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
                <div class="stat-icon"></div>
                <div class="stat-content">
                    <h3 id="totalTenants">--</h3>
                    <p>Active Tenants</p>
                    <div class="stat-detail">Tenants under your management</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-content">
                    <h3 id="pendingRequests">--</h3>
                    <p>Pending Requests</p>
                    <div class="stat-detail">Service requests awaiting action</div>
                </div>
            </div>
        </div>

        <div class="content-grid">
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

            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
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
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../view/js/dashboard.js"></script>
    <script src="../view/js/global-session.js"></script>
</body>
</html>