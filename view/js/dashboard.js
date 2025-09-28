// SmartRent Dashboard JavaScript
// Procedural JavaScript for dashboard functionality

// Global variables
var currentUser = null;
var dashboardStats = null;
var notificationInterval = null;
var sessionCheckInterval = null;

// Initialize dashboard based on user type
document.addEventListener('DOMContentLoaded', function() {
    initCommonDashboard();
    
    // Initialize specific dashboard based on body class
    if (document.body.classList.contains('owner-theme')) {
        initOwnerDashboard();
    } else if (document.body.classList.contains('manager-theme')) {
        initManagerDashboard();
    } else if (document.body.classList.contains('tenant-theme')) {
        initTenantDashboard();
    }
});

// Initialize common dashboard features
function initCommonDashboard() {
    setupNavigationDropdowns();
    setupSessionCheck();
    loadDashboardStats();
    loadNotifications();
    startNotificationPolling();
    setupModalHandlers();
    setupFormValidation();
}

// Setup navigation dropdowns
function setupNavigationDropdowns() {
    var userBtn = document.getElementById('userBtn');
    var userMenu = document.getElementById('userMenu');
    var notificationBtn = document.getElementById('notificationBtn');
    var notificationsPanel = document.getElementById('notificationsPanel');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(userMenu, 'user-dropdown');
        });
    }
    
    if (notificationBtn && notificationsPanel) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(notificationsPanel);
            markNotificationsAsRead();
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        closeAllDropdowns();
    });
}

// Toggle dropdown visibility
function toggleDropdown(dropdown, parentClass) {
    var isVisible = dropdown.style.display === 'block';
    
    // Close all dropdowns first
    closeAllDropdowns();
    
    if (!isVisible) {
        dropdown.style.display = 'block';
        dropdown.style.animation = 'fadeIn 0.3s ease';
        
        if (parentClass) {
            dropdown.closest('.' + parentClass.split(' ')[0]).classList.add('open');
        }
    }
}

// Close all dropdowns
function closeAllDropdowns() {
    var dropdowns = document.querySelectorAll('.user-menu, .notifications-panel');
    for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].style.display = 'none';
    }
    
    var openElements = document.querySelectorAll('.user-dropdown.open');
    for (var i = 0; i < openElements.length; i++) {
        openElements[i].classList.remove('open');
    }
}

// Setup session check
function setupSessionCheck() {
    sessionCheckInterval = setInterval(function() {
        checkSession();
    }, 60000); // Check every minute
    
    // Check session immediately
    checkSession();
}

// Check session status
function checkSession() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/session_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (!response.logged_in) {
                        window.location.href = '../view/login.php';
                    } else if (response.session_info && response.session_info.timeout_warning) {
                        showSessionTimeoutWarning(response.session_info.minutes_remaining);
                    }
                } catch (e) {
                    console.error('Session check failed:', e);
                }
            }
        }
    };
    
    xhr.send('action=check_session');
}

// Show session timeout warning
function showSessionTimeoutWarning(minutesRemaining) {
    var warningShown = document.getElementById('sessionWarning');
    
    if (!warningShown && minutesRemaining <= 5) {
        showMessage('Your session will expire in ' + minutesRemaining + ' minutes. Click here to extend.', 'warning', function() {
            extendSession();
        });
        
        var warningDiv = document.createElement('div');
        warningDiv.id = 'sessionWarning';
        warningDiv.style.display = 'none';
        document.body.appendChild(warningDiv);
    }
}

// Extend user session
function extendSession() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/session_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage('Session extended successfully', 'success');
                    
                    // Remove warning
                    var warning = document.getElementById('sessionWarning');
                    if (warning) {
                        warning.remove();
                    }
                }
            } catch (e) {
                console.error('Session extension failed:', e);
            }
        }
    };
    
    xhr.send('action=extend_session');
}

// Load dashboard statistics
function loadDashboardStats() {
    showLoading(true);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            showLoading(false);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        dashboardStats = response.stats;
                        updateDashboardStats(response.stats);
                    } else {
                        showMessage('Failed to load dashboard statistics', 'error');
                    }
                } catch (e) {
                    showMessage('Invalid response from server', 'error');
                }
            } else {
                showMessage('Server error while loading statistics', 'error');
            }
        }
    };
    
    xhr.send('action=get_dashboard_stats');
}

