#!/bin/bash

# ==============================================================================
# PRE-DEPLOYMENT BACKUP SCRIPT - Export Current Database
# ==============================================================================
# This script exports your current clinic_management database for backup
# before deployment to production. Run this BEFORE copying to Ubuntu/Termux server.
# ==============================================================================

source "$(dirname "$0")/config/db-config.sh"
source "$(dirname "$0")/lib-functions.sh"

# Override backup location for pre-deployment
PRE_DEPLOY_BACKUP_DIR="$(dirname "$0")/backups/pre-deployment"

# Create backup directory if it doesn't exist
mkdir -p "$PRE_DEPLOY_BACKUP_DIR"

print_header "PRE-DEPLOYMENT DATABASE BACKUP"
print_header "Export current clinic_management database"

# Test connection
log_info "Testing database connection..."
test_db_connection "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "$DB_PORT"

if [ $? -ne 0 ]; then
    log_error "Cannot connect to database. Ensure MariaDB is running and credentials are correct."
    log_error "Current credentials: User=$DB_ROOT_USER, Host=$DB_HOST:$DB_PORT"
    exit_error "Connection test failed"
fi

log_success "Connected to database successfully"

# Check if database exists
log_info "Checking if database '$DB_NAME' exists..."
DB_EXISTS=$(mysql -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" -h "$DB_HOST" -P "$DB_PORT" -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$DB_NAME';" 2>/dev/null | grep -c "$DB_NAME")

if [ "$DB_EXISTS" -eq 0 ]; then
    log_warning "Database '$DB_NAME' does not exist yet."
    log_info "This is normal for new installations."
    exit 0
fi

log_success "Database found: $DB_NAME"

# Get table count
log_info "Counting tables..."
TABLE_COUNT=$(mysql -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" -h "$DB_HOST" -P "$DB_PORT" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null | tail -1)
log_info "Found $TABLE_COUNT tables in database"

# Create backup filename
BACKUP_DATE=$(date +%Y%m%d)
BACKUP_TIME=$(date +%H%M%S)
BACKUP_FILENAME="clinic_backup_${BACKUP_DATE}_${BACKUP_TIME}_pre_deployment.sql"
BACKUP_PATH="$PRE_DEPLOY_BACKUP_DIR/$BACKUP_FILENAME"

log_info "Creating backup file: $BACKUP_FILENAME"

# Perform backup with full options
log_info "Exporting database (this may take a moment)..."
mysqldump \
    -u "$DB_ROOT_USER" \
    -p"$DB_ROOT_PASS" \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --disable-keys \
    --extended-insert \
    --quick \
    "$DB_NAME" > "$BACKUP_PATH" 2>/dev/null

if [ $? -ne 0 ]; then
    log_error "Failed to create backup"
    rm -f "$BACKUP_PATH"
    exit_error "Backup creation failed"
fi

log_success "Backup file created successfully"

# Get file size
BACKUP_SIZE=$(du -h "$BACKUP_PATH" | cut -f1)
FILE_SIZE=$(stat -f%z "$BACKUP_PATH" 2>/dev/null || stat -c%s "$BACKUP_PATH" 2>/dev/null)

log_info "Backup file size: $BACKUP_SIZE"

# Compress backup
log_info "Compressing backup file..."
gzip -9 "$BACKUP_PATH" 2>/dev/null

if [ $? -ne 0 ]; then
    log_error "Failed to compress backup"
    exit_error "Compression failed"
fi

COMPRESSED_SIZE=$(du -h "${BACKUP_PATH}.gz" | cut -f1)
log_success "Compression complete: $COMPRESSED_SIZE (uncompressed: $BACKUP_SIZE)"

# Create metadata file
METADATA_FILE="${BACKUP_PATH}.gz.info"
cat > "$METADATA_FILE" << EOF
PRE-DEPLOYMENT BACKUP METADATA
=====================================
Backup Date: $(date '+%Y-%m-%d %H:%M:%S')
Database: $DB_NAME
Table Count: $TABLE_COUNT
Uncompressed Size: $BACKUP_SIZE
Compressed Size: $COMPRESSED_SIZE
Backup File: $BACKUP_FILENAME.gz
Restore Command: restore_clinic_db.sh --file backups/pre-deployment/${BACKUP_FILENAME}.gz
Created By: pre_backup_export.sh
Status: Ready for deployment
EOF

log_success "Metadata file created"

# Generate recovery script
RECOVERY_SCRIPT="$PRE_DEPLOY_BACKUP_DIR/RESTORE_ON_UBUNTU.sh"
cat > "$RECOVERY_SCRIPT" << 'RECOVERY_EOF'
#!/bin/bash
# This script restores the pre-deployment backup on your Ubuntu/Termux server

if [ ! -f "$(dirname "$0")/$(ls -1 *.sql.gz 2>/dev/null | head -1)" ]; then
    echo "ERROR: No backup file found in this directory!"
    exit 1
fi

BACKUP_FILE=$(ls -t1 *.sql.gz 2>/dev/null | head -1)
echo "Found backup: $BACKUP_FILE"

if [ -z "$BACKUP_FILE" ]; then
    echo "ERROR: No .sql.gz backup files found"
    exit 1
fi

echo "This backup contains your current production data."
echo "It will be imported during the installation process."
echo ""
echo "To restore this backup on Ubuntu:"
echo "1. Copy this backup folder to: /path/to/clinicapp/db-setup/backups/pre-deployment/"
echo "2. Run: cd /path/to/clinicapp/db-setup"
echo "3. Run: ./install_clinic_db.sh --restore-backup"
echo ""
echo "The installation script will automatically detect and restore this backup."
RECOVERY_EOF

chmod +x "$RECOVERY_SCRIPT"
log_success "Recovery script created"

# Final summary
print_summary "PRE-DEPLOYMENT BACKUP COMPLETE"
log_success "✓ Backup File: $BACKUP_FILENAME.gz"
log_success "✓ Location: $(pwd)/$PRE_DEPLOY_BACKUP_DIR/"
log_success "✓ Size: $COMPRESSED_SIZE"
log_success "✓ Tables Backed Up: $TABLE_COUNT"
log_success "✓ Metadata: ${BACKUP_FILENAME}.gz.info"
log_info ""
log_info "NEXT STEPS:"
log_info "1. Copy the entire db-setup folder to your Ubuntu server"
log_info "2. On Ubuntu, run: ./install_clinic_db.sh --restore-backup"
log_info "3. The installation will restore your current data"
log_info ""
log_info "BACKUP LOCATION: $PRE_DEPLOY_BACKUP_DIR"
log_info "METADATA: $(cat $METADATA_FILE)"

exit 0
