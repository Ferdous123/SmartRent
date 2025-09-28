// Profile Page JavaScript for SmartRent - Basic W3Schools Style
// Handles profile updates, 2FA setup, and file uploads

// Global variables
var current2FASetup = null;
var setupStep = 1;

// Initialize tabs on page load
document.addEventListener('DOMContentLoaded', function() {
    // Ensure personal tab is active by default
    var personalTab = document.getElementById('personal-tab');
    if (personalTab) {
        personalTab.classList.add('active');
        personalTab.style.display = 'block';
    }
    
    // Ensure personal tab button is active
    var tabButtons = document.querySelectorAll('.tab-btn');
    if (tabButtons.length > 0) {
        tabButtons[0].classList.add('active');
    }
    
    initProfilePage();
});

function initProfilePage() {
    setupEventListeners();
    load2FAStatus();
    initializePasswordStrength();
    addCSSAnimations();
}

function setupEventListeners() {
    // Personal info form
    var personalForm = document.getElementById('personalInfoForm');
    if (personalForm) {
        personalForm.addEventListener('submit', handlePersonalInfoUpdate);
    }
    
    // Change password form
    var passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordChange);
    }
    
    // Profile picture upload
    var pictureUpload = document.getElementById('profilePictureUpload');
    if (pictureUpload) {
        pictureUpload.addEventListener('change', uploadProfilePicture);
    }
    
    // User dropdown
    var userBtn = document.getElementById('userBtn');
    var userMenu = document.getElementById('userMenu');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(userMenu);
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        closeAllDropdowns();
    });
}

function showTab(tabName) {
    // Hide all tab panes
    var panes = document.getElementsByClassName('tab-pane');
    for (var i = 0; i < panes.length; i++) {
        panes[i].classList.remove('active');
        panes[i].style.display = 'none';
    }
    
    // Remove active class from all tab buttons
    var buttons = document.getElementsByClassName('tab-btn');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
    }
    
    // Show the selected tab
    var activePane = document.getElementById(tabName + '-tab');
    if (activePane) {
        activePane.classList.add('active');
        activePane.style.display = 'block';
    }
    
    // Add active class to clicked button
    var clickedButton = event.target;
    clickedButton.classList.add('active');
}

// Profile picture upload
function triggerFileUpload() {
    document.getElementById('profilePictureUpload').click();
}

function uploadProfilePicture(input) {
    var file = input.files[0];
    if (!file) return;
    
    // Clear any existing messages first
    clearMessages();
    
    // Validate file
    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Please select a valid image file (JPG, PNG, or GIF)', 'error');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        showMessage('File size must be less than 2MB', 'error');
        return;
    }
    
    // Create form data
    var formData = new FormData();
    formData.append('profile_picture', file);
    formData.append('action', 'upload_picture');
    
    showMessage('Uploading photo...', 'info');
    
    // Use XMLHttpRequest for better error handling
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    
    xhr.onload = function() {
        clearMessages();
        
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage('Photo uploaded successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.message, 'error');
                }
            } catch (e) {
                showMessage('Upload response error', 'error');
            }
        } else {
            showMessage('Upload failed - server error', 'error');
        }
    };
    
    xhr.onerror = function() {
        clearMessages();
        showMessage('Upload failed - network error', 'error');
    };
    
    xhr.send(formData);
}

function clearMessages() {
    var container = document.getElementById('messageContainer');
    if (container) {
        container.innerHTML = '';
        container.style.display = 'none';
    }
}



// Personal information update
function handlePersonalInfoUpdate(event) {
    event.preventDefault();
    
    var form = event.target;
    var formData = new FormData(form);
    formData.append('action', 'update_profile');
    
    var submitBtn = document.getElementById('personalInfoSubmitBtn');
    setButtonLoading(submitBtn, true);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            setButtonLoading(submitBtn, false);
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showMessage(data.message, 'success');
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    showMessage('Update failed. Please try again.', 'error');
                }
            } else {
                showMessage('Update failed. Please try again.', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Password change
function handlePasswordChange(event) {
    event.preventDefault();
    
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        showMessage('New passwords do not match', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showMessage('Password must be at least 6 characters', 'error');
        return;
    }
    
    var form = event.target;
    var formData = new FormData(form);
    formData.append('action', 'change_password');
    
    var submitBtn = document.getElementById('passwordSubmitBtn');
    setButtonLoading(submitBtn, true);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            setButtonLoading(submitBtn, false);
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showMessage(data.message, 'success');
                        form.reset();
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    showMessage('Password change failed. Please try again.', 'error');
                }
            } else {
                showMessage('Password change failed. Please try again.', 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// Password strength indicator
function initializePasswordStrength() {
    var passwordField = document.getElementById('new_password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }
}

function updatePasswordStrength(password) {
    var strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;
    
    var strength = calculatePasswordStrength(password);
    
    strengthIndicator.className = 'password-strength ' + strength;
}

function calculatePasswordStrength(password) {
    var score = 0;
    
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/)) score++;
    if (password.match(/[A-Z]/)) score++;
    if (password.match(/[0-9]/)) score++;
    if (password.match(/[^a-zA-Z0-9]/)) score++;
    
    if (score <= 2) return 'weak';
    if (score <= 4) return 'medium';
    return 'strong';
}

// 2FA Functions
function load2FAStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    update2FADisplay(data.status);
                }
            } catch (e) {
                console.log('Failed to load 2FA status');
            }
        }
    };
    
    xhr.send('action=get_2fa_status');
}

