<?php
$page_title = 'System File Manager';
require_once '../includes/header.php';

if (!isLoggedIn() || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin','root'])) redirect('../index.php');

$upload_root = realpath(__DIR__ . '/../uploads');

// Handle Deletion
if (isset($_POST['delete_file'])) {
    $file_to_delete = $_POST['delete_file'];
    $real_path = realpath($upload_root . DIRECTORY_SEPARATOR . $file_to_delete);
    
    if ($real_path && strpos($real_path, $upload_root) === 0 && file_exists($real_path)) {
        if (is_file($real_path)) {
            unlink($real_path);
            $_SESSION['success'] = "File deleted successfully.";
        }
    } else {
        $_SESSION['error'] = "Invalid file path.";
    }
    header("Location: manage_uploads.php");
    exit;
}

// Function to scan directory recursively
function getFileList($dir) {
    $results = [];
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = [
                'name' => $value,
                'path' => $path,
                'rel_path' => str_replace(realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR, '', $path),
                'size' => filesize($path),
                'mtime' => filemtime($path)
            ];
        } else if ($value != "." && $value != "..") {
            $results = array_merge($results, getFileList($path));
        }
    }
    return $results;
}

$allFiles = getFileList($upload_root);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-folder-open text-warning me-2"></i>System File Manager</h2>
            <p class="text-muted">Manage system assets, user uploads, and chat attachments.</p>
        </div>
        <a href="admin_tools.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Tools
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="mb-0 fw-bold">All Uploaded Files (<?php echo count($allFiles); ?>)</h6>
                </div>
                <div class="col-auto">
                    <input type="text" id="fileSearch" class="form-control form-control-sm" placeholder="Filter files...">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="fileTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">File Name</th>
                        <th>Directory</th>
                        <th>Size</th>
                        <th>Last Modified</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allFiles)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No files found in the uploads directory.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allFiles as $f): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="preview-box me-3 rounded-3 overflow-hidden shadow-sm border" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; background:#f8f9fa;">
                                            <?php 
                                                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                                                $web_path = '../uploads/' . str_replace('\\', '/', $f['rel_path']);
                                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                                    <img src="<?php echo $web_path; ?>" alt="Preview" style="width:100%; height:100%; object-fit:cover;">
                                                <?php else: ?>
                                                    <i class="fas <?php echo ($ext == 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-alt text-secondary'); ?> fa-lg"></i>
                                                <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($f['name']); ?></span>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($f['rel_path']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(dirname($f['rel_path'])); ?></span></td>
                                <td><?php echo round($f['size'] / 1024, 2); ?> KB</td>
                                <td class="small text-muted"><?php echo date('Y-m-d H:i:s', $f['mtime']); ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm">
                                        <a href="../uploads/<?php echo str_replace('\\', '/', $f['rel_path']); ?>" target="_blank" class="btn btn-sm btn-light" title="View"><i class="fas fa-external-link-alt"></i></a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo addslashes($f['rel_path']); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<form id="deleteForm" method="POST">
    <input type="hidden" name="delete_file" id="deleteFilePath">
</form>

<script>
function confirmDelete(path) {
    if (confirm('Are you sure you want to permanently delete this file? This cannot be undone.')) {
        document.getElementById('deleteFilePath').value = path;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('fileSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#fileTable tbody tr').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>

<style>
.icon-box.bg-light-primary { background: rgba(13, 110, 253, 0.1); }
.rounded-4 { border-radius: 1rem !important; }
.hover-up:hover { transform: translateY(-3px); }
.preview-box img { transition: transform 0.3s ease; }
.preview-box:hover img { transform: scale(1.5); cursor: zoom-in; }
</style>

<?php require_once '../includes/footer.php'; ?>
