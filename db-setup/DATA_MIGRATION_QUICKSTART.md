# Data Migration Quick Start Guide

Fast setup guide to migrate your current database to Ubuntu server with all data preserved.

## 2-Step Process

### STEP 1: Export Data (5 minutes - On Current Server)

```bash
cd /path/to/clinicapp/db-setup
chmod +x pre_backup_export.sh
./pre_backup_export.sh
```

✅ **Result:** Your database is backed up as compressed file in `backups/pre-deployment/`

### STEP 2: Deploy with Data (5 minutes - On Ubuntu Server)

After copying `db-setup` folder to Ubuntu:

```bash
cd /path/to/clinicapp/db-setup
chmod +x *.sh
./install_clinic_db.sh --restore-backup
```

✅ **Result:** Ubuntu database now contains all your current data!

---

## Complete Workflow

```
CURRENT SERVER                          UBUNTU SERVER
═════════════════════════════════════════════════════════════
                                       
Your Database                          
   │                                   
   ├─ Patients (100+)                  
   ├─ Appointments (500+)              
   ├─ Doctors (5+)                     
   └─ ...                              
                                       
   │                                   
   ├─ Run: ./pre_backup_export.sh      
   │                                   
   └─ Creates:                         
      clinic_backup_xxx.sql.gz (2-5 MB)
                                       
      │                                
      ├─ Copy db-setup folder via SCP ──────→ /clinicapp/db-setup
      │                                   │
      │                                   ├─ backups/
      │                                   │  └─ pre-deployment/
      │                                   │     └─ clinic_backup_xxx.sql.gz
      │                                   │
      │                                   ├─ Run: ./install_clinic_db.sh 
      │                                   │        --restore-backup
      │                                   │
      │                                   └─ Creates database
      │                                      └─ Restores backup
      │                                         └─ Same data!
      │
      └─ Done!
```

---

## Commands Cheat Sheet

### Export (Current Server)
```bash
cd clinicapp/db-setup
./pre_backup_export.sh
```

**Output:**
```
✓ Backup File: clinic_backup_20250129_143022_pre_deployment.sql.gz
✓ Location: backups/pre-deployment/
✓ Size: 2.4 MB
✓ Tables: 45
```

### Transfer to Ubuntu
```bash
# Copy entire folder via SSH
scp -r clinicapp/db-setup user@ubuntu-server:/path/to/

# Or manual download and extract
# zip, download, transfer, unzip
```

### Deploy on Ubuntu
```bash
cd /path/to/clinicapp/db-setup
chmod +x *.sh
./install_clinic_db.sh --restore-backup
```

**Output:**
```
✓ Step 7: Restoring pre-deployment backup...
✓ Pre-deployment backup restored successfully
✓ Step 9: Database verification complete: 45 tables found

Installation Complete (Restored from pre-deployment backup)
```

### Verify Data
```bash
mysql -u ziabul -p clinic_management << EOF
SELECT COUNT(*) as patients FROM patients;
SELECT COUNT(*) as appointments FROM appointments;
SELECT COUNT(*) as doctors FROM doctors;
EOF
```

---

## Frequently Asked Questions

### Q: Will my current data be lost?
**A:** No! The backup includes all your current data. It's restored exactly as it was.

### Q: What if backup is too large to transfer?
**A:** The script compresses with gzip (typically 80-90% reduction). For 20MB database → 2-4 MB backup file.

### Q: Can I test restore first?
**A:** Yes! Before deployment:
```bash
./restore_clinic_db.sh --file backups/pre-deployment/clinic_backup_*.sql.gz --verify-only
```

### Q: What if something goes wrong?
**A:** Don't worry! The install script automatically creates a safety backup before restoring. If restore fails, original data is safe.

### Q: Do I need to update PHP config?
**A:** Yes, after deployment update:
```php
// config/database.php
$host = "127.0.0.1";           // Ubuntu IP
$username = "ziabul";
$password = "4080099";
$database = "clinic_management";
```

### Q: Can I use different credentials on Ubuntu?
**A:** Yes! Edit `config/db-config.sh` before running install. But keep same credentials if possible for easier migration.

