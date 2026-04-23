# ClinicApp Recovery Documentation — README

**Status:** ✅ Complete  
**Date:** November 25, 2025  
**Version:** 1.0

---

## 📂 What's in This Folder?

This folder (`docs/`) contains complete disaster recovery and backup procedures for the ClinicApp clinic management system.

### 📄 Documentation Files

#### 1. **RECOVERY_INDEX.md** (Start Here!)
   - **Purpose:** Master guide to all recovery tools and procedures
   - **Read Time:** 15-20 minutes
   - **Use When:** You want to understand the complete picture
   - **Sections:**
     - Quick decision tree (which procedure to use?)
     - Tool descriptions and how to run them
     - Time estimates for each scenario
     - Troubleshooting guide
     - Maintenance schedule
   - **👉 Start here if you're new to recovery procedures**

#### 2. **QUICK_REFERENCE_RESTORE.md** (Emergency Cheat Sheet)
   - **Purpose:** Fast copy-paste commands for emergency recovery
   - **Read Time:** 5 minutes to understand, <10 min to execute
   - **Use When:** Database is down NOW and you need it back ASAP
   - **Sections:**
     - 5-step immediate recovery
     - Schema rebuild (no backup)
     - Critical validation checks
     - Important file paths
     - Quick troubleshooting fixes
   - **👉 Use this when you're in crisis mode**

