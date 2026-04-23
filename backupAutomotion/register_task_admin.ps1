# Register Scheduled Task for ClinicApp DB Backup - 2:00 PM Daily
# Run this script with Administrator privileges: Right-click > Run with PowerShell

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] 'Administrator')

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Please: Right-click on this file > Run with PowerShell > Select 'Yes' when prompted" -ForegroundColor Yellow
    exit 1
}

# Remove existing task if it exists
$taskName = "ClinicApp DB Backup"
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing task '$taskName'..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Define backup script path
$scriptPath = "C:\xampp\htdocs\clinicapp\backupAutomotion\run_backup_v2.ps1"

# Verify script exists
if (-not (Test-Path $scriptPath)) {
    Write-Host "ERROR: Backup script not found at: $scriptPath" -ForegroundColor Red
    exit 1
}

# Create scheduled task action
$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptPath`""

# Create scheduled task trigger (Daily at 2:00 PM)
$trigger = New-ScheduledTaskTrigger `
    -Daily `
    -At "2:00 PM"

# Register the scheduled task
try {
    $task = Register-ScheduledTask `
        -TaskName $taskName `
        -Action $action `
        -Trigger $trigger `
        -RunLevel Highest `
        -Description "Daily database backup for ClinicApp at 2:00 PM"
    
    Write-Host "SUCCESS: Scheduled task registered!" -ForegroundColor Green
    Write-Host "Task Name: $taskName" -ForegroundColor Green
    Write-Host "Schedule: Daily at 2:00 PM (14:00)" -ForegroundColor Green
    Write-Host "Status: Enabled and Ready" -ForegroundColor Green
    Write-Host ""
    Write-Host "Backup will run automatically at 2:00 PM every day." -ForegroundColor Cyan
    Write-Host "Backups are saved to: C:\xampp\htdocs\clinicapp\backupAutomotion\backups\" -ForegroundColor Cyan
    
} catch {
    Write-Host "ERROR: Failed to register task!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

# Verify task was created
$verifyTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($verifyTask) {
    Write-Host ""
    Write-Host "Task Details:" -ForegroundColor Cyan
    Write-Host "  Task Name: $($verifyTask.TaskName)"
    Write-Host "  Task Path: $($verifyTask.TaskPath)"
    Write-Host "  State: $($verifyTask.State)"
} else {
    Write-Host "WARNING: Task may not have been created properly" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to close..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