// Update dashboard statistics display
function updateDashboardStats(stats) {
    // Update elements based on user type
    updateElementText('totalBuildings', stats.total_buildings || stats.managed_buildings);
    updateElementText('totalFlats', stats.total_flats);
    updateElementText('totalTenants', stats.total_tenants);
    updateElementText('occupiedFlats', stats.occupied_flats);
    updateElementText('availableFlats', stats.available_flats);
    updateElementText('occupancyRate', stats.occupancy_rate + '%');
    updateElementText('monthlyRevenue', '৳' + stats.monthly_revenue);
    updateElementText('outstandingCount', stats.outstanding_payments_count);
    updateElementText('outstandingAmount', '৳' + stats.outstanding_payments_amount);
    updateElementText('managedBuildings', stats.managed_buildings);
    updateElementText('pendingRequests', stats.pending_service_requests || stats.active_service_requests);
    
    // Tenant specific stats
    if (stats.has_assignment) {
        updateElementText('flatDetails', stats.flat_info);
        updateElementText('floorInfo', 'Floor ' + stats.floor_number);
        updateElementText('flatInfo', 'You are living in ' + stats.flat_info + ' on Floor ' + stats.floor_number);
        updateElementText('outstandingDues', '৳' + stats.outstanding_dues);
        updateElementText('advanceBalance', '৳' + stats.advance_balance);
        updateElementText('lastPaymentAmount', '৳' + stats.last_payment_amount);
        updateElementText('lastPaymentDate', stats.last_payment_date);
        updateElementText('activeRequests', stats.active_service_requests);
        updateElementText('buildingName', stats.flat_info.split(' - ')[0]);
        updateElementText('flatNumber', stats.flat_info.split(' - ')[1]);
        updateElementText('floorNumber', stats.floor_number);
    } else if (stats.has_assignment === false) {
        updateElementText('flatInfo', 'No active flat assignment found');
    }
}

// Update element text safely
function updateElementText(elementId, text) {
    var element = document.getElementById(elementId);
    if (element && text !== undefined) {
        element.textContent = text;
    }
}

// Load notifications
function loadNotifications() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateNotifications(response.notifications);
                }
            } catch (e) {
                console.error('Failed to load notifications:', e);
            }
        }
    };
    
    xhr.send('action=get_notifications');
}

// Update notifications display
function updateNotifications(notifications) {
    var notificationsList = document.getElementById('notificationsList');
    var notificationBadge = document.getElementById('notificationBadge');
    
    if (!notificationsList) return;
    
    var unreadCount = 0;
    
    // Clear existing notifications
    notificationsList.innerHTML = '';
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = '<div class="no-notifications">No new notifications</div>';
    } else {
        for (var i = 0; i < notifications.length; i++) {
            var notification = notifications[i];
            if (!notification.is_read) {
                unreadCount++;
            }
            
            var notificationElement = createNotificationElement(notification);
            notificationsList.appendChild(notificationElement);
        }
    }
    
    // Update badge
    if (unreadCount > 0) {
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = 'flex';
    } else {
        notificationBadge.style.display = 'none';
    }
}

// Create notification element
function createNotificationElement(notification) {
    var div = document.createElement('div');
    div.className = 'notification-item' + (notification.is_read ? '' : ' unread');
    div.setAttribute('data-notification-id', notification.notification_id);
    
    var title = notification.title || getNotificationTitle(notification.type);
    var time = formatTime(notification.created_at);
    
    div.innerHTML = `
        <div class="notification-content">
            <div class="notification-header">
                <strong>${escapeHtml(title)}</strong>
                <span class="notification-time">${time}</span>
            </div>
            <p>${escapeHtml(notification.message)}</p>
        </div>
    `;
    
    div.addEventListener('click', function() {
        markNotificationAsRead(notification.notification_id);
    });
    
    return div;
}

// Get notification title based on type
function getNotificationTitle(type) {
    var titles = {
        'info': 'Information',
        'warning': 'Warning',
        'alert': 'Alert',
        'payment': 'Payment Update',
        'assignment': 'Flat Assignment',
        'move_out': 'Move Out Notice'
    };
    
    return titles[type] || 'Notification';
}

// Start notification polling
function startNotificationPolling() {
    notificationInterval = setInterval(function() {
        loadNotifications();
    }, 60000); // Poll every minute
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    var element = document.querySelector('[data-notification-id="' + notificationId + '"]');
                    if (element) {
                        element.classList.remove('unread');
                    }
                    updateNotificationBadge();
                }
            } catch (e) {
                console.error('Failed to mark notification as read:', e);
            }
        }
    };
    
    xhr.send('action=mark_notification_read&notification_id=' + encodeURIComponent(notificationId));
}

// Mark all notifications as read
function markAllNotificationsRead() {
    var unreadNotifications = document.querySelectorAll('.notification-item.unread');
    
    for (var i = 0; i < unreadNotifications.length; i++) {
        var notificationId = unreadNotifications[i].getAttribute('data-notification-id');
        markNotificationAsRead(notificationId);
    }
}

