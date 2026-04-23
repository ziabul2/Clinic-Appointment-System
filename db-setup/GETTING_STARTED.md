# GETTING STARTED - Clinic App Database Setup

Complete step-by-step guide to get your clinic database running on Ubuntu/Termux.

## Prerequisites Checklist

Before you begin, ensure you have:

- [ ] Ubuntu 20.04+ or Termux environment
- [ ] MariaDB 10.5+ installed
- [ ] Apache 2.4+ (optional, for web interface)
- [ ] Bash 4.x or later
- [ ] SSH access to server (if remote)
- [ ] MySQL client tools installed (`apt install mariadb-client`)

## Step 1: Verify MariaDB Installation

```bash
# Check if MariaDB is installed
mariadb --version

# Check if service is running
sudo systemctl status mariadb

# If not running, start it
sudo systemctl start mariadb
sudo systemctl enable mariadb  # Auto-start on boot
```

## Step 2: Navigate to Database Setup Directory

```bash
# Change to the db-setup directory
cd /path/to/clinicapp/db-setup

# Verify directory structure
ls -la
# Expected output should show:
# - backups/ (directory)
# - migrations/ (directory)
# - logs/ (directory)
# - config/ (directory)
# - install_clinic_db.sh
# - backup_clinic_db.sh
# - restore_clinic_db.sh
# - lib-functions.sh
# - README.md
```

## Step 3: Make Scripts Executable

```bash
# Make bash scripts executable
chmod +x *.sh

# Secure config file (readable only by owner)
chmod 600 config/db-config.sh

# Verify permissions
ls -l *.sh config/db-config.sh
```

## Step 4: Configure Database Credentials (Optional)

The configuration is pre-filled, but you can customize it:

```bash
# Edit configuration file
nano config/db-config.sh

# Key settings:
# DB_ROOT_USER="root"              # MariaDB root username
# DB_ROOT_PASS="root123"           # MariaDB root password
# DB_APP_USER="ziabul"             # Application database user
# DB_APP_PASS="4080099"            # Application user password
# DB_HOST="127.0.0.1"              # Database host (localhost)
# DB_PORT="3306"                   # MariaDB port
# DB_NAME="clinic_management"      # Database name
# BACKUP_RETENTION_DAYS=30         # Keep backups for 30 days

# Save changes: Ctrl+O, Enter, Ctrl+X
```

## Step 5: Test MariaDB Connection

Before running installation, verify you can connect to MariaDB:

```bash
# Test root connection
mysql -u root -p -e "SELECT VERSION();"

# You'll be prompted for password. Enter: root123
# Expected output:
# +---------------------+
# | VERSION()           |
# +---------------------+
# | 10.5.x-MariaDB...   |
# +---------------------+

# If connection fails, check:
# 1. Is MariaDB running? sudo systemctl start mariadb
# 2. Is password correct? Check config/db-config.sh
# 3. Try without password: mysql -u root -e "SELECT VERSION();"
```

## Step 6: Install Database

Run the installation script:

```bash
./install_clinic_db.sh
```

Expected output (abbreviated):

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
[✓] 2024-01-15 14:30:37 - Migration 003 applied
[✓] 2024-01-15 14:30:38 - Migration 004 applied
[✓] 2024-01-15 14:30:39 - Migration 005 applied
[✓] 2024-01-15 14:30:40 - Migration 006 applied
[✓] 2024-01-15 14:30:50 - Privileges granted to 'ziabul'
[✓] 2024-01-15 14:30:51 - Installation verified (45 tables created)
════════════════════════════════════════════════════════════════════
Installation completed successfully!
```

## Step 7: Verify Installation

```bash
# Check if database exists
mysql -u root -p -e "SHOW DATABASES;" | grep clinic_management

# Check if app user exists
mysql -u root -p -e "SELECT user, host FROM mysql.user;" | grep ziabul

# Check table count
mysql -u ziabul -p clinic_management -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema='clinic_management';"
# Expected: table_count = 45

# List all tables
mysql -u ziabul -p clinic_management -e "SHOW TABLES;"
```

## Step 8: Create First Backup

```bash
# Create backup
./backup_clinic_db.sh

