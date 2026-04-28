<?php
// Simple link checker: scans project files for local href/src targets and reports missing files
$root = realpath(__DIR__ . '/..');
$patterns = ['/*.php', '/*.html', '/pages/*.php', '/includes/*.php', '/*.htm'];
$extensions = ['php','html','htm','css','js'];
$files = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($rii as $f) {
    if ($f->isDir()) continue;
    $path = $f->getPathname();
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $extensions)) continue;
    $files[] = $path;
}

$missing = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    // find href and src attributes
    if (preg_match_all('/(?:href|src)\s*=\s*"([^"]+)"/i', $content, $m)) {
        foreach ($m[1] as $url) {
            // skip external links and anchors and mailto and javascript
            if (preg_match('#^(https?:)?//#i', $url)) continue;
            if (strpos($url, 'mailto:') === 0) continue;
            if (strpos($url, 'javascript:') === 0) continue;
            if (strpos($url, '#') === 0) continue;

            // resolve relative to file
            $base = dirname($file);
            $target = realpath($base . DIRECTORY_SEPARATOR . $url);
            if ($target === false) {
                // try relative to project root
                $target2 = realpath($root . DIRECTORY_SEPARATOR . ltrim($url, '/\\'));
                if ($target2 === false) {
                    $missing[] = [
                        'file' => $file,
                        'link' => $url
                    ];
                }
            }
        }
    }
}

if (empty($missing)) {
    echo "No obvious missing local links found.\n";
} else {
    echo "Missing local links:\n";
    foreach ($missing as $m) {
        echo "- In: " . substr($m['file'], strlen($root)+1) . " -> " . $m['link'] . "\n";
    }
}

?>