// Update notification badge
function updateNotificationBadge() {
    var unreadCount = document.querySelectorAll('.notification-item.unread').length;
    var badge = document.getElementById('notificationBadge');
    
    if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Setup modal handlers
function setupModalHandlers() {
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
    
    // Close modals with escape key
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
        
        // Focus first input
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
        
        // Clear form if exists
        var form = modal.querySelector('form');
        if (form) {
            form.reset();
            clearFormErrors(form);
        }
    }
}

// Setup form validation
function setupFormValidation() {
    var forms = document.querySelectorAll('form');
    
    for (var i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(this);
        });
    }
}

// Handle form submission
function handleFormSubmission(form) {
    if (!validateForm(form)) {
        return;
    }
    
    var formData = new FormData(form);
    var action = form.id;
    
    // Set loading state
    var submitButton = form.querySelector('[type="submit"]');
    setButtonLoading(submitButton, true);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', getFormActionUrl(action), true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            setButtonLoading(submitButton, false);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    handleFormResponse(response, form);
                } catch (e) {
                    showMessage('Invalid server response', 'error');
                }
            } else {
                showMessage('Server error occurred', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Get form action URL based on form ID
function getFormActionUrl(formId) {
    var urls = {
        'paymentForm': '../controller/payment_controller.php',
        'serviceRequestForm': '../controller/service_controller.php',
        'tenantAssignmentForm': '../controller/tenant_controller.php'
    };
    
    return urls[formId] || '../controller/dashboard_controller.php';
}

// Handle form response
function handleFormResponse(response, form) {
    if (response.success) {
        showMessage(response.message, 'success');
        closeModal(form.closest('.modal'));
        
        // Refresh data if needed
        if (response.refresh_stats) {
            loadDashboardStats();
        }
        
        if (response.refresh_notifications) {
            loadNotifications();
        }
        
    } else {
        showMessage(response.message, 'error');
        
        // Show field errors
        if (response.field_errors) {
            showFormErrors(form, response.field_errors);
        }
    }
}

// Validate form
function validateForm(form) {
    var isValid = true;
    var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    clearFormErrors(form);
    
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        var value = input.value.trim();
        
        if (value === '') {
            showFieldError(input, 'This field is required');
            isValid = false;
        } else {
            // Specific validations
            if (input.type === 'email' && !isValidEmail(value)) {
                showFieldError(input, 'Please enter a valid email address');
                isValid = false;
            }
            
            if (input.type === 'number' && (isNaN(value) || parseFloat(value) < 0)) {
                showFieldError(input, 'Please enter a valid number');
                isValid = false;
            }
        }
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    var errorElement = document.createElement('span');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.color = 'var(--danger-color)';
    errorElement.style.fontSize = '13px';
    errorElement.style.display = 'block';
    errorElement.style.marginTop = '5px';
    
    field.style.borderColor = 'var(--danger-color)';
    field.parentNode.appendChild(errorElement);
}

// Clear form errors
function clearFormErrors(form) {
    var errorElements = form.querySelectorAll('.field-error');
    for (var i = 0; i < errorElements.length; i++) {
        errorElements[i].remove();
    }
    
    var inputs = form.querySelectorAll('input, select, textarea');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].style.borderColor = '';
    }
}

// Show form errors
function showFormErrors(form, errors) {
    for (var fieldName in errors) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        if (field) {
            showFieldError(field, errors[fieldName]);
        }
    }
}

// Owner Dashboard Initialization
function initOwnerDashboard() {
    console.log('Initializing Owner Dashboard');
    
    // Load owner specific data
    loadBuildingsOverview();
    loadOutstandingPayments();
    loadRecentActivity();
}

// Manager Dashboard Initialization
function initManagerDashboard() {
    console.log('Initializing Manager Dashboard');
    
    // Load manager specific data
    loadServiceRequests();
    loadTenantActivities();
    loadManagedBuildings();
    loadPaymentSummary();
}

// Tenant Dashboard Initialization
function initTenantDashboard() {
    console.log('Initializing Tenant Dashboard');
    
    // Load tenant specific data
    loadMyServiceRequests();
    loadPaymentHistory();
    loadMessages();
    loadFlatInformation();
}

// Load buildings overview (Owner)
function loadBuildingsOverview() {
    // This would make an AJAX call to get buildings data
    console.log('Loading buildings overview...');
}

// Load outstanding payments (Owner)
function loadOutstandingPayments() {
    // This would make an AJAX call to get outstanding payments
    console.log('Loading outstanding payments...');
}

