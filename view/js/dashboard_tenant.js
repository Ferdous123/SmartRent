// Tenant Dashboard JavaScript
// Extends dashboard.js with tenant-specific functionality

var dashboardData = null;
var notificationInterval = null;
var gatewayTransaction = null;

// ============================================
// INITIALIZATION
// ============================================

function initTenantDashboard() {
    // Load tenant-specific data
    loadTenantDashboardData();
    checkPendingAssignment(); // Check ONCE on load
    checkAndShowActionsNeeded(); // Shows banner if pending
    loadNotifications();
    
    // Setup intervals (only for notifications)
    notificationInterval = setInterval(loadNotifications, 60000); // Every 60 seconds
    
    // Setup form handlers
    setupTenantFormHandlers();
    
    // Setup navigation
    setupTenantNavigation();
}

// ============================================
// DASHBOARD DATA LOADING
// ============================================

// Load tenant-specific dashboard data
function loadTenantDashboardData() {
    // Show loading state in payment table
    updatePaymentHistoryTable(undefined); // Shows "⏳ Loading payments..."
    
    showLoadingOverlay();
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoadingOverlay();
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        dashboardData = response.data;
                        updateTenantDashboardUI(response.data);
                    } else {
                        showMessage('Failed to load dashboard data', 'error');
                        
                        // Show error state in payment table
                        var tbody = document.getElementById('recentPaymentsTable');
                        if (tbody) {
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #e74c3c;">Failed to load payments</td></tr>';
                        }
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    showMessage('Error loading dashboard', 'error');
                    
                    // Show error state
                    var tbody = document.getElementById('recentPaymentsTable');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #e74c3c;">Error loading data</td></tr>';
                    }
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
                
                // Show error state
                var tbody = document.getElementById('recentPaymentsTable');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #e74c3c;">Server error</td></tr>';
                }
            }
        }
    };
    
    xhr.send('action=get_dashboard_data');
}

