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
        .tenant-theme .stats-grid .stat-card.flat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .tenant-theme .stats-grid .stat-card.dues-card { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .tenant-theme .stats-grid .stat-card.advance-card { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .tenant-theme .stats-grid .stat-card.requests-card { background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); }

        /* Modal Styles */
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
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
            margin-top: 0.25rem;
        }

        .required {
            color: #f44336;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f5f7fa;
            color: #333;
            border: 2px solid #e0e0e0;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            border-color: #ccc;
        }

        .btn-danger {
            background: #f44336;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
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
            padding: 1rem;
            background: #e3f2fd;
            border-radius: 8px;
            color: #1976d2;
            margin: 1rem 0;
        }

        .countdown-timer.warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .countdown-timer.critical {
            background: #ffebee;
            color: #d32f2f;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .warning-box, .info-box {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #f57c00;
        }

        .warning-box p {
            margin: 0.5rem 0;
            color: #f57c00;
            font-size: 14px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }

        .info-box p {
            margin: 0.5rem 0;
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
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-overlay p {
            color: white;
            margin-top: 1rem;
            font-size: 18px;
        }

        .notification-badge {
            background: #f44336;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
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
                        <a href="#preferences">Preferences</a>
                        <a href="../controller/working_login.php?action=logout" style="color: #dc3545;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
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
                    <h3 id="flatDetails">Loading...</h3>
                    <p>My Flat</p>
                    <div class="stat-detail" id="floorInfo">Loading...</div>
                </div>
            </div>

            <div class="stat-card dues-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3 id="outstandingDues">৳--</h3>
                    <p>Outstanding Dues</p>
                    <div class="stat-detail">
                        <span id="dueStatus">Checking...</span>
                    </div>
                </div>
            </div>

            <div class="stat-card advance-card">
                <div class="stat-icon">💎</div>
                <div class="stat-content">
                    <h3 id="advanceBalance">৳--</h3>
                    <p>Advance Balance</p>
                    <div class="stat-detail">Available for adjustment</div>
                </div>
            </div>

            <div class="stat-card requests-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-content">
                    <h3 id="activeRequests">--</h3>
                    <p>Active Requests</p>
                    <div class="stat-detail">In progress</div>
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

            <!-- Flat Information Card -->
            <div class="card flat-info-card">
                <div class="card-header">
                    <h3>Flat Information</h3>
                </div>
                <div class="card-content">
                    <div class="flat-details">
                        <div class="detail-row">
                            <strong>Building:</strong>
                            <span id="buildingName">Loading...</span>
                        </div>
                        <div class="detail-row">
                            <strong>Flat Number:</strong>
                            <span id="flatNumber">--</span>
                        </div>
                        <div class="detail-row">
                            <strong>Floor:</strong>
                            <span id="floorNumber">--</span>
                        </div>
                        <div class="detail-row">
                            <strong>Move-in Date:</strong>
                            <span id="moveInDate">--</span>
                        </div>
                        <div class="detail-row">
                            <strong>Monthly Rent:</strong>
                            <span id="monthlyRent">৳--</span>
                        </div>
                        <div class="detail-row">
                            <strong>Security Deposit:</strong>
                            <span id="securityDeposit">৳--</span>
                        </div>
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

    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Loading...</p>
    </div>

    <div id="messageContainer" class="message-container"></div>

    <!-- Scripts -->
    <script src="../view/js/dashboard.js"></script>
    <script src="../view/js/dashboard_tenant.js"></script>
    <script src="../view/js/session-manager.js"></script>
    <script src="../view/js/global-session.js"></script>
    <script>
        // Initialize tenant dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initTenantDashboard();
        });
    </script>
</body>
</html>