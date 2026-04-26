<?php
// Scans PHP files (excluding vendor and archive) for include/require statements
// and reports references whose target files do not exist.

$root = realpath(__DIR__ . '/..');
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$problems = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (stripos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (stripos($path, DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR) !== false) continue;
    if (substr($path, -4) !== '.php') continue;
    $content = @file_get_contents($path);
    if ($content === false) continue;

    // find include/require statements with simple patterns
    if (preg_match_all('/\b(require_once|include_once|require|include)\s*\(?\s*(.+?)\s*\)?\s*;/i', $content, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m as $match) {
            $expr = $match[2][0];
            $matchPos = $match[0][1];
            $target = null;

            // literal string
            if (preg_match('/^["\'](.+?)["\']$/', trim($expr), $mm)) {
                $rel = $mm[1];
                $target = dirname($path) . DIRECTORY_SEPARATOR . $rel;
            }
            // __DIR__ . '/file'
            elseif (preg_match('/__DIR__\s*\.\s*["\'](.+?)["\']/', $expr, $mm2)) {
                $rel = $mm2[1];
                $target = dirname($path) . DIRECTORY_SEPARATOR . $rel;
            }
            // dirname(__FILE__) . '/file'
            elseif (preg_match('/dirname\s*\(\s*__FILE__\s*\)\s*\.\s*["\'](.+?)["\']/', $expr, $mm3)) {
                $rel = $mm3[1];
                $target = dirname($path) . DIRECTORY_SEPARATOR . $rel;
            }

            if ($target !== null) {
                $target = str_replace(['\\','/'], DIRECTORY_SEPARATOR, $target);
                // normalize path
                $norm = realpath($target) ?: $target;
                if (!file_exists($norm)) {
                    // compute line number
                    $before = substr($content, 0, $matchPos);
                    $lineNum = substr_count($before, "\n") + 1;
                    $line = explode("\n", $content)[$lineNum-1] ?? '';
                    $problems[] = [
                        'file' => $path,
                        'line' => $lineNum,
                        'line_snippet' => trim($line),
                        'target' => $target
                    ];
                }
            }
        }
    }
}

if (empty($problems)) {
    echo "No missing include/require targets found (excluding vendor/archive).\n";
    exit(0);
}

foreach ($problems as $p) {
    echo "{$p['file']} -> {$p['line_snippet']}  -> MISSING: {$p['target']}\n";
}

exit(0);
