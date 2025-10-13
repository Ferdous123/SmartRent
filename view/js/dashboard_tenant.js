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
    var myFlatsSection = document.getElementById('myFlatsSection');
    
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
        // Hide flats section
        if (myFlatsSection) myFlatsSection.style.display = 'none';
        
        // Update header info
        updateElementText('flatInfo', 'No flat assigned yet');
        
        // Update stats cards
        updateElementText('flatCount', '0 Flats');
        updateElementText('flatsList', 'No flats assigned');
        
        // Financial stats - all zeros
        updateElementText('totalOutstanding', '৳0.00');
        updateElementText('totalAdvance', '৳0.00');
        updateElementText('dueStatus', 'No active assignment');
        
        // Service requests
        updateElementText('activeRequests', '0');
        
        // Payment summary section
        updateElementText('lastPaymentAmount', '৳0.00');
        updateElementText('lastPaymentDate', 'No payments yet');
        updateElementText('nextDueDate', 'N/A');
        updateElementText('nextDueAmount', '৳0.00');
        
        // Flat information card - hide tabs and clear details
        var tabsContainer = document.getElementById('flatInfoTabs');
        var detailsContainer = document.getElementById('flatDetailsContainer');
        if (tabsContainer) tabsContainer.style.display = 'none';
        if (detailsContainer) {
            detailsContainer.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No flat assigned</p>';
        }
        
        // Payment history table - show empty state
        updatePaymentHistoryTable([]);
        
        return; // Stop here - no assignment to display
    }
    
    // ============================================
    // HAS ASSIGNMENT - POPULATE WITH REAL DATA
    // ============================================
    
    // Show flats section
    if (myFlatsSection) myFlatsSection.style.display = 'block';
    
    // --- HEADER SECTION ---
    if (data.total_flats > 1) {
        updateElementText('flatInfo', data.total_flats + ' Flats Assigned');
    } else {
        updateElementText('flatInfo', data.flat_info.building_name + ' - Flat ' + data.flat_info.flat_number);
    }
    
    // --- STATS CARDS - SHOW TOTALS ---
    updateElementText('flatCount', data.total_flats + ' Flat' + (data.total_flats > 1 ? 's' : ''));
    updateElementText('totalOutstanding', '৳' + formatNumber(data.outstanding_dues));
    updateElementText('totalAdvance', '৳' + formatNumber(data.total_security_deposit));
    
    // Show compact flat list in stat card
    if (data.all_flats && data.all_flats.length > 0) {
        var flatNames = data.all_flats.slice(0, 2).map(function(f) {
            return f.building_name.substring(0, 15) + ' - ' + f.flat_number;
        }).join(', ');
        if (data.all_flats.length > 2) {
            flatNames += ' +' + (data.all_flats.length - 2);
        }
        updateElementText('flatsList', flatNames);
    }
    
    // Due status
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
    
    // Active Requests Card
    updateElementText('activeRequests', data.active_service_requests || 0);
    
    // Render flats grid
    renderFlatsGrid(data.all_flats);
    
    // --- PAYMENT SUMMARY SECTION ---
    if (data.last_payment_amount && data.last_payment_amount > 0) {
        updateElementText('lastPaymentAmount', '৳' + formatNumber(data.last_payment_amount));
        updateElementText('lastPaymentDate', formatDate(data.last_payment_date));
    } else {
        updateElementText('lastPaymentAmount', '৳0.00');
        updateElementText('lastPaymentDate', 'No payments yet');
    }
    
    // Next due information (for primary flat)
    if (data.current_month) {
        updateElementText('nextDueDate', formatDate(data.current_month.due_date));
        updateElementText('nextDueAmount', '৳' + formatNumber(data.current_month.remaining));
    } else {
        updateElementText('nextDueDate', 'No upcoming dues');
        updateElementText('nextDueAmount', '৳0.00');
    }
    
    // --- FLAT INFORMATION CARD ---
    if (data.total_flats > 1) {
        // Multiple flats - show tabs
        renderFlatInfoTabs(data.all_flats);
    } else {
        // Single flat - show simple view
        renderSingleFlatInfo(data.flat_info);
    }
    
    // --- PAYMENT HISTORY TABLE ---
    updatePaymentHistoryTable(data.recent_payments);
}

// Render flats grid
function renderFlatsGrid(flats) {
    var grid = document.getElementById('flatsGrid');
    if (!grid || !flats || flats.length === 0) return;
    
    var html = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">' +
            '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                '<div>' +
                    '<h4 style="margin: 0; font-size: 18px;">' + escapeHtml(flat.building_name) + '</h4>' +
                    '<p style="margin: 0.25rem 0; opacity: 0.9;">Flat ' + escapeHtml(flat.flat_number) + ' • Floor ' + flat.floor_number + '</p>' +
                '</div>' +
                '<span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px;">Active</span>' +
            '</div>' +
            '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; margin-top: 1rem;">' +
                '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">' +
                    '<span style="opacity: 0.8;">Monthly Rent:</span>' +
                    '<strong>৳' + formatNumber(flat.base_rent) + '</strong>' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between;">' +
                    '<span style="opacity: 0.8;">Security Deposit:</span>' +
                    '<strong>৳' + formatNumber(flat.advance_amount) + '</strong>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    grid.innerHTML = html;
}

