// Session Management JavaScript for SmartRent
// Handles automatic session checking and timeout warnings

class SessionManager {
    constructor(options = {}) {
        this.checkInterval = options.checkInterval || 30000; // Check every 30 seconds
        this.warningThreshold = options.warningThreshold || 30; // Warn when 30 seconds left
        this.sessionCheckUrl = options.sessionCheckUrl || '../controller/session_controller.php';
        this.extendSessionUrl = options.extendSessionUrl || '../controller/session_controller.php';
        this.loginUrl = options.loginUrl || '../view/login.php';
        
        this.isWarningShown = false;
        this.checkTimer = null;
        this.warningTimer = null;
        
        this.init();
    }
    
    init() {
        // Start automatic session checking
        this.startSessionCheck();
        
        // Listen for user activity to extend session
        this.setupActivityListeners();
        
        // Handle page visibility changes
        this.setupVisibilityHandler();
    }
    
    startSessionCheck() {
        this.checkTimer = setInterval(() => {
            this.checkSession();
        }, this.checkInterval);
        
        // Check immediately
        this.checkSession();
    }
    
    stopSessionCheck() {
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
            this.checkTimer = null;
        }
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
            this.warningTimer = null;
        }
    }
    
    async checkSession() {
        try {
            const response = await fetch(this.sessionCheckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_session'
            });
            
            const data = await response.json();
            
            if (data.logged_in) {
                this.handleActiveSession(data.session_info);
            } else {
                this.handleExpiredSession();
            }
        } catch (error) {
            console.error('Session check failed:', error);
            // Continue checking - might be temporary network issue
        }
    }
    
    handleActiveSession(sessionInfo) {
        // Check if warning should be shown
        if (sessionInfo.timeout_warning && !this.isWarningShown) {
            this.showTimeoutWarning(sessionInfo.seconds_remaining);
        } else if (!sessionInfo.timeout_warning && this.isWarningShown) {
            this.hideTimeoutWarning();
        }
        
        // Update session info display if exists
        this.updateSessionDisplay(sessionInfo);
    }
    
    handleExpiredSession() {
        this.stopSessionCheck();
        this.showSessionExpiredMessage();
        
        // Redirect to login after a short delay
        setTimeout(() => {
            window.location.href = this.loginUrl + '?redirect=' + encodeURIComponent(window.location.pathname);
        }, 3000);
    }
    
    showTimeoutWarning(secondsRemaining) {
        this.isWarningShown = true;
        
        // Create warning modal if it doesn't exist
        let warningModal = document.getElementById('session-timeout-warning');
        if (!warningModal) {
            warningModal = this.createWarningModal();
            document.body.appendChild(warningModal);
        }
        
        // Update countdown
        const countdownElement = warningModal.querySelector('.countdown');
        countdownElement.textContent = secondsRemaining;
        
        // Show modal
        warningModal.style.display = 'flex';
        
        // Start countdown
        this.startCountdown(secondsRemaining, countdownElement);
    }
    
    hideTimeoutWarning() {
        this.isWarningShown = false;
        const warningModal = document.getElementById('session-timeout-warning');
        if (warningModal) {
            warningModal.style.display = 'none';
        }
        
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
            this.warningTimer = null;
        }
    }
    
    createWarningModal() {
        const modal = document.createElement('div');
        modal.id = 'session-timeout-warning';
        modal.className = 'session-warning-modal';
        modal.innerHTML = `
            <div class="session-warning-content">
                <div class="warning-icon">⚠️</div>
                <h3>Session Timeout Warning</h3>
                <p>Your session will expire in <span class="countdown">30</span> seconds.</p>
                <div class="warning-actions">
                    <button onclick="sessionManager.extendSession()" class="btn-extend">Stay Logged In</button>
                    <button onclick="sessionManager.logout()" class="btn-logout">Logout</button>
                </div>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-warning-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                font-family: Arial, sans-serif;
            }
            
            .session-warning-content {
                background: white;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
            }
            
            .warning-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            
            .session-warning-content h3 {
                color: #d73502;
                margin: 0 0 15px 0;
            }
            
            .countdown {
                font-weight: bold;
                color: #d73502;
                font-size: 18px;
            }
            
            .warning-actions {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
            }
            
            .btn-extend, .btn-logout {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                font-weight: bold;
                transition: background-color 0.3s;
            }
            
            .btn-extend {
                background: #28a745;
                color: white;
            }
            
            .btn-extend:hover {
                background: #218838;
            }
            
            .btn-logout {
                background: #dc3545;
                color: white;
            }
            
            .btn-logout:hover {
                background: #c82333;
            }
        `;
        document.head.appendChild(style);
        
        return modal;
    }
    
    startCountdown(seconds, element) {
        let remaining = seconds;
        
        this.warningTimer = setInterval(() => {
            remaining--;
            element.textContent = remaining;
            
            if (remaining <= 0) {
                clearInterval(this.warningTimer);
                this.handleExpiredSession();
            }
        }, 1000);
    }
    
    async extendSession() {
        try {
            const response = await fetch(this.extendSessionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=extend_session'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.hideTimeoutWarning();
                this.showMessage('Session extended successfully', 'success');
            } else {
                this.showMessage('Failed to extend session', 'error');
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
            this.showMessage('Failed to extend session', 'error');
        }
    }
    
    logout() {
        // Create a form and submit it to logout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controller/auth_controller.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'logout';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    setupActivityListeners() {
        const events = ['click', 'keypress', 'scroll', 'mousemove'];
        let lastActivity = Date.now();
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                const now = Date.now();
                // Only extend if more than 30 seconds since last activity
                if (now - lastActivity > 30000) {
                    lastActivity = now;
                    // Auto-extend session on activity (silent)
                    this.silentExtendSession();
                }
            }, { passive: true });
        });
    }
    
    async silentExtendSession() {
        try {
            await fetch(this.extendSessionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=extend_session'
            });
        } catch (error) {
            // Silent failure - don't show error to user
            console.log('Silent session extension failed:', error);
        }
    }
    
    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page hidden - reduce check frequency
                this.stopSessionCheck();
            } else {
                // Page visible again - resume checking
                this.startSessionCheck();
            }
        });
    }
    
    updateSessionDisplay(sessionInfo) {
        // Update any session info displays on the page
        const sessionDisplay = document.getElementById('session-info');
        if (sessionDisplay) {
            const timeRemaining = Math.floor(sessionInfo.seconds_remaining / 60);
            sessionDisplay.innerHTML = `
                <span class="session-user">${sessionInfo.full_name}</span>
                <span class="session-timeout">Session: ${timeRemaining}m remaining</span>
            `;
        }
    }
    
    showSessionExpiredMessage() {
        const modal = document.createElement('div');
        modal.className = 'session-expired-modal';
        modal.innerHTML = `
            <div class="session-expired-content">
                <div class="expired-icon">🔒</div>
                <h3>Session Expired</h3>
                <p>Your session has expired. You will be redirected to the login page.</p>
                <div class="loading-spinner"></div>
            </div>
        `;
        
        // Add styles for expired modal
        const style = document.createElement('style');
        style.textContent = `
            .session-expired-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10001;
                font-family: Arial, sans-serif;
            }
            
            .session-expired-content {
                background: white;
                padding: 40px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
            }
            
            .expired-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            
            .session-expired-content h3 {
                color: #333;
                margin: 0 0 15px 0;
            }
            
            .loading-spinner {
                margin: 20px auto;
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(modal);
    }
    
    showMessage(message, type = 'info') {
        // Create or update message display
        let messageContainer = document.getElementById('session-message');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'session-message';
            messageContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10002;
                max-width: 300px;
                opacity: 0;
                transition: opacity 0.3s;
            `;
            document.body.appendChild(messageContainer);
        }
        
        // Set message and color based on type
        messageContainer.textContent = message;
        switch (type) {
            case 'success':
                messageContainer.style.backgroundColor = '#28a745';
                break;
            case 'error':
                messageContainer.style.backgroundColor = '#dc3545';
                break;
            case 'warning':
                messageContainer.style.backgroundColor = '#ffc107';
                messageContainer.style.color = '#000';
                break;
            default:
                messageContainer.style.backgroundColor = '#007bff';
        }
        
        // Show message
        messageContainer.style.opacity = '1';
        
        // Hide after 3 seconds
        setTimeout(() => {
            messageContainer.style.opacity = '0';
            setTimeout(() => {
                if (messageContainer.parentNode) {
                    messageContainer.parentNode.removeChild(messageContainer);
                }
            }, 300);
        }, 3000);
    }
    
    destroy() {
        this.stopSessionCheck();
        
        // Remove event listeners
        const events = ['click', 'keypress', 'scroll', 'mousemove'];
        events.forEach(event => {
            document.removeEventListener(event, this.activityHandler);
        });
        
        document.removeEventListener('visibilitychange', this.visibilityHandler);
        
        // Remove warning modal if exists
        const warningModal = document.getElementById('session-timeout-warning');
        if (warningModal) {
            warningModal.remove();
        }
    }
}

