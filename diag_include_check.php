<?php
// Diagnostic: scan PHP files for include/require paths and check existence
$root = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$phpFiles = [];
foreach ($files as $f) {
    if ($f->isFile() && strtolower($f->getExtension()) === 'php') $phpFiles[] = $f->getPathname();
}
$patterns = [
    '/require_once\s*\(?\s*["\']([^"\']+)["\']\s*\)?/i',
    '/require\s*\(?\s*["\']([^"\']+)["\']\s*\)?/i',
    '/include_once\s*\(?\s*["\']([^"\']+)["\']\s*\)?/i',
    '/include\s*\(?\s*["\']([^"\']+)["\']\s*\)?/i',
];
$errors = [];
foreach ($phpFiles as $pf) {
    $content = file_get_contents($pf);
    foreach ($patterns as $pat) {
        if (preg_match_all($pat, $content, $m)) {
            foreach ($m[1] as $inc) {
                // skip url includes
                if (strpos($inc, 'http') === 0) continue;
                // resolve relative to file
                $resolved = $inc;
                if (!preg_match('/^\\\\|^[A-Za-z]:\\\\|^\//', $inc)) {
                    $resolved = dirname($pf) . DIRECTORY_SEPARATOR . $inc;
                }
                // normalize
                $resolved = str_replace(['/', "\\\\"], DIRECTORY_SEPARATOR, $resolved);
                if (!file_exists($resolved)) {
                    $errors[] = ["file" => $pf, "include" => $inc, "resolved" => $resolved];
                }
            }
        }
    }
}
if (empty($errors)) {
    echo "No missing includes detected.\n";
    exit(0);
}
foreach ($errors as $e) {
    echo "Missing include in {$e['file']}: {$e['include']} -> resolved to {$e['resolved']}\n";
}
exit(1);
?>