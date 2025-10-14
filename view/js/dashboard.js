
var currentUser = null;
var dashboardStats = null;
var sessionCheckInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    initCommonDashboard();
});


function initCommonDashboard() {
    loadDashboardStats();
    loadBuildingsOverview();
    loadRecentActivity();
    setupModalHandlers();
}

function loadDashboardStats() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    dashboardStats = response.stats;
                    updateDashboardStats(response.stats);
                } else {
                    console.error('Failed to load dashboard statistics');
                }
            } catch (e) {
                console.error('Invalid response from server:', e);
            }
        }
    };
    
    xhr.send('action=get_dashboard_stats');
}

function updateDashboardStats(stats) {
    updateElementText('totalBuildings', stats.total_buildings || stats.managed_buildings);
    updateElementText('totalFlats', stats.total_flats);
    updateElementText('totalTenants', stats.total_tenants);
    updateElementText('occupiedFlats', stats.occupied_flats);
    updateElementText('availableFlats', stats.available_flats);
    updateElementText('managedBuildings', stats.managed_buildings);
    updateElementText('pendingRequests', stats.pending_service_requests || stats.active_service_requests);
    
    if (stats.occupancy_rate !== undefined) {
        updateElementText('occupancyRate', stats.occupancy_rate + '%');
    }
    
    if (stats.monthly_revenue !== undefined) {
        updateElementText('monthlyRevenue', '৳' + formatNumber(stats.monthly_revenue));
    }
    
    if (stats.outstanding_payments_count !== undefined) {
        updateElementText('outstandingCount', stats.outstanding_payments_count);
    }
    
    if (stats.outstanding_payments_amount !== undefined) {
        updateElementText('outstandingAmount', '৳' + formatNumber(stats.outstanding_payments_amount));
    }
    
    if (stats.has_assignment) {
        updateElementText('flatDetails', stats.flat_info);
        updateElementText('flatInfo', stats.flat_info);
        updateElementText('outstandingDues', '৳' + formatNumber(stats.outstanding_dues || 0));
        updateElementText('advanceBalance', '৳' + formatNumber(stats.advance_balance || 0));
        updateElementText('lastPaymentAmount', '৳' + formatNumber(stats.last_payment_amount || 0));
        updateElementText('lastPaymentDate', stats.last_payment_date || 'No payments');
        updateElementText('activeRequests', stats.active_service_requests || 0);
    } else if (stats.has_assignment === false) {
        updateElementText('flatInfo', 'No active flat assignment found');
    }
}

function updateElementText(elementId, text) {
    var element = document.getElementById(elementId);
    if (element && text !== undefined && text !== null) {
        element.textContent = text;
    }
}

function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function loadRecentActivity() {
    setTimeout(function() {
        var activityList = document.getElementById('activityList');
        if (activityList) {
            activityList.innerHTML = 
                '<div class="activity-item">' +
                '<div class="activity-icon">✅</div>' +
                '<div class="activity-content">' +
                '<p><strong>Dashboard Loaded</strong></p>' +
                '<p>Welcome to your SmartRent dashboard</p>' +
                '<span class="activity-time">Just now</span>' +
                '</div>' +
                '</div>';
        }
    }, 1000);
}

function setupModalHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var openModals = document.querySelectorAll('.modal[style*="block"]');
            for (var i = 0; i < openModals.length; i++) {
                closeModal(openModals[i]);
            }
        }
    });
}

function showModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        var firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(function() {
                firstInput.focus();
            }, 100);
        }
    }
}

function closeModal(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }
    
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        var form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

// Quick action functions
function assignTenant() {
    alert('Assign Tenant feature coming soon!');
}

function generateOTP() {
    alert('Generate OTP feature coming soon!');
}

function recordPayment() {
    alert('Record Payment feature coming soon!');
}

function handleServiceRequest() {
    alert('Handle Service Request feature coming soon!');
}

function addBuilding() {
    alert('Add Building feature coming soon!');
}

function assignManager() {
    alert('Assign Manager feature coming soon!');
}

function viewReports() {
    alert('View Reports feature coming soon!');
}

function backupData() {
    alert('Backup Data feature coming soon!');
}

function makePayment() {
    alert('Make Payment feature coming soon!');
}

function createServiceRequest() {
    alert('Create Service Request feature coming soon!');
}

function downloadReceipt() {
    alert('Download Receipt feature coming soon!');
}

function updateProfile() {
    alert('Update Profile feature coming soon!');
}

function viewServiceRequests() {
    alert('Service Requests feature coming soon!');
}

// Utility functions
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../controller/auth_controller.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                window.location.href = '../index.php';
            }
        };
        
        xhr.send('action=logout');
    }
}

