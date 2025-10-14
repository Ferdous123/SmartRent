
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
    loadTenancyInfo();
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
    
    // User dropdown - SAME AS DASHBOARD
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



function handlePersonalInfoUpdate(event) {
    event.preventDefault();
    
    var form = event.target;
    var formData = new FormData(form);
    formData.append('action', 'update_profile');
    
    fetch('../controller/profile_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        return response.text();
    })
    .then(text => {
        console.log('Raw response text:', text);
        clearMessages();
        
        try {
            var data = JSON.parse(text);
            if (data.success) {
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
            }
        } catch (e) {
            console.error('JSON Parse Error:', e);
            showMessage('Response parsing failed: ' + text.substring(0, 200), 'error');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        clearMessages();
        showMessage('Network error occurred', 'error');
    });
}

// Password change
function handlePasswordChange(event) {
    event.preventDefault();
    
    // Clear all error messages
    document.getElementById('current_password_error').textContent = '';
    document.getElementById('new_password_error').textContent = '';
    document.getElementById('confirm_password_error').textContent = '';
    clearMessages();
    
    var currentPassword = document.getElementById('current_password').value;
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    
    var hasError = false;
    
    // Client-side validations
    if (!currentPassword) {
        document.getElementById('current_password_error').textContent = 'Current password is required';
        hasError = true;
    }
    
    if (!newPassword) {
        document.getElementById('new_password_error').textContent = 'New password is required';
        hasError = true;
    } else if (newPassword.length < 6) {
        document.getElementById('new_password_error').textContent = 'Password must be at least 6 characters';
        hasError = true;
    }
    
    if (!confirmPassword) {
        document.getElementById('confirm_password_error').textContent = 'Please confirm your password';
        hasError = true;
    } else if (newPassword !== confirmPassword) {
        document.getElementById('confirm_password_error').textContent = 'Passwords do not match';
        hasError = true;
    }
    
    // Stop if there are validation errors
    if (hasError) {
        return;
    }
    
    // All validations passed, submit to server
    var form = event.target;
    var formData = new FormData(form);
    formData.append('action', 'change_password');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/profile_controller.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showMessage(data.message, 'success');
                        form.reset();
                        // Clear password strength indicator
                        var strengthIndicator = document.querySelector('.password-strength');
                        if (strengthIndicator) {
                            strengthIndicator.className = 'password-strength';
                        }
                    } else {
                        // Server-side validation failed (wrong current password)
                        document.getElementById('current_password_error').textContent = data.message;
                    }
                } catch (e) {
                    showMessage('Server error occurred. Please try again.', 'error');
                }
            } else {
                showMessage('Server error occurred. Please try again.', 'error');
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
    
    strengthIndicator.className = 'password-strength password-strength-' + strength;
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
    // Close the modal
    var modal = document.getElementById('twofa-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    current2FASetup = null;
    setupStep = 1;
    
    // Reload the page to show updated button and status
    location.reload();
}

function close2FAAndUpdate() {
    // Close the modal
    var modal = document.getElementById('twofa-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    current2FASetup = null;
    setupStep = 1;
    
    // Reload the page to show updated button and status
    location.reload();
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
        if (xhr.readyState === 4) {
            console.log("Server response status:", xhr.status);
            console.log("Raw server response:", xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    console.log("Parsed response:", data);
                    
                    if (data.success) {
                        console.log("SUCCESS: 2FA verified");
                        
                        // Show backup codes
                        displayBackupCodes(current2FASetup.backup_codes);
                        var backupCodesDiv = document.getElementById('backup-codes');
                        if (backupCodesDiv) {
                            backupCodesDiv.style.display = 'block';
                        }
                        
                        // Hide the verify button and show close button instead
                        var verifyButton = document.querySelector('#step3 .btn-primary');
                        if (verifyButton && verifyButton.textContent === 'Verify & Enable') {
                            verifyButton.textContent = 'Close';
                            verifyButton.onclick = function() { close2FAAndUpdate(); };
                        }
                        
                        // Hide back button
                        var backButton = document.querySelector('#step3 .btn-secondary');
                        if (backButton && backButton.textContent === 'Back') {
                            backButton.style.display = 'none';
                        }
                        
                        // Load status to update in background
                        load2FAStatus();
                    } else {
                        console.log("Error message:", data.message);
                        showMessage(data.message, 'error');
                    }
                } catch (e) {
                    showMessage('Verification failed. Please try again.', 'error');
                }
            } else {
                showMessage('Server error. Please try again.', 'error');
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
    var code = prompt('Enter your 2FA code or backup code to disable 2FA:');
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
                    setTimeout(function() {
                        location.reload(); // Reload to show Enable button
                    }, 1000);
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

// Load tenant's ALL flats info
function loadTenancyInfo() {
    if (!document.getElementById('tenancyInfoContainer')) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../controller/tenant_dashboard_controller.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success && response.flats) {
                    displayTenancyInfo(response.flats);
                } else {
                    document.getElementById('tenancyInfoContainer').innerHTML = 
                        '<p style="text-align: center; color: #999;">No active tenancy</p>';
                }
            } catch (e) {
                console.error('Error loading tenancy:', e);
                document.getElementById('tenancyInfoContainer').innerHTML = 
                    '<p style="text-align: center; color: #e74c3c;">Error loading tenancy information</p>';
            }
        }
    };
    
    xhr.send('action=get_all_my_flats');
}

function displayTenancyInfo(flats) {
    var container = document.getElementById('tenancyInfoContainer');
    if (!container) return;
    
    if (!flats || flats.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999;">No active tenancy</p>';
        return;
    }
    
    var html = '';
    
    for (var i = 0; i < flats.length; i++) {
        var flat = flats[i];
        html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">' +
            '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
                '<div>' +
                    '<h4 style="margin: 0; font-size: 18px;">' + escapeHtml(flat.building_name) + '</h4>' +
                    '<p style="margin: 0.25rem 0; opacity: 0.9;">Flat ' + escapeHtml(flat.flat_number) + ' • Floor ' + flat.floor_number + '</p>' +
                '</div>' +
                '<span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 12px;">Active</span>' +
            '</div>' +
            '<div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem;">' +
                '<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">' +
                    '<span style="opacity: 0.8;">Move-in Date:</span>' +
                    '<strong>' + formatDate(flat.confirmed_at) + '</strong>' +
                '</div>' +
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
    
    container.innerHTML = html;
}

// Add CSS animation for slide out
function addCSSAnimations() {
    var head = document.getElementsByTagName('head')[0];
    if (head) {
        head.innerHTML += '<style type="text/css">@keyframes slideOutRight { to { opacity: 0; transform: translateX(100px); } }</style>';
    }
}

// Utility functions (add at the end of profile.js)
function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    var options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatNumber(num) {
    if (!num) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}