// Update tenant dashboard UI
function updateTenantDashboardUI(data) {
    // ============================================
    // SHOW/HIDE NO ASSIGNMENT SECTION
    // ============================================
    var noAssignmentSection = document.getElementById('noAssignmentSection');
    
    if (noAssignmentSection) {
        if (!data.has_assignment) {
            noAssignmentSection.style.display = 'block';
        } else {
            noAssignmentSection.style.display = 'none';
        }
    }
    
    // ============================================
    // NO ASSIGNMENT STATE
    // ============================================
    if (!data.has_assignment) {
        // Update header info
        updateElementText('flatInfo', 'No flat assigned yet');
        
        // Update stats cards
        updateElementText('flatDetails', 'Not assigned');
        updateElementText('floorInfo', 'N/A');
        
        // Gray out flat card
        var flatInfoCard = document.querySelector('.stat-card.flat-card');
        if (flatInfoCard) {
            flatInfoCard.style.background = 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)';
        }
        
        // Financial stats - all zeros
        updateElementText('outstandingDues', '৳0.00');
        updateElementText('advanceBalance', '৳0.00');
        updateElementText('dueStatus', 'No active assignment');
        
        // Service requests
        updateElementText('activeRequests', '0');
        
        // Payment summary section
        updateElementText('lastPaymentAmount', '৳0.00');
        updateElementText('lastPaymentDate', 'No payments yet');
        updateElementText('nextDueDate', 'N/A');
        updateElementText('nextDueAmount', '৳0.00');
        
        // Flat information card
        updateElementText('buildingName', 'N/A');
        updateElementText('flatNumber', 'N/A');
        updateElementText('floorNumber', 'N/A');
        updateElementText('moveInDate', 'N/A');
        updateElementText('monthlyRent', '৳0.00');
        updateElementText('securityDeposit', '৳0.00');
        
        // Payment history table - show empty state
        updatePaymentHistoryTable([]);
        
        return; // Stop here - no assignment to display
    }
    
    // ============================================
    // HAS ASSIGNMENT - POPULATE WITH REAL DATA
    // ============================================
    
    // --- HEADER SECTION ---
    updateElementText('flatInfo', data.flat_info.building_name + ' - Flat ' + data.flat_info.flat_number);
    
    // --- STATS CARDS ---
    // Flat Card
    updateElementText('flatDetails', 'Flat ' + data.flat_info.flat_number);
    updateElementText('floorInfo', 'Floor ' + data.flat_info.floor_number + ' • ' + data.flat_info.building_name);
    
    // Restore flat card color (in case it was grayed out)
    var flatInfoCard = document.querySelector('.stat-card.flat-card');
    if (flatInfoCard) {
        flatInfoCard.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }
    
    // Outstanding Dues Card
    updateElementText('outstandingDues', '৳' + formatNumber(data.outstanding_dues));
    
    var dueStatus = document.getElementById('dueStatus');
    if (dueStatus) {
        if (data.outstanding_dues > 0) {
            dueStatus.textContent = data.overdue_count + ' overdue payment(s)';
            dueStatus.style.color = '#e74c3c';
        } else {
            dueStatus.textContent = 'All payments up to date';
            dueStatus.style.color = '#27ae60';
        }
    }
    
    // Advance Balance Card
    updateElementText('advanceBalance', '৳' + formatNumber(data.advance_balance));
    
    // Active Requests Card
    updateElementText('activeRequests', data.active_service_requests || 0);
    
    // --- PAYMENT SUMMARY SECTION ---
    if (data.last_payment_amount && data.last_payment_amount > 0) {
        updateElementText('lastPaymentAmount', '৳' + formatNumber(data.last_payment_amount));
        updateElementText('lastPaymentDate', formatDate(data.last_payment_date));
    } else {
        updateElementText('lastPaymentAmount', '৳0.00');
        updateElementText('lastPaymentDate', 'No payments yet');
    }
    
    // Next due information
    if (data.current_month) {
        updateElementText('nextDueDate', formatDate(data.current_month.due_date));
        updateElementText('nextDueAmount', '৳' + formatNumber(data.current_month.remaining));
    } else {
        updateElementText('nextDueDate', 'No upcoming dues');
        updateElementText('nextDueAmount', '৳0.00');
    }
    
    // --- FLAT INFORMATION CARD ---
    updateElementText('buildingName', data.flat_info.building_name);
    updateElementText('flatNumber', data.flat_info.flat_number);
    updateElementText('floorNumber', data.flat_info.floor_number);
    updateElementText('moveInDate', formatDate(data.flat_info.confirmed_at));
    updateElementText('monthlyRent', '৳' + formatNumber(data.flat_info.base_rent));
    updateElementText('securityDeposit', '৳' + formatNumber(data.advance_balance));
    
    // --- PAYMENT HISTORY TABLE ---
    updatePaymentHistoryTable(data.recent_payments);
}

// Update payment history table
function updatePaymentHistoryTable(payments) {
    var tbody = document.getElementById('recentPaymentsTable');
    if (!tbody) return;
    
    // Handle undefined (loading state)
    if (payments === undefined) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #999;">⏳ Loading payments...</td></tr>';
        return;
    }
    
    // Handle empty array (no payments)
    if (!payments || payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #999;">No payment history</td></tr>';
        return;
    }
    
    // Build table rows
    var html = '';
    for (var i = 0; i < payments.length; i++) {
        var payment = payments[i];
        var statusClass = payment.is_verified ? 'verified' : 'pending';
        var statusText = payment.is_verified ? 'Verified' : 'Pending';
        
        html += '<tr>' +
            '<td>' + formatDate(payment.payment_date) + '</td>' +
            '<td>' + escapeHtml(payment.payment_type) + '</td>' +
            '<td>৳' + formatNumber(payment.amount) + '</td>' +
            '<td><span class="status ' + statusClass + '">' + statusText + '</span></td>' +
            '<td><button class="btn-small" onclick="downloadReceipt(' + payment.payment_id + ')">Download</button></td>' +
        '</tr>';
    }
    
    tbody.innerHTML = html;
}

