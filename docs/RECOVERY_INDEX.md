# ClinicApp Disaster Recovery & Documentation Index

**Last Updated:** November 25, 2025  
**Version:** 1.0  
**Status:** Complete

This is your **master reference** for all disaster recovery, backup, and restore procedures for the ClinicApp clinic management system.

---

## 📚 Documentation Files

### 1. **EMERGENCY_RESTORE.md** (Primary Guide)
   - **Location:** `docs/EMERGENCY_RESTORE.md`
   - **Purpose:** Comprehensive recovery procedures for all disaster scenarios
   - **Audience:** Admins, IT personnel
   - **Sections:**
     - Quick Start Recovery
     - Full Database Restoration
     - Partial Recovery (Single Table)
     - Corruption Detection & Repair
     - Schema Rebuild from Scratch
     - Data Validation
     - Rollback Procedures
     - Prevention & Monitoring
   - **Time to Read:** 30 minutes
   - **When to Use:** You have time to read and understand procedures

### 2. **QUICK_REFERENCE_RESTORE.md** (Cheat Sheet)
   - **Location:** `docs/QUICK_REFERENCE_RESTORE.md`
   - **Purpose:** Fast recovery steps for common scenarios
   - **Audience:** Anyone in emergency mode
   - **Format:** Step-by-step commands with minimal explanation
   - **Sections:**
     - 5-minute recovery procedure
     - Schema rebuild
     - Critical checks
     - Important paths
     - Troubleshooting quick fixes
   - **Time to Use:** 5-10 minutes
   - **When to Use:** Database is down NOW and you need it back ASAP

### 3. **This File** (Index & Roadmap)
   - **Location:** `docs/RECOVERY_INDEX.md`
   - **Purpose:** Overview of all recovery tools and procedures
   - **Use This To:** Decide which procedure to follow

---

## 🛠️ Automated Recovery Tools

### 1. **restore.php** (One-Click Restore)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\restore.php`
   - **What It Does:**
     - Finds most recent backup automatically
     - Saves current DB as forensics backup
     - Drops corrupted DB
     - Restores from backup
     - Validates restored data
   - **Usage:**
     ```powershell
     # Restore from latest backup
     php "c:\xampp\htdocs\clinicapp\restore.php"
     
     # Restore from specific backup
     php "c:\xampp\htdocs\clinicapp\restore.php" backup_20251124_180000.sql
     ```
   - **Time Required:** 10-15 minutes
   - **Safety:** Creates forensics backup before erasing anything

### 2. **db_test.php** (Health Check)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\db_test.php`
   - **What It Does:**
     - Tests database connection
     - Verifies all tables exist
     - Runs sample queries
     - Reports issues
   - **Usage:** `php db_test.php`
   - **When to Use:** After any restore or recovery

### 3. **db_inspect.php** (Detailed Inspection)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\db_inspect.php`
   - **What It Does:**
     - Detailed table analysis
     - Column type verification
     - Foreign key checking
     - Index verification
     - Data corruption detection
   - **Usage:** `php db_inspect.php`
   - **When to Use:** To diagnose data corruption

### 4. **migrate_db.php** (Schema Rebuild)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\migrate_db.php`
   - **What It Does:**
     - Rebuilds complete database schema from scratch
     - Applies all migrations
     - Creates tables, indexes, foreign keys
   - **Usage:** `php migrate_db.php`
   - **When to Use:** No backup available, need empty working database

### 5. **apply_migration_006.php** (Recurrence Features)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\apply_migration_006.php`
   - **What It Does:**
     - Adds recurrence_rules, waiting_list, availability_cache tables
     - Adds columns for recurring appointments
     - Safe to run even if tables already exist
   - **Usage:** `php apply_migration_006.php`
   - **When to Use:** After schema rebuild, to add advanced features

### 6. **Backup Creation Script** (Manual Backup)
   - **Location:** Root: `c:\xampp\htdocs\clinicapp\backup_db.php`
   - **What It Does:**
     - Creates SQL dump of entire database
     - Saves with timestamp to `archive/` folder
     - Can be run manually or scheduled
   - **Usage:** `php backup_db.php`
   - **When to Use:** Before making major changes

---

## 📋 Backup File Locations

| File Type | Location | Frequency | Purpose |
|-----------|----------|-----------|---------|
| **Latest Backup** | `archive/database_backup.sql` | Manual or daily | Most recent good state |
| **Timestamped Backups** | `archive/backup_*.sql` | Daily (auto) | Historical backups (rolling 10) |
| **Forensics Backup** | `archive/forensics_*.sql` | On restore | Corrupted DB saved for analysis |
| **Test Backups** | `scripts/test_*.php` | Never deleted | Test/validation scripts |

