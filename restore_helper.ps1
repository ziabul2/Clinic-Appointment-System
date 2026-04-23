# Emergency Restore Helper Script for ClinicApp
# PowerShell script with interactive prompts for database recovery
# 
# Usage: .\restore_helper.ps1
# Requires: PowerShell 5.0+, XAMPP MySQL installed

param(
    [switch]$SkipConfirmation = $false,
    [string]$BackupFile = $null
)

# Colors
$ErrorColor = "Red"
$SuccessColor = "Green"
$WarningColor = "Yellow"
$InfoColor = "Cyan"

function Write-Status($message, $type = "Info") {
    switch ($type) {
        "Error" { Write-Host "❌ $message" -ForegroundColor $ErrorColor }
        "Success" { Write-Host "✓ $message" -ForegroundColor $SuccessColor }
        "Warning" { Write-Host "⚠ $message" -ForegroundColor $WarningColor }
        "Info" { Write-Host "ℹ $message" -ForegroundColor $InfoColor }
        default { Write-Host $message }
    }
}

function Test-AdminRights {
    $isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]"Administrator")
    return $isAdmin
}

# Header
Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  CLINICAPP EMERGENCY RESTORE HELPER                    ║" -ForegroundColor Cyan
Write-Host "║  Interactive Database Recovery Script                  ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Check admin rights
if (!(Test-AdminRights)) {
    Write-Status "WARNING: This script should ideally run as Administrator for service control" "Warning"
    Write-Host "You can still proceed, but service restart may fail.`n"
}

# ============================================================================
# Step 1: Locate Backups
# ============================================================================

Write-Status "Step 1: Finding backup files..." "Info"

$archiveDir = "C:\xampp\htdocs\clinicapp\archive"
$backups = @()

if (Test-Path $archiveDir) {
    $backups += @(Get-ChildItem "$archiveDir\backup_*.sql" -ErrorAction SilentlyContinue)
    $backups += @(Get-ChildItem "$archiveDir\database_backup.sql" -ErrorAction SilentlyContinue)
    $backups = $backups | Sort-Object LastWriteTime -Descending | Get-Unique
}

if ($backups.Count -eq 0) {
    Write-Status "No backup files found in $archiveDir" "Error"
    Write-Host "`nOptions:`n"
    Write-Host "  1. Create a backup first: php C:\xampp\htdocs\clinicapp\backup_db.php"
    Write-Host "  2. Rebuild schema from scratch: php C:\xampp\htdocs\clinicapp\migrate_db.php`n"
    exit 1
}

Write-Status "Found $($backups.Count) backup file(s):" "Success"
Write-Host ""

# Display backup options
$backups | ForEach-Object -Begin {$i=1} -Process {
    $ageMins = [Math]::Round(((Get-Date) - $_.LastWriteTime).TotalMinutes, 0)
    $sizeMB = [Math]::Round($_.Length / 1MB, 2)
    Write-Host "  [$i] $($_.Name) ($sizeMB MB, $ageMins min old)" -ForegroundColor Gray
    $i++
}

Write-Host ""

# Select backup
$selectedIndex = 0
if ($BackupFile) {
    # User specified backup
    $selectedBackup = $backups | Where-Object { $_.Name -eq $BackupFile -or $_.FullName -eq $BackupFile }
    if (!$selectedBackup) {
        Write-Status "Specified backup file not found: $BackupFile" "Error"
        exit 1
    }
} else {
    # Prompt user
    $response = Read-Host "Select backup number to restore (default: 1)"
    if ([string]::IsNullOrWhiteSpace($response)) { $response = "1" }
    
    if (![int]::TryParse($response, [ref]$selectedIndex)) {
        Write-Status "Invalid selection" "Error"
        exit 1
    }
    
    $selectedIndex = $selectedIndex - 1
    
    if ($selectedIndex -lt 0 -or $selectedIndex -ge $backups.Count) {
        Write-Status "Selection out of range" "Error"
        exit 1
    }
    
    $selectedBackup = $backups[$selectedIndex]
}

Write-Status "Selected: $($selectedBackup.Name) ($([Math]::Round($selectedBackup.Length/1MB, 2)) MB)" "Success"

# ============================================================================
# Step 2: Confirm Action
# ============================================================================

