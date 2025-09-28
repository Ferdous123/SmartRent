<?php
// Enhanced Password Reset with 2FA Support
session_start();

require_once '../model/database.php';
require_once '../model/user_model.php';
require_once '../model/twofa_model.php';

// Get reset token from URL
$reset_token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$step = isset($_GET['step']) ? sanitize_input($_GET['step']) : 'identify';

// If no token provided, start with user identification
if (empty($reset_token) && $step !== 'identify') {
    header("Location: reset_password.php?step=identify");
    exit();
}

$user_data = null;
$token_data = null;

// If token is provided, verify it
if (!empty($reset_token)) {
    $token_result = verify_password_reset_token($reset_token);
    if (!$token_result['success']) {
        $error_message = $token_result['message'];
        $step = 'error';
    } else {
        $token_data = $token_result['token_data'];
        $user_data = array(
            'user_id' => $token_data['user_id'],
            'username' => $token_data['username'],
            'email' => $token_data['email']
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Additional styles for multi-step password reset */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--border-color);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .step-number.active {
            background: var(--nav-color);
            color: white;
        }

        .step-number.completed {
            background: var(--success-color);
            color: white;
        }

        .step-connector {
            width: 50px;
            height: 2px;
            background: var(--border-color);
            margin: 0 10px;
        }

        .step-connector.completed {
            background: var(--success-color);
        }

        .verification-options {
            display: grid;
            gap: 15px;
            margin-bottom: 25px;
        }

        .verification-option {
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .verification-option:hover {
            border-color: var(--nav-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .verification-option.selected {
            border-color: var(--nav-color);
            background: rgba(102, 126, 234, 0.1);
        }

        .verification-option h4 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
        }

        .verification-option p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .backup-code-warning {
            background: rgba(243, 156, 18, 0.1);
            border: 1px solid var(--warning-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .backup-code-warning strong {
            color: var(--warning-color);
        }

        .password-strength-indicator {
            margin-top: 10px;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }

        .password-strength-fill.weak {
            width: 33%;
            background: var(--danger-color);
        }

        .password-strength-fill.medium {
            width: 66%;
            background: var(--warning-color);
        }

        .password-strength-fill.strong {
            width: 100%;
            background: var(--success-color);
        }

        .success-message {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="../index.php">
                    <h2>SmartRent</h2>
                </a>
            </div>
            <nav class="nav-menu">
                <a href="../index.php">Home</a>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>

    <!-- Reset Password Content -->
    <div class="auth-container">
        <div class="auth-form-wrapper">
            
            <?php if ($step === 'identify'): ?>
                <!-- Step 1: User Identification -->
                <div class="step-indicator">
                    <div class="step-item">
                        <div class="step-number active">1</div>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                    </div>
                </div>

                <div class="auth-header">
                    <h2>Identify Your Account</h2>
                    <p>Enter your username or email address to begin password reset</p>
                </div>

                <form id="identifyForm" class="auth-form">
                    <input type="hidden" name="action" value="identify_user">
                    
                    <div class="form-group">
                        <label for="username_email">Username or Email</label>
                        <input type="text" name="username_email" id="username_email" required autofocus
                               placeholder="Enter your username or email address">
                        <span class="error-message" id="username_email_error"></span>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="identifyBtn">
                            <span class="btn-text">Continue</span>
                            <span class="btn-loading" style="display: none;">Checking...</span>
                        </button>
                    </div>

                    <div class="form-footer">
                        <p>Remember your password? <a href="login.php">Sign in here</a></p>
                    </div>
                </form>

            <?php elseif ($step === 'verify'): ?>
                <!-- Step 2: 2FA/Backup Code Verification -->
                <div class="step-indicator">
                    <div class="step-item">
                        <div class="step-number completed">1</div>
                    </div>
                    <div class="step-connector completed"></div>
                    <div class="step-item">
                        <div class="step-number active">2</div>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                    </div>
                </div>

                <div class="auth-header">
                    <h2>Verify Your Identity</h2>
                    <p>Account: <?php echo htmlspecialchars($user_data['username']); ?></p>
                </div>

                <div class="verification-options">
                    <div class="verification-option" id="option_2fa" onclick="selectVerificationMethod('2fa')">
                        <h4>🔐 Authenticator App</h4>
                        <p>Enter the 6-digit code from your authenticator app</p>
                    </div>
                    
                    <div class="verification-option" id="option_backup" onclick="selectVerificationMethod('backup')">
                        <h4>🔑 Backup Code</h4>
                        <p>Use one of your backup codes if authenticator is unavailable</p>
                    </div>
                </div>

                <!-- 2FA Code Form -->
                <form id="verify2faForm" class="auth-form" style="display: none;">
                    <input type="hidden" name="action" value="verify_2fa_reset">
                    <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    
                    <div class="form-group">
                        <label for="twofa_code">6-Digit Code</label>
                        <input type="text" name="twofa_code" id="twofa_code" maxlength="6" 
                               placeholder="000000" class="text-center" autocomplete="off">
                        <span class="error-message" id="twofa_code_error"></span>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="verify2faBtn">
                            <span class="btn-text">Verify Code</span>
                            <span class="btn-loading" style="display: none;">Verifying...</span>
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn-secondary full-width" onclick="showVerificationOptions()">
                            Choose Different Method
                        </button>
                    </div>
                </form>

                <!-- Backup Code Form -->
                <form id="verifyBackupForm" class="auth-form" style="display: none;">
                    <input type="hidden" name="action" value="verify_backup_reset">
                    <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    
                    <div class="backup-code-warning">
                        <strong>⚠️ Important:</strong> Using a backup code will disable your 2FA. 
                        You'll need to set up 2FA again after resetting your password.
                    </div>

                    <div class="form-group">
                        <label for="backup_code">8-Character Backup Code</label>
                        <input type="text" name="backup_code" id="backup_code" maxlength="8" 
                               placeholder="ABC12D3E" style="text-transform: uppercase;" autocomplete="off">
                        <span class="error-message" id="backup_code_error"></span>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="verifyBackupBtn">
                            <span class="btn-text">Verify Backup Code</span>
                            <span class="btn-loading" style="display: none;">Verifying...</span>
                        </button>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn-secondary full-width" onclick="showVerificationOptions()">
                            Choose Different Method
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 'reset'): ?>
                <!-- Step 3: Set New Password -->
                <div class="step-indicator">
                    <div class="step-item">
                        <div class="step-number completed">1</div>
                    </div>
                    <div class="step-connector completed"></div>
                    <div class="step-item">
                        <div class="step-number completed">2</div>
                    </div>
                    <div class="step-connector completed"></div>
                    <div class="step-item">
                        <div class="step-number active">3</div>
                    </div>
                </div>

                <div class="auth-header">
                    <h2>Set New Password</h2>
                    <p>Create a strong new password for your account</p>
                </div>

                <form id="resetPasswordForm" class="auth-form">
                    <input type="hidden" name="action" value="complete_password_reset">
                    <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" required
                               placeholder="Enter your new password">
                        <div class="password-strength-indicator">
                            <div class="password-strength-fill" id="passwordStrengthFill"></div>
                        </div>
                        <span class="error-message" id="new_password_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required
                               placeholder="Confirm your new password">
                        <span class="error-message" id="confirm_password_error"></span>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="resetPasswordBtn">
                            <span class="btn-text">Reset Password</span>
                            <span class="btn-loading" style="display: none;">Resetting...</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 'success'): ?>
                <!-- Success Message -->
                <div class="success-message">
                    <div class="success-icon">✅</div>
                    <h2>Password Reset Successfully!</h2>
                    <p>Your password has been updated. You can now log in with your new password.</p>
                    
                    <?php if (isset($_GET['2fa_disabled']) && $_GET['2fa_disabled'] === '1'): ?>
                        <div class="backup-code-warning">
                            <strong>⚠️ Security Notice:</strong> Your Two-Factor Authentication has been disabled 
                            because you used a backup code. Please set up 2FA again in your profile for enhanced security.
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px;">
                        <a href="login.php" class="btn-primary">Continue to Login</a>
                    </div>
                </div>

            <?php elseif ($step === 'error'): ?>
                <!-- Error Message -->
                <div class="auth-header">
                    <h2>Reset Link Invalid</h2>
                    <p><?php echo htmlspecialchars($error_message ?? 'The password reset link is invalid or has expired.'); ?></p>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="forgot_password.php" class="btn-primary">Request New Reset Link</a>
                    <a href="login.php" class="btn-secondary" style="margin-left: 15px;">Back to Login</a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="message_container" class="message-container"></div>

    <script src="js/auth.js"></script>
    <script>
        // Password reset specific JavaScript
        var currentStep = '<?php echo $step; ?>';
        var selectedVerificationMethod = null;

        document.addEventListener('DOMContentLoaded', function() {
            initPasswordReset();
        });

        function initPasswordReset() {
            if (currentStep === 'identify') {
                initIdentifyForm();
            } else if (currentStep === 'verify') {
                initVerifyForms();
            } else if (currentStep === 'reset') {
                initResetPasswordForm();
            }
        }

        function initIdentifyForm() {
            var form = document.getElementById('identifyForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleUserIdentification();
                });
            }
        }

        function initVerifyForms() {
            var twoFaForm = document.getElementById('verify2faForm');
            var backupForm = document.getElementById('verifyBackupForm');

            if (twoFaForm) {
                twoFaForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleTwoFAVerification();
                });
            }

            if (backupForm) {
                backupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleBackupCodeVerification();
                });
            }

            // Auto-format backup code input
            var backupInput = document.getElementById('backup_code');
            if (backupInput) {
                backupInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
            }

            // Auto-format 2FA code input
            var twofaInput = document.getElementById('twofa_code');
            if (twofaInput) {
                twofaInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        }

        function initResetPasswordForm() {
            var form = document.getElementById('resetPasswordForm');
            var passwordField = document.getElementById('new_password');
            var confirmField = document.getElementById('confirm_password');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handlePasswordReset();
                });
            }

            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            if (confirmField) {
                confirmField.addEventListener('input', function() {
                    checkPasswordMatch();
                });
            }
        }

        function selectVerificationMethod(method) {
            selectedVerificationMethod = method;
            
            // Update option styling
            var options = document.querySelectorAll('.verification-option');
            options.forEach(function(option) {
                option.classList.remove('selected');
            });
            
            document.getElementById('option_' + method).classList.add('selected');
            
            // Show appropriate form
            document.getElementById('verify2faForm').style.display = method === '2fa' ? 'block' : 'none';
            document.getElementById('verifyBackupForm').style.display = method === 'backup' ? 'block' : 'none';
            
            // Focus on input
            setTimeout(function() {
                var input = method === '2fa' ? 
                    document.getElementById('twofa_code') : 
                    document.getElementById('backup_code');
                if (input) input.focus();
            }, 100);
        }

        function showVerificationOptions() {
            selectedVerificationMethod = null;
            document.getElementById('verify2faForm').style.display = 'none';
            document.getElementById('verifyBackupForm').style.display = 'none';
            
            var options = document.querySelectorAll('.verification-option');
            options.forEach(function(option) {
                option.classList.remove('selected');
            });
        }

        function handleUserIdentification() {
            var form = document.getElementById('identifyForm');
            var submitBtn = document.getElementById('identifyBtn');
            var usernameEmail = document.getElementById('username_email').value.trim();

            if (!usernameEmail) {
                showFieldError(document.getElementById('username_email'), 'Username or email is required');
                return;
            }

            setButtonLoading(submitBtn, true);

            // AJAX request to identify user and check 2FA status
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/password_reset_controller.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    setButtonLoading(submitBtn, false);
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                if (response.requires_2fa) {
                                    window.location.href = 'reset_password.php?step=verify&token=' + response.reset_token;
                                } else {
                                    window.location.href = 'reset_password.php?step=reset&token=' + response.reset_token;
                                }
                            } else {
                                showMessage(response.message, 'error');
                            }
                        } catch (e) {
                            showMessage('Invalid server response', 'error');
                        }
                    } else {
                        showMessage('Server error occurred', 'error');
                    }
                }
            };

            xhr.send('action=identify_user&username_email=' + encodeURIComponent(usernameEmail));
        }

        function handleTwoFAVerification() {
            var form = document.getElementById('verify2faForm');
            var submitBtn = document.getElementById('verify2faBtn');
            var code = document.getElementById('twofa_code').value.trim();

            if (!code || code.length !== 6) {
                showFieldError(document.getElementById('twofa_code'), 'Please enter a valid 6-digit code');
                return;
            }

            setButtonLoading(submitBtn, true);
            submitVerificationForm(form);
        }

        function handleBackupCodeVerification() {
            var form = document.getElementById('verifyBackupForm');
            var submitBtn = document.getElementById('verifyBackupBtn');
            var code = document.getElementById('backup_code').value.trim();

            if (!code || code.length !== 8) {
                showFieldError(document.getElementById('backup_code'), 'Please enter a valid 8-character backup code');
                return;
            }

            setButtonLoading(submitBtn, true);
            submitVerificationForm(form);
        }

        function submitVerificationForm(form) {
            var formData = new FormData(form);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/password_reset_controller.php', true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var submitBtn = form.querySelector('[type="submit"]');
                    setButtonLoading(submitBtn, false);
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.location.href = 'reset_password.php?step=reset&token=' + response.reset_token;
                            } else {
                                showMessage(response.message, 'error');
                            }
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

        function handlePasswordReset() {
            var form = document.getElementById('resetPasswordForm');
            var submitBtn = document.getElementById('resetPasswordBtn');
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;

            // Clear previous errors
            clearFormErrors(form);

            // Validate passwords
            if (newPassword.length < 6) {
                showFieldError(document.getElementById('new_password'), 'Password must be at least 6 characters');
                return;
            }

            if (newPassword !== confirmPassword) {
                showFieldError(document.getElementById('confirm_password'), 'Passwords do not match');
                return;
            }

            setButtonLoading(submitBtn, true);

            var formData = new FormData(form);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/password_reset_controller.php', true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    setButtonLoading(submitBtn, false);
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                var successUrl = 'reset_password.php?step=success';
                                if (response.twofa_disabled) {
                                    successUrl += '&2fa_disabled=1';
                                }
                                window.location.href = successUrl;
                            } else {
                                showMessage(response.message, 'error');
                            }
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

        function checkPasswordStrength(password) {
            var strengthFill = document.getElementById('passwordStrengthFill');
            var strength = getPasswordStrength(password);
            
            strengthFill.className = 'password-strength-fill ' + strength;
        }

        function getPasswordStrength(password) {
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

        function checkPasswordMatch() {
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            
            if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
                showFieldError(document.getElementById('confirm_password'), 'Passwords do not match');
            } else {
                clearFieldError(document.getElementById('confirm_password'));
            }
        }
    </script>
</body>
</html>