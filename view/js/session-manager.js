// Session Manager for SmartRent
// Handles session timeout and activity tracking

var sessionTimeout = 30 * 60 * 1000; // 30 minutes
var sessionWarningTime = 5 * 60 * 1000; // Show warning 5 minutes before timeout
var lastActivityTime = Date.now();
var sessionCheckInterval = null;
var warningShown = false;

// Initialize session manager
document.addEventListener('DOMContentLoaded', function() {
    initSessionManager();
});

function initSessionManager() {
    // Reset activity time on user interaction
    document.addEventListener('mousemove', resetSessionTimer);
    document.addEventListener('keypress', resetSessionTimer);
    document.addEventListener('click', resetSessionTimer);
    document.addEventListener('scroll', resetSessionTimer);
    
    // Check session every minute
    sessionCheckInterval = setInterval(checkSessionTimeout, 60000);
}

function resetSessionTimer() {
    lastActivityTime = Date.now();
    warningShown = false;
}

function checkSessionTimeout() {
    var now = Date.now();
    var timeSinceActivity = now - lastActivityTime;
    
    // Show warning if approaching timeout
    if (timeSinceActivity > sessionTimeout - sessionWarningTime && !warningShown) {
        warningShown = true;
        showSessionWarning();
    }
    
    // Logout if timeout reached
    if (timeSinceActivity > sessionTimeout) {
        handleSessionTimeout();
    }
}

function showSessionWarning() {
    if (confirm('Your session will expire in 5 minutes due to inactivity. Click OK to stay logged in.')) {
        resetSessionTimer();
    }
}

function handleSessionTimeout() {
    alert('Your session has expired due to inactivity. You will be logged out.');
    window.location.href = '../controller/working_login.php?action=logout&reason=timeout';
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});