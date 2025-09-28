# SmartRent: House Rent & Tenant Management System

## Overview

SmartRent is a comprehensive web-based property management system designed for property owners, building managers, and tenants. It provides a complete solution for managing rental properties, tenant assignments, payments, service requests, and communication.

## Features

### 🏢 For Property Owners
- Manage multiple buildings and properties
- Financial reports and analytics
- Tenant and manager oversight
- Automated rent collection tracking
- Data backup and restore functionality
- Send notices to tenants and managers

### ⚡ For Building Managers
- Tenant management and flat assignments
- Maintenance request handling
- Payment tracking and verification
- Communication with tenants and owners
- Building-specific operational logs

### 🏠 For Tenants
- Online payment tracking
- Service request portal
- Digital rent receipts (Bengali/English)
- Payment history and outstanding dues
- Direct communication with management

## Technical Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS, beginner-friendly)
- **Backend**: PHP (Procedural, no OOP)
- **Database**: MySQL/MariaDB
- **Architecture**: MVC Pattern
- **Security**: Session management, 2FA with Google Authenticator
- **Languages**: English and Bengali support

## Installation Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.2 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### XAMPP Installation (Recommended for Development)
1. Download and install [XAMPP](https://www.apachefriends.org/)
2. Start Apache and MySQL services
3. Access phpMyAdmin at `http://localhost/phpmyadmin`

## Installation Steps

### Step 1: Download and Extract
1. Download the SmartRent system files
2. Extract to your web server directory:
   - XAMPP: `C:/xampp/htdocs/smartrent/`
   - Linux: `/var/www/html/smartrent/`

### Step 2: Database Setup
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Create a new database named `smartrent_db`
3. Import the database schema:
   - Copy the entire database schema from `model/database.php`
   - Execute it in phpMyAdmin SQL tab
4. The schema will create all necessary tables and initial data

### Step 3: Configuration
1. Update database connection settings in `model/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Your MySQL password
define('DB_NAME', 'smartrent_db');
```

### Step 4: File Permissions
Set appropriate permissions for file uploads and logs:
```bash
chmod 755 view/uploads/
chmod 755 logs/
```

### Step 5: Favicon
1. Create a favicon.ico file and place it in the root directory
2. You can use any favicon generator online

## Project Structure

```
smartrent/
├── index.php                 # Landing page
├── favicon.ico              # Site favicon
├── model/                   # Database operations
│   ├── database.php         # Database connection and utilities
│   ├── user_model.php       # User management functions
│   ├── property_model.php   # Property and building functions
│   └── notification_model.php # Notification functions
├── view/                    # HTML templates and assets
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   ├── forgot_password.php # Password reset page
│   ├── error.php           # Error handling page
│   ├── dashboard_owner.php # Owner dashboard
│   ├── dashboard_manager.php # Manager dashboard
│   ├── dashboard_tenant.php # Tenant dashboard
│   ├── css/                # Stylesheets
│   │   ├── landing.css
│   │   ├── auth.css
│   │   └── dashboard.css
│   └── js/                 # JavaScript files
│       ├── landing.js
│       ├── auth.js
│       └── dashboard.js
└── controller/             # Business logic
    ├── auth_controller.php      # Authentication handling
    ├── session_controller.php   # Session management
    └── dashboard_controller.php # Dashboard operations
```

## Getting Started

### Initial Access
1. Navigate to `http://localhost/smartrent/`
2. Click "Get Started" or "Register"
3. Create your first account (choose Owner to access all features)
4. Complete the registration process
5. Set up Google Authenticator when prompted for enhanced security

### Creating Your First Building
1. Login as an Owner
2. Click "Add Building" from the dashboard
3. Fill in building details (name, address, floors, etc.)
4. Add flats to the building
5. Assign managers (optional) or manage directly

### Adding Tenants
There are two ways to add tenants:

**Method 1: Direct Assignment**
1. Go to tenant management
2. Click "Assign Tenant"
3. Enter tenant details or existing tenant ID
4. Select flat and set advance amount
5. Tenant receives notification to confirm

**Method 2: OTP System**
1. Generate OTP for specific flat
2. Share OTP with prospective tenant
3. Tenant registers using the OTP
4. Confirms assignment with advance payment

## Default Features

### User Roles and Permissions

**Owner (Full Access)**
- Create/manage buildings and flats
- Assign/remove managers
- View all financial reports
- Send system-wide notices
- Backup/restore data
- Access to all system logs

**Manager (Building-Specific)**
- Manage assigned buildings only
- Add/remove tenants in managed buildings
- Handle service requests
- Generate payment slips
- Send notices to building tenants
- View building-specific reports

**Tenant (Personal Access)**
- View own payment history
- Submit service requests
- Download rent receipts
- Update personal profile
- Communicate with management
- View flat and lease details

### Security Features

1. **Session Management**
   - Automatic session timeout (1 hour default)
   - "Stay logged in" option (30 days)
   - Session fingerprinting for security

2. **Two-Factor Authentication**
   - Google Authenticator integration
   - Backup codes provided
   - Required for password reset

3. **Access Control**
   - Role-based permissions
   - User activity logging
   - Input sanitization and validation

## Configuration Options

### Language Support
- Default: English
- Available: Bengali (বাংলা)
- Switch language from user preferences
- Rental slips available in both languages

### Theme Customization
- Light/Dark mode support
- Customizable navigation colors
- Font size options (Small, Medium, Large)
- User-specific preferences saved in cookies

### Payment System
- Multiple payment methods (Cash, Bank Transfer, Mobile Banking, Check)
- Unique transaction number generation
- Automatic receipt generation
- Advance payment management with adjustment features

## Troubleshooting

### Common Issues

**1. Database Connection Error**
- Verify MySQL is running
- Check database credentials in `model/database.php`
- Ensure database `smartrent_db` exists

**2. Session Issues**
- Clear browser cookies
- Check PHP session configuration
- Verify file permissions for session storage

**3. File Upload Problems**
- Check `view/uploads/` directory permissions
- Verify PHP `upload_max_filesize` settings
- Ensure sufficient disk space

**4. 2FA Setup Issues**
- Verify system time is synchronized
- Check Google Authenticator app installation
- Use backup codes if available

### Error Codes
- **403**: Access denied - insufficient permissions
- **404**: Page not found - check URL
- **500**: Server error - check error logs
- **session_expired**: Login again required

## Security Best Practices

### For Production Deployment

1. **Database Security**
   - Use strong database passwords
   - Create dedicated database user with limited privileges
   - Enable SSL for database connections

2. **File Security**
   - Set restrictive file permissions (644 for files, 755 for directories)
   - Move configuration files outside web root if possible
   - Regular security updates

3. **SSL/HTTPS**
   - Enable HTTPS in production
   - Update session settings for secure cookies
   - Use HSTS headers

4. **Backup Strategy**
   - Regular automated database backups
   - Store backups securely off-site
   - Test backup restoration procedures

## Support and Updates

### Getting Help
1. Check this README for common solutions
2. Review error logs in browser developer tools
3. Check PHP error logs for server-side issues

### System Maintenance
- Regular database cleanup of old logs (automated)
- User notification cleanup (30+ days old)
- Monitor disk space usage
- Update PHP and MySQL regularly

## License

This project is designed for educational and commercial use. The code follows beginner-friendly patterns similar to W3Schools examples for easy learning and modification.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+

For technical support or feature requests, please refer to the system documentation or contact your system administrator.
