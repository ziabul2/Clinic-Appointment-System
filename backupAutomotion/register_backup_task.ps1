param(
    [string]$TaskName = "ClinicApp DB Backup",
    [string]$Time = "03:00",
    [string]$ScriptPath = (Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Definition) "run_backup.ps1")
)

if (-not (Test-Path $ScriptPath)) {
    Write-Error "run_backup.ps1 not found at $ScriptPath. Modify the -ScriptPath parameter to the correct path."
    exit 2
}

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`""
$trigger = New-ScheduledTaskTrigger -Daily -At $Time

try {
    # Run as current user (S4U) with highest privileges. If registration fails, run PowerShell as Administrator.
    $principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType S4U -RunLevel Highest
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Force
    Write-Output "Task '$TaskName' registered to run daily at $Time."
} catch {
    Write-Warning "Failed to register scheduled task: $_"
    Write-Output "Try running this script as Administrator or create a task manually in Task Scheduler."
    exit 3
}

exit 0