// ============================================
// PENDING ASSIGNMENT HANDLING
// ============================================

function checkPendingAssignment() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success && response.pending_assignment) {
                    showPendingAssignmentModal(response.pending_assignment);
                }
            } catch (e) {
                console.error('Parse error:', e);
            }
        }
    };
    
    xhr.send('action=check_pending_assignment');
}

function showPendingAssignmentModal(assignment) {
    var modal = document.getElementById('pendingAssignmentModal');
    if (!modal) {
        modal = createPendingAssignmentModal(assignment);
        document.body.appendChild(modal);
    }
    
    var detailsDiv = document.getElementById('assignmentFlatDetails');
    if (detailsDiv) {
        detailsDiv.innerHTML = 
            '<strong>' + escapeHtml(assignment.building_name) + '</strong><br>' +
            'Flat ' + escapeHtml(assignment.flat_number) + ', Floor ' + assignment.floor_number + '<br>' +
            '<span style="font-size: 18px; color: #27ae60;"><strong>Advance: ৳' + formatNumber(assignment.advance_amount) + '</strong></span>';
    }
    
    var expiresAt = new Date(assignment.expires_at).getTime();
    updateCountdown(expiresAt);
    
    modal.style.display = 'flex';
}

function createPendingAssignmentModal(assignment) {
    var modalHtml = 
        '<div id="pendingAssignmentModal" class="modal" style="z-index: 10000;">' +
        '<div class="modal-content" style="max-width: 500px;">' +
        '<div class="modal-header">' +
        '<h3>⚠️ Flat Assignment Pending</h3>' +
        '</div>' +
        '<div class="modal-body">' +
        '<div class="warning-box">' +
        '<p><strong>Action Required:</strong> You have been assigned a flat. Please confirm within:</p>' +
        '<div id="assignmentCountdown" class="countdown-timer"></div>' +
        '</div>' +
        '<div id="assignmentFlatDetails" style="margin-bottom: 1.5rem; padding: 1rem; background: #f5f7fa; border-radius: 8px;"></div>' +
        '<div class="modal-actions">' +
        '<button type="button" class="btn-primary full-width" onclick="startPaymentProcess()">💳 Pay Advance Now</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    var div = document.createElement('div');
    div.innerHTML = modalHtml;
    return div.firstChild;
}

function startPaymentProcess() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success && response.pending_assignment) {
                    var modal = document.getElementById('pendingAssignmentModal');
                    if (modal) modal.style.display = 'none';
                    
                    showPaymentGatewayModal(response.pending_assignment);
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
    };
    
    xhr.send('action=check_pending_assignment');
}

function updateCountdown(expiresAt) {
    var countdownElement = document.getElementById('assignmentCountdown');
    if (!countdownElement) return;
    
    var interval = setInterval(function() {
        var now = new Date().getTime();
        var distance = expiresAt - now;
        
        if (distance < 0) {
            clearInterval(interval);
            countdownElement.innerHTML = 'EXPIRED';
            var modal = document.getElementById('pendingAssignmentModal');
            if (modal) modal.style.display = 'none';
            showMessage('Assignment has expired', 'error');
            return;
        }
        
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownElement.innerHTML = hours + 'h ' + minutes + 'm ' + seconds + 's';
        
        // Change color based on time remaining
        if (hours < 1) {
            countdownElement.className = 'countdown-timer critical';
        } else if (hours < 6) {
            countdownElement.className = 'countdown-timer warning';
        }
    }, 1000);
}

// ============================================
// ACTIONS NEEDED BANNER
// ============================================

