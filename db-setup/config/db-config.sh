#!/bin/bash
################################################################################
# Database Configuration for Clinic App
# MariaDB/MySQL connection settings for Ubuntu/Termux environment
################################################################################

# MariaDB Root Account
DB_ROOT_USER="root"
DB_ROOT_PASS="root123"

# Application Database User
DB_APP_USER="ziabul"
DB_APP_PASS="4080099"

# Database Configuration
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="clinic_management"

# Backup Configuration
BACKUP_DIR="$(dirname "$0")/../backups"
BACKUP_RETENTION_DAYS=30
BACKUP_COMPRESS=true

# Log Configuration
LOG_DIR="$(dirname "$0")/../logs"
LOG_FILE="$LOG_DIR/clinic-db-$(date +%Y%m%d).log"

# Error Handling
ENABLE_ERROR_LOGGING=true
ENABLE_EMAIL_ALERTS=false
ALERT_EMAIL="admin@clinicapp.local"

# Script Paths
MIGRATION_DIR="$(dirname "$0")/../migrations"
SCRIPT_DIR="$(dirname "$0")/.."

# Timestamp format
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
