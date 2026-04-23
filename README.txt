CLINIC APPOINTMENT SCHEDULING SYSTEM
=====================================

Project Overview:
-----------------
A comprehensive web-based application for managing clinic appointments, 
patients, doctors, and scheduling. Built with PHP, MySQL, and Bootstrap.

Features:
---------
1. Patient Management (Add, View, Update)
2. Doctor Management with Specializations
3. Appointment Booking System
4. Real-time Availability Checking
5. User Authentication System
6. Comprehensive Logging System
7. Responsive Design
8. Error Handling and Validation

System Requirements:
--------------------
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache/Nginx)
- Modern Web Browser

Installation Steps:
-------------------
1. Extract the project files to your web server directory
   (e.g., /var/www/html/clinic_appointment_system/)

2. Create MySQL Database:
   - Access your MySQL server
   - Create database: CREATE DATABASE clinic_management;
   - Import the database schema from database_backup.sql

3. Configure Database Connection:
   - Edit config/database.php
   - Update database credentials:
     $host, $db_name, $username, $password

4. Set File Permissions:
   - chmod 755 logs/ (ensure writable for logging)

5. Access the Application:
   - Open web browser
   - Navigate to: http://localhost/clinic_appointment_system/

Default Login Credentials:
--------------------------
Admin Access:
Username: admin
Password: password

Receptionist Access:
Username: reception
Password: password

File Structure:
---------------
clinic_appointment_system/
├── config/          # Configuration files
├── includes/        # Reusable components
├── pages/          # Main application pages
├── assets/         # CSS, JS, Images
├── logs/           # System logs
└── index.php       # Entry point

Key Pages:
----------
- index.php - Login page
- dashboard.php - System overview
- patients.php - Patient management
- doctors.php - Doctor management
- appointments.php - Appointment management

Security Features:
------------------
- Input sanitization
- SQL injection prevention
- Session management
- Role-based access control
- Error logging

Logging System:
---------------
- errors.log: System errors and exceptions
- process.log: User actions and system events

Backup Instructions:
--------------------
1. Regular database backup:
   mysqldump -u username -p clinic_management > backup.sql

2. Application backup:
   Copy entire project directory

Troubleshooting:
----------------
1. Database Connection Issues:
   - Check database credentials in config/database.php
   - Verify MySQL service is running

2. Permission Errors:
   - Ensure logs directory is writable
   - Check file permissions (755 for directories, 644 for files)

3. Page Not Found:
   - Verify mod_rewrite is enabled (for Apache)
   - Check server configuration

Development Notes:
------------------
- Uses PDO for database operations
- Bootstrap 5 for responsive design
- Font Awesome for icons
- Custom logging system
- Comprehensive error handling

Support:
--------
For technical support or issues, please check:
1. Error logs in logs/ directory
2. PHP error logs
3. MySQL error logs

Version: 1.0
Last Updated: 2024-01-01