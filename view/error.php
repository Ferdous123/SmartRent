<?php
// Error handling page for SmartRent
session_start();

// Get error details from URL parameters
$error_code = isset($_GET['code']) ? sanitize_input($_GET['code']) : '500';
$error_message = isset($_GET['message']) ? sanitize_input($_GET['message']) : 'An unexpected error occurred';
$redirect_url = isset($_GET['redirect']) ? sanitize_input($_GET['redirect']) : '../index.php';

// Sanitize function
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get error details based on code
function get_error_details($code) {
    $errors = array(
        '403' => array(
            'title' => 'Access Denied',
            'message' => 'You do not have permission to access this resource.',
            'icon' => '🔒'
        ),
        '404' => array(
            'title' => 'Page Not Found',
            'message' => 'The page you are looking for could not be found.',
            'icon' => '🔍'
        ),
        '500' => array(
            'title' => 'Server Error',
            'message' => 'An internal server error occurred. Please try again later.',
            'icon' => '⚠️'
        ),
        'session_expired' => array(
            'title' => 'Session Expired',
            'message' => 'Your session has expired. Please log in again.',
            'icon' => '⏰'
        ),
        'access_denied' => array(
            'title' => 'Access Denied',
            'message' => 'You do not have the required permissions for this action.',
            'icon' => '🚫'
        ),
        'maintenance' => array(
            'title' => 'Under Maintenance',
            'message' => 'The system is currently under maintenance. Please try again later.',
            'icon' => '🔧'
        ),
        'invalid_request' => array(
            'title' => 'Invalid Request',
            'message' => 'The request could not be processed due to invalid parameters.',
            'icon' => '❌'
        )
    );
    
    return isset($errors[$code]) ? $errors[$code] : $errors['500'];
}

$error_details = get_error_details($error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $error_details['title']; ?> - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        /* Inline CSS for error page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.6s ease-out;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }

        .error-code {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .error-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .error-message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .error-details {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            text-align: left;
        }

        .error-details h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .error-details p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .back-link {
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-icon {
                font-size: 60px;
            }
            
            .error-code {
                font-size: 36px;
            }
            
            .error-title {
                font-size: 24px;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><?php echo $error_details['icon']; ?></div>
        
        <?php if (is_numeric($error_code)): ?>
            <div class="error-code"><?php echo $error_code; ?></div>
        <?php endif; ?>
        
        <h1 class="error-title"><?php echo $error_details['title']; ?></h1>
        
        <p class="error-message">
            <?php echo !empty($error_message) && $error_message !== $error_details['message'] ? $error_message : $error_details['message']; ?>
        </p>

        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <span>←</span>
                Go Back
            </a>
            
            <a href="../index.php" class="btn btn-primary">
                <span>🏠</span>
                Home Page
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../controller/dashboard_controller.php" class="btn btn-primary">
                    <span>📊</span>
                    Dashboard
                </a>
            <?php else: ?>
                <a href="../view/login.php" class="btn btn-primary">
                    <span>🔑</span>
                    Login
                </a>
            <?php endif; ?>
        </div>

        <?php if ($error_code === '403' || $error_code === 'access_denied'): ?>
            <div class="error-details">
                <h4>Why am I seeing this?</h4>
                <p>This usually happens when:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>You don't have the required user role permissions</li>
                    <li>You're trying to access a resource that doesn't belong to you</li>
                    <li>Your session may have expired</li>
                </ul>
                <p>If you believe this is an error, please contact your system administrator.</p>
            </div>
        <?php elseif ($error_code === '404'): ?>
            <div class="error-details">
                <h4>What can I do?</h4>
                <p>Here are some suggestions:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Check the URL for any typos</li>
                    <li>Use the navigation menu to find what you're looking for</li>
                    <li>Go back to the previous page and try again</li>
                    <li>Visit our home page and start fresh</li>
                </ul>
            </div>
        <?php elseif ($error_code === 'session_expired'): ?>
            <div class="error-details">
                <h4>Session Security</h4>
                <p>For your security, we automatically log you out after a period of inactivity. 
                   Please log in again to continue using SmartRent.</p>
            </div>
        <?php elseif ($error_code === '500'): ?>
            <div class="error-details">
                <h4>What happened?</h4>
                <p>We're experiencing some technical difficulties. Our team has been notified 
                   and is working to resolve this issue. Please try again in a few minutes.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($redirect_url) && $redirect_url !== '../index.php'): ?>
            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="back-link">
                Return to where you came from
            </a>
        <?php endif; ?>
    </div>

    <script>
        // Auto-redirect for session expired errors
        <?php if ($error_code === 'session_expired'): ?>
        setTimeout(function() {
            window.location.href = '../view/login.php?message=session_expired';
        }, 5000);
        <?php endif; ?>

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard shortcut to go back
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    history.back();
                } else if (e.key === 'Enter' || e.key === ' ') {
                    window.location.href = '../index.php';
                }
            });

            // Add hover effects to buttons
            var buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(button) {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.05)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Auto-focus on primary button for accessibility
            var primaryBtn = document.querySelector('.btn-primary');
            if (primaryBtn) {
                primaryBtn.focus();
            }
        });

        // Report error to console for debugging
        console.error('SmartRent Error:', {
            code: '<?php echo $error_code; ?>',
            message: '<?php echo addslashes($error_message); ?>',
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href
        });
    </script>
</body>
</html>