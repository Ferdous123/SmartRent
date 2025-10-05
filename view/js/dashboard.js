// SmartRent Dashboard JavaScript
// Global variables
var currentUser = null;
var dashboardStats = null;
var sessionCheckInterval = null;

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    initCommonDashboard();
});

// Initialize common dashboard features
function initCommonDashboard() {
    loadDashboardStats();
    loadRecentActivity();
    setupModalHandlers();
}
// Load dashboard statistics
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

// Update dashboard statistics display
function updateDashboardStats(stats) {
    // Update stats based on what's available
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
    
    // Tenant specific stats
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

// Update element text safely
function updateElementText(elementId, text) {
    var element = document.getElementById(elementId);
    if (element && text !== undefined && text !== null) {
        element.textContent = text;
    }
}

// Format number with commas
function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Load recent activity
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

// Setup modal handlers
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

// Show modal
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

// Close modal
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