// Render flat info tabs for multiple flats
function renderFlatInfoTabs(flats) {
    var tabsContainer = document.getElementById('flatInfoTabs');
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (!tabsContainer || !detailsContainer || !flats || flats.length === 0) return;
    
    tabsContainer.style.display = 'flex';
    tabsContainer.style.gap = '0.5rem';
    tabsContainer.style.borderBottom = '2px solid #e0e0e0';
    tabsContainer.style.paddingBottom = '0.5rem';
    
    // Create tabs
    var tabsHtml = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        var isActive = i === 0 ? 'active' : '';
        tabsHtml += '<button class="flat-tab ' + isActive + '" onclick="showFlatDetails(' + i + ')" ' +
            'style="padding: 0.5rem 1rem; border: none; background: ' + (i === 0 ? '#667eea' : '#f5f7fa') + '; ' +
            'color: ' + (i === 0 ? 'white' : '#333') + '; border-radius: 8px; cursor: pointer; ' +
            'font-weight: 600; transition: all 0.3s;">' +
            flat.building_name.substring(0, 15) + ' - ' + flat.flat_number +
            '</button>';
    }
    tabsContainer.innerHTML = tabsHtml;
    
    // Store flats data globally for tab switching
    window.currentFlatsData = flats;
    
    // Show first flat details
    showFlatDetails(0);
}

// Show details for a specific flat
function showFlatDetails(index) {
    if (!window.currentFlatsData || !window.currentFlatsData[index]) return;
    
    var flat = window.currentFlatsData[index];
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (!detailsContainer) return;
    
    // Update tab styles
    var tabs = document.querySelectorAll('.flat-tab');
    tabs.forEach(function(tab, i) {
        if (i === index) {
            tab.style.background = '#667eea';
            tab.style.color = 'white';
            tab.classList.add('active');
        } else {
            tab.style.background = '#f5f7fa';
            tab.style.color = '#333';
            tab.classList.remove('active');
        }
    });
    
    // Render flat details
    detailsContainer.innerHTML = 
        '<div class="flat-details">' +
            '<div class="detail-row">' +
                '<strong>Building:</strong>' +
                '<span>' + escapeHtml(flat.building_name) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Flat Number:</strong>' +
                '<span>' + escapeHtml(flat.flat_number) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Floor:</strong>' +
                '<span>' + flat.floor_number + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Address:</strong>' +
                '<span>' + escapeHtml(flat.address) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Move-in Date:</strong>' +
                '<span>' + formatDate(flat.confirmed_at) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Monthly Rent:</strong>' +
                '<span>৳' + formatNumber(flat.base_rent) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Security Deposit:</strong>' +
                '<span>৳' + formatNumber(flat.advance_amount) + '</span>' +
            '</div>' +
        '</div>';
}

// Render single flat info (for one flat only)
function renderSingleFlatInfo(flat) {
    var tabsContainer = document.getElementById('flatInfoTabs');
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (tabsContainer) tabsContainer.style.display = 'none';
    
    if (!detailsContainer || !flat) return;
    
    detailsContainer.innerHTML = 
        '<div class="flat-details">' +
            '<div class="detail-row">' +
                '<strong>Building:</strong>' +
                '<span>' + escapeHtml(flat.building_name) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Flat Number:</strong>' +
                '<span>' + escapeHtml(flat.flat_number) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Floor:</strong>' +
                '<span>' + flat.floor_number + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Address:</strong>' +
                '<span>' + escapeHtml(flat.address) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Move-in Date:</strong>' +
                '<span>' + formatDate(flat.confirmed_at) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Monthly Rent:</strong>' +
                '<span>৳' + formatNumber(flat.base_rent) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Security Deposit:</strong>' +
                '<span>৳' + formatNumber(flat.advance_amount) + '</span>' +
            '</div>' +
        '</div>';
}

// Render flats grid (NEW)
function renderFlatsGrid(flats) {
    var grid = document.getElementById('flatsGrid');
    if (!grid || !flats || flats.length === 0) return;
    
    var html = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">' +
            '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                '<div>' +
                    '<h4 style="margin: 0; font-size: 18px;">' + escapeHtml(flat.building_name) + '</h4>' +
                    '<p style="margin: 0.25rem 0; opacity: 0.9;">Flat ' + escapeHtml(flat.flat_number) + ' • Floor ' + flat.floor_number + '</p>' +
                '</div>' +
                '<span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px;">Active</span>' +
            '</div>' +
            '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; margin-top: 1rem;">' +
                '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">' +
                    '<span style="opacity: 0.8;">Monthly Rent:</span>' +
                    '<strong>৳' + formatNumber(flat.base_rent) + '</strong>' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between;">' +
                    '<span style="opacity: 0.8;">Security Deposit:</span>' +
                    '<strong>৳' + formatNumber(flat.advance_amount) + '</strong>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    grid.innerHTML = html;
}