// Load recent activity
function loadRecentActivity() {
    // This would make an AJAX call to get recent activities
    console.log('Loading recent activity...');
}

// Load service requests (Manager)
function loadServiceRequests() {
    console.log('Loading service requests...');
}

// Load tenant activities (Manager)
function loadTenantActivities() {
    console.log('Loading tenant activities...');
}

// Load managed buildings (Manager)
function loadManagedBuildings() {
    console.log('Loading managed buildings...');
}

// Load payment summary (Manager)
function loadPaymentSummary() {
    console.log('Loading payment summary...');
}

// Load my service requests (Tenant)
function loadMyServiceRequests() {
    console.log('Loading my service requests...');
}

// Load payment history (Tenant)
function loadPaymentHistory() {
    console.log('Loading payment history...');
}

// Load messages (Tenant)
function loadMessages() {
    console.log('Loading messages...');
}

// Load flat information (Tenant)
function loadFlatInformation() {
    console.log('Loading flat information...');
}

// Quick action functions
function showAddBuildingModal() {
    showModal('addBuildingModal');
}

function showAddTenantModal() {
    showModal('tenantAssignmentModal');
}

function showAssignTenantModal() {
    showModal('tenantAssignmentModal');
}

function showPaymentModal() {
    showModal('paymentModal');
}

function makePayment() {
    showModal('paymentModal');
}

function createServiceRequest() {
    showModal('serviceRequestModal');
}

function downloadReceipt() {
    showMessage('Downloading receipt...', 'info');
    // This would trigger a file download
}

function generateSlips() {
    showMessage('Generating rent slips...', 'info');
    // This would generate and download slips
}

function sendNotice() {
    showMessage('Opening notice composer...', 'info');
    // This would open a notice composition modal
}

function backupDatabase() {
    if (confirm('Are you sure you want to create a database backup?')) {
        showMessage('Creating database backup...', 'info');
        // This would trigger a backup process
    }
}

// Modal close functions
function closeTenantAssignmentModal() {
    closeModal('tenantAssignmentModal');
}

function closePaymentModal() {
    closeModal('paymentModal');
}

function closeServiceRequestModal() {
    closeModal('serviceRequestModal');
}

function closeTwoFactorModal() {
    closeModal('twoFactorModal');
}

// 2FA Setup
function show2FASetupPrompt() {
    if (confirm('For better security, would you like to set up Two-Factor Authentication now?')) {
        showModal('twoFactorModal');
    }
}

function verify2FA() {
    var code = document.querySelector('.verification-input').value;
    if (code.length === 6) {
        showMessage('Verifying 2FA code...', 'info');
        // This would verify the 2FA code
        setTimeout(function() {
            showMessage('2FA enabled successfully!', 'success');
            closeModal('twoFactorModal');
        }, 2000);
    } else {
        showMessage('Please enter a valid 6-digit code', 'error');
    }
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

function showLoading(show) {
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

function showMessage(message, type, onclick) {
    var container = document.getElementById('messageContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'messageContainer';
        container.className = 'message-container';
        document.body.appendChild(container);
    }
    
    var messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + type;
    messageDiv.textContent = message;
    
    if (onclick) {
        messageDiv.style.cursor = 'pointer';
        messageDiv.addEventListener('click', onclick);
    }
    
    container.appendChild(messageDiv);
    
    setTimeout(function() {
        if (messageDiv.parentNode) {
            messageDiv.style.animation = 'slideOutRight 0.5s ease forwards';
            setTimeout(function() {
                if (messageDiv.parentNode) {
                    container.removeChild(messageDiv);
                }
            }, 500);
        }
    }, 5000);
}

function setButtonLoading(button, loading) {
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.originalText = button.textContent;
        button.textContent = 'Loading...';
        button.style.opacity = '0.7';
    } else {
        button.disabled = false;
        button.textContent = button.originalText || 'Submit';
        button.style.opacity = '1';
    }
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
    
    if (diffInMinutes < 60) {
        return diffInMinutes + ' minutes ago';
    } else if (diffInMinutes < 1440) {
        return Math.floor(diffInMinutes / 60) + ' hours ago';
    } else {
        return Math.floor(diffInMinutes / 1440) + ' days ago';
    }
}

// Clean up intervals on page unload
window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});

// Prevent form resubmission
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

// Handle responsive navigation
function setupResponsiveNavigation() {
    var mainNav = document.querySelector('.main-nav');
    if (window.innerWidth <= 768 && mainNav) {
        mainNav.style.display = 'none';
    }
}

// Initialize responsive features
window.addEventListener('resize', function() {
    setupResponsiveNavigation();
});

// Initialize responsive navigation on load
setupResponsiveNavigation();