---

## 🚨 Quick Decision Tree: Which Procedure to Use?

```
START: Database is down or corrupted
│
├─ Do you have backups?
│  │
│  ├─ YES (see archive/ folder)
│  │  │
│  │  └─ RUN: restore.php (automated, 10 min)
│  │     OR read: EMERGENCY_RESTORE.md → Section 3 (full procedure, 15 min)
│  │     OR use: QUICK_REFERENCE_RESTORE.md (cheat sheet, 5 min)
│  │
│  └─ NO (no backup files found)
│     │
│     └─ RUN: migrate_db.php (rebuild schema, 5 min)
│        THEN: apply_migration_006.php (if needed)
│        RESULT: Empty working database, no data recovery possible
│
├─ After recovery:
│  │
│  ├─ RUN: db_test.php (quick health check, 2 min)
│  ├─ RUN: db_inspect.php (detailed validation, 5 min)
│  └─ VISIT: http://localhost/clinicapp/ (manual UI test)
│
└─ Document what went wrong in logs/ folder
   (for post-mortems and prevention)
```

---

## 📊 Recovery Time Estimates

| Scenario | Tool/Procedure | Time | Complexity |
|----------|---|------|------------|
| **Full restore from backup** | `restore.php` | 10-15 min | Low |
| **Manual full restore** | EMERGENCY_RESTORE.md §3 | 15-20 min | Medium |
| **Quick restore (cheat sheet)** | QUICK_REFERENCE_RESTORE.md | 5-10 min | Low |
| **Schema rebuild (no backup)** | `migrate_db.php` | 5-10 min | Low |
| **Corruption repair** | `db_inspect.php` + mysqlcheck | 15-30 min | Medium |
| **Data validation check** | `db_test.php` | 2-5 min | Low |
| **Quarterly drill** | EMERGENCY_RESTORE.md §11 | 30 min | Medium |

---

## ✅ Pre-Disaster Checklist (Do Monthly)

- [ ] Verify latest backup exists: `ls -la archive/database_backup.sql`
- [ ] Check backup age (should be < 24 hours)
- [ ] Test that backups are readable (open file in text editor)
- [ ] Run health check: `php db_test.php`
- [ ] Verify recovery tools are in place:
  - [ ] `restore.php` exists and is readable
  - [ ] `migrate_db.php` exists
  - [ ] This documentation exists
- [ ] Review MySQL logs for errors: `C:\xampp\mysql\data\*.err`
- [ ] Check available disk space: `Get-Volume C`

---

## 🔍 Troubleshooting Reference

### Backup Problems

| Issue | Cause | Solution |
|-------|-------|----------|
| No backup files | Backups never created | Create manual backup now; schedule automated backups |
| Backup is old (>1 day) | Automation failed | Check Windows Task Scheduler; restart backup service |
| Backup is tiny (<1 MB) | Corrupted/incomplete | Use next backup; if all small, schema-rebuild |
| Can't read backup file | Permissions issue | Run cmd/PowerShell as admin; check file properties |

### Restore Problems

| Issue | Cause | Solution |
|-------|-------|----------|
| "Access Denied" | MySQL permission issue | Stop/restart MySQL; check credentials in config.php |
| "Syntax error in backup" | Corrupted backup file | Try older backup file |
| "Out of disk space" | No space for DB | Delete old backups; free up drive space |
| "MySQL won't start" | Database lock | Check for stale processes; manual restart in Services |
| "Data looks wrong" | Incomplete restore | Run `db_inspect.php` to validate; check logs |

### Validation Problems

| Issue | Cause | Solution |
|-------|-------|----------|
| "Orphaned records" | FK violation | Review data; delete orphaned records or restore from older backup |
| "Users can't log in" | Users table corrupted/missing | Check users table count; re-import user accounts |
| "App won't load" | Config or migration issue | Check `logs/errors.log`; run `migrate_db.php` |

---

## 📞 Support & Escalation

### Level 1: Self-Service (You can handle this)
- ✓ Database won't connect → Run `db_test.php`
- ✓ Data corruption → Run `db_inspect.php`
- ✓ Old backup exists → Use `restore.php`
- ✓ Need empty database → Run `migrate_db.php`

### Level 2: Needs Investigation (Save error logs, check documentation)
- ⚠ Multiple tables missing
- ⚠ All backups corrupted
- ⚠ MySQL won't start after recovery
- ⚠ Unidentifiable data corruption

