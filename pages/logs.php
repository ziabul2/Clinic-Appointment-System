<?php
// Include config manually for auth before any HTML output
require_once __DIR__ . '/../config/config.php';

// Auth check
if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'root'])) {
    header("Location: ../index.php");
    exit;
}

$logDir = realpath(__DIR__ . '/../logs');

// AJAX handler for loading log content
if (isset($_GET['ajax_load'])) {
    $file = $_GET['ajax_load'];
    $filePath = realpath($logDir . DIRECTORY_SEPARATOR . $file);

    if ($filePath && strpos($filePath, $logDir) === 0 && is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext === 'log') {
            $maxRead = 102400; // 100KB
            $size = filesize($filePath);
            
            if ($size > $maxRead) {
                $f = fopen($filePath, 'r');
                fseek($f, $size - $maxRead);
                $content = fread($f, $maxRead);
                fclose($f);
                echo "<div class='text-muted small border-bottom mb-3 pb-2'><i class='fas fa-info-circle me-2'></i>Showing last 100KB of " . htmlspecialchars($file) . " (Newest at top)</div>";
            } else {
                $content = file_get_contents($filePath);
                echo "<div class='text-muted small border-bottom mb-3 pb-2'><i class='fas fa-info-circle me-2'></i>Showing " . htmlspecialchars($file) . " (Newest at top)</div>";
            }

            $lines = explode("\n", trim($content));
            $lines = array_reverse($lines);
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                // Human-readable formatting: Try to catch [DATE] LEVEL: MESSAGE
                $line = htmlspecialchars($line);
                
                // Colorize Dates
                $line = preg_replace('/^\[(.*?)\]/', '<span class="text-info fw-bold">[$1]</span>', $line);
                
                // Colorize Levels
                $line = preg_replace('/(ERROR|FATAL|WARNING|CRITICAL):/i', '<span class="text-danger fw-bold">$1:</span>', $line);
                $line = preg_replace('/(INFO|SUCCESS|NOTICE):/i', '<span class="text-success fw-bold">$1:</span>', $line);
                $line = preg_replace('/(DEBUG):/i', '<span class="text-warning fw-bold">$1:</span>', $line);
                
                echo "<div class='log-line py-1 border-bottom border-dark border-opacity-25'>" . $line . "</div>";
            }
        } else {
            echo "<div class='text-danger'>Access Denied: Invalid file type.</div>";
        }
    } else {
        echo "<div class='text-danger'>Access Denied: Invalid path.</div>";
    }
    exit;
}

$page_title = 'System Logs';
require_once '../includes/header.php';

// Get all log files
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $f) {
        if (is_file($logDir . DIRECTORY_SEPARATOR . $f) && pathinfo($f, PATHINFO_EXTENSION) === 'log') {
            $logFiles[] = [
                'name' => $f,
                'size' => filesize($logDir . DIRECTORY_SEPARATOR . $f),
                'mtime' => filemtime($logDir . DIRECTORY_SEPARATOR . $f)
            ];
        }
    }
}

