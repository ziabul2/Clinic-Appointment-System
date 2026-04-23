#!/bin/bash
################################################################################
# Clinic App Database Installation Script
# Creates database, tables, applies migrations, sets up user privileges
# Usage: bash install_clinic_db.sh [--skip-backup] [--force] [--restore-backup]
################################################################################

set -euo pipefail

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Source configuration and functions
source "$SCRIPT_DIR/config/db-config.sh"
source "$SCRIPT_DIR/lib-functions.sh"

# Initialize logs
init_logs

# Parse command line arguments
SKIP_BACKUP=false
FORCE_INSTALL=false
RESTORE_BACKUP=false
PRE_DEPLOY_BACKUP_FILE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-backup) SKIP_BACKUP=true; shift ;;
        --force) FORCE_INSTALL=true; shift ;;
        --restore-backup) RESTORE_BACKUP=true; shift ;;
        *) log_warning "Unknown option: $1"; shift ;;
    esac
done

print_header "Clinic App Database Installation"

# Check for pre-deployment backup if --restore-backup flag set
if [ "$RESTORE_BACKUP" = true ]; then
    log_info "Checking for pre-deployment backup files..."
    
    PRE_DEPLOY_DIR="$SCRIPT_DIR/backups/pre-deployment"
    if [ -d "$PRE_DEPLOY_DIR" ]; then
        BACKUP_FILES=$(ls -1 "$PRE_DEPLOY_DIR"/*.sql.gz 2>/dev/null || true)
        if [ ! -z "$BACKUP_FILES" ]; then
            PRE_DEPLOY_BACKUP_FILE=$(ls -t1 "$PRE_DEPLOY_DIR"/*.sql.gz 2>/dev/null | head -1)
            log_success "Found pre-deployment backup: $(basename $PRE_DEPLOY_BACKUP_FILE)"
            RESTORE_BACKUP=true
        else
            log_warning "No pre-deployment backups found in $PRE_DEPLOY_DIR"
            RESTORE_BACKUP=false
        fi
    else
        log_warning "Pre-deployment backup directory not found: $PRE_DEPLOY_DIR"
        RESTORE_BACKUP=false
    fi
fi

# Step 1: Check MariaDB is running
log_info "Step 1: Checking MariaDB service..."
if ! check_mariadb_running; then
    exit_error "Please start MariaDB and try again"
fi

# Step 2: Test root connection
log_info "Step 2: Testing root database connection..."
if ! test_db_connection "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST"; then
    exit_error "Cannot connect to MariaDB as root. Check credentials."
fi

# Step 3: Check if database already exists
log_info "Step 3: Checking if database already exists..."
DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" -se "SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$DB_NAME'" 2>/dev/null || echo "0")

if [ "$DB_EXISTS" = "1" ]; then
    if [ "$FORCE_INSTALL" = false ] && [ "$SKIP_BACKUP" = false ]; then
        log_warning "Database '$DB_NAME' already exists"
        read -p "Do you want to backup the existing database? (y/n) " -n 1 -r REPLY
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            log_info "Running backup before reinstall..."
            bash "$SCRIPT_DIR/backup_clinic_db.sh" || log_warning "Backup failed, continuing anyway"
        fi
    elif [ "$SKIP_BACKUP" = true ]; then
        log_warning "Skipping backup (--skip-backup flag set)"
    fi
    
    if [ "$FORCE_INSTALL" = true ]; then
        log_warning "Dropping existing database (--force flag set)"
        execute_sql "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "mysql" "DROP DATABASE \`$DB_NAME\`;"
        log_success "Database dropped"
    else
        log_info "Database already exists, proceeding with migration only"
    fi
fi

# Step 4: Create database
log_info "Step 4: Creating/verifying database..."
if ! create_database "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "$DB_NAME"; then
    exit_error "Failed to create database"
fi

# Step 5: Create application user if not exists
log_info "Step 5: Setting up application user..."
execute_sql "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "mysql" "CREATE USER IF NOT EXISTS '$DB_APP_USER'@'%' IDENTIFIED BY '$DB_APP_PASS';" 2>/dev/null || true
log_info "User '$DB_APP_USER' created or already exists"

# Step 6: Grant privileges
log_info "Step 6: Granting privileges..."
if ! grant_privileges "$DB_ROOT_USER" "$DB_ROOT_PASS" "$DB_HOST" "$DB_APP_USER" "$DB_APP_PASS" "$DB_NAME"; then
    exit_error "Failed to grant privileges"
fi

# Step 7: Restore pre-deployment backup if available
if [ "$RESTORE_BACKUP" = true ] && [ ! -z "$PRE_DEPLOY_BACKUP_FILE" ]; then
    log_info "Step 7: Restoring pre-deployment backup..."
    
    if [ ! -f "$PRE_DEPLOY_BACKUP_FILE" ]; then
        log_error "Backup file not found: $PRE_DEPLOY_BACKUP_FILE"
        exit_error "Backup file missing"
    fi
    
    log_info "Backup file: $(basename $PRE_DEPLOY_BACKUP_FILE)"
    
    # Decompress and restore
    log_info "Decompressing and restoring database..."
    
    if gunzip -c "$PRE_DEPLOY_BACKUP_FILE" | mysql -u "$DB_ROOT_USER" -p"$DB_ROOT_PASS" -h "$DB_HOST" -P "$DB_PORT" "$DB_NAME" 2>/dev/null; then
        log_success "Pre-deployment backup restored successfully"
        BACKUP_RESTORED=true
    else
        log_error "Failed to restore backup"
        exit_error "Backup restore failed"
    fi
else
    log_info "Step 7: Skipping backup restoration (no backup available or --restore-backup not set)"
    BACKUP_RESTORED=false
fi

# Step 8: Apply migrations (only if backup was not restored)
if [ "$BACKUP_RESTORED" = false ]; then
    log_info "Step 8: Applying database migrations..."
else
    log_info "Step 8: Skipping migrations (backup already contains all data)"
fi


MIGRATION_DIR="$SCRIPT_DIR/migrations"

if [ "$BACKUP_RESTORED" = false ]; then
    if [ ! -d "$MIGRATION_DIR" ]; then
        log_error "Migrations directory not found: $MIGRATION_DIR"
        exit_error "Cannot apply migrations"
    fi

    # Find all .sql files in migrations directory
    migration_count=0
    migration_success=0

    for migration_file in $(ls -1 "$MIGRATION_DIR"/*.sql 2>/dev/null | sort); do
        migration_name=$(basename "$migration_file")
        migration_count=$((migration_count + 1))
        
        log_info "Applying migration: $migration_name"
        
        if execute_sql_file "$DB_APP_USER" "$DB_APP_PASS" "$DB_HOST" "$DB_NAME" "$migration_file"; then
            log_success "Migration applied: $migration_name"
            migration_success=$((migration_success + 1))
        else
            log_error "Failed to apply migration: $migration_name"
            log_warning "Continuing with next migration..."
        fi
    done

    if [ $migration_count -eq 0 ]; then
        log_warning "No migration files found"
    else
        log_info "Migrations completed: $migration_success/$migration_count successful"
    fi
else
    log_info "Skipping migrations - backup restoration handles schema and data"
fi

# Step 9: Verify installation
log_info "Step 9: Verifying database structure..."
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_APP_USER" -p"$DB_APP_PASS" "$DB_NAME" -se "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null || echo "0")

log_success "Database verification complete: $TABLE_COUNT tables found"

# Summary
print_header "Installation Complete"
RESTORE_STATUS=""
if [ "$BACKUP_RESTORED" = true ]; then
    RESTORE_STATUS="(Restored from pre-deployment backup)"
fi

cat << EOF
Database: $DB_NAME $RESTORE_STATUS
Host: $DB_HOST
Port: $DB_PORT
App User: $DB_APP_USER
Tables: $TABLE_COUNT

Next steps:
1. Update config/config.php in your PHP application with these credentials
2. Test the application by logging in
3. Run 'bash backup_clinic_db.sh' to create a backup
4. Review logs at: $LOG_FILE

For troubleshooting, check the log file above.
EOF

log_success "Database installation script completed successfully"
print_summary "success"

