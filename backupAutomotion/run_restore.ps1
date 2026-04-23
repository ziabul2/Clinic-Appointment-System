param(
    [string]$MysqlPath = "C:\\xampp\\mysql\\bin\\mysql.exe",
    [string]$BackupFile = "",
    [string]$DbName = "clinic_management",
    [string]$DbUser = "root",
    [string]$DbPass = "",
    [string]$DbHost = "localhost",
    [switch]$Force
)

if (-not $BackupFile) {
    Write-Error "Specify -BackupFile <path to .zip or .sql>"
    exit 2
}

if (-not (Test-Path $BackupFile)) {
    Write-Error "Backup file not found: $BackupFile"
    exit 3
}

if (-not (Test-Path $MysqlPath)) {
    Write-Error "mysql.exe not found at $MysqlPath. Update -MysqlPath parameter.";
    exit 4
}

if (-not $Force) {
    $confirm = Read-Host "Restoring will overwrite data in database '$DbName' on '$DbHost'. Type YES to continue"
    if ($confirm -ne 'YES') {
        Write-Output "Restore aborted by user."
        exit 0
    }
}

$tempDir = Join-Path $env:TEMP ([Guid]::NewGuid().ToString())
New-Item -ItemType Directory -Path $tempDir | Out-Null

try {
    $sqlPath = ''
    if ($BackupFile -match "\.zip$") {
        Expand-Archive -Path $BackupFile -DestinationPath $tempDir -Force
        $candidates = Get-ChildItem -Path $tempDir -Filter "*.sql" -File
        if ($candidates.Count -eq 0) { throw "No .sql file found inside the zip." }
        $sqlPath = $candidates[0].FullName
    } elseif ($BackupFile -match "\.sql$") {
        $sqlPath = (Resolve-Path $BackupFile).Path
    } else {
        throw "Unsupported backup file type. Provide .zip or .sql"
    }

    Write-Output "Restoring SQL file: $sqlPath"

    # Build mysql command
    $argList = @()
    if ($DbUser) { $argList += "-u$DbUser" }
    if ($DbPass -ne "") { $argList += "-p$DbPass" }
    if ($DbHost) { $argList += "-h$DbHost" }
    $argList += $DbName

    $cmd = "`"$MysqlPath`" $($argList -join ' ') < `"$sqlPath`""
    Write-Output "Executing: mysql to restore (password may be visible in command)."
    cmd.exe /c $cmd

    Write-Output "Restore completed."
} catch {
    Write-Error "Restore failed: $_"
    exit 5
} finally {
    # cleanup
    if (Test-Path $tempDir) { Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue }
}

exit 0
