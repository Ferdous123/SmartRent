<?php
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
        .tenant-theme .stats-grid .stat-card.flat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .tenant-theme .stats-grid .stat-card.dues-card { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .tenant-theme .stats-grid .stat-card.advance-card { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .tenant-theme .stats-grid .stat-card.requests-card { background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); }
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
                        <hr>
                        <a href="../controller/working_login.php?action=logout" style="display: block; padding: 0.5rem 0; color: #dc3545; text-decoration: none;">Logout</a>
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
                <h1>Welcome home, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
                <p id="flatInfo">Loading your flat information...</p>
            </div>
            <div class="header-actions">
                <button class="btn-primary" onclick="makePayment()">
                    <span class="btn-icon">💳</span>
                    Make Payment
                </button>
                <button class="btn-secondary" onclick="createServiceRequest()">
                    <span class="btn-icon">🔧</span>
                    Service Request
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card flat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-content">
                    <h3 id="flatDetails">Loading...</h3>
                    <p>My Flat</p>
                    <div class="stat-detail" id="floorInfo">
                        Floor information loading...
                    </div>
                </div>
            </div>

            <div class="stat-card dues-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3 id="outstandingDues">৳--</h3>
                    <p>Outstanding Dues</p>
                    <div class="stat-detail">
                        <span id="dueStatus">Checking payment status...</span>
                    </div>
                </div>
            </div>

            <div class="stat-card advance-card">
                <div class="stat-icon">💎</div>
                <div class="stat-content">
                    <h3 id="advanceBalance">৳--</h3>
                    <p>Advance Balance</p>
                    <div class="stat-detail">
                        Available for adjustment
                    </div>
                </div>
            </div>

            <div class="stat-card requests-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-content">
                    <h3 id="activeRequests">--</h3>
                    <p>Active Requests</p>
                    <div class="stat-detail">
                        Service requests in progress
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
                        <button class="quick-action-btn" onclick="makePayment()">
                            <span class="action-icon">💳</span>
                            <span>Make Payment</span>
                        </button>
                        <button class="quick-action-btn" onclick="downloadReceipt()">
                            <span class="action-icon">🧾</span>
                            <span>Download Receipt</span>
                        </button>
                        <button class="quick-action-btn" onclick="createServiceRequest()">
                            <span class="action-icon">🔧</span>
                            <span>Service Request</span>
                        </button>
                        <button class="quick-action-btn" onclick="viewPaymentHistory()">
                            <span class="action-icon">📊</span>
                            <span>Payment History</span>
                        </button>
                        <button class="quick-action-btn" onclick="contactManager()">
                            <span class="action-icon">📞</span>
                            <span>Contact Manager</span>
                        </button>
                        <button class="quick-action-btn" onclick="requestMoveOut()">
                            <span class="action-icon">📤</span>
                            <span>Request Move Out</span>
                        </button>
                        <button class="quick-action-btn" onclick="updateProfile()">
                            <span class="action-icon">👤</span>
                            <span>Update Profile</span>
                        </button>
                        <button class="quick-action-btn" onclick="viewDocuments()">
                            <span class="action-icon">📄</span>
                            <span>View Documents</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="card payment-summary-card">
                <div class="card-header">
                    <h3>Payment Summary</h3>
                    <button class="view-all-btn" onclick="viewPaymentHistory()">View History</button>
                </div>
                <div class="card-content">
                    <div class="payment-overview">
                        <div class="last-payment">
                            <h4>Last Payment</h4>
                            <p class="payment-amount" id="lastPaymentAmount">৳--</p>
                            <p class="payment-date" id="lastPaymentDate">--</p>
                        </div>
                        <div class="next-due">
                            <h4>Next Due Date</h4>
                            <p class="due-date" id="nextDueDate">--</p>
                            <p class="due-amount" id="nextDueAmount">৳--</p>
                        </div>
                    </div>
                    
                    <div class="payment-actions">
                        <button class="btn-primary full-width" onclick="makePayment()">
                            Make Payment Now
                        </button>
                        <button class="btn-secondary full-width" onclick="downloadSlip()">
                            Download Current Slip
                        </button>
                    </div>
                </div>
            </div>

            <!-- Service Requests -->
            <div class="card service-requests-card">
                <div class="card-header">
                    <h3>My Service Requests</h3>
                    <button class="view-all-btn" onclick="viewAllServiceRequests()">View All</button>
                </div>
                <div class="card-content">
                    <div class="service-requests-list" id="myServiceRequests">
                        <div class="service-request-item in-progress">
                            <div class="request-info">
                                <div class="request-header">
                                    <span class="request-type">Plumbing</span>
                                    <span class="request-status in-progress">In Progress</span>
                                </div>
                                <p class="request-description">Kitchen sink drainage issue</p>
                                <div class="request-details">
                                    <span class="request-date">Submitted: Oct 15, 2025</span>
                                    <span class="request-assignee">Assigned to: Maintenance Team</span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn-small" onclick="viewRequestDetails()">View Details</button>
                                <button class="btn-small" onclick="addRequestUpdate()">Add Update</button>
                            </div>
                        </div>

                        <div class="service-request-item completed">
                            <div class="request-info">
                                <div class="request-header">
                                    <span class="request-type">Electrical</span>
                                    <span class="request-status completed">Completed</span>
                                </div>
                                <p class="request-description">Bedroom light switch replacement</p>
                                <div class="request-details">
                                    <span class="request-date">Completed: Oct 10, 2025</span>
                                    <span class="request-rating">Rating: ⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn-small" onclick="viewRequestDetails()">View Details</button>
                            </div>
                        </div>

                        <div class="new-request-prompt">
                            <p>Need something fixed?</p>
                            <button class="btn-primary" onclick="createServiceRequest()">Create New Request</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="card messages-card">
                <div class="card-header">
                    <h3>Recent Messages</h3>
                    <button class="view-all-btn" onclick="viewAllMessages()">View All</button>
                </div>
                <div class="card-content">
                    <div class="messages-list" id="recentMessages">
                        <div class="message-item unread">
                            <div class="message-sender">
                                <strong>Building Manager</strong>
                                <span class="message-time">2 hours ago</span>
                            </div>
                            <p class="message-preview">Your service request has been assigned to our maintenance team...</p>
                            <div class="message-actions">
                                <button class="btn-small" onclick="readMessage()">Read</button>
                                <button class="btn-small" onclick="replyMessage()">Reply</button>
                            </div>
                        </div>

                        <div class="message-item">
                            <div class="message-sender">
                                <strong>Property Owner</strong>
                                <span class="message-time">1 day ago</span>
                            </div>
                            <p class="message-preview">Monthly rent slip for October 2025 is now available...</p>
                            <div class="message-actions">
                                <button class="btn-small" onclick="readMessage()">Read</button>
                            </div>
                        </div>

                        <div class="message-item">
                            <div class="message-sender">
                                <strong>Building Notice</strong>
                                <span class="message-time">3 days ago</span>
                            </div>
                            <p class="message-preview">Water supply maintenance scheduled for this weekend...</p>
                            <div class="message-actions">
                                <button class="btn-small" onclick="readMessage()">Read</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="compose-message">
                        <button class="btn-secondary full-width" onclick="composeMessage()">
                            Send Message to Management
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card payment-history-card">
                <div class="card-header">
                    <h3>Recent Payments</h3>
                    <button class="view-all-btn" onclick="viewPaymentHistory()">View All</button>
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
                                <tr>
                                    <td>Oct 01, 2025</td>
                                    <td>Monthly Rent</td>
                                    <td>৳25,000</td>
                                    <td><span class="status verified">Verified</span></td>
                                    <td><button class="btn-small" onclick="downloadReceipt()">Download</button></td>
                                </tr>
                                <tr>
                                    <td>Sep 28, 2025</td>
                                    <td>Utility Bill</td>
                                    <td>৳3,500</td>
                                    <td><span class="status verified">Verified</span></td>
                                    <td><button class="btn-small" onclick="downloadReceipt()">Download</button></td>
                                </tr>
                                <tr>
                                    <td>Sep 01, 2025</td>
                                    <td>Monthly Rent</td>
                                    <td>৳25,000</td>
                                    <td><span class="status verified">Verified</span></td>
                                    <td><button class="btn-small" onclick="downloadReceipt()">Download</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Flat Information -->
            <div class="card flat-info-card">
                <div class="card-header">
                    <h3>Flat Information</h3>
                    <button class="edit-btn" onclick="editFlatInfo()">Edit Info</button>
                </div>
                <div class="card-content">
                    <div class="flat-details">
                        <div class="detail-row">
                            <strong>Building:</strong>
                            <span id="buildingName">Loading...</span>
                        </div>
                        <div class="detail-row">
                            <strong>Flat Number:</strong>
                            <span id="flatNumber">Loading...</span>
                        </div>
                        <div class="detail-row">
                            <strong>Floor:</strong>
                            <span id="floorNumber">Loading...</span>
                        </div>
                        <div class="detail-row">
                            <strong>Move-in Date:</strong>
                            <span id="moveInDate">Loading...</span>
                        </div>
                        <div class="detail-row">
                            <strong>Lease Status:</strong>
                            <span id="leaseStatus" class="status active">Active</span>
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

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make Payment</h3>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <div class="form-group">
                        <label for="paymentType">Payment Type</label>
                        <select name="payment_type" id="paymentType" required>
                            <option value="">Select Payment Type</option>
                            <option value="rent">Monthly Rent</option>
                            <option value="utility">Utility Bill</option>
                            <option value="mixed">Mixed Payment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentAmount">Amount (৳)</label>
                        <input type="number" name="amount" id="paymentAmount" min="0" step="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentMethod">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" required>
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_banking">Mobile Banking</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transactionNumber">Transaction Number</label>
                        <input type="text" name="transaction_number" id="transactionNumber" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentDate">Payment Date</label>
                        <input type="date" name="payment_date" id="paymentDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentRemarks">Remarks (Optional)</label>
                        <textarea name="remarks" id="paymentRemarks" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closePaymentModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Service Request Modal -->
    <div id="serviceRequestModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Service Request</h3>
                <button class="modal-close" onclick="closeServiceRequestModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="serviceRequestForm">
                    <div class="form-group">
                        <label for="requestType">Request Type</label>
                        <select name="request_type" id="requestType" required>
                            <option value="">Select Request Type</option>
                            <option value="maintenance">General Maintenance</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="requestPriority">Priority</label>
                        <select name="priority" id="requestPriority" required>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="requestDescription">Description</label>
                        <textarea name="description" id="requestDescription" rows="4" required 
                                  placeholder="Please describe the issue in detail..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeServiceRequestModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Submit Request</button>
                    </div>
                </form>
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

    <!-- Scripts -->
    <script src="../view/js/dashboard.js"></script>
    <script>
        // Initialize tenant dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initTenantDashboard();
        });

        // Set default payment date to today
        document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];
    </script>
    <script src="../view/js/session-manager.js"></script>
</body>
</html>