function renderFlatsGrid(flats) {
    var grid = document.getElementById('flatsGrid');
    if (!grid || !flats || flats.length === 0) return;
    
    var html = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">' +
            '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                '<div>' +
                    '<h4 style="margin: 0; font-size: 18px;">' + escapeHtml(flat.building_name) + '</h4>' +
                    '<p style="margin: 0.25rem 0; opacity: 0.9;">Flat ' + escapeHtml(flat.flat_number) + ' • Floor ' + flat.floor_number + '</p>' +
                '</div>' +
                '<span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px;">Active</span>' +
            '</div>' +
            '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; margin-top: 1rem;">' +
                '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">' +
                    '<span style="opacity: 0.8;">Monthly Rent:</span>' +
                    '<strong>৳' + formatNumber(flat.base_rent) + '</strong>' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between;">' +
                    '<span style="opacity: 0.8;">Security Deposit:</span>' +
                    '<strong>৳' + formatNumber(flat.advance_amount) + '</strong>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    grid.innerHTML = html;
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
                        window.gatewayTransaction = response;
                        console.log('Set gatewayTransaction:', window.gatewayTransaction); // ADD THIS
                        
                        document.getElementById('gateway_txn_id').textContent = response.transaction_id;
                        document.getElementById('gateway_paid_amount').textContent = formatNumber(response.amount);
                        
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
                    
                    var gw = window.gatewayTransaction;
                    document.getElementById('verify_transaction_id').value = gw ? gw.transaction_id : '';
                    document.getElementById('verify_amount').value = gw ? gw.amount : '';
                    
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

// Render flat info tabs for multiple flats
function renderFlatInfoTabs(flats) {
    var tabsContainer = document.getElementById('flatInfoTabs');
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (!tabsContainer || !detailsContainer || !flats || flats.length === 0) return;
    
    tabsContainer.style.display = 'flex';
    tabsContainer.style.gap = '0.5rem';
    tabsContainer.style.borderBottom = '2px solid #e0e0e0';
    tabsContainer.style.paddingBottom = '0.5rem';
    
    // Create tabs
    var tabsHtml = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        var isActive = i === 0 ? 'active' : '';
        tabsHtml += '<button class="flat-tab ' + isActive + '" onclick="showFlatDetails(' + i + ')" ' +
            'style="padding: 0.5rem 1rem; border: none; background: ' + (i === 0 ? '#667eea' : '#f5f7fa') + '; ' +
            'color: ' + (i === 0 ? 'white' : '#333') + '; border-radius: 8px; cursor: pointer; ' +
            'font-weight: 600; transition: all 0.3s;">' +
            flat.building_name.substring(0, 15) + ' - ' + flat.flat_number +
            '</button>';
    }
    tabsContainer.innerHTML = tabsHtml;
    
    // Store flats data globally for tab switching
    window.currentFlatsData = flats;
    
    // Show first flat details
    showFlatDetails(0);
}

// Show details for a specific flat
function showFlatDetails(index) {
    if (!window.currentFlatsData || !window.currentFlatsData[index]) return;
    
    var flat = window.currentFlatsData[index];
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (!detailsContainer) return;
    
    // Update tab styles
    var tabs = document.querySelectorAll('.flat-tab');
    tabs.forEach(function(tab, i) {
        if (i === index) {
            tab.style.background = '#667eea';
            tab.style.color = 'white';
            tab.classList.add('active');
        } else {
            tab.style.background = '#f5f7fa';
            tab.style.color = '#333';
            tab.classList.remove('active');
        }
    });
    
    // Render flat details
    detailsContainer.innerHTML = 
        '<div class="flat-details">' +
            '<div class="detail-row">' +
                '<strong>Building:</strong>' +
                '<span>' + escapeHtml(flat.building_name) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Flat Number:</strong>' +
                '<span>' + escapeHtml(flat.flat_number) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Floor:</strong>' +
                '<span>' + flat.floor_number + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Address:</strong>' +
                '<span>' + escapeHtml(flat.address) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Move-in Date:</strong>' +
                '<span>' + formatDate(flat.confirmed_at) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Monthly Rent:</strong>' +
                '<span>৳' + formatNumber(flat.base_rent) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Security Deposit:</strong>' +
                '<span>৳' + formatNumber(flat.advance_amount) + '</span>' +
            '</div>' +
        '</div>';
}

