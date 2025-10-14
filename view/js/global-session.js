
var sessionCheckInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    initSessionManagement();
});

function initSessionManagement() {
    setupSessionCheck();
    
    setupUserDropdown();
    setupNotificationDropdown();
}

function setupSessionCheck() {
    checkSessionStatus();
    
    sessionCheckInterval = setInterval(function() {
        checkSessionStatus();
    }, 60000);
}

function checkSessionStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/session_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (!response.logged_in) {
                    window.location.href = '../view/login.php?error=session_expired';
                }
            } catch (e) {
                console.error('Session check failed:', e);
            }
        }
    };
    
    xhr.send('action=check_session');
}

function setupUserDropdown() {
    var userBtn = document.getElementById('userBtn');
    var userMenu = document.getElementById('userMenu');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            var notifPanel = document.getElementById('notificationsPanel');
            if (notifPanel) notifPanel.style.display = 'none';
            
            userMenu.style.display = userMenu.style.display === 'none' ? 'block' : 'none';
        });
        
        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.style.display = 'none';
            }
        });
    }
}

function setupNotificationDropdown() {
    var notifBtn = document.getElementById('notificationBtn');
    var notifPanel = document.getElementById('notificationsPanel');
    
    if (notifBtn && notifPanel) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            var userMenu = document.getElementById('userMenu');
            if (userMenu) userMenu.style.display = 'none';
            
            notifPanel.style.display = notifPanel.style.display === 'none' ? 'block' : 'none';
        });
        
        document.addEventListener('click', function(e) {
            if (!notifBtn.contains(e.target) && !notifPanel.contains(e.target)) {
                notifPanel.style.display = 'none';
            }
        });
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

window.addEventListener('beforeunload', function() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});