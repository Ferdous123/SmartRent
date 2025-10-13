<?php
if (!isset($current_user)) {
    require_once '../controller/session_controller.php';
    
    if (!is_user_logged_in()) {
        header("Location: ../view/login.php");
        exit();
    }
    
    $current_user = get_logged_in_user();
}

if ($current_user['user_type'] !== 'tenant') {
    header("Location: ../controller/dashboard_controller.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code'] ?? 'en'; ?>">
<head>
    <title>Tenant Dashboard - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../view/css/dashboard.css">
    
    <style>
    :root {
        --nav-color: <?php echo $user_preferences['nav_color'] ?? '#667eea'; ?>;
        --primary-bg: <?php echo $user_preferences['primary_bg_color'] ?? '#ffffff'; ?>;
        --secondary-bg: <?php echo $user_preferences['secondary_bg_color'] ?? '#f5f5f5'; ?>;
        --font-size: <?php echo ($user_preferences['font_size'] ?? 'medium') === 'small' ? '14px' : (($user_preferences['font_size'] ?? 'medium') === 'large' ? '18px' : '16px'); ?>;
    }
    
    body { 
        font-family: Arial, sans-serif; 
        margin: 0; 
        padding: 0; 
        background: var(--primary-bg);
        font-size: var(--font-size);
    }
    
    .tenant-theme .stats-grid .stat-card.flat-card { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    
    .tenant-theme .stats-grid .stat-card.dues-card { 
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); 
    }
    
    .tenant-theme .stats-grid .stat-card.advance-card { 
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); 
    }
    
    .tenant-theme .stats-grid .stat-card.requests-card { 
        background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); 
    }

    .user-dropdown {
        position: relative;
    }

    .user-btn {
        cursor: pointer;
    }

    .user-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
        margin-top: 8px;
        min-width: 200px;
        z-index: 10001;
    }

    .user-menu a {
        display: block;
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
    }

    .user-menu a:hover {
        background: #f5f5f5;
    }

    .notifications-panel {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
        margin-top: 8px;
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
        z-index: 10001;
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .modal-content {
        background: white;
        border-radius: 15px;
        max-width: 600px;
        width: 90%;
        max-height: 600px;
        overflow-y: auto;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }

    .modal-header {
        padding: 20px 25px 15px;
        border-bottom: 2px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 20px;
    }

    .modal-close {
        background: rgba(255,255,255,0.2);
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        background: rgba(255,255,255,0.3);
    }

    .modal-body {
        padding: 25px;
    }

    .modal-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #e0e0e0;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-error {
        display: block;
        color: #f44336;
        font-size: 12px;
        margin-top: 4px;
    }

    .required {
        color: #f44336;
    }

    .btn-primary {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-primary:hover {
        background: #5568d3;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #f5f5f5;
        color: #333;
        border: 2px solid #e0e0e0;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
        border-color: #ccc;
    }

    .btn-danger {
        background: #f44336;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-danger:hover {
        background: #d32f2f;
    }

    .full-width {
        width: 100%;
    }

    .countdown-timer {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        padding: 15px;
        background: #e3f2fd;
        border-radius: 8px;
        color: #1976d2;
        margin: 15px 0;
    }

    .countdown-timer.warning {
        background: #fff3e0;
        color: #f57c00;
    }

    .countdown-timer.critical {
        background: #ffebee;
        color: #d32f2f;
    }

    .warning-box, 
    .info-box {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
    }

    .warning-box {
        background: #fff3e0;
        border-left: 4px solid #f57c00;
    }

    .warning-box p {
        margin: 8px 0;
        color: #f57c00;
        font-size: 14px;
    }

    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
    }

    .info-box p {
        margin: 8px 0;
        color: #1976d2;
        font-size: 14px;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10001;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
    }

    .loading-overlay p {
        color: white;
        margin-top: 15px;
        font-size: 18px;
    }

    .notification-badge {
        background: #f44336;
        color: white;
        border-radius: 50%;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: bold;
        position: absolute;
        top: -5px;
        right: -5px;
    }
</style>
</head>