# Expected output:
# [✓] 2024-01-15 14:30:22 - Connected to MariaDB (root)
# [→] 2024-01-15 14:30:23 - Dumping database 'clinic_management'...
# [✓] 2024-01-15 14:30:26 - Dump completed (18.2 MB uncompressed)
# [→] 2024-01-15 14:30:27 - Compressing backup...
# [✓] 2024-01-15 14:30:30 - Backup compressed (2.4 MB)
# [→] 2024-01-15 14:30:31 - Verifying backup integrity...
# [✓] 2024-01-15 14:30:32 - Backup verified successfully
# [✓] 2024-01-15 14:30:33 - Backup completed: clinic_backup_20240115_143022.sql.gz

# Verify backup was created
ls -lh backups/clinic_backup_*.sql.gz
```

## Step 9: Update PHP Configuration

Update your PHP config to connect to the new database:

```bash
# Edit PHP database configuration
nano /path/to/clinicapp/config/database.php

# Ensure these match config/db-config.sh:
# define('DB_HOST', '127.0.0.1');
# define('DB_USER', 'ziabul');
# define('DB_PASS', '4080099');
# define('DB_NAME', 'clinic_management');
```

## Step 10: Setup Automated Backups (Optional but Recommended)

Create automatic daily backups:

```bash
# Edit crontab
crontab -e

# Add this line (creates backup every day at 2 AM):
0 2 * * * /path/to/clinicapp/db-setup/backup_clinic_db.sh >> /path/to/clinicapp/db-setup/logs/backup-cron.log 2>&1

# Save and exit

# Verify cron job was added
crontab -l
```

## Troubleshooting During Setup

### Issue: "MariaDB service is not running"

```bash
# Start the service
sudo systemctl start mariadb

# Enable auto-start
sudo systemctl enable mariadb

# Check status
sudo systemctl status mariadb
```

### Issue: "Access denied for user 'root'@'localhost'"

```bash
# Try connecting without password
mysql -u root -e "SELECT VERSION();"

# If that works, update config/db-config.sh to remove password
# Or update password in MariaDB:
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';"
```

### Issue: "Database 'clinic_management' already exists"

```bash
# Option 1: Drop existing database
mysql -u root -p -e "DROP DATABASE clinic_management;"

# Then run installation again
./install_clinic_db.sh

# Option 2: Force reinstall
./install_clinic_db.sh --force
```

### Issue: "Permission denied" on scripts

```bash
# Make scripts executable
chmod +x *.sh

# Run with bash explicitly
bash install_clinic_db.sh
```

### Issue: "Migrations failed to apply"

```bash
# Check log file
cat logs/clinic-db-$(date +%Y%m%d).log

# Verify migrations directory has SQL files
ls -l migrations/

# Try running migrations manually
mysql -u root -p clinic_management < migrations/001_add_doctor_id_to_users.sql
```

## Next Steps

1. **Update PHP Application Config**
   ```bash
   nano config/config.php
   # Verify SITE_URL, database credentials match
   ```

2. **Test PHP Database Connection**
   ```bash
   php -r "include 'config/database.php'; echo 'Connection successful';"
   ```

3. **Access Web Application**
   ```
   http://localhost/clinicapp
   http://your-server-ip/clinicapp
   ```

4. **Monitor Backups**
   ```bash
   # Check backup logs
   tail -f logs/backup-cron.log
   
   # List backups
   ls -lh backups/
   ```

5. **Setup Regular Maintenance**
   - Review logs weekly
   - Test restore procedures monthly
   - Update documentation as needed

## Common Commands Reference

```bash
# Navigate to db-setup
cd /path/to/clinicapp/db-setup

# Create backup
./backup_clinic_db.sh

# Verify backup
./restore_clinic_db.sh --file backups/clinic_backup_YYYYMMDD_*.sql.gz --verify-only

# Restore from backup
./restore_clinic_db.sh --file backups/clinic_backup_YYYYMMDD_*.sql.gz

# View today's logs
cat logs/clinic-db-$(date +%Y%m%d).log

# Monitor logs in real-time
tail -f logs/clinic-db-$(date +%Y%m%d).log

# Reinstall database
./install_clinic_db.sh --force

# Connect to database as app user
mysql -u ziabul -p clinic_management

# Check backup sizes
du -sh backups/*

# Find old backups
find backups/ -name "*.sql.gz" -mtime +30
```

## Support & Help

- **Comprehensive Documentation:** See `README.md`
- **Quick Reference:** See `QUICK_REFERENCE.sh`
- **Logs Location:** `logs/clinic-db-YYYYMMDD.log`
- **Configuration:** `config/db-config.sh`
- **Migrations:** `migrations/*.sql`

---

**Setup Complete!** Your clinic database is now ready for use. 🎉

For detailed documentation, see README.md or QUICK_REFERENCE.sh
