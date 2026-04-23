<#
Enhanced Database Backup Script with Per-Table Export
Uses db_backup_enhanced.php to export all tables separately, then compresses them.
#>

param(
    [string]$PhpPath = "C:\\xampp\\php\\php.exe",
    [string]$ProjectPath = (Split-Path -Parent $MyInvocation.MyCommand.Definition),
    [string]$BackupDir = "$(Join-Path $ProjectPath 'backups')",
    [int]$RetentionDays = 30
)

if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$baseName = "db_backup_$timestamp"
$zipFile = Join-Path $BackupDir "$baseName.zip"
$metaFile = Join-Path $BackupDir "$baseName.meta.json"
$enhancedBackupScript = Join-Path $ProjectPath "db_backup_enhanced.php"
$legacyBackupScript = Join-Path $ProjectPath "db_backup.php"

if (-not (Test-Path $PhpPath)) {
    Write-Error "PHP executable not found at $PhpPath"
    exit 2
}

# Select backup script
$selectedScript = $null
if (Test-Path $enhancedBackupScript) {
    $selectedScript = $enhancedBackupScript
    Write-Output "Using enhanced backup script"
} elseif (Test-Path $legacyBackupScript) {
    $selectedScript = $legacyBackupScript
    Write-Output "Using legacy backup script"
} else {
    Write-Error "No backup script found"
    exit 3
}

# Run backup script
Write-Output "Running: $PhpPath $selectedScript"
&$PhpPath $selectedScript 2>&1

$exit = $LASTEXITCODE
if ($exit -ne 0) {
    Write-Warning "Backup script exited with code $exit"
}

# Collect SQL files
$sqlFiles = @()
$sqlFiles += Get-ChildItem -Path $ProjectPath -Filter "*_database.sql" -File -ErrorAction SilentlyContinue
$sqlFiles += Get-ChildItem -Path $ProjectPath -Filter "*_table_*.sql" -File -ErrorAction SilentlyContinue
$sqlFiles += Get-ChildItem -Path $ProjectPath -Filter "*_extra.sql" -File -ErrorAction SilentlyContinue

if ($sqlFiles.Count -eq 0) {
    Write-Error "No SQL files created"
    exit 4
}

Write-Output "Compressing $($sqlFiles.Count) SQL file(s)..."

# Compress files
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

$filesToCompress = $sqlFiles | Select-Object -ExpandProperty FullName
Compress-Archive -Path $filesToCompress -DestinationPath $zipFile -Force -CompressionLevel Optimal

if (-not (Test-Path $zipFile)) {
    Write-Error "Failed to create zip file"
    exit 5
}

$fileInfo = Get-Item $zipFile
$meta = @{
    backup_name = $baseName
    created_at = (Get-Date).ToString('o')
    zip_path = $fileInfo.FullName
    zip_size_bytes = $fileInfo.Length
    database = "clinic_management"
    table_count = ($sqlFiles | Where-Object { $_.Name -match '_table_' }).Count
    total_sql_files = $sqlFiles.Count
}

$meta | ConvertTo-Json | Out-File -FilePath $metaFile -Encoding UTF8

Write-Output "Backup created: $zipFile"
Write-Output "  Size: $([Math]::Round($fileInfo.Length / 1MB, 2)) MB"
Write-Output "  Metadata: $metaFile"

# Cleanup temporary SQL files
foreach ($file in $sqlFiles) {
    Remove-Item $file.FullName -Force -ErrorAction SilentlyContinue
}

Write-Output "Temporary files cleaned up"

# Remove old backups
$oldFiles = Get-ChildItem -Path $BackupDir -Filter "db_backup_*.zip" -ErrorAction SilentlyContinue | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) }
foreach ($file in $oldFiles) {
    Remove-Item $file.FullName -Force -ErrorAction SilentlyContinue
}

Write-Output "Backup completed at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

exit 0