function checkAndShowActionsNeeded() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                var actionsSection = document.getElementById('actionsNeededSection');
                var actionText = document.getElementById('actionNeededText');
                
                if (response.success && response.pending_assignment) {
                    if (actionsSection) {
                        actionsSection.style.display = 'block';
                    }
                    
                    if (actionText) {
                        var assignment = response.pending_assignment;
                        var hoursLeft = Math.floor(assignment.seconds_remaining / 3600);
                        var minutesLeft = Math.floor((assignment.seconds_remaining % 3600) / 60);
                        
                        actionText.innerHTML = 
                            '<strong>' + escapeHtml(assignment.building_name) + ' - ' + 
                            escapeHtml(assignment.flat_number) + '</strong><br>' +
                            'Complete payment within <strong>' + hoursLeft + 'h ' + minutesLeft + 'm</strong> | ' +
                            'Advance: <strong>৳' + formatNumber(assignment.advance_amount) + '</strong>';
                    }
                } else {
                    if (actionsSection) {
                        actionsSection.style.display = 'none';
                    }
                }
            } catch (e) {
                console.error('Parse error:', e);
            }
        }
    };
    
    xhr.send('action=check_pending_assignment');
}

// ============================================
// OTP CLAIM FUNCTIONALITY
// ============================================

function showClaimFlatModal() {
    var form = document.getElementById('otpClaimForm');
    if (form) form.reset();
    
    var claimedInfo = document.getElementById('claimedFlatInfo');
    if (claimedInfo) claimedInfo.style.display = 'none';
    
    var claimBtn = document.getElementById('claimFlatBtn');
    if (claimBtn) {
        claimBtn.textContent = '🔓 Claim Flat';
        claimBtn.disabled = false;
        claimBtn.style.background = '#667eea';
    }
    
    loadExistingAssignments();
    document.getElementById('claimFlatModal').style.display = 'flex';
}

function closeClaimFlatModal() {
    document.getElementById('claimFlatModal').style.display = 'none';
}

// Load tenant's existing assignments
function loadExistingAssignments() {
    var container = document.getElementById('existingAssignmentsList');
    if (!container) return;
    
    // Show loading indicator
    container.innerHTML = '<p style="text-align: center; color: #999; padding: 1rem;">⏳ Loading your flats...</p>';
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        displayExistingAssignments(response.assignments);
                    } else {
                        container.innerHTML = '<p style="text-align: center; color: #999; padding: 1rem;">No flats assigned yet</p>';
                    }
                } catch (e) {
                    console.error('Error loading assignments:', e);
                    container.innerHTML = '<p style="text-align: center; color: #e74c3c; padding: 1rem;">Error loading assignments</p>';
                }
            } else {
                container.innerHTML = '<p style="text-align: center; color: #e74c3c; padding: 1rem;">Failed to load assignments</p>';
            }
        }
    };
    
    xhr.send('action=get_my_assignments');
}

function displayExistingAssignments(assignments) {
    var container = document.getElementById('existingAssignmentsList');
    if (!container) return;
    
    if (!assignments || assignments.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 1rem;">No flats assigned yet</p>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < assignments.length; i++) {
        var assignment = assignments[i];
        var statusColor = assignment.status === 'confirmed' ? '#28a745' : '#ffc107';
        var statusText = assignment.status === 'confirmed' ? '✅ Active' : '⏳ Pending Payment';
        
        html += '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem; border-left: 4px solid ' + statusColor + ';">' +
            '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<div>' +
                    '<strong style="font-size: 16px; color: #333;">' + escapeHtml(assignment.building_name) + ' - ' + escapeHtml(assignment.flat_number) + '</strong><br>' +
                    '<span style="color: #666; font-size: 14px;">Floor ' + assignment.floor_number + '</span><br>' +
                    '<span style="color: #666; font-size: 14px;">Advance: ৳' + formatNumber(assignment.advance_balance) + '</span>' +
                '</div>' +
                '<div style="text-align: right;">' +
                    '<span style="background: ' + statusColor + '; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px; font-weight: bold;">' + statusText + '</span><br>';
        
        if (assignment.status === 'pending' && assignment.seconds_remaining > 0) {
            var hoursLeft = Math.floor(assignment.seconds_remaining / 3600);
            html += '<span style="color: #f57c00; font-size: 12px; margin-top: 0.25rem; display: inline-block;">⏱ ' + hoursLeft + 'h left</span>';
        }
        
        html += '</div>' +
            '</div>' +
        '</div>';
    }
    
    container.innerHTML = html;
}