// Render single flat info (for one flat only)
function renderSingleFlatInfo(flat) {
    var tabsContainer = document.getElementById('flatInfoTabs');
    var detailsContainer = document.getElementById('flatDetailsContainer');
    
    if (tabsContainer) tabsContainer.style.display = 'none';
    
    if (!detailsContainer || !flat) return;
    
    detailsContainer.innerHTML = 
        '<div class="flat-details">' +
            '<div class="detail-row">' +
                '<strong>Building:</strong>' +
                '<span>' + escapeHtml(flat.building_name) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Flat Number:</strong>' +
                '<span>' + escapeHtml(flat.flat_number) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Floor:</strong>' +
                '<span>' + flat.floor_number + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Address:</strong>' +
                '<span>' + escapeHtml(flat.address) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Move-in Date:</strong>' +
                '<span>' + formatDate(flat.confirmed_at) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Monthly Rent:</strong>' +
                '<span>৳' + formatNumber(flat.base_rent) + '</span>' +
            '</div>' +
            '<div class="detail-row">' +
                '<strong>Security Deposit:</strong>' +
                '<span>৳' + formatNumber(flat.advance_amount) + '</span>' +
            '</div>' +
        '</div>';
}

// Update renderFlatsGrid to add onclick
function renderFlatsGrid(flats) {
    var grid = document.getElementById('flatsGrid');
    if (!grid || !flats || flats.length === 0) return;
    
    var html = '';
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        html += '<div onclick="showFlatActionsModal(' + i + ')" style="cursor: pointer; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-4px)\'" onmouseout="this.style.transform=\'translateY(0)\'">' +
            '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                '<div>' +
                    '<h4 style="margin: 0; font-size: 18px;">' + escapeHtml(flat.building_name) + '</h4>' +
                    '<p style="margin: 0.25rem 0; opacity: 0.9;">Flat ' + escapeHtml(flat.flat_number) + ' • Floor ' + flat.floor_number + '</p>' +
                '</div>' +
                '<span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px;">Active</span>' +
            '</div>' +
            '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; margin-top: 1rem;">' +
                '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">' +
                    '<span style="opacity: 0.8;">Monthly Rent:</span>' +
                    '<strong>৳' + formatNumber(flat.base_rent) + '</strong>' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between;">' +
                    '<span style="opacity: 0.8;">Security Deposit:</span>' +
                    '<strong>৳' + formatNumber(flat.advance_amount) + '</strong>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    grid.innerHTML = html;
}

// Show flat actions modal
function showFlatActionsModal(index) {
    if (!window.currentFlatsData || !window.currentFlatsData[index]) return;
    
    var flat = window.currentFlatsData[index];
    window.selectedFlatForActions = flat;
    
    var modal = document.getElementById('flatActionsModal');
    var title = document.getElementById('flatActionsTitle');
    var menu = document.getElementById('flatActionsMenu');
    
    title.textContent = flat.building_name + ' - Flat ' + flat.flat_number;
    
    // Check if move-out is already requested
    var hasMoveOutRequest = flat.move_out_date && !flat.actual_ended_at;
    var canCancelMoveOut = false;
    
    if (hasMoveOutRequest) {
        var moveOutDate = new Date(flat.move_out_date);
        var previousMonthFirst = new Date(moveOutDate.getFullYear(), moveOutDate.getMonth() - 1, 1);
        canCancelMoveOut = new Date() < previousMonthFirst;
    }
    
    var actionsHtml = 
        '<button onclick="viewFlatDetails()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">📊</span>' +
            '<div><strong>View Full Details</strong><br><small style="color: #666;">See complete flat information</small></div>' +
        '</button>' +
        
        '<button onclick="viewFlatPaymentHistory()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">💰</span>' +
            '<div><strong>Payment History</strong><br><small style="color: #666;">View all payments for this flat</small></div>' +
        '</button>' +
        
        '<button onclick="viewFlatOutstanding()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">📋</span>' +
            '<div><strong>Outstanding Dues</strong><br><small style="color: #666;">See pending payments</small></div>' +
        '</button>' +
        
        '<button onclick="createServiceRequest()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">🔧</span>' +
            '<div><strong>Create Service Request</strong><br><small style="color: #666;">Report maintenance issues</small></div>' +
        '</button>' +
        
        '<button onclick="viewServiceRequests()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">📝</span>' +
            '<div><strong>View Service Requests</strong><br><small style="color: #666;">Check request status</small></div>' +
        '</button>' +
        
        '<button onclick="viewFlatExpenses()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">💵</span>' +
            '<div><strong>View Expenses</strong><br><small style="color: #666;">Monthly expense breakdown</small></div>' +
        '</button>' +
        
        '<button onclick="downloadFlatSlip()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">📄</span>' +
            '<div><strong>Download Rent Slip</strong><br><small style="color: #666;">Get latest rent receipt</small></div>' +
        '</button>' +
        
        '<button onclick="viewMeterReadings()" class="action-btn" style="padding: 1rem; background: #f5f7fa; border: 2px solid #e0e0e0; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
            '<span style="font-size: 24px;">⚡</span>' +
            '<div><strong>Meter Readings</strong><br><small style="color: #666;">View utility meters</small></div>' +
        '</button>';
    
    // Move out actions
    if (hasMoveOutRequest && canCancelMoveOut) {
        actionsHtml += 
            '<button onclick="cancelMoveOut()" class="action-btn" style="padding: 1rem; background: #fff3cd; border: 2px solid #f57c00; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
                '<span style="font-size: 24px;">🔙</span>' +
                '<div><strong>Cancel Move Out</strong><br><small style="color: #856404;">Cancel your move-out request</small></div>' +
            '</button>';
    } else if (!hasMoveOutRequest) {
        actionsHtml += 
            '<button onclick="requestMoveOut()" class="action-btn" style="padding: 1rem; background: #ffebee; border: 2px solid #f44336; border-radius: 8px; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem;">' +
                '<span style="font-size: 24px;">🚪</span>' +
                '<div><strong>Request Move Out</strong><br><small style="color: #c62828;">Submit move-out notice</small></div>' +
            '</button>';
    }
    
    menu.innerHTML = actionsHtml;
    
    // Add hover effects
    setTimeout(function() {
        var buttons = document.querySelectorAll('.action-btn');
        buttons.forEach(function(btn) {
            btn.addEventListener('mouseover', function() {
                this.style.background = '#667eea';
                this.style.color = 'white';
                this.style.borderColor = '#667eea';
                this.style.transform = 'translateX(5px)';
            });
            btn.addEventListener('mouseout', function() {
                this.style.background = '#f5f7fa';
                this.style.color = '#333';
                this.style.borderColor = '#e0e0e0';
                this.style.transform = 'translateX(0)';
            });
        });
    }, 100);
    
    modal.style.display = 'flex';
}