// Sort by modified time (newest first)
usort($logFiles, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar: Log List -->
        <div class="col-lg-3 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Log Files</h5>
                    <span class="badge bg-primary rounded-pill"><?php echo count($logFiles); ?></span>
                </div>
                <div class="p-3 bg-light border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="logSearch" class="form-control border-start-0 ps-0" placeholder="Filter logs...">
                    </div>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: calc(100vh - 250px);" id="logFileList">
                    <?php if (empty($logFiles)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-ghost fa-3x mb-3"></i>
                            <p>No log files found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logFiles as $log): ?>
                            <button type="button" 
                                    class="list-group-item list-group-item-action border-0 py-3 log-item" 
                                    data-filename="<?php echo htmlspecialchars($log['name']); ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 text-truncate fw-bold"><?php echo htmlspecialchars($log['name']); ?></h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M d, H:i', $log['mtime']); ?></small>
                                    <small class="badge bg-light text-dark border fw-normal"><?php echo formatSize($log['size']); ?></small>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Area: Log Viewer -->
        <div class="col-lg-9 col-md-8">
            <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div id="viewerIcon" class="icon-box bg-light-primary text-primary me-3 rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-terminal"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark" id="currentFileName">Select a log file</h5>
                            <small class="text-secondary" id="currentFileMeta">Choose a file from the list to view its content</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="refreshBtn" class="btn btn-sm btn-primary rounded-pill px-3" style="display:none;">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <a href="admin_tools.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                
                <div class="card-body bg-black p-0 position-relative flex-grow-1" style="min-height: 500px;">
                    <!-- Loading Overlay -->
                    <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-black bg-opacity-75 d-flex align-items-center justify-content-center z-3" style="display:none !important;">
                        <div class="text-center text-white">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="mb-0">Loading content...</p>
                        </div>
                    </div>

                    <!-- Viewer -->
                    <pre id="logViewer" class="m-0 p-4 text-success overflow-auto w-100 h-100" style="font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 13px; line-height: 1.6; white-space: pre-wrap;"></pre>
                    
                    <!-- Empty State for Viewer -->
                    <div id="viewerEmptyState" class="w-100 h-100 d-flex align-items-center justify-content-center text-center p-5">
                        <div class="text-muted">
                            <div class="icon-box bg-light text-secondary rounded-circle mx-auto mb-4" style="width:80px; height:80px; display:flex; align-items:center; justify-content:center; font-size: 32px;">
                                <i class="fas fa-glasses"></i>
                            </div>
                            <h3 class="text-dark">Log Viewer Ready</h3>
                            <p>Select a file to begin diagnostics</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center border-0">
                    <small id="viewerStatus">Ready</small>
                    <div class="d-flex align-items-center gap-3">
                        <button id="copyBtn" class="btn btn-xs btn-outline-light py-0 px-2" style="font-size: 11px; display:none;">
                            <i class="fas fa-copy me-1"></i>Copy All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #logViewer::-webkit-scrollbar { width: 8px; height: 8px; }
    #logViewer::-webkit-scrollbar-track { background: #000; }
    #logViewer::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
    #logViewer::-webkit-scrollbar-thumb:hover { background: #444; }
    
    .log-item.active {
        background-color: #e9ecef !important;
        border-left: 4px solid #0d6efd !important;
    }
    
    .bg-black { background-color: #0c0c0c !important; }
    .text-success { color: #50fa7b !important; }
    
    .bg-light-primary { background: rgba(13, 110, 253, 0.1); }
    .icon-box i { font-size: 1.2rem; }
    
    .btn-xs { padding: 0.1rem 0.4rem; font-size: 0.75rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logViewer = document.getElementById('logViewer');
    const logFileList = document.getElementById('logFileList');
    const logItems = document.querySelectorAll('.log-item');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const viewerEmptyState = document.getElementById('viewerEmptyState');
    const currentFileName = document.getElementById('currentFileName');
    const currentFileMeta = document.getElementById('currentFileMeta');
    const viewerStatus = document.getElementById('viewerStatus');
    const refreshBtn = document.getElementById('refreshBtn');
    const copyBtn = document.getElementById('copyBtn');
    const logSearch = document.getElementById('logSearch');
    
    let activeFile = null;

    function loadLog(filename) {
        if (!filename) return;
        activeFile = filename;
        
        loadingOverlay.style.setProperty('display', 'flex', 'important');
        viewerEmptyState.style.display = 'none';
        viewerStatus.textContent = 'Loading ' + filename + '...';
        
        fetch(`logs.php?ajax_load=${encodeURIComponent(filename)}`)
            .then(response => response.text())
            .then(data => {
                logViewer.innerHTML = data;
                currentFileName.textContent = filename;
                currentFileMeta.textContent = 'Updated: ' + new Date().toLocaleTimeString();
                viewerStatus.textContent = 'Showing ' + filename + ' (Newest first)';
                
                refreshBtn.style.display = 'block';
                copyBtn.style.display = 'block';
                
                // Scroll to TOP because newest entries are at top
                logViewer.scrollTop = 0;
            })
            .catch(error => {
                logViewer.textContent = 'Error: ' + error;
                viewerStatus.textContent = 'Error loading file';
            })
            .finally(() => {
                loadingOverlay.style.setProperty('display', 'none', 'important');
            });
    }

    logItems.forEach(item => {
        item.addEventListener('click', function() {
            logItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            loadLog(this.dataset.filename);
        });
    });

    refreshBtn.addEventListener('click', function() {
        if (activeFile) loadLog(activeFile);
    });

    copyBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(logViewer.textContent).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            setTimeout(() => this.innerHTML = originalText, 2000);
        });
    });

    logSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        logItems.forEach(item => {
            const name = item.dataset.filename.toLowerCase();
            item.style.display = name.includes(query) ? 'block' : 'none';
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
