#!/bin/bash
################################################################################
# Clinic App Database Backup Script
# Backs up all databases, compresses with gzip, timestamps, verifies integrity
# Usage: bash backup_clinic_db.sh [--full] [--daily]
################################################################################

set -euo pipefail

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Source configuration and functions
source "$SCRIPT_DIR/config/db-config.sh"
source "$SCRIPT_DIR/lib-functions.sh"

# Initialize logs
init_logs

# Parse arguments
BACKUP_TYPE="incremental"
while [[ $# -gt 0 ]]; do
    case $1 in
        --full) BACKUP_TYPE="full"; shift ;;
        --daily) BACKUP_TYPE="daily"; shift ;;
        *) log_warning "Unknown option: $1"; shift ;;
    esac
done

print_header "Clinic App Database Backup"

# Step 1: Verify MariaDB
log_info "Step 1: Checking MariaDB service..."
if ! check_mariadb_running; then
    exit_error "MariaDB is not running. Cannot backup."
fi

# Step 2: Verify connection
log_info "Step 2: Testing database connection..."
if ! test_db_connection "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST"; then
    exit_error "Cannot connect to database"
fi

# Step 3: Create backup directory
log_info "Step 3: Preparing backup directory..."
mkdir -p "$BACKUP_DIR"
chmod 755 "$BACKUP_DIR"
log_success "Backup directory ready: $BACKUP_DIR"

# Step 4: Generate backup filename
BACKUP_DATE=$(date +%Y%m%d)
BACKUP_TIME=$(date +%H%M%S)
BACKUP_NAME="clinic_backup_${BACKUP_DATE}_${BACKUP_TIME}"
BACKUP_SQL="$BACKUP_DIR/${BACKUP_NAME}.sql"
BACKUP_GZ="$BACKUP_DIR/${BACKUP_NAME}.sql.gz"
BACKUP_INFO="$BACKUP_DIR/${BACKUP_NAME}.info"

log_info "Backup type: $BACKUP_TYPE"
log_info "Backup file: $BACKUP_GZ"

# Step 5: Export database
log_info "Step 5: Exporting database..."
START_TIME=$(date +%s)

if mysqldump -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_SQL" 2>/dev/null; then
    
    log_success "Database export completed"
else
    rm -f "$BACKUP_SQL"
    exit_error "Failed to export database"
fi

# Step 6: Compress backup
log_info "Step 6: Compressing backup..."
if gzip -9 "$BACKUP_SQL"; then
    log_success "Backup compressed: $(du -h "$BACKUP_GZ" | cut -f1)"
else
    rm -f "$BACKUP_SQL"
    exit_error "Failed to compress backup"
fi

# Step 7: Create backup info file
log_info "Step 7: Creating backup metadata..."
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
BACKUP_SIZE=$(du -h "$BACKUP_GZ" | cut -f1)

cat > "$BACKUP_INFO" << EOF
Backup Information
==================
Database: $DB_NAME
Backup Date: $BACKUP_DATE
Backup Time: $BACKUP_TIME
Backup Type: $BACKUP_TYPE
Duration: ${DURATION}s
Size: $BACKUP_SIZE
Compressed File: $(basename "$BACKUP_GZ")

MariaDB Details
===============
Host: $DB_HOST
Port: $DB_PORT
User: $DB_ROOT_USER

Restore Command
===============
bash restore_clinic_db.sh --file "$BACKUP_GZ"

EOF

log_success "Backup metadata created"

# Step 8: Verify backup
log_info "Step 8: Verifying backup integrity..."
if verify_backup "$BACKUP_GZ"; then
    log_success "Backup verified successfully"
else
    exit_error "Backup verification failed"
fi

# Step 9: Cleanup old backups
if [ "$BACKUP_COMPRESS" = true ]; then
    log_info "Step 9: Cleaning up old backups..."
    cleanup_old_backups "$BACKUP_DIR" "$BACKUP_RETENTION_DAYS"
    log_success "Old backups cleaned up"
fi

# Summary
print_header "Backup Complete"
cat << EOF
Backup File: $(basename "$BACKUP_GZ")
Location: $BACKUP_DIR
Size: $BACKUP_SIZE
Duration: ${DURATION}s

Backup Details:
$(cat "$BACKUP_INFO" | head -15)

Next steps:
1. Copy backup to secure location: cp "$BACKUP_GZ" /path/to/secure/location/
2. Store credentials securely
3. Test restore procedure periodically

Log: $LOG_FILE
EOF

log_success "Backup script completed successfully"
print_summary "success"
