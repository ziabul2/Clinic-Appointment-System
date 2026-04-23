# Backup automation for ClinicApp

This folder contains PowerShell helper scripts to run `db_backup.php` automatically on Windows (XAMPP environment). The scripts are intentionally minimal and configurable.

Files
- `run_backup.ps1` — Runs `db_backup.php` using the PHP executable, captures stdout into a `.sql` file, compresses it to `.zip`, and rotates old backups.
- `register_backup_task.ps1` — Registers a Windows Scheduled Task to run `run_backup.ps1` daily at a specified time.

Default behavior and assumptions
- Assumes PHP is at `C:\xampp\php\php.exe`. If your PHP lives elsewhere, pass `-PhpPath` to `run_backup.ps1` or edit the script.
- Assumes `db_backup.php` is in the same folder as these scripts (or in the parent project folder). The script will fail if `db_backup.php` is not present.
- Backups are stored in `backupAutomotion/backups/` as `db_backup_YYYYMMDD_HHMMSS.zip`.
- Rotation keeps the last 30 days by default (change `-RetentionDays` parameter).

Quick start — test run
1. Open PowerShell and change into the project folder (where this README is):

```
cd c:\xampp\htdocs\clinicapp\backupAutomotion
```

2. Run the backup manually (adjust `-PhpPath` if necessary):

```
.\run_backup.ps1 -PhpPath "C:\\xampp\\php\\php.exe"
```

3. Check `backups/` for the produced `.zip` file and inspect the log output.

Registering a daily scheduled task
1. Open PowerShell as the user you want the task to run as (you may need Administrator to create system-level tasks).

2. From the `backupAutomotion` folder run (example schedules at 03:00):

```
.\register_backup_task.ps1 -Time "03:00" -TaskName "ClinicApp DB Backup"
```

Notes and customization
- To change retention days, pass `-RetentionDays` to `run_backup.ps1` (e.g. `-RetentionDays 14`).
- If your `db_backup.php` already writes files on disk rather than stdout, adjust `run_backup.ps1` to skip capturing stdout and instead compress the existing file. I can update the wrapper if you tell me how `db_backup.php` writes outputs.
- If you prefer to use Task Scheduler GUI, create an action that runs `powershell.exe` with the argument `-NoProfile -ExecutionPolicy Bypass -File "<path>\run_backup.ps1"` and set a daily trigger.

Security
- Store backups in a directory with appropriate permissions — backups may contain database credentials or PHI. Consider moving backups to a secure location or network share with restricted access.

If you want, I can:
- Inspect `db_backup.php` and adapt `run_backup.ps1` to match the script's output behavior.
- Create an automated restore helper that locates the latest backup and runs `db_restore.php`.
- Register the scheduled task now using your environment values (I can provide the exact PowerShell command you should run).
