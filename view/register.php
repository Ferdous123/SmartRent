<?php

session_start();
require_once '../controller/session_controller.php';


redirect_if_authenticated();


$selected_type = isset($_GET['type']) ? $_GET['type'] : '';


$error_message = isset($_GET['error']) ? $_GET['error'] : '';
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }
        
        .logo h2 {
            color: white;
            font-weight: 600;
            text-decoration: none;
        }
        
        .logo a {
            color: white;
            text-decoration: none;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            margin-left: 2rem;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .nav-menu a:hover {
            opacity: 0.8;
        }
        
        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .auth-form-wrapper {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        
        .form-group select {
            cursor: pointer;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-top: 0.25rem;
            flex-shrink: 0;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            line-height: 1.4;
        }
        
        .checkbox-group a {
            color: #667eea;
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 2rem;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .role-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .role-card h4 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .role-card ul {
            margin-left: 1.5rem;
            color: #666;
        }
        
        .role-card li {
            margin-bottom: 0.5rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength.weak {
            background: linear-gradient(to right, #dc3545 0%, #dc3545 33%, #e1e5e9 33%);
        }
        
        .password-strength.medium {
            background: linear-gradient(to right, #ffc107 0%, #ffc107 66%, #e1e5e9 66%);
        }
        
        .password-strength.strong {
            background: #28a745;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .auth-form-wrapper {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>

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


    <div class="auth-container">
        <div class="auth-form-wrapper">
            <div class="auth-header">
                <h2>Create Your Account</h2>
                <p>Join SmartRent and start managing your properties efficiently</p>
            </div>

            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form id="registerForm" class="auth-form" method="POST" action="../controller/working_register.php">
                <input type="hidden" name="action" value="register">
                

                <div class="form-group">
                    <label for="user_type">I am a:</label>
                    <select name="user_type" id="user_type" required>
                        <option value="">Select your role</option>
                        <option value="owner" <?php echo ($selected_type === 'owner') ? 'selected' : ''; ?>>Property Owner</option>
                        <option value="manager" <?php echo ($selected_type === 'manager') ? 'selected' : ''; ?>>Building Manager</option>
                        <option value="tenant" <?php echo ($selected_type === 'tenant') ? 'selected' : ''; ?>>Tenant</option>
                    </select>
                    <span class="error-message" id="user_type_error"></span>
                </div>


                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required>
                        <span class="error-message" id="full_name_error"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required>
                        <span class="error-message" id="username_error"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" required>
                        <span class="error-message" id="email_error"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" name="phone_number" id="phone_number" placeholder="Optional">
                        <span class="error-message" id="phone_number_error"></span>
                    </div>
                </div>


                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                        <span class="error-message" id="password_error"></span>
                        <div class="password-strength" id="password_strength"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <span class="error-message" id="confirm_password_error"></span>
                    </div>
                </div>


                <div class="form-group checkbox-group">
                    <input type="checkbox" name="agree_terms" id="agree_terms" required>
                    <label for="agree_terms">I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a></label>
                    <span class="error-message" id="agree_terms_error"></span>
                </div>


                <div class="form-group">
                    <button type="submit" class="btn-primary" id="registerBtn">
                        <span class="btn-text">Create Account</span>
                    </button>
                </div>


                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>


            <div class="role-info" id="role_info" style="display: none;">
                <div class="role-card" id="owner_info">
                    <h4>As a Property Owner, you can:</h4>
                    <ul>
                        <li>Manage multiple buildings and properties</li>
                        <li>Assign managers to your buildings</li>
                        <li>View comprehensive financial reports</li>
                        <li>Monitor all tenant activities</li>
                        <li>Backup and restore system data</li>
                    </ul>
                </div>

                <div class="role-card" id="manager_info">
                    <h4>As a Building Manager, you can:</h4>
                    <ul>
                        <li>Manage tenants and flat assignments</li>
                        <li>Handle maintenance requests</li>
                        <li>Track rent payments</li>
                        <li>Send notices to tenants</li>
                        <li>Monitor building operations</li>
                    </ul>
                </div>

                <div class="role-card" id="tenant_info">
                    <h4>As a Tenant, you can:</h4>
                    <ul>
                        <li>Make online rent payments</li>
                        <li>Submit service requests</li>
                        <li>Download rent receipts</li>
                        <li>View payment history</li>
                        <li>Communicate with management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>

document.addEventListener('DOMContentLoaded', function() {
    

    document.getElementById('username').addEventListener('blur', function() {
        const value = this.value.trim();
        const error = document.getElementById('username_error');
        
        if (value.length < 3) {
            error.textContent = 'Username must be at least 3 characters';
            this.style.borderColor = '#dc3545';
        } else {
            error.textContent = '';
            this.style.borderColor = '#28a745';
        }
    });
    

    document.getElementById('email').addEventListener('blur', function() {
        const value = this.value.trim();
        const error = document.getElementById('email_error');
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            error.textContent = 'Please enter a valid email address';
            this.style.borderColor = '#dc3545';
        } else {
            error.textContent = '';
            this.style.borderColor = '#28a745';
        }
    });
    

    document.getElementById('password').addEventListener('blur', function() {
        const value = this.value;
        const error = document.getElementById('password_error');
        
        if (value.length < 6) {
            error.textContent = 'Password must be at least 6 characters';
            this.style.borderColor = '#dc3545';
        } else {
            error.textContent = '';
            this.style.borderColor = '#28a745';
        }
    });
    

    document.getElementById('full_name').addEventListener('blur', function() {
        const value = this.value.trim();
        const error = document.getElementById('full_name_error');
        
        if (value.length < 2) {
            error.textContent = 'Full name must be at least 2 characters';
            this.style.borderColor = '#dc3545';
        } else {
            error.textContent = '';
            this.style.borderColor = '#28a745';
        }
    });


    document.getElementById('user_type').addEventListener('change', function() {
        const roleInfo = document.getElementById('role_info');
        const allCards = roleInfo.querySelectorAll('.role-card');
        

        allCards.forEach(card => card.style.display = 'none');
        
        if (this.value) {

            const selectedCard = document.getElementById(this.value + '_info');
            if (selectedCard) {
                selectedCard.style.display = 'block';
                roleInfo.style.display = 'block';
            }
        } else {
            roleInfo.style.display = 'none';
        }
    });


    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('password_strength');
        
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        strengthBar.className = 'password-strength';
        if (strength < 3) {
            strengthBar.classList.add('weak');
        } else if (strength < 5) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    });


    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const agreeTerms = document.getElementById('agree_terms').checked;
        

        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        
        let isValid = true;
        

        if (password !== confirmPassword) {
            document.getElementById('confirm_password_error').textContent = 'Passwords do not match';
            isValid = false;
        }
        

        if (!agreeTerms) {
            document.getElementById('agree_terms_error').textContent = 'You must agree to the terms';
            isValid = false;
        }
        
        if (!isValid) {
            return false;
        }
        

        const btn = document.getElementById('registerBtn');
        btn.disabled = true;
        btn.innerHTML = 'Creating Account...';
        

        const formData = new FormData(this);
        
        fetch('../controller/working_register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {

                window.location.href = '../view/login.php?success=' + encodeURIComponent(data.message);
            } else {

                if (data.field_errors) {

                    Object.keys(data.field_errors).forEach(field => {
                        const errorElement = document.getElementById(field + '_error');
                        if (errorElement) {
                            errorElement.textContent = data.field_errors[field];
                            document.getElementById(field).style.borderColor = '#dc3545';
                        }
                    });
                } else {

                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Create Account';
        });
    });


    if (document.getElementById('user_type').value) {
        document.getElementById('user_type').dispatchEvent(new Event('change'));
    }
});
</script>

</body>
</html>