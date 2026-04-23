#!/bin/bash
################################################################################
# Common Functions for Database Scripts
# Logging, error handling, and utility functions
################################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Initialize log directory
init_logs() {
    mkdir -p "$LOG_DIR"
    touch "$LOG_FILE"
    chmod 644 "$LOG_FILE"
}

# Log messages to file and console
log_info() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [INFO] $msg" | tee -a "$LOG_FILE"
}

log_error() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${RED}[$timestamp] [ERROR] $msg${NC}" | tee -a "$LOG_FILE"
}

log_success() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${GREEN}[$timestamp] [SUCCESS] $msg${NC}" | tee -a "$LOG_FILE"
}

log_warning() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${YELLOW}[$timestamp] [WARNING] $msg${NC}" | tee -a "$LOG_FILE"
}

# Check if MariaDB is running
check_mariadb_running() {
    if ! pgrep -x "mysqld" > /dev/null; then
        log_error "MariaDB is not running"
        log_info "Start MariaDB with: sudo service mariadb start (or mysqld if running standalone)"
        return 1
    fi
    log_success "MariaDB is running"
    return 0
}

# Test database connection
test_db_connection() {
    local user="$1"
    local pass="$2"
    local host="$3"
    
    if mysql -h "$host" -u "$user" -p"$pass" -e "SELECT 1" > /dev/null 2>&1; then
        log_success "Database connection successful [$user@$host]"
        return 0
    else
        log_error "Failed to connect to database [$user@$host]"
        return 1
    fi
}

# Execute SQL query
execute_sql() {
    local user="$1"
    local pass="$2"
    local host="$3"
    local db="$4"
    local query="$5"
    
    mysql -h "$host" -u "$user" -p"$pass" "$db" -e "$query" 2>&1
}

# Execute SQL file
execute_sql_file() {
    local user="$1"
    local pass="$2"
    local host="$3"
    local db="$4"
    local file="$5"
    
    if [ ! -f "$file" ]; then
        log_error "SQL file not found: $file"
        return 1
    fi
    
    mysql -h "$host" -u "$user" -p"$pass" "$db" < "$file" 2>&1
}

# Create database if not exists
create_database() {
    local user="$1"
    local pass="$2"
    local host="$3"
    local db="$4"
    
    log_info "Creating database: $db"
    execute_sql "$user" "$pass" "$host" "mysql" "CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 
    
    if [ $? -eq 0 ]; then
        log_success "Database created/verified: $db"
        return 0
    else
        log_error "Failed to create database: $db"
        return 1
    fi
}

# Grant privileges to user
grant_privileges() {
    local root_user="$1"
    local root_pass="$2"
    local host="$3"
    local app_user="$4"
    local app_pass="$5"
    local db="$6"
    
    log_info "Granting privileges to $app_user on $db"
    execute_sql "$root_user" "$root_pass" "$host" "mysql" "GRANT ALL PRIVILEGES ON \`$db\`.* TO '$app_user'@'%' IDENTIFIED BY '$app_pass'; FLUSH PRIVILEGES;"
    
    if [ $? -eq 0 ]; then
        log_success "Privileges granted to $app_user"
        return 0
    else
        log_error "Failed to grant privileges"
        return 1
    fi
}

# Cleanup old backups
cleanup_old_backups() {
    local backup_dir="$1"
    local retention_days="${2:-30}"
    
    log_info "Cleaning up backups older than $retention_days days"
    find "$backup_dir" -name "clinic_backup_*.sql.gz" -mtime +"$retention_days" -delete
    log_success "Backup cleanup completed"
}

# Verify backup file integrity
verify_backup() {
    local backup_file="$1"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    log_info "Verifying backup file: $(basename "$backup_file")"
    if gzip -t "$backup_file" 2>/dev/null; then
        log_success "Backup integrity verified"
        return 0
    else
        log_error "Backup file is corrupted"
        return 1
    fi
}

# Exit with error
exit_error() {
    local msg="$1"
    log_error "$msg"
    exit 1
}

# Print section header
print_header() {
    local title="$1"
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $title${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

# Print summary
print_summary() {
    local status="$1"
    local details="$2"
    
    echo ""
    if [ "$status" = "success" ]; then
        echo -e "${GREEN}✓ Operation completed successfully${NC}"
    else
        echo -e "${RED}✗ Operation failed${NC}"
    fi
    
    if [ -n "$details" ]; then
        echo "$details"
    fi
    echo ""
}
