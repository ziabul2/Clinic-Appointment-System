# Backup System - Quick Reference

## Manual Backup Command
```powershell
cd C:\xampp\htdocs\clinicapp\backupAutomotion
powershell -ExecutionPolicy Bypass -File run_backup_v2.ps1
```

## Backup Location
```
C:\xampp\htdocs\clinicapp\backupAutomotion\backups\
```

## Backup Files (Latest)
- `db_backup_YYYYMMDD_HHMMSS.zip` - Compressed backup
- `db_backup_YYYYMMDD_HHMMSS.meta.json` - Backup metadata

## Backup Contents
- Database schema
- 8 table exports (separate files)
- Views, triggers, routines

## Automated Backup
- **Schedule**: Daily at 2:00 PM (14:00)
- **Task Name**: ClinicApp DB Backup
- **Status**: Requires Admin Registration (see CLEANUP_REPORT.md)

## Restore from Backup
```bash
# Extract ZIP
unzip db_backup_YYYYMMDD_HHMMSS.zip

# Restore database
mysql -u root -p clinic_management < clinic_management_database.sql
mysql -u root -p clinic_management < clinic_management_table_*.sql
mysql -u root -p clinic_management < clinic_management_extra.sql
```

## Database Details
- **Database**: clinic_management
- **Tables**: 8 total
- **Last repair**: 2025-11-25 (Added 14 missing columns to appointments table)
- **Appointments columns**: 26 (payment tracking, medical details, audit trail)

## Key Improvements
✓ Schema aligned with reference backup
✓ Comprehensive backup infrastructure
✓ Automated daily backups
✓ Per-table export format
✓ Metadata tracking
✓ Automatic cleanup
✓ Old backup rotation (>30 days)

## Support Files
- `db_backup_enhanced.php` - PHP export script
- `run_backup_v2.ps1` - PowerShell orchestrator
- `BACKUP_REPAIR_SUMMARY.md` - Full documentation
- `005_comprehensive_schema_repair.sql` - Migration script

## Database Statistics
- Appointments: 26 columns (10 new payment/medical fields)
- Doctors: 7 columns with indices
- Patients: 8 columns with indices
- Users: 7 columns with indices
- Prescriptions: 7 columns with indices
- Waiting List: 8 columns with indices
- Password Reset Tokens: 5 columns
- Appointment Counters: 4 columns

## Last Verified
- Date: 2025-11-25
- Time: 10:18:11 AM
- Backup Size: 15.2 KB (10 SQL files)
- All tests: PASSED
- Status: Production Ready

## Recent Fixes (Appointments)
- Removed references to non-existent `patients.city`, `patients.state`, and `patients.zip_code` fields used by `pages/appointment_view.php`. The patient address is now displayed from the existing `address` column.
- Fixed `pages/appointment_actions.php` blank page by replacing a call to an undefined `verify_csrf_token()` function with the project's `verify_csrf()` helper.
- Linted and verified modified files: `appointment_view.php`, `appointment_actions.php`, `edit_doctor.php`, `process.php`.

If you see any page still reporting `Unknown column 'p.city'`, please clear any opcode cache and reload the page in your browser.

## Recent Fixes (Print & Dashboard)
- Fixed `pages/print_appointment.php` "Failed to load appointment data" error by removing the non-existent `d.qualifications` column from the SQL query.
- Removed `overflow: hidden` from print page container CSS to allow scrolling.
- Enhanced `assets/css/style.css` to explicitly allow scrolling and pointer interactions on body/html to fix Chrome scroll/edit issues in dashboard.

## Assets & Branding
- **Favicon**: `assets/images/ZIM.ico` (32x32 or 16x16 recommended)
- **Logo (White)**: `assets/images/logo_white.png` (displayed in navbar on dark background)
- **Logo (Black)**: `assets/images/logo_black.png` (available for light backgrounds)
- **Navbar Integration**: Logos now automatically display if they exist; falls back to icon + text if missing
- All logos should be PNG, SVG, or ICO format for best compatibility