function closeFlatActionsModal() {
    document.getElementById('flatActionsModal').style.display = 'none';
}

// 1. View Full Details
function viewFlatDetails() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayFlatDetails(response.details);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading details', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_flat_full_details&flat_id=' + flat.flat_id);
}

function displayFlatDetails(details) {
    var content = document.getElementById('flatDetailsContent');
    
    var html = '<div style="display: grid; gap: 1rem;">';
    
    // Basic Info
    html += '<div style="background: #f5f7fa; padding: 1rem; border-radius: 8px;">' +
        '<h4 style="margin: 0 0 0.5rem 0;">Basic Information</h4>' +
        '<div class="detail-row"><strong>Building:</strong><span>' + escapeHtml(details.building_name) + '</span></div>' +
        '<div class="detail-row"><strong>Address:</strong><span>' + escapeHtml(details.address) + '</span></div>' +
        '<div class="detail-row"><strong>Flat:</strong><span>' + escapeHtml(details.flat_number) + '</span></div>' +
        '<div class="detail-row"><strong>Floor:</strong><span>' + details.floor_number + '</span></div>' +
        '<div class="detail-row"><strong>Bedrooms:</strong><span>' + (details.bedrooms || 'N/A') + '</span></div>' +
        '<div class="detail-row"><strong>Bathrooms:</strong><span>' + (details.bathrooms || 'N/A') + '</span></div>' +
        '</div>';
    
    // Financial Info
    html += '<div style="background: #e3f2fd; padding: 1rem; border-radius: 8px;">' +
        '<h4 style="margin: 0 0 0.5rem 0;">Financial Details</h4>' +
        '<div class="detail-row"><strong>Monthly Rent:</strong><span>৳' + formatNumber(details.base_rent) + '</span></div>' +
        '<div class="detail-row"><strong>Security Deposit:</strong><span>৳' + formatNumber(details.advance_amount) + '</span></div>' +
        '<div class="detail-row"><strong>Move-in Date:</strong><span>' + formatDate(details.confirmed_at) + '</span></div>' +
        '</div>';
    
    html += '</div>';
    
    content.innerHTML = html;
    document.getElementById('flatDetailsModal').style.display = 'flex';
}

function closeFlatDetailsModal() {
    document.getElementById('flatDetailsModal').style.display = 'none';
}

// 2. View Payment History
function viewFlatPaymentHistory() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayFlatPaymentHistory(response.payments);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading payment history', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_flat_payment_history&flat_id=' + flat.flat_id);
}

function displayFlatPaymentHistory(payments) {
    var content = document.getElementById('flatPaymentHistoryContent');
    
    if (!payments || payments.length === 0) {
        content.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No payments yet</p>';
    } else {
        var html = '<table style="width: 100%; border-collapse: collapse;">' +
            '<thead><tr style="background: #f5f7fa;">' +
            '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Date</th>' +
            '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Type</th>' +
            '<th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid #e0e0e0;">Amount</th>' +
            '<th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid #e0e0e0;">Status</th>' +
            '</tr></thead><tbody>';
        
        for (var i = 0; i < payments.length; i++) {
            var payment = payments[i];
            var statusColor = payment.is_verified ? '#28a745' : '#ffc107';
            var statusText = payment.is_verified ? 'Verified' : 'Pending';
            
            html += '<tr style="border-bottom: 1px solid #f0f0f0;">' +
                '<td style="padding: 0.75rem;">' + formatDate(payment.payment_date) + '</td>' +
                '<td style="padding: 0.75rem;">' + escapeHtml(payment.payment_type) + '</td>' +
                '<td style="padding: 0.75rem; text-align: right;">৳' + formatNumber(payment.amount) + '</td>' +
                '<td style="padding: 0.75rem; text-align: center;"><span style="background: ' + statusColor + '; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 12px;">' + statusText + '</span></td>' +
                '</tr>';
        }
        
        html += '</tbody></table>';
        content.innerHTML = html;
    }
    
    document.getElementById('flatPaymentHistoryModal').style.display = 'flex';
}