**Action:** 
1. Save logs to `c:\xampp\htdocs\clinicapp\logs\`
2. Save backup file and first few lines of error
3. Review EMERGENCY_RESTORE.md troubleshooting section
4. Contact database administrator

### Level 3: External Help Required
- ❌ Hardware failure
- ❌ Operating system crash
- ❌ Complete data loss (no backups)
- ❌ Security incident (suspected hacking)

**Action:** Contact disaster recovery service or MySQL expert.

---

## 🎓 Learning Path (Recommended)

**New Administrator?** Follow this order:

1. **Read:** QUICK_REFERENCE_RESTORE.md (15 min)
   - Get the big picture of recovery steps

2. **Read:** EMERGENCY_RESTORE.md Sections 1-3 (20 min)
   - Understand full and partial restoration

3. **Hands-On:** Test on non-production (30 min)
   ```powershell
   # Create a test database
   & "c:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE clinic_test;"
   # Run a restore drill
   php "c:\xampp\htdocs\clinicapp\restore.php"
   ```

4. **Read:** EMERGENCY_RESTORE.md Sections 4-9 (30 min)
   - Learn corruption repair and prevention

5. **Run Quarterly Drill** (30 min per quarter)
   - Test full restore process on test DB
   - Verify all backups are usable
   - Document any issues

---

## 📅 Maintenance Schedule

| Task | Frequency | Time | Owner |
|------|-----------|------|-------|
| Verify backup existence | Daily (automated) | 1 min | System |
| Test health check | Weekly | 5 min | Admin |
| Test restore procedure | Quarterly | 30 min | Admin |
| Review logs for errors | Monthly | 10 min | Admin |
| Archive old backups | Monthly | 5 min | Admin |
| Update this documentation | Annually | 15 min | Admin |

---

## 💾 Backup Automation Setup (One-Time)

Create Windows Task Scheduler job:

1. Open `taskschd.msc`
2. Create Basic Task:
   - **Name:** ClinicApp Daily Backup
   - **Trigger:** Daily, 2:00 AM
   - **Action:** Start a program
   - **Program:** `C:\xampp\php\php.exe`
   - **Arguments:** `C:\xampp\htdocs\clinicapp\backup_db.php`
3. Save and enable

**Verification:**
```powershell
# Check that a backup was created today
Get-ChildItem "c:\xampp\htdocs\clinicapp\archive\backup_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
```

---

## 📖 Additional Resources

### Official Documentation
- **MySQL Reference:** https://dev.mysql.com/doc/
- **XAMPP Support:** https://www.apachefriends.org/
- **PHP Backup Best Practices:** https://www.php.net/

### Tools Referenced
- **mysqldump:** MySQL backup utility (included with XAMPP)
- **mysqlcheck:** MySQL table repair utility (included with XAMPP)
- **Windows Task Scheduler:** Built-in Windows automation

### Related Files in This Project
- `config/config.php` — Database credentials
- `logs/errors.log` — Application error log
- `logs/process.log` — Process execution log
- `migrations/` — Database schema definitions
- `archive/` — Backup storage

---

## 🔐 Security Notes

### Backup Security

⚠️ **Important:** Backups contain sensitive data (patient info, user credentials in hashed form).

**Best Practices:**
- Store backups on encrypted drives only
- Limit access to backup files (admins only)
- Do NOT store backups in public folders
- Do NOT email backups
- Consider off-site backup storage for production

### After Recovery

- [ ] Rotate admin passwords after emergency
- [ ] Review access logs for suspicious activity
- [ ] Notify users if data was potentially compromised
- [ ] Document incident for compliance/audit

---

## 📝 Change Log

| Date | Change | Version |
|------|--------|---------|
| 2025-11-25 | Initial documentation created | 1.0 |
| | Complete recovery procedures | |
| | Automated tools & cheat sheets | |
| | Decision trees & troubleshooting | |

---

## ✨ Summary

**3 Ways to Recover from Disaster:**

1. **Automated (Easiest):** `php restore.php`
   - ✓ One command
   - ✓ Handles everything
   - ✓ 10 minutes

2. **Quick Manual:** QUICK_REFERENCE_RESTORE.md
   - ✓ Copy-paste commands
   - ✓ Fast (5 min)
   - ✓ Understand what you're doing

3. **Full Control:** EMERGENCY_RESTORE.md
   - ✓ Step-by-step explanations
   - ✓ Detailed procedures
   - ✓ Advanced scenarios

**Choose based on:**
- Skill level (automated → quick → full)
- Time available (fast → full)
- Disaster severity (data loss → schema rebuild)

---

**You are now prepared for common disaster scenarios. Good luck!**

---

**Questions?** Check the troubleshooting section or contact your database administrator.

**Last Updated:** November 25, 2025  
**Next Review:** February 25, 2026

