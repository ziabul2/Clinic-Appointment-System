<?php
$page_title = 'Admin Tools';
require_once '../includes/header.php';
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');

// Fetch Medicine Stats
$medStats = [];
try {
    $q = $db->prepare("SELECT COUNT(*) as total FROM medicine_master_data");
    $q->execute();
    $medStats['total_medicines'] = $q->fetch(PDO::FETCH_ASSOC)['total'];

    $q = $db->prepare("SELECT COUNT(DISTINCT drug_class) as total FROM medicine_master_data");
    $q->execute();
    $medStats['total_categories'] = $q->fetch(PDO::FETCH_ASSOC)['total'];

    $q = $db->prepare("SELECT COUNT(DISTINCT manufacturer) as total FROM medicine_master_data");
    $q->execute();
    $medStats['total_manufacturers'] = $q->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    $medStats = ['total_medicines' => 0, 'total_categories' => 0, 'total_manufacturers' => 0];
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-tools text-primary me-2"></i>System Administration & Tools</h2>
            <p class="text-muted">Manage system health, security, and audit logs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="session_history.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-history me-2"></i>Session History
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- System Monitoring & Audits -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-primary text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Monitoring & Audits</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="session_history.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="icon-box bg-light-primary text-primary me-3 rounded-3 p-2">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Session History</h6>
                                <small class="text-muted">View detailed login/logout logs and durations.</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="db_check.php">
                            <div class="icon-box bg-light-info text-info me-3 rounded-3 p-2">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Database Health</h6>
                                <small class="text-muted">Check database connectivity and integrity.</small>
                            </div>
                        </a>
                        <a href="logs.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="icon-box bg-light-danger text-danger me-3 rounded-3 p-2">
                                <i class="fas fa-file-medical-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">System Logs</h6>
                                <small class="text-muted">View application and error logs smoothly.</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schema & Maintenance -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-info text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-database me-2"></i>Database & Schema</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="db_check.php">
                            <div class="icon-box bg-light-info text-info me-3 rounded-3 p-2"><i class="fas fa-check-double"></i></div>
                            <div><h6 class="mb-0">Check Connectivity</h6><small class="text-muted">Verify DB link and latency.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="repair_schema.php">
                            <div class="icon-box bg-light-warning text-warning me-3 rounded-3 p-2"><i class="fas fa-tools"></i></div>
                            <div><h6 class="mb-0">Repair Schema</h6><small class="text-muted">Fix missing tables or columns.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="inspect_schema.php">
                            <div class="icon-box bg-light-secondary text-secondary me-3 rounded-3 p-2"><i class="fas fa-search"></i></div>
                            <div><h6 class="mb-0">Inspect Schema</h6><small class="text-muted">View raw table structures.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="apply_schema_fixes.php">
                            <div class="icon-box bg-light-success text-success me-3 rounded-3 p-2"><i class="fas fa-patch-check"></i></div>
                            <div><h6 class="mb-0">Apply Fixes</h6><small class="text-muted">Run pending migrations.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="db_backup.php">
                            <div class="icon-box bg-light-warning text-warning me-3 rounded-3 p-2"><i class="fas fa-download"></i></div>
                            <div><h6 class="mb-0">Database Backup</h6><small class="text-muted">Export SQL and JSON snapshots.</small></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- User & Security Management -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-danger text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-user-shield me-2"></i>User & Security</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="purge_expired_tokens.php">
                            <div class="icon-box bg-light-danger text-danger me-3 rounded-3 p-2"><i class="fas fa-broom"></i></div>
                            <div><h6 class="mb-0">Purge Tokens</h6><small class="text-muted">Clear expired reset tokens.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="dump_users.php">
                            <div class="icon-box bg-light-dark text-dark me-3 rounded-3 p-2"><i class="fas fa-users-viewfinder"></i></div>
                            <div><h6 class="mb-0">Dump Users</h6><small class="text-muted">Export user list with roles.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="send_set_passwords.php">
                            <div class="icon-box bg-light-warning text-warning me-3 rounded-3 p-2"><i class="fas fa-key"></i></div>
                            <div><h6 class="mb-0">Reset Invites</h6><small class="text-muted">Send password set links to users.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="set_password.php">
                            <div class="icon-box bg-light-primary text-primary me-3 rounded-3 p-2"><i class="fas fa-user-lock"></i></div>
                            <div><h6 class="mb-0">Set Password</h6><small class="text-muted">Force update a user password.</small></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Developer & Code Utilities -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-dark text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-code me-2"></i>Developer Utilities</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="check_includes.php">
                            <div class="icon-box bg-light-secondary text-secondary me-3 rounded-3 p-2"><i class="fas fa-file-import"></i></div>
                            <div><h6 class="mb-0">Check Includes</h6><small class="text-muted">Verify file dependency integrity.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="find_refs.php">
                            <div class="icon-box bg-light-info text-info me-3 rounded-3 p-2"><i class="fas fa-search-location"></i></div>
                            <div><h6 class="mb-0">Find References</h6><small class="text-muted">Scan codebase for specific calls.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="check_braces.php">
                            <div class="icon-box bg-light-danger text-danger me-3 rounded-3 p-2"><i class="fas fa-code-branch"></i></div>
                            <div><h6 class="mb-0">Syntax Check</h6><small class="text-muted">Verify brace levels and balance.</small></div>
                        </a>
                    </div>
                </div>
            </div>
</div>

        <!-- Communication & Diagnostics -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-success text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-vial me-2"></i>Diagnostics & Comms</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="send_test_mail.php">
                            <div class="icon-box bg-light-success text-success me-3 rounded-3 p-2"><i class="fas fa-paper-plane"></i></div>
                            <div><h6 class="mb-0">Test Email</h6><small class="text-muted">Verify SMTP configuration.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="send_prescription_test.php">
                            <div class="icon-box bg-light-info text-info me-3 rounded-3 p-2"><i class="fas fa-file-prescription"></i></div>
                            <div><h6 class="mb-0">Test Rx Delivery</h6><small class="text-muted">Simulate Rx email to patient.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="simulate_booking.php">
                            <div class="icon-box bg-light-primary text-primary me-3 rounded-3 p-2"><i class="fas fa-robot"></i></div>
                            <div><h6 class="mb-0">Simulate Booking</h6><small class="text-muted">Run automated appointment tests.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="link_checker.php">
                            <div class="icon-box bg-light-secondary text-secondary me-3 rounded-3 p-2"><i class="fas fa-link"></i></div>
                            <div><h6 class="mb-0">Link Checker</h6><small class="text-muted">Audit system internal routes.</small></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage & Assets -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-warning text-dark border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-folder-open me-2"></i>Storage & Assets</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="manage_uploads.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="icon-box bg-light-warning text-warning me-3 rounded-3 p-2"><i class="fas fa-file-invoice"></i></div>
                            <div><h6 class="mb-0">File Manager</h6><small class="text-muted">Manage all uploaded files and media.</small></div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="clear_temp.php">
                            <div class="icon-box bg-light-danger text-danger me-3 rounded-3 p-2"><i class="fas fa-trash-alt"></i></div>
                            <div><h6 class="mb-0">Clear Cache</h6><small class="text-muted">Purge temporary files and logs.</small></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hybrid Database & Sync -->
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-up">
                <div class="card-header bg-secondary text-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-sync-alt me-2"></i>Offline Sync Engine</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div id="syncStatusBadge" class="badge rounded-pill px-3 py-2 me-3 <?php echo DB_OK ? 'bg-success' : 'bg-danger'; ?>">
                            <i class="fas <?php echo DB_OK ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-1"></i>
                            <?php echo DB_OK ? 'Online' : 'Offline Mode'; ?>
                        </div>
                        <div id="pendingSyncCount" class="small text-muted fw-bold">
                            Checking pending changes...
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <button id="manualSyncBtn" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center" <?php echo !DB_OK ? 'disabled' : ''; ?>>
                            <div class="icon-box bg-light-primary text-primary me-3 rounded-3 p-2"><i class="fas fa-cloud-upload-alt"></i></div>
                            <div><h6 class="mb-0">Sync Pending Changes</h6><small class="text-muted">Manually push offline changes to MySQL.</small></div>
                        </button>
                        <button id="rebuildIndexBtn" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                            <div class="icon-box bg-light-success text-success me-3 rounded-3 p-2"><i class="fas fa-bolt"></i></div>
                            <div><h6 class="mb-0">Optimize Search Cache</h6><small class="text-muted">Rebuild index for super fast medicine search.</small></div>
                        </button>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center run-tool-btn" data-tool="db_backup.php">
                            <div class="icon-box bg-light-info text-info me-3 rounded-3 p-2"><i class="fas fa-file-export"></i></div>
                            <div><h6 class="mb-0">Export JSON Snapshot</h6><small class="text-muted">Force update all JSON files from MySQL.</small></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medicine Directory Analytics Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-microscope me-2 text-info"></i> Medicine Directory Analytics</h5>
                    <a href="medicine_search.php" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm">Open Search Explorer</a>
                </div>
                <div class="card-body bg-light bg-opacity-50 p-4">
                    <div class="row g-4 text-center">
                        <div class="col-md-4">
                            <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-bottom border-4 border-info">
                                <div class="icon-box bg-soft-info text-info rounded-circle mx-auto mb-3" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; font-size: 28px; background-color: rgba(13, 202, 240, 0.1);">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($medStats['total_medicines']); ?></h2>
                                <p class="text-muted small mb-0 fw-bold text-uppercase">Total Medicines</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-bottom border-4 border-primary">
                                <div class="icon-box bg-soft-primary text-primary rounded-circle mx-auto mb-3" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; font-size: 28px; background-color: rgba(13, 110, 253, 0.1);">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($medStats['total_categories']); ?></h2>
                                <p class="text-muted small mb-0 fw-bold text-uppercase">Drug Categories</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-bottom border-4 border-success">
                                <div class="icon-box bg-soft-success text-success rounded-circle mx-auto mb-3" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; font-size: 28px; background-color: rgba(25, 135, 84, 0.1);">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($medStats['total_manufacturers']); ?></h2>
                                <p class="text-muted small mb-0 fw-bold text-uppercase">Pharmaceuticals</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Output Terminal -->
    <div class="mt-4 card border-0 shadow-sm" id="toolOutputCard" style="display:none;">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span class="small fw-bold"><i class="fas fa-terminal me-2"></i>Tool Execution Output</span>
            <button type="button" class="btn-close btn-close-white btn-sm" onclick="document.getElementById('toolOutputCard').style.display='none'"></button>
        </div>
        <div class="card-body bg-black p-0">
            <pre id="toolOutput" class="p-3 text-success mb-0" style="max-height: 400px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 13px;"></pre>
        </div>
    </div>
</div>

<style>
.hover-up { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-up:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
.bg-light-primary { background: rgba(13, 110, 253, 0.1); }
.bg-light-info { background: rgba(13, 202, 240, 0.1); }
.bg-light-success { background: rgba(25, 135, 84, 0.1); }
.bg-light-warning { background: rgba(255, 193, 7, 0.1); }
.bg-light-danger { background: rgba(220, 53, 69, 0.1); }
.bg-black { background: #111; }
</style>

<script>
document.querySelectorAll('.run-tool-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const tool = this.dataset.tool;
        const outCard = document.getElementById('toolOutputCard');
        const outPre = document.getElementById('toolOutput');
        
        outCard.style.display = 'block';
        outPre.textContent = 'Executing ' + tool + '...\n';
        outCard.scrollIntoView({ behavior: 'smooth' });

        const formData = new FormData();
        formData.append('tool_file', tool);
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

        fetch('../process.php?action=run_tool', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                outPre.textContent += res.output || 'No output returned.';
            } else {
                outPre.innerHTML += '<span class="text-danger">Error: ' + res.message + '</span>';
            }
        })
        .catch(err => {
            outPre.innerHTML += '<span class="text-danger">Request Failed: ' + err.message + '</span>';
        });
    });
});

