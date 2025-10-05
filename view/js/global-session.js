// Global Session Management - Include on ALL authenticated pages
// W3Schools style - Simple procedural JavaScript

var sessionCheckInterval = null;

// Initialize session checking when page loads
document.addEventListener('DOMContentLoaded', function() {
    initSessionManagement();
});

// Initialize session management
function initSessionManagement() {
    // Start session checking
    setupSessionCheck();
    
    // Setup user dropdown if exists
    setupUserDropdown();
}

// Setup periodic session check
function setupSessionCheck() {
    // Check immediately
    checkSessionStatus();
    
    // Then check every 60 seconds
    sessionCheckInterval = setInterval(function() {
        checkSessionStatus();
    }, 60000);
}

// Check if session is still valid
function checkSessionStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/session_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (!response.logged_in) {
                    // Session expired - redirect to login
                    window.location.href = '../view/login.php?error=session_expired';
                }
            } catch (e) {
                console.error('Session check failed:', e);
            }
        }
    };
    
    xhr.send('action=check_session');
}

// Setup user dropdown menu (common across all pages)
function setupUserDropdown() {
    var userBtn = document.getElementById('userBtn');
    var userMenu = document.getElementById('userMenu');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.style.display = userMenu.style.display === 'none' ? 'block' : 'none';
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.style.display = 'none';
            }
        });
    }
}

// Utility function to show messages (reusable across pages)
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

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});