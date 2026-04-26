<?php
// Recursive reference finder for given keywords
$keywords = [
    'database_backup.sql', 'fileloc.txt', 'test_users_import.csv', 'composer.phar',
    'google-api-php-client-main', 'NoNeed', 'README.txt', 'installer', 'backup_db.php'
];

function find_refs_scan($dir, $keywords) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $matches = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        // skip vendor and archive to avoid noise
        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
        if (strpos($path, DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR) !== false) continue;
        // only scan text files
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, ['php','html','js','css','txt','md','json'])) continue;
        $content = @file_get_contents($path);
        if ($content === false) continue;
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            foreach ($keywords as $k) {
                if (stripos($line, $k) !== false) {
                    $matches[] = [ 'file' => $path, 'line' => $i+1, 'keyword' => $k, 'text' => trim($line) ];
                }
            }
        }
    }
    return $matches;
}

$matches = find_refs_scan(__DIR__ . '/../', $keywords);
if (empty($matches)) {
    echo "No references found for keywords (excluding vendor/archive)\n";
    exit(0);
}
foreach ($matches as $m) {
    echo "{$m['file']}:{$m['line']} -> {$m['keyword']} => {$m['text']}\n";
}

?>
