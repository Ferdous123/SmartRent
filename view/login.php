<?php
session_start();


if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {

    if (isset($_SESSION['session_timeout']) && time() < $_SESSION['session_timeout']) {
        header("Location: ../controller/dashboard_controller.php");
        exit();
    } else {

        session_destroy();
        session_start();
    }
}


$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';


$error_message = isset($_GET['error']) ? $_GET['error'] : '';
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - SmartRent</title>
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
            max-width: 450px;
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
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
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
        
        .form-footer hr {
            margin: 1.5rem 0;
            border: none;
            border-top: 1px solid #e1e5e9;
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
        
        .quick-access {
            margin-top: 2rem;
            text-align: center;
        }
        
        .quick-access h4 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .quick-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .quick-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: transform 0.2s;
            min-width: 80px;
        }
        
        .quick-btn:hover {
            transform: translateY(-2px);
        }
        
        .owner-btn { background: #28a745; }
        .manager-btn { background: #17a2b8; }
        .tenant-btn { background: #6c757d; }
        
        .quick-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .quick-text {
            color: #666;
            font-size: 0.875rem;
            margin-top: 1rem;
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
                <a href="register.php">Register</a>
            </nav>
        </div>
    </header>


    <div class="auth-container">
        <div class="auth-form-wrapper login-wrapper">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your SmartRent account</p>
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

            <form id="loginForm" class="auth-form" method="POST" action="../controller/working_login.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">
                

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" name="username" id="username" required autofocus>
                    <span class="error-message" id="username_error"></span>
                </div>


                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span id="password_toggle_text">Show</span>
                        </button>
                    </div>
                    <span class="error-message" id="password_error"></span>
                </div>


                <div class="form-group checkbox-group">
                    <input type="checkbox" name="stay_logged_in" id="stay_logged_in">
                    <label for="stay_logged_in">Stay logged in</label>
                </div>


                <div class="form-group">
                    <button type="submit" class="btn-primary" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                    </button>
                </div>


                <div class="form-footer">
                    <div class="auth-links">
                        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    <hr>
                    <p>Don't have an account? <a href="register.php">Create one here</a></p>
                </div>
            </form>


            <div class="quick-access">
                <h4>Quick Access</h4>
                <div class="quick-buttons">
                    <a href="register.php?type=owner" class="quick-btn owner-btn">
                        <span class="quick-icon">👑</span>
                        <span>Owner</span>
                    </a>
                    <a href="register.php?type=manager" class="quick-btn manager-btn">
                        <span class="quick-icon">⚡</span>
                        <span>Manager</span>
                    </a>
                    <a href="register.php?type=tenant" class="quick-btn tenant-btn">
                        <span class="quick-icon">🏠</span>
                        <span>Tenant</span>
                    </a>
                </div>
                <p class="quick-text">New to SmartRent? Choose your role above</p>
            </div>
        </div>
    </div>

    <script>

        function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            var toggleText = document.getElementById(fieldId + '_toggle_text');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggleText.textContent = 'Hide';
            } else {
                field.type = 'password';
                toggleText.textContent = 'Show';
            }
        }


        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value;
            
            if (!username) {
                alert('Please enter your username or email');
                e.preventDefault();
                return false;
            }
            
            if (!password) {
                alert('Please enter your password');
                e.preventDefault();
                return false;
            }
            

            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = 'Signing In...';
        });
    </script>
</body>
</html>