function update2FADisplay(status) {
    var statusBadge = document.getElementById('twofa-status');
    var setupDiv = document.getElementById('twofa-setup');
    var enabledDiv = document.getElementById('twofa-enabled');
    
    if (status.is_enabled) {
        if (statusBadge) {
            statusBadge.textContent = 'Enabled';
            statusBadge.className = 'status-badge enabled';
        }
        if (setupDiv) setupDiv.style.display = 'none';
        if (enabledDiv) enabledDiv.style.display = 'block';
    } else {
        if (statusBadge) {
            statusBadge.textContent = 'Disabled';
            statusBadge.className = 'status-badge disabled';
        }
        if (setupDiv) setupDiv.style.display = 'block';
        if (enabledDiv) enabledDiv.style.display = 'none';
    }
}

function setup2FA() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    current2FASetup = data;
                    show2FAModal();
                    setupStep = 1;
                    showStep(1);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to setup 2FA. Please try again.', 'error');
            }
        }
    };
    
    xhr.send('action=setup_2fa');
}

function show2FAModal() {
    var modal = document.getElementById('twofa-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeTwoFAModal() {
    var modal = document.getElementById('twofa-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    current2FASetup = null;
    setupStep = 1;
}

function showStep(step) {
    var i;
    
    // Hide all steps
    for (i = 1; i <= 3; i++) {
        var stepElement = document.getElementById('step' + i);
        if (stepElement) {
            stepElement.style.display = (i === step) ? 'block' : 'none';
        }
    }
    
    // Setup step 2 content
    if (step === 2 && current2FASetup) {
        var qrImage = document.getElementById('qrCodeImage');
        var manualCode = document.getElementById('manualCode');
        
        if (qrImage) {
            qrImage.src = current2FASetup.qr_url;
        }
        if (manualCode) {
            manualCode.textContent = current2FASetup.secret;
        }
    }
}

function nextStep(step) {
    setupStep = step;
    showStep(step);
}

function previousStep(step) {
    setupStep = step;
    showStep(step);
}

function verify2FASetup() {
    var verificationCode = document.getElementById('verification-code').value;
    
    if (!verificationCode || verificationCode.length !== 6) {
        showMessage('Please enter a valid 6-digit code', 'error');
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    // Show backup codes
                    displayBackupCodes(current2FASetup.backup_codes);
                    var backupCodesDiv = document.getElementById('backup-codes');
                    if (backupCodesDiv) {
                        backupCodesDiv.style.display = 'block';
                    }
                    
                    setTimeout(function() {
                        showMessage(data.message, 'success');
                        closeTwoFAModal();
                        load2FAStatus();
                    }, 3000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (e) {
                showMessage('Verification failed. Please try again.', 'error');
            }
        }
    };
    
    var params = 'action=verify_2fa_setup&verification_code=' + encodeURIComponent(verificationCode);
    xhr.send(params);
}

function displayBackupCodes(codes) {
    var codesList = document.getElementById('backup-codes-list');
    if (!codesList || !codes) return;
    
    var codesHTML = '';
    for (var i = 0; i < codes.length; i++) {
        codesHTML += '<div class="backup-code">' + codes[i] + '</div>';
    }
    codesList.innerHTML = codesHTML;
}

function disable2FA() {
    var code = prompt('Enter your current 2FA code to disable:');
    if (!code) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    showMessage(data.message, 'success');
                    load2FAStatus();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (e) {
                showMessage('Failed to disable 2FA. Please try again.', 'error');
            }
        }
    };
    
    var params = 'action=disable_2fa&verification_code=' + encodeURIComponent(code);
    xhr.send(params);
}

// Utility functions
function setButtonLoading(button, loading) {
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.originalText = button.textContent;
        button.textContent = 'Loading...';
    } else {
        button.disabled = false;
        button.textContent = button.originalText || 'Submit';
    }
}

function showMessage(message, type) {
    clearMessages();
    
    var container = document.getElementById('messageContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'messageContainer';
        container.className = 'message-container';
        document.body.appendChild(container);
    }
    
    container.style.display = 'block';
    
    var messageDiv = document.createElement('div');
    messageDiv.className = 'message message-' + type;
    messageDiv.textContent = message;
    
    container.appendChild(messageDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        clearMessages();
    }, 3000);
}

function toggleDropdown(dropdown) {
    var isVisible = dropdown.style.display === 'block';
    dropdown.style.display = isVisible ? 'none' : 'block';
}

function closeAllDropdowns() {
    var userMenu = document.getElementById('userMenu');
    var notificationsPanel = document.getElementById('notificationsPanel');
    
    if (userMenu) {
        userMenu.style.display = 'none';
    }
    if (notificationsPanel) {
        notificationsPanel.style.display = 'none';
    }
}

// Add CSS animation for slide out
function addCSSAnimations() {
    var head = document.getElementsByTagName('head')[0];
    if (head) {
        head.innerHTML += '<style type="text/css">@keyframes slideOutRight { to { opacity: 0; transform: translateX(100px); } }</style>';
    }
}