function closeFlatPaymentHistoryModal() {
    document.getElementById('flatPaymentHistoryModal').style.display = 'none';
}

// 3. View Outstanding Dues
function viewFlatOutstanding() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayFlatOutstanding(response.dues);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading dues', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_flat_outstanding&flat_id=' + flat.flat_id);
}

function displayFlatOutstanding(dues) {
    var content = document.getElementById('flatOutstandingContent');
    
    if (!dues || dues.length === 0) {
        content.innerHTML = '<div style="text-align: center; padding: 2rem; color: #28a745;">' +
            '<div style="font-size: 48px;">✅</div>' +
            '<h3 style="margin: 0.5rem 0;">All Paid!</h3>' +
            '<p style="color: #666;">You have no outstanding dues for this flat</p>' +
            '</div>';
    } else {
        var totalOutstanding = 0;
        var html = '<table style="width: 100%; border-collapse: collapse;">' +
            '<thead><tr style="background: #f5f7fa;">' +
            '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Month</th>' +
            '<th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid #e0e0e0;">Total</th>' +
            '<th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid #e0e0e0;">Paid</th>' +
            '<th style="padding: 0.75rem; text-align: right; border-bottom: 2px solid #e0e0e0;">Remaining</th>' +
            '</tr></thead><tbody>';
        
        for (var i = 0; i < dues.length; i++) {
            var due = dues[i];
            totalOutstanding += parseFloat(due.remaining_amount);
            
            html += '<tr style="border-bottom: 1px solid #f0f0f0;">' +
                '<td style="padding: 0.75rem;">' + formatDate(due.billing_month) + '</td>' +
                '<td style="padding: 0.75rem; text-align: right;">৳' + formatNumber(due.total_due) + '</td>' +
                '<td style="padding: 0.75rem; text-align: right;">৳' + formatNumber(due.paid_amount) + '</td>' +
                '<td style="padding: 0.75rem; text-align: right; color: #f44336; font-weight: bold;">৳' + formatNumber(due.remaining_amount) + '</td>' +
                '</tr>';
        }
        
        html += '<tr style="background: #fff3cd; font-weight: bold;">' +
            '<td colspan="3" style="padding: 0.75rem; text-align: right;">Total Outstanding:</td>' +
            '<td style="padding: 0.75rem; text-align: right; color: #f44336;">৳' + formatNumber(totalOutstanding) + '</td>' +
            '</tr>';
        
        html += '</tbody></table>';
        content.innerHTML = html;
    }
    
    document.getElementById('flatOutstandingModal').style.display = 'flex';
}

function closeFlatOutstandingModal() {
    document.getElementById('flatOutstandingModal').style.display = 'none';
}

// 4. Create Service Request
function createServiceRequest() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
    document.getElementById('service_flat_id').value = flat.flat_id;
    document.getElementById('serviceRequestForm').reset();
    document.getElementById('service_flat_id').value = flat.flat_id;
    
    document.getElementById('serviceRequestModal').style.display = 'flex';
}

function closeServiceRequestModal() {
    document.getElementById('serviceRequestModal').style.display = 'none';
}

function submitServiceRequest(event) {
    event.preventDefault();
    
    var flatId = document.getElementById('service_flat_id').value;
    var type = document.getElementById('service_type').value;
    var priority = document.getElementById('service_priority').value;
    var description = document.getElementById('service_description').value;
    
    showLoadingOverlay();
    
    var formData = new FormData();
    formData.append('action', 'create_service_request');
    formData.append('flat_id', flatId);
    formData.append('request_type', type);
    formData.append('priority', priority);
    formData.append('description', description);
    
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
                        closeServiceRequestModal();
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error submitting request', 'error');
                }
            }
        }
    };
    
    xhr.send(formData);
}

// 5. View Service Requests
function viewServiceRequests() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayServiceRequests(response.requests);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading requests', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_flat_service_requests&flat_id=' + flat.flat_id);
}