<body class="<?php echo $user_preferences['theme_mode'] ?? 'light'; ?>-theme tenant-theme">
   <!-- Navigation Header -->
    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="#dashboard" class="nav-link active">Dashboard</a>
                    <a href="#payments" class="nav-link">Payments</a>
                    <a href="#services" class="nav-link">Service Requests</a>
                    <a href="#documents" class="nav-link">Documents</a>
                    <a href="#messages" class="nav-link">Messages</a>
                </nav>
            </div>
            
            <div class="nav-right">
                <!-- Add OTP Button -->
                <button class="btn-primary" onclick="showClaimFlatModal()" style="margin-right: 1rem; padding: 0.5rem 1rem; font-size: 14px; border: none; cursor: pointer; border-radius: 8px;">
                    🔑 Claim Flat
                </button>
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
                        <a href="../controller/working_login.php?action=logout" style="color: #dc3545;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

<main class="dashboard-main">
        <!-- Actions Needed Banner -->
        <div id="actionsNeededSection" style="display: none;">
            <div style="background: #fff3cd; border-left: 4px solid #f57c00; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 48px;">⚠️</div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #f57c00; font-size: 18px;">Action Required</h3>
                        <p style="margin: 0; color: #856404; font-size: 14px;" id="actionNeededText">
                            You have pending assignments that require confirmation
                        </p>
                    </div>
                    <div>
                        <button class="btn-primary" onclick="checkPendingAssignment()" style="white-space: nowrap;">
                            View Pending
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Assignment Section -->
        <div id="noAssignmentSection" style="display: none;">
            <div style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="text-align: center;">
                    <div style="font-size: 64px; margin-bottom: 1rem;">🏠</div>
                    <h2 style="margin: 0 0 0.5rem 0; color: #333;">No Active Assignment</h2>
                    <p style="color: #666; margin: 0 0 2rem 0;">You don't have any flat assigned yet</p>
                    
                    <button class="btn-primary" onclick="showClaimFlatModal()" style="padding: 1rem 2rem; font-size: 16px;">
                        <span style="font-size: 20px; margin-right: 0.5rem;">🔑</span>
                        Claim Flat with OTP
                    </button>
                    
                    <p style="margin-top: 1.5rem; color: #999; font-size: 14px;">
                        Have an OTP code from your property manager? Click above to claim your flat
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card flat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-content">
                    <h3 id="flatCount">0 Flats</h3>
                    <p>My Properties</p>
                    <div class="stat-detail" id="flatsList">Loading...</div>
                </div>
            </div>

            <div class="stat-card dues-card">
                <div class="stat-icon"></div>
                <div class="stat-content">
                    <h3 id="totalOutstanding">৳--</h3>
                    <p>Total Outstanding</p>
                    <div class="stat-detail" id="dueStatus">Across all properties</div>
                </div>
            </div>

            <div class="stat-card advance-card">
                <div class="stat-icon"></div>
                <div class="stat-content">
                    <h3 id="totalAdvance">৳--</h3>
                    <p>Total Security Deposit</p>
                    <div class="stat-detail">All flats combined</div>
                </div>
            </div>

            <div class="stat-card requests-card">
                <div class="stat-icon"></div>
                <div class="stat-content">
                    <h3 id="activeRequests">--</h3>
                    <p>Active Requests</p>
                    <div class="stat-detail">In progress</div>
                </div>
            </div>
        </div>

        <!-- My Flats Section (New - before content grid) -->
        <div id="myFlatsSection" style="display: none; margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3>My Flats</h3>
                </div>
                <div class="card-content">
                    <div id="flatsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                        <!-- Flats will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid (Payment History, Flat Info, etc.) -->
        <div class="content-grid">
            <!-- Payment History Card -->
            <div class="card payment-history-card">
                <div class="card-header">
                    <h3>Recent Payments</h3>
                </div>
                <div class="card-content">
                    <div class="payments-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody id="recentPaymentsTable">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Flat Information Card - Updated for Multiple Flats -->
            <div class="card flat-info-card">
                <div class="card-header">
                    <h3>Flat Information</h3>
                </div>
                <div class="card-content">
                    <div id="flatInfoTabs" style="display: none; margin-bottom: 1rem;">
                        <!-- Tabs will be generated dynamically -->
                    </div>
                    <div id="flatDetailsContainer">
                        <!-- Single or multiple flat details will be shown here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Claim Flat Modal -->
    <div id="claimFlatModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>🔑 Claim Flat with OTP</h3>
                <button class="modal-close" onclick="closeClaimFlatModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-box">
                    <p style="margin: 0; font-size: 14px;"><strong>How it works:</strong></p>
                    <ol style="margin: 0.5rem 0 0 1.5rem; padding: 0; font-size: 14px;">
                        <li>Your property manager generates an OTP code for you</li>
                        <li>Enter the 6-digit code below to claim the flat</li>
                        <li>You'll have 24 hours to complete the advance payment</li>
                        <li>After payment, the flat will be assigned to you</li>
                    </ol>
                </div>
                
                <form id="otpClaimForm" onsubmit="handleOTPClaim(event)">
                    <div class="form-group">
                        <label>OTP Code <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="otp_code_input" 
                            name="otp_code"
                            placeholder="Enter 6-digit OTP"
                            maxlength="6"
                            pattern="\d{6}"
                            required
                            style="text-align: center; font-size: 24px; letter-spacing: 0.5em; font-weight: bold; padding: 1rem;"
                        >
                        <small style="display: block; margin-top: 0.5rem; color: #666;">
                            Example: <code style="background: #f5f7fa; padding: 0.25rem 0.5rem; border-radius: 4px;">123456</code>
                        </small>
                    </div>
                    
                    <div id="claimedFlatInfo" style="display: none; margin-top: 1.5rem;">
                        <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 1rem; border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #155724;">✅ Flat Claimed Successfully!</h4>
                            <p style="margin: 0; color: #155724;" id="claimedFlatDetails"></p>
                            <p style="margin: 0.5rem 0 0 0; color: #155724; font-weight: bold;">
                                Please complete payment within 24 hours
                            </p>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeClaimFlatModal()">Cancel</button>
                        <button type="submit" class="btn-primary" id="claimFlatBtn">🔓 Claim Flat</button>
                    </div>
                </form>
                
                <div id="existingAssignmentsSection" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e0e0e0;">
                    <h4 style="margin: 0 0 1rem 0; color: #333;">📋 Your Current Flats</h4>
                    <div id="existingAssignmentsList">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Assignment Confirmation Modal -->
    <div id="pendingAssignmentModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>⏰ Pending Flat Assignment</h3>
                <button class="modal-close" onclick="closePendingModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="assignmentFlatDetails" style="background: #f5f7fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"></div>
                
                <div class="countdown-timer" id="assignmentCountdown"></div>
                
                <div class="warning-box">
                    <p><strong>⚠️ Complete payment to confirm assignment</strong></p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-primary full-width" onclick="startPaymentProcess()">
                        💳 Pay Advance Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Gateway Modal -->
    <div id="paymentGatewayModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>🏦 Payment Gateway (Test Mode)</h3>
                <button class="modal-close" onclick="closePaymentGatewayModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        ⚠️ TEST MODE: Simulates bKash/Nagad. Production will use real gateway.
                    </p>
                </div>
                
                <form id="gatewayPaymentForm">
                    <input type="hidden" id="gateway_assignment_id">
                    
                    <div class="form-group">
                        <label>Amount to Pay (৳)</label>
                        <input type="number" id="gateway_amount" readonly style="font-size: 20px; font-weight: bold; color: #27ae60;">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select id="gateway_method">
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Rocket">Rocket</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" id="gateway_phone" placeholder="01XXXXXXXXX" value="01700000000">
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closePaymentGatewayModal()">Cancel</button>
                        <button type="button" class="btn-primary" onclick="processGatewayPayment()">💳 Pay Now</button>
                    </div>
                </form>
                
                <div id="gatewayResult" style="display: none; margin-top: 1rem;">
                    <div style="background: #d4edda; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #155724;">✅ Payment Successful!</h4>
                        <p style="margin: 0.25rem 0; font-size: 14px; color: #155724;">
                            <strong>Transaction ID:</strong> <span id="gateway_txn_id"></span>
                        </p>
                        <p style="margin: 0.25rem 0; font-size: 14px; color: #155724;">
                            <strong>Amount:</strong> ৳<span id="gateway_paid_amount"></span>
                        </p>
                    </div>
                    <button type="button" class="btn-primary full-width" style="margin-top: 1rem;" onclick="proceedToVerification()">
                        Continue to Verification →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Verification Modal -->
    <div id="paymentVerifyModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Verify Payment</h3>
                <button class="modal-close" onclick="closePaymentVerifyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="verifyPaymentForm">
                    <input type="hidden" id="verify_assignment_id">
                    
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="margin: 0.25rem 0;"><strong>Required:</strong> ৳<span id="verify_required"></span></p>
                        <p style="margin: 0.25rem 0;"><strong>Already Paid:</strong> ৳<span id="verify_paid"></span></p>
                        <p style="margin: 0.25rem 0;"><strong>Remaining:</strong> ৳<span id="verify_remaining"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Transaction ID <span class="required">*</span></label>
                        <input type="text" id="verify_transaction_id" required placeholder="Enter transaction ID">
                    </div>
                    
                    <div class="form-group">
                        <label>Amount Paid (৳) <span class="required">*</span></label>
                        <input type="number" id="verify_amount" step="0.01" required placeholder="Enter amount paid">
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closePaymentVerifyModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Verify & Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flat Actions Modal (Add after Payment Verification Modal) -->
    <div id="flatActionsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="flatActionsTitle">Flat Actions</h3>
                <button class="modal-close" onclick="closeFlatActionsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="flatActionsMenu" style="display: grid; gap: 0.75rem;">
                    <!-- Actions will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Flat Details Modal -->
    <div id="flatDetailsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>📊 Flat Details</h3>
                <button class="modal-close" onclick="closeFlatDetailsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="flatDetailsContent">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Flat Payment History Modal -->
    <div id="flatPaymentHistoryModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>💰 Payment History</h3>
                <button class="modal-close" onclick="closeFlatPaymentHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="flatPaymentHistoryContent">
                    <!-- Payment history will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Flat Outstanding Dues Modal -->
    <div id="flatOutstandingModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>📋 Outstanding Dues</h3>
                <button class="modal-close" onclick="closeFlatOutstandingModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="flatOutstandingContent">
                    <!-- Outstanding dues will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Service Request Modal -->
    <div id="serviceRequestModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3> Create Service Request</h3>
                <button class="modal-close" onclick="closeServiceRequestModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="serviceRequestForm" onsubmit="submitServiceRequest(event)">
                    <input type="hidden" id="service_flat_id">
                    
                    <div class="form-group">
                        <label>Request Type <span class="required">*</span></label>
                        <select id="service_type" required>
                            <option value="">Select type...</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Priority <span class="required">*</span></label>
                        <select id="service_priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <textarea id="service_description" rows="4" required placeholder="Describe the issue..."></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeServiceRequestModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Service Requests Modal -->
    <div id="viewServiceRequestsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>📝 Service Requests</h3>
                <button class="modal-close" onclick="closeViewServiceRequestsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="serviceRequestsContent">
                    <!-- Service requests will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Flat Expenses Modal -->
    <div id="flatExpensesModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>📊 Monthly Expenses</h3>
                <button class="modal-close" onclick="closeFlatExpensesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="flatExpensesContent">
                    <!-- Expenses will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Move Out Request Modal -->
    <div id="moveOutModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>🚪 Request Move Out</h3>
                <button class="modal-close" onclick="closeMoveOutModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-box">
                    <p><strong>⚠️ Important:</strong></p>
                    <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                        <li>Move-out date must be the 1st day of a month</li>
                        <li>You can cancel up to the last day of the previous month</li>
                        <li>Your advance will be adjusted with final dues</li>
                    </ul>
                </div>
                
                <form id="moveOutForm" onsubmit="submitMoveOut(event)">
                    <input type="hidden" id="moveout_flat_id">
                    <input type="hidden" id="moveout_assignment_id">
                    
                    <div class="form-group">
                        <label>Move Out Date <span class="required">*</span></label>
                        <input type="date" id="moveout_date" required>
                        <small>Select the 1st day of the month you want to move out</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea id="moveout_reason" rows="3" placeholder="Optional - Why are you moving out?"></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeMoveOutModal()">Cancel</button>
                        <button type="submit" class="btn-danger">Submit Move Out Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Meter Readings Modal -->
    <div id="meterReadingsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>⚡ Meter Readings</h3>
                <button class="modal-close" onclick="closeMeterReadingsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="meterReadingsContent">
                    <!-- Meter readings will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Loading...</p>
    </div>

    <div id="messageContainer" class="message-container"></div>

</main>

    <!-- Scripts -->
    <script src="../view/js/dashboard.js"></script>
    <script src="../view/js/session-manager.js"></script>
    <script src="../view/js/global-session.js"></script>
    <script src="../view/js/dashboard_tenant.js"></script>
    <script>
        // Initialize tenant dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initTenantDashboard();
        });
    </script>
</body>
</html>