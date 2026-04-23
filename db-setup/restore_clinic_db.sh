#!/bin/bash
################################################################################
# Clinic App Database Restore Script
# Restores from backup with verification and rollback on error
# Usage: bash restore_clinic_db.sh --file backup_file.sql.gz [--verify-only]
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
RESTORE_FILE=""
VERIFY_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --file) RESTORE_FILE="$2"; shift 2 ;;
        --verify-only) VERIFY_ONLY=true; shift ;;
        *) log_error "Unknown option: $1"; shift ;;
    esac
done

if [ -z "$RESTORE_FILE" ]; then
    log_error "Usage: bash restore_clinic_db.sh --file <backup_file.sql.gz> [--verify-only]"
    exit 1
fi

print_header "Clinic App Database Restore"

# Step 1: Verify backup file exists
log_info "Step 1: Checking backup file..."
if [ ! -f "$RESTORE_FILE" ]; then
    exit_error "Backup file not found: $RESTORE_FILE"
fi

# Step 2: Verify backup integrity
log_info "Step 2: Verifying backup integrity..."
if ! verify_backup "$RESTORE_FILE"; then
    exit_error "Backup file is corrupted, cannot restore"
fi

if [ "$VERIFY_ONLY" = true ]; then
    log_success "Backup verification successful (--verify-only mode)"
    print_summary "success" "Backup file is valid and can be restored"
    exit 0
fi

# Step 3: Check MariaDB
log_info "Step 3: Checking MariaDB service..."
if ! check_mariadb_running; then
    exit_error "MariaDB is not running"
fi

# Step 4: Test connection
log_info "Step 4: Testing database connection..."
if ! test_db_connection "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST"; then
    exit_error "Cannot connect to database"
fi

# Step 5: Create backup before restore (safety measure)
log_info "Step 5: Creating safety backup of current database..."
SAFETY_BACKUP="$BACKUP_DIR/pre_restore_backup_$(date +%Y%m%d_%H%M%S).sql.gz"

if mysqldump -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" \
    --single-transaction "$DB_NAME" 2>/dev/null | gzip -9 > "$SAFETY_BACKUP"; then
    log_success "Safety backup created: $(basename "$SAFETY_BACKUP")"
else
    log_warning "Failed to create safety backup, proceeding with caution"
fi

# Step 6: Confirm restore
read -p "This will replace the current database. Are you sure? (yes/no) " -r CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    log_warning "Restore cancelled by user"
    exit 0
fi

# Step 7: Drop current database
log_info "Step 7: Preparing database for restore..."
log_warning "Dropping current database: $DB_NAME"
execute_sql "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "mysql" "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
log_success "Old database dropped"

# Step 8: Create fresh database
log_info "Step 8: Creating fresh database..."
if ! create_database "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "$DB_NAME"; then
    log_error "Failed to create database, attempting rollback..."
    
    # Rollback: restore from safety backup
    if [ -f "$SAFETY_BACKUP" ]; then
        log_warning "Restoring from safety backup..."
        gunzip -c "$SAFETY_BACKUP" | mysql -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" "$DB_NAME" 2>/dev/null || true
        log_warning "Rollback attempted. Please verify database integrity."
    fi
    exit_error "Restore failed with rollback"
fi

# Step 9: Restore database
log_info "Step 9: Restoring database from backup..."
START_TIME=$(date +%s)

if gunzip -c "$RESTORE_FILE" | mysql -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" "$DB_NAME" 2>/dev/null; then
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    log_success "Database restored successfully (${DURATION}s)"
else
    log_error "Failed to restore database"
    
    # Rollback
    if [ -f "$SAFETY_BACKUP" ]; then
        log_warning "Attempting rollback..."
        gunzip -c "$SAFETY_BACKUP" | mysql -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" "$DB_NAME" 2>/dev/null || true
        log_warning "Rollback completed. Check data integrity."
    fi
    exit_error "Restore failed"
fi

# Step 10: Verify restore
log_info "Step 10: Verifying restore..."
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" "$DB_NAME" -se "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null || echo "0")

log_success "Database verification: $TABLE_COUNT tables"

# Summary
print_header "Restore Complete"
cat << EOF
Database: $DB_NAME
Restore File: $(basename "$RESTORE_FILE")
Tables Restored: $TABLE_COUNT
Duration: ${DURATION}s

Safety Backup: $(basename "$SAFETY_BACKUP")
Location: $BACKUP_DIR

Next steps:
1. Test application functionality
2. Monitor for any errors in application logs
3. Verify all data integrity

Log: $LOG_FILE
EOF

log_success "Restore script completed successfully"
print_summary "success"