function displayServiceRequests(requests) {
    var content = document.getElementById('serviceRequestsContent');
    
    if (!requests || requests.length === 0) {
        content.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No service requests yet</p>';
    } else {
        var html = '<div style="display: grid; gap: 1rem;">';
        
        for (var i = 0; i < requests.length; i++) {
            var request = requests[i];
            var statusColors = {
                'pending': '#ffc107',
                'assigned': '#2196f3',
                'in_progress': '#ff9800',
                'completed': '#28a745',
                'cancelled': '#dc3545'
            };
            var statusColor = statusColors[request.status] || '#999';
            
            var priorityColors = {
                'low': '#999',
                'medium': '#2196f3',
                'high': '#ff9800',
                'urgent': '#dc3545'
            };
            var priorityColor = priorityColors[request.priority] || '#999';
            
            html += '<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid ' + statusColor + ';">' +
                '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">' +
                    '<div>' +
                        '<strong style="font-size: 16px;">' + escapeHtml(request.request_type).toUpperCase() + '</strong>' +
                        '<span style="background: ' + priorityColor + '; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 11px; margin-left: 0.5rem;">' + request.priority + '</span>' +
                    '</div>' +
                    '<span style="background: ' + statusColor + '; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 12px;">' + request.status + '</span>' +
                '</div>' +
                '<p style="margin: 0.5rem 0; color: #666;">' + escapeHtml(request.description) + '</p>' +
                '<small style="color: #999;">Created: ' + formatDate(request.created_at) + '</small>';
            
            if (request.resolution_notes) {
                html += '<div style="margin-top: 0.5rem; padding: 0.5rem; background: #e8f5e9; border-radius: 4px;">' +
                    '<strong style="color: #2e7d32;">Resolution:</strong> ' + escapeHtml(request.resolution_notes) +
                    '</div>';
            }
            
            html += '</div>';
        }
        
        html += '</div>';
        content.innerHTML = html;
    }
    
    document.getElementById('viewServiceRequestsModal').style.display = 'flex';
}

function closeViewServiceRequestsModal() {
    document.getElementById('viewServiceRequestsModal').style.display = 'none';
}

// 6. View Flat Expenses
function viewFlatExpenses() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayFlatExpenses(response.expenses);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading expenses', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_flat_expenses&flat_id=' + flat.flat_id);
}

function displayFlatExpenses(expenses) {
    var content = document.getElementById('flatExpensesContent');
    
    if (!expenses || expenses.length === 0) {
        content.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No expense records yet</p>';
    } else {
        var html = '<div style="display: grid; gap: 1rem;">';
        
        for (var i = 0; i < expenses.length; i++) {
            var expense = expenses[i];
            
            html += '<div style="background: #f5f7fa; padding: 1rem; border-radius: 8px;">' +
                '<h4 style="margin: 0 0 1rem 0; color: #667eea;">' + formatDate(expense.billing_month) + '</h4>' +
                '<table style="width: 100%;">' +
                '<tr><td style="padding: 0.25rem 0;">Rent:</td><td style="text-align: right;">৳' + formatNumber(expense.rent) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Electric:</td><td style="text-align: right;">৳' + formatNumber(expense.electric_bill) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Gas:</td><td style="text-align: right;">৳' + formatNumber(expense.gas_bill) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Water:</td><td style="text-align: right;">৳' + formatNumber(expense.water_bill) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Service Charge:</td><td style="text-align: right;">৳' + formatNumber(expense.service_charge) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Cleaning:</td><td style="text-align: right;">৳' + formatNumber(expense.cleaning_charge) + '</td></tr>' +
                '<tr><td style="padding: 0.25rem 0;">Miscellaneous:</td><td style="text-align: right;">৳' + formatNumber(expense.miscellaneous) + '</td></tr>' +
                '<tr style="border-top: 2px solid #667eea; font-weight: bold;"><td style="padding: 0.5rem 0;">Total:</td><td style="text-align: right; color: #667eea;">৳' + formatNumber(expense.total_amount) + '</td></tr>' +
                '</table>' +
                '</div>';
        }
        
        html += '</div>';
        content.innerHTML = html;
    }
    
    document.getElementById('flatExpensesModal').style.display = 'flex';
}

function closeFlatExpensesModal() {
    document.getElementById('flatExpensesModal').style.display = 'none';
}

// 7. Download Rent Slip
function downloadFlatSlip() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
    window.open('../controller/tenant_dashboard_controller.php?action=download_flat_slip&flat_id=' + flat.flat_id, '_blank');
    showMessage('Downloading slip...', 'info');
}

// 8. View Meter Readings
function viewMeterReadings() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
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
                        displayMeterReadings(response.meters);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error loading meters', 'error');
                }
            }
        }
    };
    
    xhr.send('action=get_meter_readings&flat_id=' + flat.flat_id);
}

