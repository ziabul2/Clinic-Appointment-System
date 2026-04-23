# Clinic App Database Management Scripts

Complete bash automation suite for MariaDB database setup, backup, and restore operations on Ubuntu/Termux environments.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Deployment with Your Current Data](#deployment-with-your-current-data)
3. [Scripts Overview](#scripts-overview)
4. [Configuration](#configuration)
5. [Installation](#installation)
6. [Backup & Restore](#backup--restore)
7. [Troubleshooting](#troubleshooting)
8. [Advanced Usage](#advanced-usage)

---

## Quick Start

### First-Time Setup

```bash
# 1. Make scripts executable
chmod +x install_clinic_db.sh backup_clinic_db.sh restore_clinic_db.sh

# 2. Run installation (creates database, app user, runs migrations)
./install_clinic_db.sh

# 3. Create your first backup
./backup_clinic_db.sh --full
```

### Daily Operations

```bash
# Create daily backup
./backup_clinic_db.sh

# Verify recent backup
ls -lh backups/

# Restore from backup (if needed)
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz
```

---

## Deployment with Your Current Data

**Important:** If you want to migrate your current database to Ubuntu/Termux, use this process:

### Step 1: Export Current Database (Current Server)

```bash
cd db-setup
chmod +x pre_backup_export.sh
./pre_backup_export.sh
```

This exports your current `clinic_management` database as a compressed backup file.

### Step 2: Deploy to Ubuntu Server

1. Copy entire `db-setup` folder to Ubuntu server (includes your backup)
2. On Ubuntu, run:

```bash
cd clinicapp/db-setup
chmod +x *.sh
./install_clinic_db.sh --restore-backup
```

The installation will:
- Create database and user
- Restore all your current data
- Verify database integrity

**Result:** Ubuntu database contains all your current data!

**See:** [UBUNTU_DEPLOYMENT_WITH_DATA.md](UBUNTU_DEPLOYMENT_WITH_DATA.md) for complete step-by-step guide.

---

## Scripts Overview

### 1. `install_clinic_db.sh`

**Purpose:** Automated database installation and initialization

**What it does:**
- Checks MariaDB service is running
- Tests root database connection
- Creates `clinic_management` database (UTF8MB4 encoding)
- Creates application user (`ziabul`) with secure password
- Optionally restores pre-deployment backup (all your current data)
- Runs SQL migrations from `migrations/` directory (if backup not restored)
- Grants necessary privileges to application user
- Verifies installation by counting tables

**Usage:**

```bash
# Standard installation (creates fresh database)
./install_clinic_db.sh

# Install with data restoration (restore pre-exported database)
./install_clinic_db.sh --restore-backup

# Force reinstall (drops existing database, recreates from scratch)
./install_clinic_db.sh --force

# Skip creating backup of existing database
./install_clinic_db.sh --skip-backup

# Combine flags
./install_clinic_db.sh --force --skip-backup
```

**Flags:**
- `--restore-backup` - Restore pre-deployment backup if available (recommended for migrations)
- `--force` - Force reinstall, drop existing database
- `--skip-backup` - Skip pre-backup of existing database

**Exit Codes:**
- `0` - Success
- `1` - Error (see log file for details)

**Output Example:**
```
════════════════════════════════════════════════════════════════════
 Clinic App Database Installation
════════════════════════════════════════════════════════════════════
[✓] 2024-01-15 14:30:22 - MariaDB service is running
[✓] 2024-01-15 14:30:23 - Connected to MariaDB as root
[→] 2024-01-15 14:30:24 - Creating database 'clinic_management'...
[✓] 2024-01-15 14:30:25 - Database created successfully
[→] 2024-01-15 14:30:26 - Creating application user 'ziabul'...
[✓] 2024-01-15 14:30:27 - User created successfully
[→] 2024-01-15 14:30:28 - Running migrations...
[✓] 2024-01-15 14:30:35 - Migration 001 applied
[✓] 2024-01-15 14:30:36 - Migration 002 applied
[✓] 2024-01-15 14:30:45 - Migration 003 applied
[✓] 2024-01-15 14:30:46 - Migration 004 applied
[✓] 2024-01-15 14:30:47 - Migration 005 applied
[✓] 2024-01-15 14:30:48 - Migration 006 applied
[✓] 2024-01-15 14:30:50 - Privileges granted to 'ziabul'
[✓] 2024-01-15 14:30:51 - Installation verified (45 tables created)
════════════════════════════════════════════════════════════════════
Installation completed successfully!
```

### 2. `backup_clinic_db.sh`

**Purpose:** Create compressed database backups with automatic rotation and verification

**What it does:**
- Creates mysqldump of entire `clinic_management` database
- Compresses backup with gzip (level 9)
- Creates `.info` metadata file with backup details
- Verifies backup integrity using gzip test
- Automatically removes backups older than 30 days
- Logs all operations with timestamps

**File Output:**
- Backup file: `backups/clinic_backup_YYYYMMDD_HHMMSS.sql.gz` (~2-5 MB)
- Metadata file: `backups/clinic_backup_YYYYMMDD_HHMMSS.info` (plain text)

**Usage:**

```bash
# Create standard backup
./backup_clinic_db.sh

# Create full backup (includes routines, triggers, events)
./backup_clinic_db.sh --full

# Create backup and force daily flag
./backup_clinic_db.sh --daily
```

**Sample .info File:**
```
Backup Information
==================
Timestamp: 2024-01-15 14:30:22
Database: clinic_management
User: root
Backup Type: full
Compressed: yes
File Size: 2.4 MB
Restore Command: restore_clinic_db.sh --file clinic_backup_20240115_143022.sql.gz
```

**Output Example:**
```
[✓] 2024-01-15 14:30:22 - Connected to MariaDB (root)
[→] 2024-01-15 14:30:23 - Dumping database 'clinic_management'...
[✓] 2024-01-15 14:30:26 - Dump completed (18.2 MB uncompressed)
[→] 2024-01-15 14:30:27 - Compressing backup...
[✓] 2024-01-15 14:30:30 - Backup compressed (2.4 MB)
[→] 2024-01-15 14:30:31 - Verifying backup integrity...
[✓] 2024-01-15 14:30:32 - Backup verified successfully
[→] 2024-01-15 14:30:33 - Cleaning up old backups (30-day retention)...
[✓] 2024-01-15 14:30:33 - Backup completed: clinic_backup_20240115_143022.sql.gz
```

### 3. `restore_clinic_db.sh`

**Purpose:** Restore database from backup with automatic safety features and rollback

**What it does:**
- Verifies backup file exists and is readable
- Tests MariaDB connection
- Creates safety backup of current database (before restore)
- Prompts user for confirmation
- Drops old database and recreates it
- Restores from backup
- Verifies table count matches backup
- Automatic rollback to safety backup if restore fails

**Safety Features:**
- Pre-restore safety backup: `backups/pre_restore_safety_YYYYMMDD_HHMMSS.sql.gz`
- User confirmation prompt (type "yes" to proceed)
- Automatic rollback on restore failure
- Table count verification before/after

**Usage:**

```bash
# Restore from specific backup
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz

# Verify backup without restoring (check if backup is valid)
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz --verify-only

# Find latest backup and restore
./restore_clinic_db.sh --file $(ls -t backups/clinic_backup_*.sql.gz | head -1)
```

**Output Example (Verification Mode):**
```
[✓] 2024-01-15 14:30:22 - Backup file found: backups/clinic_backup_20240115_143022.sql.gz
[✓] 2024-01-15 14:30:23 - Backup file is readable
[✓] 2024-01-15 14:30:24 - Verified backup integrity
[✓] 2024-01-15 14:30:25 - Connected to MariaDB (root)
Backup Status: VALID ✓
File Size: 2.4 MB
```

**Output Example (Restore Mode):**
```
[✓] 2024-01-15 14:30:22 - Backup file verified
[✓] 2024-01-15 14:30:23 - Connected to MariaDB (root)
[→] 2024-01-15 14:30:24 - Creating safety backup of current database...
[✓] 2024-01-15 14:30:28 - Safety backup created: pre_restore_safety_20240115_143024.sql.gz

WARNING: This will DROP and RECREATE the 'clinic_management' database!
Are you sure? (type 'yes' to proceed): yes

[→] 2024-01-15 14:30:30 - Dropping existing database...
[✓] 2024-01-15 14:30:31 - Database dropped
[→] 2024-01-15 14:30:32 - Creating new database...
[✓] 2024-01-15 14:30:33 - Database created
[→] 2024-01-15 14:30:34 - Restoring from backup...
[✓] 2024-01-15 14:30:45 - Database restored successfully
[✓] 2024-01-15 14:30:46 - Restore verified (45 tables)
════════════════════════════════════════════════════════════════════
Restore completed successfully!
```

---

## Configuration

All configuration is in `config/db-config.sh`. Modify this file to customize settings:

```bash
# Database Credentials
DB_ROOT_USER="root"              # MariaDB root user
DB_ROOT_PASS="root123"           # MariaDB root password
DB_APP_USER="ziabul"             # Application database user
DB_APP_PASS="4080099"            # Application user password
DB_HOST="127.0.0.1"              # Database host
DB_PORT="3306"                   # Database port
DB_NAME="clinic_management"      # Database name

# Backup Settings
BACKUP_RETENTION_DAYS=30         # Keep backups for 30 days
BACKUP_COMPRESS=true             # Compress backups with gzip
BACKUP_LEVEL=9                   # Gzip compression level (1-9)

# Directory Paths (relative to script location)
BACKUP_DIR="./backups"
LOG_DIR="./logs"
MIGRATION_DIR="./migrations"

# Log Settings
LOG_FILE="$LOG_DIR/clinic-db-$(date +%Y%m%d).log"
LOG_LEVEL="INFO"                 # DEBUG, INFO, WARN, ERROR
```

### Customization Examples

**Change backup retention to 60 days:**
```bash
BACKUP_RETENTION_DAYS=60
```

**Use different database credentials:**
```bash
DB_APP_USER="clinic_app"
DB_APP_PASS="your_secure_password_here"
```

**Use TCP connection instead of socket (for remote servers):**
```bash
DB_HOST="192.168.1.100"
DB_PORT="3306"
```

---

## Installation

### Prerequisites

- Ubuntu/Termux environment with bash 4.x or later
- MariaDB 10.x installed and running
- MySQL client tools: `mysql`, `mysqldump`
- Standard utilities: `gzip`, `date`, `grep`, `awk`

### Initial Setup

```bash
# 1. Navigate to db-setup directory
cd /path/to/clinicapp/db-setup

# 2. Make scripts executable
chmod +x *.sh
chmod 600 config/db-config.sh  # Secure credentials file

# 3. Verify MariaDB is running
sudo systemctl status mariadb

# 4. Test MySQL connectivity
mysql -u root -p -e "SELECT VERSION();"

# 5. Run installation
./install_clinic_db.sh
```

### Verify Installation

```bash
# Check if database exists
mysql -u root -p -e "SHOW DATABASES;" | grep clinic_management

# Check if app user exists
mysql -u root -p -e "SELECT user FROM mysql.user;" | grep ziabul

# Check table count
mysql -u ziabul -p4080099 clinic_management -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema='clinic_management';"

# Connect as app user (should work without password)
mysql -u ziabul -p clinic_management -e "SELECT COUNT(*) FROM appointments;"
```

---

## Backup & Restore

### Backup Strategies

#### Daily Backups (Recommended)
```bash
# Create backup every day at 2 AM
# Add to crontab: crontab -e
0 2 * * * /path/to/db-setup/backup_clinic_db.sh >> /path/to/db-setup/logs/backup-cron.log 2>&1
```

#### Full Backup (Weekly)
```bash
# Create comprehensive backup every Sunday at 3 AM
0 3 * * 0 /path/to/db-setup/backup_clinic_db.sh --full >> /path/to/db-setup/logs/backup-cron.log 2>&1
```

### Restore Scenarios

**Scenario 1: Restore to Most Recent Backup**
```bash
./restore_clinic_db.sh --file $(ls -t backups/clinic_backup_*.sql.gz | head -1)
```

**Scenario 2: Restore to Specific Date**
```bash
# Restore from January 15, 2024
./restore_clinic_db.sh --file backups/clinic_backup_20240115_*.sql.gz
```

**Scenario 3: Verify Backup Before Restore**
```bash
# Check if backup is valid
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz --verify-only

# If valid, restore
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz
```

**Scenario 4: Transfer Database Between Servers**
```bash
# On source server: Create backup
./backup_clinic_db.sh

# Copy backup to destination
scp backup_clinic_db.sql.gz user@dest-server:/path/to/db-setup/backups/

# On destination server: Restore
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz
```

### Backup File Management

```bash
# List all backups with sizes
ls -lh backups/clinic_backup_*.sql.gz

# Count total backups
ls backups/clinic_backup_*.sql.gz | wc -l

# View backup metadata
cat backups/clinic_backup_20240115_143022.info

# Find backups older than 30 days
find backups/ -name "clinic_backup_*.sql.gz" -mtime +30

# Manually delete old backup
rm backups/clinic_backup_20240110_*.sql.gz
```

---

## Troubleshooting

### Issue: "MariaDB service is not running"

**Solution:**
```bash
# Start MariaDB service
sudo systemctl start mariadb

# Enable auto-start on boot
sudo systemctl enable mariadb

# Verify running
sudo systemctl status mariadb
```

### Issue: "Access denied for user 'root'"

**Solution:**
```bash
# Check config credentials
cat config/db-config.sh | grep DB_ROOT

# Test connection manually
mysql -u root -p -e "SELECT VERSION();"

# If password is blank (default)
mysql -u root -e "SELECT VERSION();"

# Update config with correct password
nano config/db-config.sh
```

### Issue: "Database 'clinic_management' already exists"

**Solution:**
```bash
# Option 1: Use --force to drop and recreate
./install_clinic_db.sh --force

# Option 2: Manually drop database
mysql -u root -p -e "DROP DATABASE clinic_management;"

# Then run installation again
./install_clinic_db.sh
```

### Issue: "Backup verification failed"

**Solution:**
```bash
# Check backup file size
ls -lh backups/clinic_backup_*.sql.gz

# Test gzip integrity manually
gzip -t backups/clinic_backup_20240115_143022.sql.gz

# If corrupted, try previous backup
ls -t backups/clinic_backup_*.sql.gz | head -5

# Restore from known good backup
./restore_clinic_db.sh --file backups/clinic_backup_20240114_020000.sql.gz --verify-only
```

### Issue: "Restore failed - insufficient disk space"

**Solution:**
```bash
# Check available disk space
df -h

# Check backup file size
ls -lh backups/clinic_backup_*.sql.gz

# Free up space
rm backups/clinic_backup_*_old.sql.gz  # Remove old backups

# Try restore again
./restore_clinic_db.sh --file backups/clinic_backup_20240115_143022.sql.gz
```

### Issue: "Permission denied" on scripts

**Solution:**
```bash
# Make scripts executable
chmod +x *.sh

# Make config readable but secure
chmod 600 config/db-config.sh

# Make backup directory writable
chmod 755 backups/ logs/

# Run with proper permissions
./install_clinic_db.sh
```

### View Logs

```bash
# View today's log
cat logs/clinic-db-20240115.log

# Monitor logs in real-time
tail -f logs/clinic-db-20240115.log

# Search for errors
grep ERROR logs/clinic-db-20240115.log

# Search for specific backup
grep "clinic_backup_20240115" logs/clinic-db-20240115.log
```

---

## Advanced Usage

### Manual Database Queries

```bash
# Connect as app user
mysql -u ziabul -p clinic_management

# Or with password in command (not recommended for security)
mysql -u ziabul -p4080099 clinic_management

# Run query from command line
mysql -u ziabul -p4080099 clinic_management -e "SELECT COUNT(*) FROM appointments;"

# Execute SQL file
mysql -u ziabul -p4080099 clinic_management < custom_migration.sql
```

### Custom Migrations

```bash
# Create new migration
cat > migrations/007_custom_migration.sql << 'EOF'
-- Add custom column
ALTER TABLE appointments ADD COLUMN custom_field VARCHAR(255);
EOF

# Run migration manually
mysql -u root -p clinic_management < migrations/007_custom_migration.sql

# Or re-run full installation
./install_clinic_db.sh --force
```

### Database Statistics

```bash
# Get database size
mysql -u root -p -e "SELECT 
    table_schema as 'Database',
    SUM(data_length + index_length)/1024/1024 as 'Size in MB'
FROM information_schema.tables
WHERE table_schema = 'clinic_management'
GROUP BY table_schema;"

# Get table row counts
mysql -u ziabul -p4080099 clinic_management -e "SELECT 
    TABLE_NAME,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'clinic_management'
ORDER BY TABLE_ROWS DESC;"
```

### Performance Tuning

```bash
# Optimize all tables
mysql -u ziabul -p4080099 clinic_management -e "OPTIMIZE TABLE *;"

# Repair corrupted tables (if any)
mysql -u root -p clinic_management -e "CHECK TABLE *; REPAIR TABLE *;"

# Analyze tables for query optimization
mysql -u ziabul -p4080099 clinic_management -e "ANALYZE TABLE *;"
```

---

## Support & Maintenance

### Monthly Maintenance

```bash
# 1. Verify recent backups
ls -lh backups/clinic_backup_*.sql.gz | head -5

# 2. Check log file sizes (rotate if needed)
du -sh logs/

# 3. Optimize database tables
mysql -u ziabul -p4080099 clinic_management -e "OPTIMIZE TABLE *;"

# 4. Verify app user privileges
mysql -u root -p -e "SHOW GRANTS FOR 'ziabul'@'localhost';"
```

### Automated Maintenance (Cron Job)

```bash
# Create maintenance script
cat > scripts/db_maintenance.sh << 'EOF'
#!/bin/bash
source "$(dirname "$0")/../config/db-config.sh"

# Daily backup
"$(dirname "$0")/../backup_clinic_db.sh"

# Weekly optimization (Sundays)
if [ "$(date +%A)" = "Sunday" ]; then
    mysql -u ziabul -p${DB_APP_PASS} ${DB_NAME} -e "OPTIMIZE TABLE *;"
fi

# Monthly full backup (1st of month)
if [ "$(date +%d)" = "01" ]; then
    "$(dirname "$0")/../backup_clinic_db.sh" --full
fi
EOF

chmod +x scripts/db_maintenance.sh

# Add to crontab
crontab -e
# Add line: 2 * * * * /path/to/scripts/db_maintenance.sh
```

---

## Changelog

### Version 1.0 (Current)
- ✅ Initial release with install, backup, restore scripts
- ✅ Comprehensive error handling and logging
- ✅ Automated backup rotation (30-day retention)
- ✅ Database restore with rollback capability
- ✅ 6 migrations included (users, password reset, doctor profile, prescriptions, numbering, notifications)

---

## License

These scripts are part of the Clinic App project and are provided as-is for internal use.

---

**Last Updated:** January 15, 2024  
**Tested On:** Ubuntu 20.04 LTS, Termux, MariaDB 10.5+