### Q: How long does migration take?
**A:** Typically 5-15 minutes total:
- Export: 2-5 minutes
- Transfer: 1-2 minutes (depends on file size)
- Deploy: 1-2 minutes
- Verify: <1 minute

---

## Troubleshooting

### Issue: "Cannot connect to MariaDB" during export

```bash
# Start MariaDB
sudo systemctl start mariadb

# Check if running
sudo systemctl status mariadb
```

### Issue: "Backup file not found" during restore

```bash
# Check if backup exists
ls -lh backups/pre-deployment/

# If not, export again
./pre_backup_export.sh
```

### Issue: "Permission denied" on scripts

```bash
chmod +x *.sh
chmod 600 config/db-config.sh
```

### Issue: Restore appears to hang

Wait! Large backups can take time. Monitor with:
```bash
tail -f logs/clinic-db-$(date +%Y%m%d).log
```

---

## Data Migration Verification

After deployment, verify everything transferred correctly:

```bash
# Check table counts
mysql -u ziabul -p clinic_management -e "
  SELECT 'patients' as table_name, COUNT(*) as count FROM patients
  UNION ALL
  SELECT 'appointments', COUNT(*) FROM appointments
  UNION ALL
  SELECT 'doctors', COUNT(*) FROM doctors
  UNION ALL
  SELECT 'users', COUNT(*) FROM users;
"

# Check specific patient
mysql -u ziabul -p clinic_management -e "
  SELECT patient_id, first_name, last_name, email FROM patients LIMIT 1;
"

# Check appointments
mysql -u ziabul -p clinic_management -e "
  SELECT appointment_id, patient_id, appointment_date FROM appointments LIMIT 5;
"
```

If data matches your current server, migration was successful! ✅

---

## Safety Checklist

Before exporting, ensure:
- [ ] MariaDB is running and accessible
- [ ] You have disk space (backup file ~2-5 MB)
- [ ] Database has no active connections (or at least minimal traffic)
- [ ] You have sudo access if needed

Before deploying on Ubuntu, ensure:
- [ ] MariaDB is installed and running on Ubuntu
- [ ] db-setup folder copied with backup included
- [ ] Sufficient disk space on Ubuntu (~5x backup size)
- [ ] Ubuntu can access MariaDB (usually already running)

After deployment, ensure:
- [ ] PHP config updated with correct credentials
- [ ] Application can connect to database
- [ ] Sample data queries return results
- [ ] Application features work (login, create appointment, etc.)

---

## Post-Migration Tasks

After successful migration:

1. **Setup Automated Backups**
   ```bash
   crontab -e
   # Add: 0 2 * * * /path/to/clinicapp/db-setup/backup_clinic_db.sh
   ```

2. **Monitor Application**
   - Test all features
   - Check error logs
   - Verify data consistency

3. **Document Setup**
   - Save credentials (store securely)
   - Document any custom configurations
   - Keep backup of Ubuntu database

4. **Regular Maintenance**
   - Monitor backup folder size
   - Test restore procedure monthly
   - Keep logs for troubleshooting

---

## Files Reference

**For Export:**
- `pre_backup_export.sh` - Export current database

**For Deployment:**
- `install_clinic_db.sh --restore-backup` - Deploy with data
- `backup_clinic_db.sh` - Create backup
- `restore_clinic_db.sh` - Restore from backup
- `config/db-config.sh` - Configuration

**Documentation:**
- `UBUNTU_DEPLOYMENT_WITH_DATA.md` - Detailed deployment guide
- `README.md` - Complete reference
- `QUICK_REFERENCE.sh` - Command lookup

---

## Summary

**Current Server:** 
```bash
./pre_backup_export.sh
```

**Ubuntu Server:**
```bash
./install_clinic_db.sh --restore-backup
```

**Result:** 
✅ Complete database with all your data on Ubuntu

**Time Required:** ~15 minutes total

---

**Version:** 1.0  
**Last Updated:** January 29, 2025  
**Status:** Ready for Production