function showMessage(message, type, onclick) {
    var container = document.getElementById('messageContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'messageContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '10000';
        document.body.appendChild(container);
    }
    
    var messageDiv = document.createElement('div');
    messageDiv.textContent = message;
    messageDiv.style.padding = '1rem 1.5rem';
    messageDiv.style.marginBottom = '10px';
    messageDiv.style.borderRadius = '8px';
    messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    messageDiv.style.maxWidth = '400px';
    messageDiv.style.fontWeight = '500';
    
    // Set color based on type
    if (type === 'success') {
        messageDiv.style.background = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.border = '1px solid #f5c6cb';
    } else if (type === 'warning') {
        messageDiv.style.background = '#fff3cd';
        messageDiv.style.color = '#856404';
        messageDiv.style.border = '1px solid #ffeeba';
    } else {
        messageDiv.style.background = '#d1ecf1';
        messageDiv.style.color = '#0c5460';
        messageDiv.style.border = '1px solid #bee5eb';
    }
    
    if (onclick) {
        messageDiv.style.cursor = 'pointer';
        messageDiv.addEventListener('click', onclick);
    }
    
    container.appendChild(messageDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        if (messageDiv.parentNode) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                if (messageDiv.parentNode) {
                    container.removeChild(messageDiv);
                }
            }, 500);
        }
    }, 5000);
}

function isValidEmail(email) {
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(dateString) {
    var date = new Date(dateString);
    var now = new Date();
    var diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) {
        return 'Just now';
    } else if (diffInMinutes < 60) {
        return diffInMinutes + ' minute' + (diffInMinutes === 1 ? '' : 's') + ' ago';
    } else if (diffInMinutes < 1440) {
        var hours = Math.floor(diffInMinutes / 60);
        return hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
    } else {
        var days = Math.floor(diffInMinutes / 1440);
        return days + ' day' + (days === 1 ? '' : 's') + ' ago';
    }
}

// Load buildings overview
function loadBuildingsOverview() {
    var container = document.getElementById('buildingsList');
    if (!container) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayBuildingsOverview(response.buildings);
                }
            } catch (e) {
                console.error('Error loading buildings overview:', e);
            }
        }
    };
    
    xhr.send('action=get_buildings_overview');
}

// Display buildings overview
function displayBuildingsOverview(buildings) {
    var container = document.getElementById('buildingsList');
    
    if (!container) return;
    
    if (!buildings || buildings.length === 0) {
        container.innerHTML = '<div class="empty-state">' +
            '<p>No buildings added yet</p>' +
            '<button class="btn-primary" onclick="showAddBuildingModal()">Add Building</button>' +
            '</div>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < buildings.length; i++) {
        var building = buildings[i];
        var occupancyPercent = 0;
        
        if (building.total_flats > 0) {
            occupancyPercent = Math.round((building.occupied_flats / building.total_flats) * 100);
        }
        
        html += '<div class="building-item">' +
            '<div class="building-info">' +
                '<h4>' + escapeHtml(building.building_name) + '</h4>' +
                '<p>' + escapeHtml(building.address) + '</p>' +
            '</div>' +
            '<div class="building-stats">' +
                '<span class="stat">' + building.occupied_flats + '/' + building.total_flats + ' occupied</span>' +
                '<span class="occupancy-bar">' +
                    '<span class="occupancy-fill" style="width: ' + occupancyPercent + '%;"></span>' +
                '</span>' +
            '</div>' +
            '<div class="building-actions">' +
                '<button class="btn-small" onclick="viewBuilding(' + building.building_id + ')">View</button>' +
                '<button class="btn-small" onclick="manageBuilding(' + building.building_id + ')">Manage</button>' +
            '</div>' +
        '</div>';
    }
    
    container.innerHTML = html;
}

// Load actions needed
function loadActionsNeeded() {
    var container = document.getElementById('actionsNeededList');
    if (!container) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayActionsNeeded(response.assignments);
                }
            } catch (e) {
                console.error('Error loading actions:', e);
            }
        }
    };
    
    xhr.send('action=get_pending_assignments');
}

// Display actions needed
function displayActionsNeeded(assignments) {
    var container = document.getElementById('actionsNeededList');
    if (!container) return;
    
    if (!assignments || assignments.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999;">No pending actions</p>';
        return;
    }
    
    var html = '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';
    
    var count = 0;
    for (var i = 0; i < assignments.length && count < 5; i++) {
        var assignment = assignments[i];
        var hours = Math.floor(assignment.seconds_remaining / 3600);
        
        html += '<div style="padding: 0.75rem; background: #fff3e0; border-left: 4px solid #f57c00; border-radius: 4px;">' +
            '<p style="margin: 0; font-size: 13px; color: #333;">' +
            '<strong>' + escapeHtml(assignment.building_name) + ' - ' + escapeHtml(assignment.flat_number) + '</strong><br>' +
            (assignment.tenant_name ? 'Tenant: ' + escapeHtml(assignment.tenant_name) : 'Awaiting claim') +
            ' - <span style="color: #f57c00;">Expires in ' + hours + ' hours</span>' +
            '</p>' +
            '</div>';
        count++;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Go to tenants page and open add modal
function goToAddTenant() {
    window.location.href = '../view/tenants.php?action=add';
}

// Update initCommonDashboard to include actions needed
function initCommonDashboard() {
    loadDashboardStats();
    loadBuildingsOverview();
    loadActionsNeeded(); // Add this line
    loadRecentActivity();
    setupModalHandlers();
}

// View building - redirect to buildings page
function viewBuilding(buildingId) {
    window.location.href = '../view/buildings.php?building_id=' + buildingId;
}

// Manage building - redirect to buildings page with edit action
function manageBuilding(buildingId) {
    window.location.href = '../view/buildings.php?building_id=' + buildingId + '&action=edit';
}

// Clean up intervals on page unload
window.addEventListener('beforeunload', function() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});

// Prevent form resubmission on page refresh
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});
