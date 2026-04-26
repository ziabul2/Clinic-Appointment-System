<?php
$page_title = 'Admin Tools';
require_once '../includes/header.php';
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tools"></i> Admin Tools</h1>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <!-- Database & Schema Tools -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <i class="fas fa-database"></i> Database & Schema
            </div>
            <div class="card-body">
                <a href="../private/tools/db_check.php" class="btn btn-block btn-sm btn-outline-primary mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-heartbeat"></i> Database Health Check
                </a>
                <a href="../private/tools/repair_schema.php" class="btn btn-block btn-sm btn-outline-primary mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-wrench"></i> Repair Schema
                </a>
                <a href="../private/tools/apply_schema_fixes.php" class="btn btn-block btn-sm btn-outline-primary mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-tools"></i> Apply Schema Fixes
                </a>
                <a href="../private/tools/check_includes.php" class="btn btn-block btn-sm btn-outline-primary" style="width:100%;text-align:left;">
                    <i class="fas fa-list"></i> Check Includes
                </a>
            </div>
        </div>
    </div>

    <!-- Email & Communication Tools -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <i class="fas fa-envelope"></i> Email & Communication
            </div>
            <div class="card-body">
                <a href="../private/tools/send_test_mail.php" class="btn btn-block btn-sm btn-outline-success mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-envelope"></i> Send Test Email
                </a>
                <a href="../private/tools/send_set_passwords.php" class="btn btn-block btn-sm btn-outline-success mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-key"></i> Send Set Password Links
                </a>
                <a href="../private/tools/send_prescription_test.php" class="btn btn-block btn-sm btn-outline-success" style="width:100%;text-align:left;">
                    <i class="fas fa-file-prescription"></i> Send Prescription Test
                </a>
            </div>
        </div>
    </div>

    <!-- Security & Tokens -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-lock"></i> Security & Tokens
            </div>
            <div class="card-body">
                <a href="../private/tools/purge_expired_tokens.php" class="btn btn-block btn-sm btn-outline-warning mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-trash"></i> Purge Expired Tokens
                </a>
                <a href="../private/tools/test_login.php" class="btn btn-block btn-sm btn-outline-warning" style="width:100%;text-align:left;">
                    <i class="fas fa-sign-in-alt"></i> Test Login System
                </a>
            </div>
        </div>
    </div>

    <!-- Migration & Setup Tools -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-cogs"></i> Migration & Setup
            </div>
            <div class="card-body">
                <a href="../private/tools/migrate_add_users_profile_columns.php" class="btn btn-block btn-sm btn-outline-secondary mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-user-plus"></i> Add User Profile Columns
                </a>
                <a href="map_patients_users.php" class="btn btn-block btn-sm btn-outline-secondary" style="width:100%;text-align:left;">
                    <i class="fas fa-link"></i> Map Patients to Users
                </a>
            </div>
        </div>
    </div>

    <!-- Booking & Simulation -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-calendar"></i> Booking & Simulation
            </div>
            <div class="card-body">
                <a href="../private/tools/simulate_booking.php" class="btn btn-block btn-sm btn-outline-primary" style="width:100%;text-align:left;">
                    <i class="fas fa-vial"></i> Simulate Appointment Booking
                </a>
            </div>
        </div>
    </div>

    <!-- Data Management -->
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-file"></i> Data Management
            </div>
            <div class="card-body">
                <a href="../private/tools/auto_update_archive_refs.php" class="btn btn-block btn-sm btn-outline-danger mb-2" style="width:100%;text-align:left;">
                    <i class="fas fa-link"></i> Update Archive References
                </a>
                <a href="../private/tools/link_checker.php" class="btn btn-block btn-sm btn-outline-danger" style="width:100%;text-align:left;">
                    <i class="fas fa-link"></i> Check Links
                </a>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <strong>Note:</strong> All tools on this page are restricted to administrators only. Each tool performs specific maintenance, debugging, or configuration tasks for the clinic management system.
</div>

<?php require_once '../includes/footer.php';