#### 3. **EMERGENCY_RESTORE.md** (Comprehensive Guide)
   - **Purpose:** Detailed procedures for all recovery scenarios
   - **Read Time:** 30-40 minutes (reference document, don't read all at once)
   - **Use When:** You need detailed explanations and step-by-step guidance
   - **Sections (9 total):**
     - Quick Start (TL;DR)
     - Backup locations
     - Full database restoration
     - Partial recovery (single table)
     - Corruption detection & repair
     - Schema rebuild from migrations
     - Data validation after restore
     - Rollback to older backup
     - Prevention & monitoring best practices
   - **👉 Use this as your primary reference document**

---

## 🛠️ Recovery Tools

### PHP Scripts (Run via command line)

#### `restore.php` (Automated Recovery)
```powershell
php "c:\xampp\htdocs\clinicapp\restore.php"
```
- Finds most recent backup automatically
- Saves current DB as forensics backup
- Restores from backup with validation
- ~10-15 minutes

#### `migrate_db.php` (Schema Rebuild)
```powershell
php "c:\xampp\htdocs\clinicapp\migrate_db.php"
```
- Rebuilds complete database schema from scratch
- Use when no backup is available
- ~5 minutes

#### `db_test.php` (Health Check)
```powershell
php "c:\xampp\htdocs\clinicapp\db_test.php"
```
- Tests database connectivity
- Verifies all tables exist
- Runs basic query tests
- ~2 minutes

#### `db_inspect.php` (Detailed Diagnostics)
```powershell
php "c:\xampp\htdocs\clinicapp\db_inspect.php"
```
- Analyzes all tables and columns
- Detects corruption and missing data
- Checks foreign key integrity
- ~5 minutes

### PowerShell Helper Script

#### `restore_helper.ps1` (Interactive Recovery)
```powershell
.\restore_helper.ps1
```
- Interactive menu to select backup
- Automatic service control (stop/start)
- Runs validation checks
- Friendly error messages
- ~15 minutes

---

## 🚨 Quick Start: Emergency Recovery

**Database is down and you need it back in 10 minutes?**

### Option A: Automated (Easiest)
```powershell
php "c:\xampp\htdocs\clinicapp\restore.php"
# Follow prompts, type RESTORE to confirm
```

### Option B: Interactive (User-Friendly)
```powershell
cd "c:\xampp\htdocs\clinicapp"
.\restore_helper.ps1
# Select backup, confirm, wait
```

### Option C: Manual (Copy-Paste)
Read: `QUICK_REFERENCE_RESTORE.md` and run the commands step-by-step.

---

## 📋 Common Scenarios

### "My database is corrupted"
1. Read: QUICK_REFERENCE_RESTORE.md (5 min)
2. Run: `php restore.php` (10 min)
3. Validate: `php db_test.php` (2 min)
4. Check: http://localhost/clinicapp/ (1 min)

### "I have no backup"
1. Read: EMERGENCY_RESTORE.md Section 6 (5 min)
2. Run: `php migrate_db.php` (5 min)
3. Result: Empty working database

### "Data looks corrupted but app runs"
1. Run: `php db_inspect.php` (5 min, see what's wrong)
2. Read: EMERGENCY_RESTORE.md Section 5 (repair options)
3. Run: Recommended fix from section 5

### "I want to test if recovery works"
1. Read: RECOVERY_INDEX.md Section 11 (quarterly drill)
2. Follow: Test restoration procedure
3. Result: Confidence in your backups

---

## 📊 Decision Tree

```
Database Problem?
├─ Down / Won't Connect
│  └─ Run: restore.php (or QUICK_REFERENCE steps)
├─ Data Looks Corrupted
│  └─ Run: db_inspect.php (then read EMERGENCY_RESTORE §5)
├─ No Backup Available
│  └─ Run: migrate_db.php (read EMERGENCY_RESTORE §6)
└─ Need to Test Procedures
   └─ Read: RECOVERY_INDEX.md §11
```

---

## ✅ Getting Started Checklist

- [ ] **Read** RECOVERY_INDEX.md (15 min) — Understand the landscape
- [ ] **Skim** QUICK_REFERENCE_RESTORE.md (5 min) — Know where it is
- [ ] **Keep Handy:** EMERGENCY_RESTORE.md — Your detailed reference
- [ ] **Test:** Run `php db_test.php` — Verify tools work
- [ ] **Schedule:** Monthly backup verification (5 min/month)
- [ ] **Drill:** Quarterly restore test (30 min/quarter)

---

## 🎓 Learning Path

**For new administrators** (first time learning recovery):

1. **10 min:** Skim RECOVERY_INDEX.md (big picture)
2. **15 min:** Read QUICK_REFERENCE_RESTORE.md (emergency steps)
3. **30 min:** Read EMERGENCY_RESTORE.md Sections 1-3 (full procedures)
4. **30 min:** Hands-on test on non-production database
5. **15 min:** Read EMERGENCY_RESTORE.md Sections 4-9 (advanced topics)
6. **Monthly:** Review and test procedures

---

## 📞 When to Use Each Document

| Situation | Use Document | Time |
|-----------|---|------|
| Database is down NOW | QUICK_REFERENCE_RESTORE.md | 5 min |
| Need to understand recovery | RECOVERY_INDEX.md | 15 min |
| Detailed step-by-step help | EMERGENCY_RESTORE.md | 30 min |
| Deciding what to do | RECOVERY_INDEX.md (decision tree) | 5 min |
| Troubleshooting error | RECOVERY_INDEX.md (troubleshooting) | 10 min |
| Setting up prevention | EMERGENCY_RESTORE.md §9 | 20 min |

---

## 🔒 Important Security Notes

⚠️ **Backups contain sensitive data:**
- Patient health information
- User accounts and credentials
- Appointments and medical history

**Best Practices:**
- Store backups on encrypted drives
- Limit access (admins only)
- Never email backups
- Keep off-site backup copy for production systems

---

## 📧 Support & Questions

### Self-Service
- ✓ "How do I restore?" → QUICK_REFERENCE_RESTORE.md
- ✓ "What tools exist?" → RECOVERY_INDEX.md
- ✓ "Why is my data corrupted?" → Run `db_inspect.php`
- ✓ "Which backup do I use?" → RECOVERY_INDEX.md (decision tree)

### Need Help?
1. Save error messages from logs: `C:\xampp\htdocs\clinicapp\logs\`
2. Run: `php db_inspect.php` (save output)
3. Read: EMERGENCY_RESTORE.md troubleshooting section
4. Contact: Your database administrator

---

## 📅 Regular Maintenance

### Monthly
- [ ] Verify latest backup exists and is readable
- [ ] Check backup age (should be < 24 hours)
- [ ] Run: `php db_test.php`

### Quarterly
- [ ] Complete restore test on test database
- [ ] Document any issues
- [ ] Update these procedures if needed

### Annually
- [ ] Review all documentation for accuracy
- [ ] Test disaster recovery with full team
- [ ] Update contact information and resources

---

## 📖 File Locations Quick Reference

| What | Where |
|------|-------|
| **Backups** | `c:\xampp\htdocs\clinicapp\archive\` |
| **Restore Tools** | Root: `restore.php`, `db_test.php`, `migrate_db.php` |
| **This Documentation** | `docs/` (you are here) |
| **Application Logs** | `logs/errors.log`, `logs/process.log` |
| **Config** | `config/config.php` |
| **Database** | MySQL: `clinic_management` database |

---

## 🎯 Success Criteria (You'll Know It Worked)

After running recovery:

- ✓ `php db_test.php` shows "Connection OK"
- ✓ http://localhost/clinicapp/ loads without errors
- ✓ You can log in with admin credentials
- ✓ Patient and appointment data is visible
- ✓ No error messages in `logs/errors.log`

---

## 🚀 Next Steps

1. **Right Now:** Read RECOVERY_INDEX.md (15 min)
2. **This Week:** Test one recovery procedure
3. **This Month:** Set up automated backups
4. **This Quarter:** Run full disaster recovery drill
5. **Ongoing:** Monthly 5-minute verification checks

---

## 📝 Version History

| Date | Version | Changes |
|------|---------|---------|
| 2025-11-25 | 1.0 | Initial complete documentation release |

---

## ✨ TL;DR

**Backup exists?** Run `php restore.php` → Done in 10 min  
**No backup?** Run `php migrate_db.php` → Done in 5 min  
**Unsure?** Read QUICK_REFERENCE_RESTORE.md → 5 min guidance  
**Need details?** Read EMERGENCY_RESTORE.md → Complete reference  

---

**Good luck! You're now prepared for emergency database recovery.**

*Last Updated: November 25, 2025*

