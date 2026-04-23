<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (empty($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin','root'])) {
    $_SESSION['error'] = 'Unauthorized';
    redirect('index.php');
}

// ensure uploads dir exists
$dir = __DIR__ . '/../uploads/prescriptions_images/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { $msg = 'Invalid CSRF token.'; }
    else if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) { $msg = 'Upload failed or no file.'; }
    else {
        $f = $_FILES['file'];
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array(strtolower($ext), $allowed)) { $msg = 'File type not allowed.'; }
        else {
            $fileName = 'img_test_' . time() . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $dir . $fileName)) {
                $msg = 'Uploaded: ' . htmlspecialchars($fileName) . '\nURL: ' . rtrim(SITE_URL, '/') . '/uploads/prescriptions_images/' . $fileName;
            } else { $msg = 'Failed to move uploaded file.'; }
        }
    }
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container mt-3">
    <h2>TinyMCE Image Upload Test</h2>
    <p class="text-muted">Upload an image to verify the `upload_image` endpoint used by TinyMCE.</p>
    <?php if ($msg): ?><div class="alert alert-info"><pre><?php echo htmlspecialchars($msg); ?></pre></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_input(); ?>
        <div class="mb-3">
            <label class="form-label">Select image</label>
            <input type="file" name="file" accept="image/*" class="form-control">
        </div>
        <button class="btn btn-primary">Upload</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