// Hybrid DB Sync Logic
function updateSyncStatus() {
    fetch('../ajax/sync_db.php?action=status')
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            const countElem = document.getElementById('pendingSyncCount');
            if (res.pending_count > 0) {
                countElem.innerHTML = `<span class="text-warning">${res.pending_count} pending changes</span>`;
                if (!res.is_offline) document.getElementById('manualSyncBtn').disabled = false;
            } else {
                countElem.textContent = 'All data synchronized';
            }
        }
    })
    .catch(err => console.error('Sync check failed:', err));
}

document.getElementById('manualSyncBtn').addEventListener('click', function() {
    const btn = this;
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-3" role="status"></div><div><h6 class="mb-0">Syncing...</h6></div>';

    fetch('../ajax/sync_db.php?action=sync')
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            alert(res.message);
            updateSyncStatus();
        } else {
            alert('Sync failed: ' + res.message);
        }
    })
    .catch(err => alert('Request failed: ' + err.message))
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
});

document.getElementById('rebuildIndexBtn').addEventListener('click', function() {
    const btn = this;
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm text-success me-3" role="status"></div><div><h6 class="mb-0">Indexing...</h6></div>';

    fetch('../ajax/sync_db.php?action=rebuild_index')
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            alert(res.message);
        } else {
            alert('Indexing failed: ' + res.message);
        }
    })
    .catch(err => alert('Request failed: ' + err.message))
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
});

// Initial check
updateSyncStatus();
// Poll every 30 seconds
setInterval(updateSyncStatus, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>