function handleOTPClaim(event) {
    event.preventDefault();
    
    var otpCode = document.getElementById('otp_code_input').value.trim();
    
    if (!otpCode || otpCode.length !== 6) {
        showMessage('Please enter a valid 6-digit OTP code', 'error');
        return;
    }
    
    if (!/^\d{6}$/.test(otpCode)) {
        showMessage('OTP must be 6 digits (numbers only)', 'error');
        return;
    }
    
    var claimBtn = document.getElementById('claimFlatBtn');
    claimBtn.disabled = true;
    claimBtn.textContent = '⏳ Claiming...';
    
    showLoadingOverlay();
    
    var formData = new FormData();
    formData.append('action', 'claim_otp');
    formData.append('otp_code', otpCode);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoadingOverlay();
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        showMessage(response.message, 'success');
                        
                        var claimedInfo = document.getElementById('claimedFlatInfo');
                        var claimedDetails = document.getElementById('claimedFlatDetails');
                        
                        if (response.assignment) {
                            claimedDetails.textContent = response.assignment.building_name + ' - ' + 
                                response.assignment.flat_number + ' | Advance: ৳' + 
                                formatNumber(response.assignment.advance_amount);
                            claimedInfo.style.display = 'block';
                        }
                        
                        claimBtn.textContent = '✅ Claimed';
                        claimBtn.style.background = '#28a745';
                        
                        loadExistingAssignments();
                        document.getElementById('otp_code_input').value = '';
                        
                        setTimeout(function() {
                            closeClaimFlatModal();
                            checkPendingAssignment();
                            loadTenantDashboardData();
                        }, 3000);
                        
                    } else {
                        showMessage(response.message, 'error');
                        claimBtn.disabled = false;
                        claimBtn.textContent = '🔓 Claim Flat';
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    showMessage('Error processing OTP', 'error');
                    claimBtn.disabled = false;
                    claimBtn.textContent = '🔓 Claim Flat';
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
                claimBtn.disabled = false;
                claimBtn.textContent = '🔓 Claim Flat';
            }
        }
    };
    
    xhr.send(formData);
}

// ============================================
// PAYMENT GATEWAY
// ============================================

function showPaymentGatewayModal(assignment) {
    document.getElementById('gateway_assignment_id').value = assignment.assignment_id;
    document.getElementById('gateway_amount').value = assignment.advance_amount;
    
    document.getElementById('gatewayPaymentForm').style.display = 'block';
    document.getElementById('gatewayResult').style.display = 'none';
    
    document.getElementById('paymentGatewayModal').style.display = 'flex';
}

function closePaymentGatewayModal() {
    document.getElementById('paymentGatewayModal').style.display = 'none';
    gatewayTransaction = null;
}

function processGatewayPayment() {
    var amount = document.getElementById('gateway_amount').value;
    var method = document.getElementById('gateway_method').value;
    
    showLoadingOverlay();
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoadingOverlay();
            
            if (xhr.status === 200) {
                try {
                    var responseText = xhr.responseText.trim();
                    
                    if (responseText.startsWith('<') || responseText.startsWith('<!DOCTYPE')) {
                        console.error('Server returned HTML:', responseText.substring(0, 200));
                        showMessage('Server error. Please check console.', 'error');
                        return;
                    }
                    
                    var response = JSON.parse(responseText);
                    
                    if (response.success) {
                        gatewayTransaction = response.transaction;
                        
                        document.getElementById('gateway_txn_id').textContent = response.transaction.transaction_id;
                        document.getElementById('gateway_paid_amount').textContent = formatNumber(response.transaction.amount);
                        
                        document.getElementById('gatewayPaymentForm').style.display = 'none';
                        document.getElementById('gatewayResult').style.display = 'block';
                    } else {
                        showMessage(response.message || 'Payment failed', 'error');
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    showMessage('Error processing payment', 'error');
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
            }
        }
    };
    
    xhr.send('action=simulate_payment&amount=' + amount + '&payment_method=' + method);
}

