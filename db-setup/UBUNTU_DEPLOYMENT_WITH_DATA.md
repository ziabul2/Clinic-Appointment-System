# Ubuntu Server Deployment Guide - With Your Current Data

Complete guide to deploy your clinic database to Ubuntu/Termux with all your current data preserved.

## Overview

This guide walks through 2 phases:
1. **Phase 1 (Current Server):** Export your current database
2. **Phase 2 (Ubuntu Server):** Deploy and restore your data

---

## Phase 1: Export Current Database (On Current Server)

### Step 1: Run Pre-Backup Export Script

This script exports your current `clinic_management` database as a compressed SQL file.

```bash
cd /path/to/clinicapp/db-setup
chmod +x pre_backup_export.sh
./pre_backup_export.sh
```

**What happens:**
- Connects to your current MariaDB
- Verifies database exists
- Exports full database dump
- Compresses with gzip (typically 2-5 MB)
- Creates metadata file with backup information
- Generates recovery script

**Expected output:**
```
✅ Backup File: clinic_backup_YYYYMMDD_HHMMSS_pre_deployment.sql.gz
✅ Location: db-setup/backups/pre-deployment/
✅ Size: 2.4 MB (compressed)
✅ Tables Backed Up: 45
```

### Step 2: Verify Backup Was Created

```bash
ls -lh db-setup/backups/pre-deployment/
```

You should see:
```
clinic_backup_20250129_143022_pre_deployment.sql.gz
clinic_backup_20250129_143022_pre_deployment.sql.gz.info
RESTORE_ON_UBUNTU.sh
```

### Step 3: Copy db-setup Folder to Ubuntu Server

Now copy the entire db-setup folder (including your backup) to your Ubuntu server:

**Option A: Using SCP (SSH Copy)**
```bash
scp -r db-setup user@ubuntu-server:/path/to/clinicapp/
```

**Option B: Manual Transfer**
1. Zip the db-setup folder
2. Download to your Ubuntu server
3. Extract in `/path/to/clinicapp/`

**Example:**
```bash
# On current server
zip -r db-setup.zip db-setup/
# Download db-setup.zip to Ubuntu

# On Ubuntu server
unzip db-setup.zip
cd clinicapp
```

---

## Phase 2: Deploy and Restore on Ubuntu Server

### Step 1: Make Scripts Executable

```bash
cd /path/to/clinicapp/db-setup
chmod +x *.sh
chmod 600 config/db-config.sh
```

### Step 2: Verify Ubuntu Setup

Ensure MariaDB is installed and running:

```bash
# Install MariaDB (if not installed)
sudo apt update
sudo apt install mariadb-server

# Start MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Verify it's running
sudo systemctl status mariadb
```

### Step 3: Update Configuration (If Needed)

Edit the database config to match your Ubuntu server setup:

```bash
nano config/db-config.sh
```

Check these settings:
```bash
DB_ROOT_USER="root"
DB_ROOT_PASS="root123"
DB_APP_USER="ziabul"
DB_APP_PASS="4080099"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="clinic_management"
```

**Important:** Keep username/password same as current server if you want to reuse them.

### Step 4: Run Installation WITH Backup Restoration

This command will:
1. Create database
2. Create application user
3. **Restore your pre-exported data**
4. Verify everything

```bash
./install_clinic_db.sh --restore-backup
```

**What happens:**
```
[✓] Step 1: Checking MariaDB service... ✓
[✓] Step 2: Testing root database connection... ✓
[✓] Step 3: Checking if database already exists... 
[✓] Step 4: Creating/verifying database... ✓
[✓] Step 5: Setting up application user... ✓
[✓] Step 6: Granting privileges... ✓
[→] Step 7: Restoring pre-deployment backup...
    Decompressing and restoring database...
[✓] Pre-deployment backup restored successfully
[✓] Step 8: Skipping migrations (backup already contains all data)
[✓] Step 9: Verifying database structure... ✓
    Database verification complete: 45 tables found

════════════════════════════════════════════════════════════════════
Installation Complete (Restored from pre-deployment backup)
Database: clinic_management
Host: 127.0.0.1
Port: 3306
App User: ziabul
Tables: 45
════════════════════════════════════════════════════════════════════
```

### Step 5: Verify Data Was Restored

