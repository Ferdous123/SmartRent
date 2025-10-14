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
    <title>Tenants Management - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../view/css/dashboard.css">
    <link rel="stylesheet" href="../view/css/tenants-page.css">
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
                    <a href="../view/buildings.php" class="nav-link">Buildings</a>
                    <a href="../view/tenants.php" class="nav-link active">Tenants</a>
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

    <main class="tenants-main">
        <div class="page-header">
            <div class="header-content">
                <h1>Tenants Management</h1>
                <p>Manage tenant assignments, payments, and more</p>
            </div>
            <div class="header-actions">
                <button class="btn-primary" onclick="showAddTenantModal()">
                    <span class="btn-icon">+</span>
                    Add Tenant
                </button>
            </div>
        </div>

        <div class="tenants-tabs">
            <button class="tab-btn active" onclick="switchTenantsTab('all')">All Tenants</button>
            <button class="tab-btn" onclick="switchTenantsTab('pending')">Pending Assignments</button>
            <button class="tab-btn" onclick="switchTenantsTab('outstanding')">Outstanding Payments</button>
        </div>


        <div class="search-filter-bar">
            <div class="filter-row">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, phone, email, or flat..." onkeyup="searchTenants()">
                
                <select id="buildingFilter" class="filter-select" onchange="filterTenants()">
                    <option value="">All Buildings</option>
                </select>
                
                <select id="statusFilter" class="filter-select" onchange="filterTenants()">
                    <option value="">All Payment Status</option>
                    <option value="paid">Paid</option>
                    <option value="due">Due Soon</option>
                    <option value="overdue">Overdue</option>
                </select>
                
                <select id="moveInFilter" class="filter-select" onchange="filterTenants()">
                    <option value="">All Move-in Dates</option>
                    <option value="this_month">Moved in This Month</option>
                    <option value="last_3_months">Last 3 Months</option>
                    <option value="last_6_months">Last 6 Months</option>
                    <option value="older">Older than 6 Months</option>
                </select>
                
                <select id="moveOutFilter" class="filter-select" onchange="filterTenants()">
                    <option value="">All Move-out Plans</option>
                    <option value="this_month">Moving This Month</option>
                    <option value="next_month">Moving Next Month</option>
                    <option value="next_3_months">Moving in 3 Months</option>
                    <option value="has_notice">Has End Notice</option>
                </select>
                
                <button class="btn-reset" onclick="resetFilters()">Reset Filters</button>
            </div>
        </div>

        <div class="tab-content">
            <div id="allTenantsTab" class="tab-pane active">
                <table class="tenants-table" id="tenantsTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Flat(s)</th>
                            <th>Contact</th>
                            <th>Advance Balance</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tenantsTableBody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">
                                Loading tenants...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pendingTab" class="tab-pane">
                <table class="tenants-table">
                    <thead>
                        <tr>
                            <th>Flat</th>
                            <th>Building</th>
                            <th>Tenant</th>
                            <th>Type</th>
                            <th>Advance Amount</th>
                            <th>Time Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pendingTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                Loading pending assignments...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="outstandingTab" class="tab-pane">
                <table class="tenants-table">
                    <thead>
                        <tr>
                            <th>Tenant Name</th>
                            <th>Contact</th>
                            <th>Outstanding Amount</th>
                            <th>Overdue Count</th>
                            <th>Days Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="outstandingTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                Loading outstanding payments...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include 'modals/add_tenant_modal.php'; ?>
    <?php include 'modals/tenant_details_modal.php'; ?>
    <?php include 'modals/move_tenant_modal.php'; ?>
    <?php include 'modals/end_tenancy_modal.php'; ?>

    <div id="messageContainer"></div>

    <script src="../view/js/global-session.js"></script>
    <script src="../view/js/tenants-page.js"></script>
</body>
</html>