function proceedToVerification() {
    closePaymentGatewayModal();
    
    if (!gatewayTransaction) {
        showMessage('No payment transaction found', 'error');
        return;
    }
    
    var assignmentId = document.getElementById('gateway_assignment_id').value;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success && response.pending_assignment) {
                    var assignment = response.pending_assignment;
                    
                    document.getElementById('verify_assignment_id').value = assignment.assignment_id;
                    document.getElementById('verify_required').textContent = formatNumber(assignment.advance_amount);
                    document.getElementById('verify_paid').textContent = '0.00';
                    document.getElementById('verify_remaining').textContent = formatNumber(assignment.advance_amount);
                    
                    document.getElementById('verify_transaction_id').value = gatewayTransaction.transaction_id;
                    document.getElementById('verify_amount').value = gatewayTransaction.amount;
                    
                    document.getElementById('paymentVerifyModal').style.display = 'flex';
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
    };
    
    xhr.send('action=check_pending_assignment');
}

function closePaymentVerifyModal() {
    document.getElementById('paymentVerifyModal').style.display = 'none';
}

// ============================================
// NOTIFICATIONS
// ============================================

function loadNotifications() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    updateNotifications(response.notifications, response.unread_count);
                }
            } catch (e) {
                console.error('Parse error:', e);
            }
        }
    };
    
    xhr.send('action=get_notifications');
}

function updateNotifications(notifications, unreadCount) {
    var badge = document.getElementById('notificationBadge');
    var list = document.getElementById('notificationsList');
    
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.style.display = 'inline-block';
            badge.style.animation = 'pulse 2s infinite';
        } else {
            badge.style.display = 'none';
        }
    }
    
    if (!list) return;
    
    if (!notifications || notifications.length === 0) {
        list.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No notifications</div>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < notifications.length; i++) {
        var notif = notifications[i];
        var unreadClass = notif.is_read ? '' : 'unread';
        
        html += '<div class="notification-item ' + unreadClass + '" onclick="markNotificationRead(' + notif.notification_id + ')">' +
            '<div class="notification-title">' + escapeHtml(notif.title) + '</div>' +
            '<div class="notification-message">' + escapeHtml(notif.message) + '</div>' +
            '<div class="notification-time">' + formatRelativeTime(notif.created_at) + '</div>' +
        '</div>';
    }
    
    list.innerHTML = html;
}

function markNotificationRead(notificationId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            loadNotifications();
        }
    };
    
    xhr.send('action=mark_notification_read&notification_id=' + notificationId);
}

function markAllNotificationsRead() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            loadNotifications();
        }
    };
    
    xhr.send('action=mark_all_notifications_read');
}

// ============================================
// FORM HANDLERS
// ============================================

