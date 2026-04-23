# Emergency Restore Procedures — ClinicApp

**Last updated:** November 25, 2025

This document covers emergency recovery procedures for the ClinicApp database and system. Use these steps when the system experiences data loss, corruption, or catastrophic failure.

---

## Table of Contents

1. [Quick Start Recovery (TL;DR)](#quick-start-recovery-tldr)
2. [Database Backup Locations](#database-backup-locations)
3. [Full Database Restoration](#full-database-restoration)
4. [Partial Recovery (Single Table)](#partial-recovery-single-table)
5. [Corruption Detection & Repair](#corruption-detection--repair)
6. [Schema Rebuild from Migration](#schema-rebuild-from-migration)
7. [Data Validation After Restore](#data-validation-after-restore)
8. [Rollback to Last Good Backup](#rollback-to-last-good-backup)
9. [Prevention & Monitoring](#prevention--monitoring)

---

## Quick Start Recovery (TL;DR)

**If the database is completely broken:**

```powershell
# 1. Stop Apache & MySQL
Stop-Service -Name "Apache2.4" -Force
Stop-Service -Name "MySQL80" -Force

# 2. Drop and recreate the database
"c:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS clinic_management; CREATE DATABASE clinic_management;"

# 3. Import the most recent backup
Get-Content "c:\xampp\htdocs\clinicapp\archive\database_backup.sql" | & "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management

# 4. Restart services
Start-Service -Name "Apache2.4"
Start-Service -Name "MySQL80"

# 5. Test the app
Start-Process "http://localhost/clinicapp/"
```

If that doesn't work, proceed to the full recovery steps below.

---

## Database Backup Locations

Backups are stored in multiple locations:

### Primary Backup
- **Location:** `c:\xampp\htdocs\clinicapp\archive\database_backup.sql`
- **Type:** SQL dump (human-readable, portable)
- **Frequency:** Manual or via backup script
- **Size:** ~2-5 MB (typical)

### Archive Backups
- **Location:** `c:\xampp\htdocs\clinicapp\archive\`
- **Pattern:** `backup_*.sql` (timestamped backups)
- **Retention:** Last 5-10 backups kept
- **Manual Backup Script:** `c:\xampp\htdocs\clinicapp\backup_db.php`

### Recovery Tools Location
- **DB Inspection Tool:** `c:\xampp\htdocs\clinicapp\db_inspect.php`
- **DB Test Tool:** `c:\xampp\htdocs\clinicapp\db_test.php`
- **Migration Runner:** `c:\xampp\htdocs\clinicapp\migrate_db.php`
- **Migration Helper:** `c:\xampp\htdocs\clinicapp\apply_migration_006.php` (for recurrence features)

---

## Full Database Restoration

### Step 1: Verify Backups Exist

```powershell
# List all available backups
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\*.sql" | Sort-Object LastWriteTime -Descending | Select-Object Name, LastWriteTime, Length
```

If no backups exist, skip to **Schema Rebuild from Migration** (Section 6).

### Step 2: Stop Services

```powershell
# Stop Apache and MySQL to prevent conflicts
Stop-Service -Name "Apache2.4" -Force -ErrorAction SilentlyContinue
Stop-Service -Name "MySQL80" -Force -ErrorAction SilentlyContinue
# Alternative if using different MySQL service name:
Stop-Service -Name "MySQL57" -Force -ErrorAction SilentlyContinue
```

### Step 3: Backup Current Database (for forensics)

```powershell
# Save corrupted DB as evidence (optional but recommended)
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
& "c:\xampp\mysql\bin\mysqldump.exe" -u root clinic_management > "c:\xampp\htdocs\clinicapp\archive\corrupted_backup_${timestamp}.sql" 2>$null
Write-Host "Saved corrupted database to forensics backup."
```

### Step 4: Drop and Recreate Database

```powershell
# This erases the corrupted data
& "c:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS clinic_management; CREATE DATABASE clinic_management;"
Write-Host "Database dropped and recreated."
```

### Step 5: Import Backup

```powershell
# Identify the backup to restore (choose the most recent one)
$backupFile = "c:\xampp\htdocs\clinicapp\archive\database_backup.sql"

# Verify file exists
if (!(Test-Path $backupFile)) {
    Write-Error "Backup file not found: $backupFile"
    exit 1
}

# Import the backup
Write-Host "Importing backup from $backupFile..."
Get-Content $backupFile | & "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management
Write-Host "Backup imported successfully."
```

### Step 6: Restart Services

```powershell
# Start services
Start-Service -Name "Apache2.4"
Start-Service -Name "MySQL80"
Write-Host "Services restarted."

# Wait for services to stabilize
Start-Sleep -Seconds 3

# Test connectivity
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "SELECT 'Connection OK' as status;" 2>&1
```

### Step 7: Validate Restored Data

See **Data Validation After Restore** (Section 7) below.

---

## Partial Recovery (Single Table)

If only one table is corrupted or missing:

### Option A: Restore from Backup (Targeted)

```powershell
# Extract specific table from backup
$backupFile = "c:\xampp\htdocs\clinicapp\archive\database_backup.sql"

# Search for the table structure and data
$tableName = "appointments"
$tableLines = @()
$capturing = $false

Get-Content $backupFile | ForEach-Object {
    if ($_ -match "CREATE TABLE.*$tableName") {
        $capturing = $true
    }
    if ($capturing) {
        $tableLines += $_
        if ($_ -match "^;$" -and $capturing) {
            $capturing = $false
        }
    }
}

# Print extracted SQL (review before applying)
$tableLines | Head -20
```

### Option B: Rebuild Table from Migration

```powershell
# Run the migrations that include the missing table
php "c:\xampp\htdocs\clinicapp\migrate_db.php"
```

### Option C: Restore Single Table via MySQL

```powershell
# Drop the corrupted table
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "DROP TABLE IF EXISTS appointments;"

# Extract and apply only that table from backup (advanced)
# This requires manual SQL editing — not recommended for beginners
```

---

## Corruption Detection & Repair

### Check for Table Corruption

```powershell
# Run the built-in diagnostic
php "c:\xampp\htdocs\clinicapp\db_inspect.php"
```

This script checks:
- Column existence and types
- Foreign key constraints
- Data integrity issues
- Missing indexes

### Automatic Repair (MySQL)

```powershell
# Repair all tables in the database
& "c:\xampp\mysql\bin\mysqlcheck.exe" -u root --repair clinic_management
```

**Output Example:**
```
clinic_management.appointments                            OK
clinic_management.patients                                OK
clinic_management.doctors                                 OK
```

### Optimize Tables (Recommended After Repair)

```powershell
# Optimize all tables to reclaim space and improve performance
& "c:\xampp\mysql\bin\mysqlcheck.exe" -u root --optimize clinic_management
```

---

## Schema Rebuild from Migration

If the entire database is lost and no backup is available:

### Step 1: Create Empty Database

```powershell
& "c:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE clinic_management;"
```

### Step 2: Run All Migrations

```powershell
# Run the migration runner (builds schema from scratch)
php "c:\xampp\htdocs\clinicapp\migrate_db.php"
```

This creates:
- All tables (users, patients, doctors, appointments, etc.)
- All columns and indexes
- Foreign key constraints

### Step 3: Seed Initial Data (Optional)

```powershell
# If you have a users import CSV, run:
php "c:\xampp\htdocs\clinicapp\import_cli.php" "c:\xampp\htdocs\clinicapp\archive\test_users_import.csv"

# Or use the web UI at: http://localhost/clinicapp/pages/users.php
```

### Result

You now have:
- ✓ Complete schema
- ✓ Empty data (no patient/appointment records, but system is functional)
- ✓ Ready for user sign-in and new data entry

---

## Data Validation After Restore

After restoring from a backup, validate the data integrity:

### Quick Health Check

```powershell
php "c:\xampp\htdocs\clinicapp\db_test.php"
```

This tests:
- Database connection
- Table existence
- Basic SELECT queries
- Insert/Update capability

### Detailed Validation

```powershell
# Check record counts for critical tables
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT 'Users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'Patients', COUNT(*) FROM patients
UNION ALL
SELECT 'Doctors', COUNT(*) FROM doctors
UNION ALL
SELECT 'Appointments', COUNT(*) FROM appointments
UNION ALL
SELECT 'Recurrence Rules', COUNT(*) FROM recurrence_rules
ORDER BY record_count DESC;
"
```

### Check for Orphaned Records

```powershell
# Find appointments with non-existent patients (data corruption indicator)
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT a.appointment_id, a.patient_id, a.doctor_id 
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.patient_id
WHERE p.patient_id IS NULL
LIMIT 10;
"

# Find appointments with non-existent doctors
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT a.appointment_id, a.patient_id, a.doctor_id 
FROM appointments a
LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
WHERE d.doctor_id IS NULL
LIMIT 10;
"
```

If orphaned records found, decide:
- **Option 1:** Delete orphaned records (if inconsequential)
- **Option 2:** Restore from earlier backup without corruption
- **Option 3:** Manually fix references via SQL UPDATE

### Verify User Accounts

```powershell
# Confirm admin/receptionist users can still access the app
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT user_id, username, role, created_at FROM users WHERE role IN ('admin','receptionist') LIMIT 5;
"
```

---

## Rollback to Last Good Backup

If a recent change corrupted the data, revert to the backup from before that change:

### Step 1: Identify Backup Timeline

```powershell
# List all backup files with modification dates
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object Name, LastWriteTime, @{Name="Size MB"; Expression={[math]::Round($_.Length/1MB, 2)}}
```

**Example Output:**
```
Name                           LastWriteTime         Size MB
----                           -------------         -------
backup_20251124_180000.sql     11/24/2025 18:00:00   4.2
backup_20251124_120000.sql     11/24/2025 12:00:00   4.1
backup_20251123_180000.sql     11/23/2025 18:00:00   3.8
```

### Step 2: Choose Appropriate Backup

- Select the backup **before** the suspected corruption occurred
- Typically 1-2 backups ago
- If unsure, restore to daily backup from 1-2 days prior

### Step 3: Perform Full Restoration

Follow **Full Database Restoration** (Section 3) using the older backup file:

```powershell
$backupFile = "c:\xampp\htdocs\clinicapp\archive\backup_20251123_180000.sql"
Get-Content $backupFile | & "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management
```

### Step 4: Notify Users

After rollback, newer data (after the backup timestamp) is lost. Notify users:
- "Database restored to [date/time]"
- "Any appointments or changes made after [time] are lost. Please re-enter them."

---

## Prevention & Monitoring

### Enable Automated Backups

Create a Windows Task Scheduler job to run backups automatically:

#### Option A: PowerShell Script (Recommended)

Create `c:\xampp\htdocs\clinicapp\backupAutomotion\daily_backup.ps1`:

```powershell
# Daily backup script for ClinicApp
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "c:\xampp\htdocs\clinicapp\archive"
$backupFile = "$backupDir\backup_$timestamp.sql"

Write-Host "Starting database backup at $(Get-Date)..."

# Export database
& "c:\xampp\mysql\bin\mysqldump.exe" -u root clinic_management | Out-File -Encoding UTF8 -FilePath $backupFile
Write-Host "Backup created: $backupFile"

# Keep only last 10 backups (cleanup old files)
$backups = Get-ChildItem "$backupDir\backup_*.sql" | Sort-Object LastWriteTime -Descending
if ($backups.Count -gt 10) {
    $toDelete = $backups | Select-Object -Skip 10
    $toDelete | Remove-Item -Force
    Write-Host "Deleted $(($toDelete | Measure-Object).Count) old backups."
}

Write-Host "Backup complete."
```

#### Option B: Windows Task Scheduler

1. Open **Task Scheduler** (`taskschd.msc`)
2. Create Basic Task:
   - **Name:** "ClinicApp Daily Backup"
   - **Trigger:** Daily at 2:00 AM
   - **Action:** Run PowerShell script
   - **Script:** Path to the backup script above

### Monitor Backup Success

Create a PowerShell script to verify backups are being created:

```powershell
# Check backup age (should be < 24 hours for daily backups)
$latestBackup = Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
$age = (Get-Date) - $latestBackup.LastWriteTime

if ($age.TotalHours -gt 24) {
    Write-Warning "WARNING: Latest backup is $([math]::Round($age.TotalHours, 1)) hours old!"
} else {
    Write-Host "✓ Backup is current ($([math]::Round($age.TotalMinutes, 1)) minutes old)"
}
```

Run this check weekly and alert if backups fail.

### Database Maintenance Schedule

- **Daily:** Automated backups (via Task Scheduler)
- **Weekly:** Manual backup verification + checksum test
- **Monthly:** Full database optimization (`mysqlcheck --optimize`)
- **Quarterly:** Disaster recovery drill (test full restoration)

### Create Backup Checksums

Verify backup file integrity:

```powershell
# Generate checksum for each backup
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | ForEach-Object {
    $hash = (Get-FileHash $_.FullName -Algorithm SHA256).Hash
    Add-Content "c:\xampp\htdocs\clinicapp\archive\BACKUP_CHECKSUMS.txt" "$($_.Name)`t$hash"
    Write-Host "$($_.Name): $hash"
}

# Verify a backup hasn't been corrupted
$backupFile = "c:\xampp\htdocs\clinicapp\archive\backup_20251124_120000.sql"
$storedHash = (Select-String -Path "c:\xampp\htdocs\clinicapp\archive\BACKUP_CHECKSUMS.txt" -Pattern (Split-Path $backupFile -Leaf)).Line.Split("`t")[1]
$currentHash = (Get-FileHash $backupFile -Algorithm SHA256).Hash

if ($storedHash -eq $currentHash) {
    Write-Host "✓ Backup integrity verified"
} else {
    Write-Warning "✗ Backup file may be corrupted"
}
```

### Off-Site Backup (Optional but Recommended)

For production systems, maintain off-site backups:

```powershell
# Copy latest backup to an external drive or cloud storage
$latestBackup = Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
Copy-Item $latestBackup.FullName "E:\Backups\ClinicApp\" -Force
Write-Host "Backup copied to external drive."
```

---

## Troubleshooting

### Problem: "MySQL service won't start after restore"

**Solution:**
1. Check MySQL error log: `C:\xampp\mysql\data\*.err`
2. Verify disk space: `Get-PSDrive C | Select-Object @{Name="Free GB"; Expression={[math]::Round($_.Free/1GB, 2)}}`
3. Restart MySQL with skip-grant-tables: See MySQL documentation
4. Contact XAMPP support or restore from older backup

### Problem: "Backup file is corrupted (can't parse SQL)"

**Solution:**
1. Try an older backup file
2. Verify file encoding is UTF-8: `Get-Content $file | Get-Encoding`
3. Check file size (should be > 1 MB for typical DB): `(Get-Item $file).Length / 1MB`
4. Try repairing with MySQL: `mysqlcheck --repair`

### Problem: "Restored but data is inconsistent"

**Solution:**
1. Run validation (Section 7)
2. Delete orphaned records: `DELETE FROM appointments WHERE patient_id NOT IN (SELECT patient_id FROM patients);`
3. Restore from earlier backup if issue persists
4. Check application logs: `c:\xampp\htdocs\clinicapp\logs\*.log`

### Problem: "Users can't log in after restore"

**Solution:**
```powershell
# Check if users table was restored
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "SELECT COUNT(*) as user_count FROM users;"

# If users are missing, re-import or re-create
php "c:\xampp\htdocs\clinicapp\import_cli.php"
```

---

## Emergency Contacts & Resources

- **XAMPP Support:** https://www.apachefriends.org/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **GitHub Repository:** (if applicable)
- **Local Backup Location:** `C:\xampp\htdocs\clinicapp\archive\`
- **Application Logs:** `C:\xampp\htdocs\clinicapp\logs\`
- **MySQL Logs:** `C:\xampp\mysql\data\`

---

## Testing Restoration (Recommended Quarterly)

Perform a test restore to verify backups work:

```powershell
# 1. Create a test database
& "c:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE clinic_management_test;"

# 2. Import latest backup into test DB
$backupFile = "c:\xampp\htdocs\clinicapp\archive\database_backup.sql"
Get-Content $backupFile | & "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management_test

# 3. Validate test DB
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management_test -e "SELECT COUNT(*) as users FROM users; SELECT COUNT(*) as patients FROM patients;"

# 4. Clean up test DB
& "c:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE clinic_management_test;"

Write-Host "✓ Restore test passed"
```

Schedule this test monthly to catch backup issues before they become critical.

---

**Document Version:** 1.0  
**Last Reviewed:** November 25, 2025  
**Next Review:** February 25, 2026

