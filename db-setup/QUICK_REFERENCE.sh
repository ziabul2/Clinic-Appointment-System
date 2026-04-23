#!/bin/bash

# ==============================================================================
# QUICK REFERENCE - Clinic App Database Management
# ==============================================================================
# Location: c:/xampp/htdocs/clinicapp/db-setup/
# Credentials: root:root123 | ziabul:4080099
# Database: clinic_management
# ==============================================================================

# FIRST TIME SETUP (Run these commands in order)
# ==============================================================================

# 1. Navigate to db-setup directory
cd /path/to/clinicapp/db-setup

# 2. Make scripts executable
chmod +x *.sh
chmod 600 config/db-config.sh

# 3. Start MariaDB service (if not running)
sudo systemctl start mariadb

# 4. Install database
./install_clinic_db.sh

# Expected output: "Installation completed successfully!"


# DAILY OPERATIONS
# ==============================================================================

# Create backup
./backup_clinic_db.sh

# List recent backups
ls -lh backups/ | tail -5

# Verify backup integrity
./restore_clinic_db.sh --file backups/clinic_backup_YYYYMMDD_*.sql.gz --verify-only


# RESTORE DATABASE (If something goes wrong)
# ==============================================================================

# Option 1: Restore most recent backup
./restore_clinic_db.sh --file $(ls -t backups/clinic_backup_*.sql.gz | head -1)

# Option 2: Restore specific backup
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz


# TROUBLESHOOTING
# ==============================================================================

# Check if MariaDB is running
sudo systemctl status mariadb

# View logs
tail -f logs/clinic-db-$(date +%Y%m%d).log

# Test database connection
mysql -u root -p -e "SELECT VERSION();"

# Check database exists
mysql -u root -p -e "SHOW DATABASES;" | grep clinic_management

# Force reinstall (drops existing database)
./install_clinic_db.sh --force


# CRON SETUP (Automatic daily backups)
# ==============================================================================

# Open crontab editor
crontab -e

# Add this line for daily backup at 2 AM:
0 2 * * * /path/to/clinicapp/db-setup/backup_clinic_db.sh >> /path/to/clinicapp/db-setup/logs/backup-cron.log 2>&1

# Add this line for weekly full backup (Sunday 3 AM):
0 3 * * 0 /path/to/clinicapp/db-setup/backup_clinic_db.sh --full >> /path/to/clinicapp/db-setup/logs/backup-cron.log 2>&1


# CONFIGURATION
# ==============================================================================

# Edit database credentials (if needed)
nano config/db-config.sh

# Key variables:
# DB_ROOT_USER="root"
# DB_ROOT_PASS="root123"
# DB_APP_USER="ziabul"
# DB_APP_PASS="4080099"
# BACKUP_RETENTION_DAYS=30


# VERIFICATION COMMANDS
# ==============================================================================

# Check app user password works
mysql -u ziabul -p clinic_management -e "SELECT COUNT(*) FROM appointments;"

# Count total tables
mysql -u ziabul -p clinic_management -e "SELECT COUNT(*) as tables FROM information_schema.tables WHERE table_schema='clinic_management';"

# Check backup file size
du -sh backups/clinic_backup_*.sql.gz | tail -5

# View backup metadata
cat backups/clinic_backup_YYYYMMDD_HHMMSS.info


# EMERGENCY PROCEDURES
# ==============================================================================

# If database is corrupted
./install_clinic_db.sh --force

# If MariaDB won't start
sudo systemctl restart mariadb

# If password forgotten
mysql -u root -e "ALTER USER 'ziabul'@'localhost' IDENTIFIED BY '4080099'; FLUSH PRIVILEGES;"

# Clear old backups manually
find backups/ -name "clinic_backup_*.sql.gz" -mtime +30 -delete


# FILES REFERENCE
# ==============================================================================

# Main scripts:
# - install_clinic_db.sh      : Database installation/setup
# - backup_clinic_db.sh       : Create compressed backups
# - restore_clinic_db.sh      : Restore from backup
# - lib-functions.sh          : Utility functions (sourced by other scripts)

# Configuration:
# - config/db-config.sh       : Database credentials and settings

# Migrations:
# - migrations/*.sql          : SQL schema files (applied in order)

# Output directories:
# - backups/                  : Backup files (.sql.gz) and metadata (.info)
# - logs/                     : Log files (one per day)


# QUICK TROUBLESHOOTING FLOWCHART
# ==============================================================================

# Problem: "Database error occurred" in PHP app
# Fix: sudo systemctl restart mariadb; ./install_clinic_db.sh

# Problem: Backup verification failed
# Fix: rm backups/clinic_backup_*.sql.gz; ./backup_clinic_db.sh

# Problem: Restore stopped with error
# Fix: Check logs/clinic-db-YYYYMMDD.log; restore from previous backup

# Problem: Can't connect as ziabul user
# Fix: Update DB_APP_PASS in config/db-config.sh; run ./install_clinic_db.sh --force

# Problem: Disk full with backups
# Fix: find backups/ -name "*.sql.gz" -mtime +30 -delete

# ==============================================================================
# For detailed documentation, see README.md
# ==============================================================================
