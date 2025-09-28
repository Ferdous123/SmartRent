<?php
// Basic session start to check if user is already logged in
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SmartRent - House Rent & Tenant Management System</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="view/css/landing.css">
    <style>
        /* Inline CSS for immediate loading */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="navbar">
        <div class="nav-container">
            <div class="logo">
                <h2>SmartRent</h2>
            </div>
            <nav class="nav-menu">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#contact">Contact</a>
                <a href="view/login.php" onclick="checkSessionBeforeAuth(event, 'login')">Login</a>
                <a href="view/register.php" onclick="checkSessionBeforeAuth(event, 'register')">Get Started</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Complete House Rent & Tenant Management Solution</h1>
                <p class="hero-subtitle">Streamline your property management with our comprehensive system designed for Property Owners, Managers, and Tenants</p>
                <div class="hero-buttons">
                    <a href="view/register.php" class="btn-primary">Start Free Trial</a>
                    <a href="#demo" class="btn-secondary">Watch Demo</a>
                </div>
            </div>
            <div class="hero-image">
                <!-- Placeholder for hero image -->
                <div class="hero-placeholder">🏢 Property Management Dashboard Screenshot</div>
            </div>
        </div>
    </section>

    <!-- User Type Cards -->
    <section class="user-types">
        <div class="container">
            <h2 class="section-title">Designed for Every User</h2>
            <div class="user-cards">
                <div class="user-card owner-card">
                    <div class="card-icon">👑</div>
                    <h3>Property Owner</h3>
                    <ul>
                        <li>Financial Reports & Analytics</li>
                        <li>Multi-Building Management</li>
                        <li>Tenant & Manager Oversight</li>
                        <li>Automated Rent Collection</li>
                        <li>Data Backup & Restore</li>
                    </ul>
                    <a href="view/register.php?type=owner" class="card-btn">Start as Owner</a>
                </div>

                <div class="user-card manager-card">
                    <div class="card-icon">⚡</div>
                    <h3>Building Manager</h3>
                    <ul>
                        <li>Tenant Management & Assignment</li>
                        <li>Maintenance Request Handling</li>
                        <li>Payment Tracking</li>
                        <li>Communication Center</li>
                        <li>Operational Logs</li>
                    </ul>
                    <a href="view/register.php?type=manager" class="card-btn">Start as Manager</a>
                </div>

                <div class="user-card tenant-card">
                    <div class="card-icon">🏠</div>
                    <h3>Tenant</h3>
                    <ul>
                        <li>Online Rent Payment</li>
                        <li>Service Request Portal</li>
                        <li>Digital Rent Receipts</li>
                        <li>Payment History</li>
                        <li>Direct Communication</li>
                    </ul>
                    <a href="view/register.php?type=tenant" class="card-btn">Start as Tenant</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Powerful Features</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <h4>Smart Payment System</h4>
                    <p>Automated rent collection with advance payment management and multi-language receipt generation</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📱</div>
                    <h4>Real-time Notifications</h4>
                    <p>Instant updates via Server-Sent Events for payments, assignments, and important announcements</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🔐</div>
                    <h4>Advanced Security</h4>
                    <p>Google Authenticator 2FA, role-based access control, and comprehensive activity logging</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <h4>Analytics & Reports</h4>
                    <p>Comprehensive financial reports, occupancy statistics, and performance analytics</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🏗️</div>
                    <h4>Multi-Property Support</h4>
                    <p>Manage multiple buildings, assign managers, and track everything from one dashboard</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🌐</div>
                    <h4>Multi-language Support</h4>
                    <p>Complete Bengali and English support with customizable themes and preferences</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="demo">
        <div class="container">
            <h2 class="section-title">See SmartRent in Action</h2>
            <div class="demo-video">
                <!-- Placeholder for demo video -->
                <div class="video-placeholder">🎥 Interactive Demo Video Coming Soon</div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Transform Your Property Management?</h2>
            <p>Join thousands of property owners, managers, and tenants who trust SmartRent</p>
            <div class="cta-buttons">
                <a href="view/register.php" class="btn-primary">Get Started Free</a>
                <a href="#contact" class="btn-secondary">Contact Sales</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>SmartRent</h4>
                    <p>Complete property management solution for modern needs</p>
                </div>
                <div class="footer-section">
                    <h4>Features</h4>
                    <ul>
                        <li><a href="#features">Payment Management</a></li>
                        <li><a href="#features">Tenant Portal</a></li>
                        <li><a href="#features">Analytics</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="#demo">Demo</a></li>
                        <li><a href="#features">Documentation</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 SmartRent. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="view/js/landing.js"></script>
    <script>
    function checkSessionBeforeAuth(event, type) {
    // Let the link work normally - auth pages will handle redirect if logged in
    return true;
    }
</script>
</body>
</html>