function setupTenantFormHandlers() {
    var verifyForm = document.getElementById('verifyPaymentForm');
    if (verifyForm) {
        verifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var assignmentId = document.getElementById('verify_assignment_id').value;
            var transactionId = document.getElementById('verify_transaction_id').value;
            var amount = document.getElementById('verify_amount').value;
            
            if (!transactionId || !amount) {
                showMessage('Please fill all fields', 'error');
                return;
            }
            
            showLoadingOverlay();
            
            var formData = new FormData();
            formData.append('action', 'verify_and_pay');
            formData.append('assignment_id', assignmentId);
            formData.append('transaction_id', transactionId);
            formData.append('amount', amount);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoadingOverlay();
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                if (response.status === 'confirmed') {
                                    // FULL PAYMENT - CONFIRMED
                                    showMessage(response.message, 'success');
                                    closePaymentVerifyModal();
                                    
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 2000);
                                } else if (response.status === 'partial') {
                                    // PARTIAL PAYMENT
                                    showMessage(response.message + ' - Remaining: ৳' + formatNumber(response.remaining), 'warning');
                                    
                                    document.getElementById('verify_paid').textContent = formatNumber(response.total_paid);
                                    document.getElementById('verify_remaining').textContent = formatNumber(response.remaining);
                                    
                                    document.getElementById('verify_transaction_id').value = '';
                                    document.getElementById('verify_amount').value = '';
                                    
                                    if (confirm('Partial payment recorded. Remaining: ৳' + formatNumber(response.remaining) + '\n\nDo you want to make another payment now?')) {
                                        // Stay on verification modal
                                    } else {
                                        closePaymentVerifyModal();
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 2000);
                                    }
                                }
                            } else {
                                showMessage(response.message, 'error');
                            }
                        } catch (e) {
                            console.error('Error:', e);
                            showMessage('Verification error', 'error');
                        }
                    }
                }
            };
            
            xhr.send(formData);
        });
    }
}

// ============================================
// NAVIGATION
// ============================================

// Setup tenant navigation
function setupTenantNavigation() {
    console.log('Setting up navigation...');
    
    // Notification button
    var notificationBtn = document.getElementById('notificationBtn');
    if (notificationBtn) {
        notificationBtn.onclick = function(e) {
            e.stopPropagation();
            var panel = document.getElementById('notificationsPanel');
            var userMenu = document.getElementById('userMenu');
            
            if (userMenu) userMenu.style.display = 'none';
            if (panel) {
                panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            }
        };
    }
    
    // User button
    var userBtn = document.getElementById('userBtn');
    if (userBtn) {
        userBtn.onclick = function(e) {
            e.stopPropagation();
            var menu = document.getElementById('userMenu');
            var panel = document.getElementById('notificationsPanel');
            
            if (panel) panel.style.display = 'none';
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        };
    }
    
    // Close dropdowns when clicking outside
    document.onclick = function(e) {
        if (!e.target.closest('.notifications-dropdown')) {
            var panel = document.getElementById('notificationsPanel');
            if (panel) panel.style.display = 'none';
        }
        if (!e.target.closest('.user-dropdown')) {
            var menu = document.getElementById('userMenu');
            if (menu) menu.style.display = 'none';
        }
    };
    
    console.log('✅ Navigation setup complete');
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function showLoadingOverlay() {
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = 'flex';
}

function hideLoadingOverlay() {
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = 'none';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    var options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatRelativeTime(dateString) {
    if (!dateString) return '';
    
    var date = new Date(dateString);
    var now = new Date();
    var diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    
    return formatDate(dateString);
}

function formatNumber(num) {
    if (!num) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function updateElementText(id, text) {
    var element = document.getElementById(id);
    if (element) {
        element.textContent = text;
    }
}

// ============================================
// PLACEHOLDER FUNCTIONS (Coming Soon)
// ============================================

function makePayment() {
    showMessage('Payment modal coming soon', 'info');
}

function createServiceRequest() {
    showMessage('Service request feature coming soon', 'info');
}

function downloadReceipt(paymentId) {
    window.open('../controller/tenant_dashboard_controller.php?action=download_latest_slip', '_blank');
}

function downloadSlip() {
    window.open('../controller/tenant_dashboard_controller.php?action=download_latest_slip', '_blank');
}

function viewPaymentHistory() {
    showMessage('Full payment history feature coming soon', 'info');
}

function contactManager() {
    showMessage('Messaging feature coming soon', 'info');
}

function requestMoveOut() {
    showMessage('Move out request feature coming soon', 'info');
}

function updateProfile() {
    window.location.href = '../controller/profile_controller.php';
}

function viewDocuments() {
    showMessage('Documents feature coming soon', 'info');
}

// ============================================
// CLEANUP
// ============================================

window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});