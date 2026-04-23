# Emergency Restore Quick Reference Card

**Print this page and keep it accessible. In a crisis, use these commands step-by-step.**

---

## 🚨 Database is Down — IMMEDIATE RECOVERY (5 minutes)

### Step 1: Stop Services
```powershell
Stop-Service -Name "Apache2.4" -Force -ErrorAction SilentlyContinue
Stop-Service -Name "MySQL80" -Force -ErrorAction SilentlyContinue
```

### Step 2: Check for Backup
```powershell
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 3
```

**STOP** — if no files listed, you have no backup. Skip to **Schema Rebuild** below.

### Step 3: Drop Bad Database
```powershell
& "c:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS clinic_management; CREATE DATABASE clinic_management;"
```

### Step 4: Restore Backup
```powershell
Get-Content "c:\xampp\htdocs\clinicapp\archive\database_backup.sql" | & "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management
```

### Step 5: Restart Services
```powershell
Start-Service -Name "Apache2.4"
Start-Service -Name "MySQL80"
Start-Sleep -Seconds 3
```

### Step 6: Test
```powershell
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "SELECT 'OK' as status;"
Start-Process "http://localhost/clinicapp/"
```

✓ **If you see OK and the app loads, you're done. Stop here.**

---

## 🛠️ No Backup? Schema Rebuild (10 minutes)

```powershell
# Run migrations to rebuild schema from scratch
php "c:\xampp\htdocs\clinicapp\migrate_db.php"

# Restart and test
Start-Service -Name "Apache2.4"
Start-Service -Name "MySQL80"
php "c:\xampp\htdocs\clinicapp\db_test.php"
Start-Process "http://localhost/clinicapp/"
```

✓ You have an empty working database. Users can log in, but no appointment/patient data.

---

## 🔧 Repair Corrupted Data (Without Full Restore)

```powershell
# Check what's wrong
php "c:\xampp\htdocs\clinicapp\db_inspect.php"

# Auto-repair tables
& "c:\xampp\mysql\bin\mysqlcheck.exe" -u root --repair clinic_management

# Optimize
& "c:\xampp\mysql\bin\mysqlcheck.exe" -u root --optimize clinic_management
```

---

## 📋 Critical Checks (After Any Recovery)

```powershell
# 1. Database connection
php "c:\xampp\htdocs\clinicapp\db_test.php"

# 2. Record counts
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL SELECT 'Patients', COUNT(*) FROM patients
UNION ALL SELECT 'Doctors', COUNT(*) FROM doctors
UNION ALL SELECT 'Appointments', COUNT(*) FROM appointments;"

# 3. Check for orphaned records
& "c:\xampp\mysql\bin\mysql.exe" -u root clinic_management -e "
SELECT 'Orphaned Appointments' as issue, COUNT(*) FROM appointments a 
LEFT JOIN patients p ON a.patient_id = p.patient_id 
WHERE p.patient_id IS NULL;"

# 4. Test user login capability
php "c:\xampp\htdocs\clinicapp\pages\login.php"
```

---

## 📂 Important Paths (Copy these down)

| Item | Path |
|------|------|
| **Backup Files** | `C:\xampp\htdocs\clinicapp\archive\` |
| **Latest Backup** | `C:\xampp\htdocs\clinicapp\archive\database_backup.sql` |
| **Logs** | `C:\xampp\htdocs\clinicapp\logs\` |
| **Migrations** | `C:\xampp\htdocs\clinicapp\migrations\` |
| **Config** | `C:\xampp\htdocs\clinicapp\config\config.php` |
| **MySQL Bin** | `C:\xampp\mysql\bin\` |

---

## 🔄 Selecting Right Backup (Not Sure Which?)

```powershell
# List all backups with sizes and dates
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | Sort-Object LastWriteTime -Descending | Format-Table Name, LastWriteTime, @{Name="MB"; Expression={[math]::Round($_.Length/1MB, 2)}}
```

**Rule of thumb:**
- If recent corruption: use backup from **24 hours ago**
- If sudden crash: use most recent backup marked **"database_backup.sql"**
- If all backups seem old: use oldest available (less data, but stable)

---

## ⏱️ Estimated Recovery Times

| Scenario | Time | Steps |
|----------|------|-------|
| Full restore (5 MB DB) | 5 min | 6 |
| Schema rebuild | 10 min | 3 |
| Repair corrupted table | 15 min | 2 |
| Rollback to older backup | 8 min | 4 |

---

## ❌ If Something Goes Wrong

**Can't drop database?**
```powershell
Stop-Service -Name "MySQL80"
Start-Service -Name "MySQL80"
# Retry drop command
```

**Can't restore backup file?**
```powershell
# Check file exists and is readable
Test-Path "c:\xampp\htdocs\clinicapp\archive\database_backup.sql"
Get-Item "c:\xampp\htdocs\clinicapp\archive\database_backup.sql" | Select-Object Length
```

**MySQL won't start?**
- Check `C:\xampp\mysql\data\*.err` for errors
- Verify disk space: `Get-Volume C`
- Try restarting from XAMPP Control Panel

**Still broken?**
- Check logs: `C:\xampp\htdocs\clinicapp\logs\errors.log`
- Check MySQL logs: `C:\xampp\mysql\data\*.err`
- Save error message and contact support

---

## 📞 When to Call for Help

- ✓ Backups are OK, restoration fails → Check MySQL logs, contact DBA
- ✓ All backups corrupted → Call disaster recovery service
- ✓ Can't identify cause → Check `logs/process.log`, save output
- ✓ Multiple tables missing → Restore from older backup (previous week)

---

## 🎯 Best Practices (Do This Weekly)

```powershell
# 1. Verify latest backup exists and is readable
Test-Path "c:\xampp\htdocs\clinicapp\archive\database_backup.sql" -PathType Leaf

# 2. Check backup age (should be < 1 day old)
$latest = Get-Item "c:\xampp\htdocs\clinicapp\archive\database_backup.sql"
(Get-Date) - $latest.LastWriteTime | Select-Object TotalHours

# 3. Test that restoration would work (on test database)
php "c:\xampp\htdocs\clinicapp\scripts\test_backup_restore.php"
```

---

**This card was last updated: November 25, 2025**  
**Keep this guide accessible and up-to-date.**

