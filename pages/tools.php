<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Root'])) {
    $_SESSION['error'] = 'Unauthorized';
    redirect('index.php');
}

$toolsDir = realpath(__DIR__ . '/../tools');
$files = [];
if ($toolsDir && is_dir($toolsDir)) {
    foreach (glob($toolsDir . '/*.php') as $f) {
        $base = basename($f);
        $desc = '';
        $fh = fopen($f, 'r');
        if ($fh) {
            $first = trim(fgets($fh));
            if (strpos($first, '/*') === 0 || strpos($first, '//') === 0) {
                $desc = trim($first);
            } else {
                // look further for a comment line
                rewind($fh);
                while (($line = fgets($fh)) !== false) {
                    $line = trim($line);
                    if ($line === '') continue;
                    if (strpos($line, '//') === 0 || strpos($line, '/*') === 0) { $desc = $line; break; }
                    break;
                }
            }
            fclose($fh);
        }
        $files[] = ['file' => $base, 'desc' => $desc];
    }
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container mt-3">
    <h2>Admin Tools</h2>
    <p class="text-muted">Run maintenance and diagnostic scripts from here. Outputs are shown below.</p>
    <div class="mb-2">
        <a class="btn btn-sm btn-outline-secondary" href="tools_image_upload.php">Test TinyMCE Image Upload</a>
    </div>
    <table class="table table-sm">
        <thead><tr><th>Script</th><th>Description</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($files as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['file']) ?></td>
                <td><?= htmlspecialchars($t['desc']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary run-tool" data-file="<?= htmlspecialchars($t['file']) ?>">Run</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h4>Output</h4>
    <pre id="tool-output" style="background:#f8f9fa;padding:10px;max-height:400px;overflow:auto;">Select a tool and click Run to see output.</pre>
</div>

<script src="/assets/js/tools.js?v=1"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