Write-Host ""
Write-Status "CAUTION: This procedure will DROP your current database!" "Warning"
Write-Host "`nBefore proceeding:"
Write-Host "  - Current database will be DELETED (after saving forensics backup)"
Write-Host "  - All unsaved changes will be lost"
Write-Host "  - Restore from: $($selectedBackup.Name)"
Write-Host ""

if (!$SkipConfirmation) {
    $confirm = Read-Host "Type 'YES' (in all caps) to proceed, or anything else to cancel"
    if ($confirm -ne "YES") {
        Write-Status "Restore cancelled by user" "Warning"
        exit 0
    }
}

# ============================================================================
# Step 3: Stop Services
# ============================================================================

Write-Host ""
Write-Status "Step 2: Stopping services..." "Info"

$services = @("Apache2.4", "MySQL80", "MySQL57")
$serviceStopped = $false

foreach ($svc in $services) {
    $service = Get-Service -Name $svc -ErrorAction SilentlyContinue
    if ($service) {
        try {
            Stop-Service -Name $svc -Force -ErrorAction SilentlyContinue
            Start-Sleep -Milliseconds 500
            Write-Status "Stopped $svc" "Success"
            $serviceStopped = $true
            break
        } catch {
            Write-Status "Could not stop $svc (may already be stopped)" "Warning"
        }
    }
}

if (!$serviceStopped) {
    Write-Status "Could not stop any MySQL service. Continuing anyway..." "Warning"
}

Start-Sleep -Seconds 1

# ============================================================================
# Step 4: Run Restore
# ============================================================================

Write-Host ""
Write-Status "Step 3: Running automated restore..." "Info"
Write-Host ""

# Run PHP restore script
$phpPath = "C:\xampp\php\php.exe"
$restoreScript = "C:\xampp\htdocs\clinicapp\restore.php"

if (!(Test-Path $phpPath)) {
    Write-Status "PHP executable not found at $phpPath" "Error"
    exit 1
}

if (!(Test-Path $restoreScript)) {
    Write-Status "Restore script not found at $restoreScript" "Error"
    exit 1
}

# Build command to run restore script with backup file parameter
$backupFileName = Split-Path $selectedBackup.FullName -Leaf
$process = & $phpPath $restoreScript $backupFileName 2>&1

$process | ForEach-Object { Write-Host $_ }

# ============================================================================
# Step 5: Restart Services
# ============================================================================

Write-Host ""
Write-Status "Step 4: Restarting services..." "Info"

foreach ($svc in $services) {
    $service = Get-Service -Name $svc -ErrorAction SilentlyContinue
    if ($service) {
        try {
            Start-Service -Name $svc -ErrorAction SilentlyContinue
            Write-Status "Started $svc" "Success"
            Start-Sleep -Seconds 1
            break
        } catch {
            Write-Status "Could not start $svc" "Warning"
        }
    }
}

# ============================================================================
# Step 6: Validation
# ============================================================================

Write-Host ""
Write-Status "Step 5: Running validation checks..." "Info"

Start-Sleep -Seconds 2

# Test database connection
$testScript = "C:\xampp\htdocs\clinicapp\db_test.php"
if (Test-Path $testScript) {
    Write-Host ""
    & $phpPath $testScript 2>&1 | Tee-Object -Variable testOutput | Select-Object -First 20
    
    if ($testOutput -match "successful" -or $testOutput -match "Connected") {
        Write-Status "Database connection test PASSED" "Success"
    } else {
        Write-Status "Database connection test showed issues. Review above." "Warning"
    }
} else {
    Write-Status "Test script not found, skipping automated validation" "Warning"
}

# ============================================================================
# Step 7: Summary
# ============================================================================

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  RESTORE COMPLETE                                      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Write-Status "Database has been restored from: $($selectedBackup.Name)" "Success"

Write-Host "`nNext steps:`n"
Write-Host "  1. Visit http://localhost/clinicapp/ to verify the app loads"
Write-Host "  2. Log in with your admin credentials"
Write-Host "  3. Check data integrity in the app"
Write-Host "  4. Review logs if any issues: C:\xampp\htdocs\clinicapp\logs\"
Write-Host ""

Write-Status "If you encounter errors, see the documentation:" "Info"
Write-Host "  - Quick Fix: docs/QUICK_REFERENCE_RESTORE.md"
Write-Host "  - Full Guide: docs/EMERGENCY_RESTORE.md"
Write-Host "  - Troubleshooting: docs/RECOVERY_INDEX.md`n"