```bash
# Connect as app user
mysql -u ziabul -p clinic_management

# Inside MySQL prompt:
SELECT COUNT(*) as total_patients FROM patients;
SELECT COUNT(*) as total_appointments FROM appointments;
SELECT COUNT(*) as total_doctors FROM doctors;

# Exit
exit
```

All your previous data should be there!

---

## Phase 3: Update PHP Configuration

### Update config/database.php

Update your PHP database configuration to match the Ubuntu credentials:

```php
<?php
class Database {
    private $host = "127.0.0.1";
    private $db_name = "clinic_management";
    private $username = "ziabul";      // App user
    private $password = "4080099";     // App user password
    public $conn;
    // ... rest of config
```

### Test PHP Connection

```bash
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=clinic_management;charset=utf8mb4', 'ziabul', '4080099');
    echo 'Database connection successful!' . PHP_EOL;
    \$count = \$pdo->query('SELECT COUNT(*) FROM appointments')->fetch()[0];
    echo 'Found ' . \$count . ' appointments' . PHP_EOL;
} catch(PDOException \$e) {
    echo 'Connection failed: ' . \$e->getMessage() . PHP_EOL;
}
"
```

---

## Complete Workflow Summary

### Before Ubuntu Deployment

```bash
# On current server
cd clinicapp/db-setup
./pre_backup_export.sh                    # Export current data
# Copy db-setup folder to Ubuntu
```

### On Ubuntu Server

```bash
# After copying db-setup folder
cd clinicapp/db-setup
chmod +x *.sh
./install_clinic_db.sh --restore-backup  # Install + restore data
```

### That's It!

Your complete database with all current data is now on Ubuntu server.

---

## Troubleshooting

### Issue: "Cannot connect to MariaDB"

**Solution:**
```bash
# Check if MariaDB is running
sudo systemctl status mariadb

# If not running, start it
sudo systemctl start mariadb

# Check if credentials are correct
mysql -u root -p -e "SELECT VERSION();"
```

### Issue: "Backup file not found"

**Solution:**
```bash
# Check if backup was created
ls -lh backups/pre-deployment/

# Run export script again
./pre_backup_export.sh
```

### Issue: "Restore failed"

**Solution:**
```bash
# Check logs
cat logs/clinic-db-$(date +%Y%m%d).log

# Try manual restore
./restore_clinic_db.sh --file backups/pre-deployment/clinic_backup_*.sql.gz
```

### Issue: "Application still showing old connection error"

**Solution:**
```bash
# Update PHP config
nano ../config/database.php

# Change credentials to match Ubuntu:
# - Host: 127.0.0.1
# - User: ziabul
# - Password: 4080099
# - Database: clinic_management

# Clear PHP cache if using opcache
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

---

## Command Reference

### Export (Current Server)
```bash
./pre_backup_export.sh
```

### Deploy with Restore (Ubuntu Server)
```bash
./install_clinic_db.sh --restore-backup
```

### Manual Restore (Ubuntu Server)
```bash
./restore_clinic_db.sh --file backups/pre-deployment/clinic_backup_*.sql.gz
```

### Create New Backup (Ubuntu Server)
```bash
./backup_clinic_db.sh
```

### Verify Data
```bash
mysql -u ziabul -p clinic_management -e "SELECT COUNT(*) FROM appointments;"
```

---

## Important Notes

1. **Database Credentials:** Keep credentials same as current server (root:root123, ziabul:4080099) for easy migration
2. **Data Preservation:** All your current data is included in the backup
3. **Backup File:** Located in `db-setup/backups/pre-deployment/` - includes metadata
4. **Automatic Detection:** Installation script automatically finds and restores backup
5. **No Data Loss:** Even if restore fails, original database is preserved (safety backup created)

---

## Next Steps After Deployment

1. ✅ Database deployed with your data
2. ✅ Update PHP configuration
3. ⏳ Setup automated daily backups:
   ```bash
   crontab -e
   # Add: 0 2 * * * /path/to/clinicapp/db-setup/backup_clinic_db.sh
   ```
4. ⏳ Monitor application logs
5. ⏳ Test all features work with restored data

---

## Support

For issues or questions:
- Check logs: `logs/clinic-db-YYYYMMDD.log`
- Review README.md: Complete technical reference
- Check GETTING_STARTED.md: Additional setup details
- View QUICK_REFERENCE.sh: Common commands

---

**Version:** 1.0  
**Created:** January 29, 2025  
**Status:** Ready for Deployment
