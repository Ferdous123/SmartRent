<?php
// Check if accessed directly
if (!isset($current_user)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $user_preferences['language_code'] ?? 'en'; ?>">
<head>
    <title>Profile Settings - SmartRent</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../view/css/dashboard.css">
    <link rel="stylesheet" href="../view/css/profile.css">
    <style>
        :root {
            --nav-color: <?php echo $user_preferences['nav_color'] ?? '#667eea'; ?>;
            --primary-bg: <?php echo $user_preferences['primary_bg_color'] ?? '#ffffff'; ?>;
            --secondary-bg: <?php echo $user_preferences['secondary_bg_color'] ?? '#f5f7fa'; ?>;
            --font-size: <?php echo ($user_preferences['font_size'] ?? 'medium') === 'small' ? '14px' : (($user_preferences['font_size'] ?? 'medium') === 'large' ? '18px' : '16px'); ?>;
        }
        /* Tab display fixes */
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block !important;
        }

        /* Show personal tab by default */
        #personal-tab.active {
            display: block !important;
        }
    </style>
</head>
<body class="<?php echo $user_preferences['theme_mode'] ?? 'light'; ?>-theme">
    <!-- Navigation Header -->
    <header class="dashboard-navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">
                    <h2>SmartRent</h2>
                </div>
                <nav class="main-nav">
                    <a href="../controller/dashboard_controller.php" class="nav-link">Dashboard</a>
                    <a href="../controller/profile_controller.php" class="nav-link active">Profile</a>
                    <?php if ($current_user['user_type'] === 'owner'): ?>
                        <a href="#buildings" class="nav-link">Buildings</a>
                        <a href="#reports" class="nav-link">Reports</a>
                    <?php elseif ($current_user['user_type'] === 'manager'): ?>
                        <a href="#tenants" class="nav-link">Tenants</a>
                        <a href="#maintenance" class="nav-link">Maintenance</a>
                    <?php else: ?>
                        <a href="#payments" class="nav-link">Payments</a>
                        <a href="#services" class="nav-link">Services</a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="nav-right">
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            <?php if (!empty($user_profile['profile_picture_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($user_profile['profile_picture_url']); ?>" alt="Profile">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-menu" id="userMenu" style="display: none;">
                        <a href="../controller/profile_controller.php">Profile Settings</a>
                        <a href="../controller/working_login.php?action=logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="profile-container">
            <!-- Profile Header -->
            <!-- Profile Header -->
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-section">
                <div class="profile-avatar-large" onclick="triggerFileUpload()">
                    <?php if (!empty($user_profile['profile_picture_url']) && file_exists('../' . $user_profile['profile_picture_url'])): ?>
                        <img id="profileImage" src="../<?php echo htmlspecialchars($user_profile['profile_picture_url']); ?>" alt="Profile Picture" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <span id="profileInitial"><?php echo strtoupper(substr($user_profile['full_name'] ?? 'U', 0, 1)); ?></span>
                    <?php endif; ?>
                    <div class="upload-overlay">
                        <span>Change Photo</span>
                    </div>
                </div>
                <!-- Separate file input, not part of any form -->
                <input type="file" id="profilePictureUpload" accept="image/*" style="display: none;" onchange="uploadProfilePicture(this)">
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user_profile['full_name'] ?? 'Unknown User'); ?></h1>
                <p class="user-role"><?php echo ucfirst($current_user['user_type']); ?></p>
                <p class="user-email"><?php echo htmlspecialchars($user_profile['email']); ?></p>
                <p class="member-since">Member since <?php echo isset($user_profile['created_at']) && $user_profile['created_at'] ? date('M Y', strtotime($user_profile['created_at'])) : 'Unknown'; ?></p>
            </div>
        </div>
        <!-- Profile Sections - No Tabs -->
            <div class="profile-sections">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <div class="card">
                        <div class="card-header">
                            <h3>Personal Information</h3>
                        </div>
                        <div class="card-content">
                            <form id="personalInfoForm">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" value="<?php echo htmlspecialchars($user_profile['username']); ?>" disabled>
                                        <small>Username cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" disabled>
                                        <small>Contact support to change email</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_profile['contact_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nid_number">NID Number</label>
                                        <input type="text" id="nid_number" name="nid_number" value="<?php echo htmlspecialchars($user_profile['nid_number'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="user_type">Account Type</label>
                                        <input type="text" value="<?php echo ucfirst($current_user['user_type']); ?>" disabled>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="permanent_address">Permanent Address</label>
                                    <textarea id="permanent_address" name="permanent_address" rows="3"><?php echo htmlspecialchars($user_profile['permanent_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" id="personalInfoSubmitBtn" class="btn-primary">Update Information</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="profile-section">
                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Change Password</h3>
                        </div>
                        <div class="card-content">
                            <form id="changePasswordForm">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                    <span class="form-error" id="current_password_error"></span>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" required>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <span class="form-error" id="new_password_error"></span>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required>
                                        <span class="form-error" id="confirm_password_error"></span>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" id="passwordSubmitBtn" class="btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Two-Factor Authentication (2FA)</h3>
                            <div class="security-status">
                                <span id="twofa-status" class="status-badge <?php echo $twofa_status['is_enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $twofa_status['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-content">
                            <p>Two-factor authentication adds an extra layer of security to your account.</p>
                            <?php if (!$twofa_status['is_enabled']): ?>
                                <button id="setup2FABtn" class="btn-primary" onclick="setup2FA()">Enable 2FA</button>
                            <?php else: ?>
                                <button class="btn-warning" onclick="disable2FA()">Disable 2FA</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Preferences Section -->
                <div class="profile-section">
                    <div class="card">
                        <div class="card-header">
                            <h3>Display Preferences</h3>
                        </div>
                        <div class="card-content">
                            <form id="preferencesForm">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="theme_mode">Theme</label>
                                        <select id="theme_mode" name="theme_mode">
                                            <option value="light" <?php echo ($user_preferences['theme_mode'] === 'light') ? 'selected' : ''; ?>>Light</option>
                                            <option value="dark" <?php echo ($user_preferences['theme_mode'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="language_code">Language</label>
                                        <select id="language_code" name="language_code">
                                            <option value="en" <?php echo ($user_preferences['language_code'] === 'en') ? 'selected' : ''; ?>>English</option>
                                            <option value="bn" <?php echo ($user_preferences['language_code'] === 'bn') ? 'selected' : ''; ?>>Bengali</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="font_size">Font Size</label>
                                        <select id="font_size" name="font_size">
                                            <option value="small" <?php echo ($user_preferences['font_size'] === 'small') ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo ($user_preferences['font_size'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo ($user_preferences['font_size'] === 'large') ? 'selected' : ''; ?>>Large</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="nav_color">Navigation Color</label>
                                        <input type="color" id="nav_color" name="nav_color" value="<?php echo $user_preferences['nav_color']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">Save Preferences</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if ($current_user['user_type'] === 'tenant'): ?>
                <!-- Tenancy Section -->
                <div class="profile-section">
                    <div class="card">
                        <div class="card-header">
                            <h3>Current Tenancy</h3>
                        </div>
                        <div class="card-content">
                            <p>Tenancy information will be displayed here.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
    </main>

    <!-- 2FA Setup Modal -->
    <div id="twofa-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Setup Two-Factor Authentication</h3>
                <button class="modal-close" onclick="closeTwoFAModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="twofa-setup-steps">
                    <!-- Step 1: Install App -->
                    <div class="setup-step" id="step1">
                        <h4>Step 1: Install Google Authenticator</h4>
                        <p>Download and install Google Authenticator on your mobile device</p>
                        <button class="btn-primary" onclick="nextStep(2)">Next</button>
                    </div>

                    <!-- Step 2: Scan QR Code -->
                    <div class="setup-step" id="step2" style="display: none;">
                        <h4>Step 2: Scan QR Code</h4>
                        <p>Open Google Authenticator and scan this QR code:</p>
                        <div class="qr-code-container">
                            <img id="qrCodeImage" src="" alt="QR Code">
                        </div>
                        <div class="manual-setup">
                            <p>Can't scan? Enter this code manually:</p>
                            <code id="manualCode"></code>
                        </div>
                        <button class="btn-primary" onclick="nextStep(3)">Next</button>
                    </div>

                    <!-- Step 3: Verify Setup -->
                    <div class="setup-step" id="step3" style="display: none;">
                        <h4>Step 3: Verify Setup</h4>
                        <p>Enter the 6-digit code from your authenticator app:</p>
                        <input type="text" id="verification-code" maxlength="6" placeholder="000000" class="verification-input">
                        <div class="backup-codes" id="backup-codes" style="display: none;">
                            <h5>Backup Codes</h5>
                            <p>Save these backup codes in a safe place. You can use them to access your account if you lose your phone:</p>
                            <div id="backup-codes-list"></div>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-secondary" onclick="previousStep(2)">Back</button>
                            <button class="btn-primary" onclick="verify2FASetup()">Verify & Enable</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div id="messageContainer" class="message-container"></div>

    <!-- Scripts -->
    <script src="../view/js/profile.js"></script>
</body>
</html>