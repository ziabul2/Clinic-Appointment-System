<?php
// auto_update_archive_refs.php
// Scans repository (excluding vendor/archive) for occurrences of archived filenames
// and updates quoted file-path string literals to include '/archive/' prefix where safe.
// Creates a .bak backup before modifying files.

$root = realpath(__DIR__ . '/..');
$keywords = [
    'database_backup.sql', 'fileloc.txt', 'test_users_import.csv', 'composer.phar',
    'google-api-php-client-main', 'NoNeed', 'README.txt', 'installer', 'backup_db.php'
];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (stripos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (stripos($path, DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR) !== false) continue;
    // only edit text/php/html/js/css files
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php','html','js','css','txt','md','json'])) continue;

    $content = @file_get_contents($path);
    if ($content === false) continue;

    $original = $content;
    foreach ($keywords as $k) {
        // find quoted occurrences containing keyword
        $regex = '/(["\'])([^"\']*'.preg_quote($k,'/').'[^"\']*)(["\'])/i';
        if (preg_match_all($regex, $content, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $quote = $match[1];
                $str = $match[2];
                // if already contains '/archive/' or 'archive\\' skip
                if (stripos($str, 'archive' . DIRECTORY_SEPARATOR) !== false || stripos($str, '/archive/') !== false) continue;
                // heuristic: only change if the string looks like a path (contains '/' or starts with '.' or contains directory separators)
                if (strpos($str, '/') === false && strpos($str, '\\') === false && strpos($str, './') === false && strpos($str, '../') === false) {
                    // likely documentation mention, skip
                    continue;
                }
                // construct new string with archive inserted before filename
                // if the keyword already has a directory component, ensure we only insert if not present
                $newStr = preg_replace('/(\/)'.preg_quote($k,'/').'$/i', '/archive/'.$k, $str);
                if ($newStr === $str) {
                    // if not matched, just insert '/archive/' before the filename
                    $newStr = preg_replace('/('.preg_quote($k,'/').')/i', 'archive/'.$k, $str, 1);
                }
                // replace in content
                $content = str_replace($quote.$str.$quote, $quote.$newStr.$quote, $content);
            }
        }
    }

    if ($content !== $original) {
        // Backup original
        $bak = $path . '.bak.' . time();
        copy($path, $bak);
        file_put_contents($path, $content);
        $changed[] = ['file' => $path, 'backup' => $bak];
    }
}

if (empty($changed)) {
    echo "No code references required updating.\n";
    exit(0);
}

foreach ($changed as $c) {
    echo "Updated: {$c['file']} (backup: {$c['backup']})\n";
}

exit(0);