// Initialize session manager when DOM is loaded
let sessionManager;

document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on pages that need session management
    const protectedPages = [
        'dashboard_controller.php',
        'dashboard_owner.php',
        'dashboard_manager.php',
        'dashboard_tenant.php'
    ];
    
    const currentPage = window.location.pathname.split('/').pop();
    
    if (protectedPages.some(page => currentPage.includes(page))) {
        sessionManager = new SessionManager({
            checkInterval: 30000, // Check every 30 seconds
            warningThreshold: 30,  // Warn when 30 seconds left
        });
        
        // Add session info to header if element exists
        addSessionInfo();
    }
});

// Add session info display to page header
function addSessionInfo() {
    const header = document.querySelector('.navbar') || document.querySelector('header');
    if (header && !document.getElementById('session-info')) {
        const sessionInfo = document.createElement('div');
        sessionInfo.id = 'session-info';
        sessionInfo.style.cssText = `
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 12px;
            color: #666;
            background: rgba(255, 255, 255, 0.9);
            padding: 5px 10px;
            border-radius: 15px;
            border: 1px solid #ddd;
        `;
        header.style.position = 'relative';
        header.appendChild(sessionInfo);
    }
}

// Global function to manually extend session (can be called from anywhere)
function extendSession() {
    if (sessionManager) {
        sessionManager.extendSession();
    }
}

// Global function to logout (can be called from anywhere)
function logoutUser() {
    if (sessionManager) {
        sessionManager.logout();
    }
}