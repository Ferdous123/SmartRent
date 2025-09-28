<?php

session_start();
require_once '../controller/session_controller.php';

// Redirect if already logged in
redirect_if_authenticated();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Inline CSS for immediate loading */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .form-container { max-width: 400px; margin: 100px auto; padding: 20px; }
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
                <a href="register.php">Register</a>
            </nav>
        </div>
    </header>

    <!-- Forgot Password Form -->
    <div class="auth-container">
        <div class="auth-form-wrapper">
            <div class="auth-header">
                <h2>Reset Your Password</h2>
                <p>Enter your email address and we'll send you instructions to reset your password</p>
            </div>

            <form id="forgotPasswordForm" class="auth-form" method="POST" action="../controller/auth_controller.php">
                <input type="hidden" name="action" value="forgot_password">
                
                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required autofocus 
                           placeholder="Enter your registered email address">
                    <span class="error-message" id="email_error"></span>
                </div>

                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" class="btn-primary" id="forgotPasswordBtn">
                        <span class="btn-text">Send Reset Instructions</span>
                        <span class="btn-loading" style="display: none;">Sending...</span>
                    </button>
                </div>

                <!-- Links -->
                <div class="form-footer">
                    <p>Remember your password? <a href="login.php">Sign in here</a></p>
                    <p>Don't have an account? <a href="register.php">Create one</a></p>
                </div>
            </form>

            <!-- Instructions -->
            <div class="reset-instructions">
                <h4>What happens next?</h4>
                <div class="instruction-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <p>We'll send reset instructions to your email address</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <p>Click the reset link in your email</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <p>Create a new password for your account</p>
                    </div>
                </div>
                
                <div class="security-note">
                    <p><strong>Security Note:</strong> For your protection, reset links expire after 1 hour and can only be used once.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="message_container" class="message-container"></div>

    <script src="js/auth.js"></script>
    <script>
        // Initialize forgot password form
        document.addEventListener('DOMContentLoaded', function() {
            initForgotPasswordForm();
        });

        // Initialize forgot password functionality
        function initForgotPasswordForm() {
            var form = document.getElementById('forgotPasswordForm');
            
            if (!form) return;
            
            // Setup form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleForgotPassword();
            });
        }

        // Handle forgot password form submission
        function handleForgotPassword() {
            var form = document.getElementById('forgotPasswordForm');
            var submitBtn = document.getElementById('forgotPasswordBtn');
            var emailField = document.getElementById('email');
            
            // Clear previous errors
            clearFieldError(emailField);
            
            // Validate email
            var email = emailField.value.trim();
            if (email === '') {
                showFieldError(emailField, 'Email address is required');
                return;
            }
            
            if (!isValidEmail(email)) {
                showFieldError(emailField, 'Please enter a valid email address');
                return;
            }
            
            // Set loading state
            setButtonLoading(submitBtn, true);
            
            // Get form data
            var formData = new FormData(form);
            
            // Submit form via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controller/auth_controller.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    setButtonLoading(submitBtn, false);
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                showMessage(response.message, 'success');
                                
                                // Show success state and disable form
                                form.style.opacity = '0.7';
                                emailField.disabled = true;
                                submitBtn.disabled = true;
                                submitBtn.textContent = 'Instructions Sent';
                                
                                // Redirect to login after delay
                                setTimeout(function() {
                                    window.location.href = 'login.php?message=reset_sent';
                                }, 3000);
                                
                            } else {
                                showMessage(response.message, 'error');
                                
                                // Handle field-specific errors
                                if (response.field_errors && response.field_errors.email) {
                                    showFieldError(emailField, response.field_errors.email);
                                }
                            }
                        } catch (e) {
                            showMessage('Invalid server response', 'error');
                        }
                    } else {
                        showMessage('Server error. Please try again.', 'error');
                    }
                }
            };
            
            xhr.send(formData);
        }
    </script>

    <style>
        /* Additional styles for forgot password page */
        .reset-instructions {
            margin-top: 30px;
            padding: 20px;
            background: var(--secondary-bg);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--info-color);
        }

        .reset-instructions h4 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-size: 16px;
        }

        .instruction-steps {
            margin-bottom: 20px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .step-number {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: var(--info-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .step p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .security-note {
            padding: 15px;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 8px;
            border-left: 3px solid var(--warning-color);
        }

        .security-note p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .security-note strong {
            color: var(--warning-color);
        }

        .auth-form-wrapper {
            max-width: 450px;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .step {
                align-items: flex-start;
            }
            
            .step-number {
                margin-top: 2px;
            }
            
            .reset-instructions {
                padding: 15px;
            }
        }
    </style>
</body>
</html>