function displayMeterReadings(meters) {
    var content = document.getElementById('meterReadingsContent');
    
    if (!meters || meters.length === 0) {
        content.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No meter data available</p>';
    } else {
        var html = '<div style="display: grid; gap: 1rem;">';
        
        var meterIcons = {
            'electric_prepaid': '⚡',
            'electric_postpaid': '💡',
            'gas': '🔥',
            'water': '💧'
        };
        
        for (var i = 0; i < meters.length; i++) {
            var meter = meters[i];
            var icon = meterIcons[meter.meter_type] || '📊';
            var consumption = meter.current_reading - meter.previous_reading;
            var cost = consumption * meter.per_unit_cost;
            
            html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px;">' +
                '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                    '<div>' +
                        '<span style="font-size: 32px;">' + icon + '</span>' +
                        '<h4 style="margin: 0.5rem 0 0 0;">' + escapeHtml(meter.meter_type).replace(/_/g, ' ').toUpperCase() + '</h4>' +
                    '</div>' +
                    '<div style="text-align: right;">' +
                        '<div style="font-size: 24px; font-weight: bold;">৳' + formatNumber(cost) + '</div>' +
                        '<small style="opacity: 0.8;">Current bill</small>' +
                    '</div>' +
                '</div>' +
                '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Meter Number</small>' +
                        '<div style="font-weight: bold;">' + escapeHtml(meter.meter_number || 'N/A') + '</div>' +
                    '</div>' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Last Reading</small>' +
                        '<div style="font-weight: bold;">' + formatDate(meter.last_reading_date) + '</div>' +
                    '</div>' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Previous</small>' +
                        '<div style="font-weight: bold;">' + formatNumber(meter.previous_reading) + ' units</div>' +
                    '</div>' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Current</small>' +
                        '<div style="font-weight: bold;">' + formatNumber(meter.current_reading) + ' units</div>' +
                    '</div>' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Consumption</small>' +
                        '<div style="font-weight: bold;">' + formatNumber(consumption) + ' units</div>' +
                    '</div>' +
                    '<div>' +
                        '<small style="opacity: 0.8;">Rate</small>' +
                        '<div style="font-weight: bold;">৳' + formatNumber(meter.per_unit_cost) + '/unit</div>' +
                    '</div>' +
                '</div>' +
                '</div>';
        }
        
        html += '</div>';
        content.innerHTML = html;
    }
    
    document.getElementById('meterReadingsModal').style.display = 'flex';
}

function closeMeterReadingsModal() {
    document.getElementById('meterReadingsModal').style.display = 'none';
}

// 9. Request Move Out
function requestMoveOut() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
    document.getElementById('moveout_flat_id').value = flat.flat_id;
    document.getElementById('moveout_assignment_id').value = flat.assignment_id;
    
    // Set min date to next month's 1st
    var today = new Date();
    var nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
    var minDate = nextMonth.toISOString().split('T')[0];
    document.getElementById('moveout_date').setAttribute('min', minDate);
    
    // Validate only 1st of month
    document.getElementById('moveout_date').addEventListener('change', function() {
        var selectedDate = new Date(this.value);
        if (selectedDate.getDate() !== 1) {
            showMessage('Move-out date must be the 1st day of a month', 'error');
            this.value = '';
        }
    });
    
    document.getElementById('moveOutModal').style.display = 'flex';
}

function closeMoveOutModal() {
    document.getElementById('moveOutModal').style.display = 'none';
}

function submitMoveOut(event) {
    event.preventDefault();
    
    var flatId = document.getElementById('moveout_flat_id').value;
    var assignmentId = document.getElementById('moveout_assignment_id').value;
    var moveOutDate = document.getElementById('moveout_date').value;
    var reason = document.getElementById('moveout_reason').value;
    
    // Validate date is 1st of month
    var date = new Date(moveOutDate);
    if (date.getDate() !== 1) {
        showMessage('Move-out date must be the 1st day of a month', 'error');
        return;
    }
    
    // Confirm
    if (!confirm('Are you sure you want to request move-out on ' + formatDate(moveOutDate) + '?')) {
        return;
    }
    
    showLoadingOverlay();
    
    var formData = new FormData();
    formData.append('action', 'request_move_out');
    formData.append('flat_id', flatId);
    formData.append('assignment_id', assignmentId);
    formData.append('move_out_date', moveOutDate);
    formData.append('reason', reason);
    
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
                        closeMoveOutModal();
                        setTimeout(function() {
                            loadTenantDashboardData();
                        }, 1500);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error submitting request', 'error');
                }
            }
        }
    };
    
    xhr.send(formData);
}

// 10. Cancel Move Out
function cancelMoveOut() {
    closeFlatActionsModal();
    var flat = window.selectedFlatForActions;
    
    // Check if cancellation is still allowed
    var moveOutDate = new Date(flat.move_out_date);
    var previousMonthFirst = new Date(moveOutDate.getFullYear(), moveOutDate.getMonth() - 1, 1);
    
    if (new Date() >= previousMonthFirst) {
        showMessage('Cannot cancel move-out request. Deadline has passed.', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to cancel your move-out request?')) {
        return;
    }
    
    showLoadingOverlay();
    
    var formData = new FormData();
    formData.append('action', 'cancel_move_out');
    formData.append('assignment_id', flat.assignment_id);
    
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
                        setTimeout(function() {
                            loadTenantDashboardData();
                        }, 1500);
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error cancelling request', 'error');
                }
            }
        }
    };
    
    xhr.send(formData);
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

function closePendingModal() {
    document.getElementById('pendingAssignmentModal').style.display = 'none';
}

// ============================================
// CLEANUP
// ============================================

window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});