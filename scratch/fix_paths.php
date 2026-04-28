<?php
$dir = __DIR__ . '/../private/tools';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Match __DIR__ . '/../config/ OR __DIR__ . "/../config/"
    $newContent = preg_replace('/__DIR__\s*\.\s*[\'"]\/\.\.\/(config|includes|assets)\//', "__DIR__ . '/../../$1/", $content);
    
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Fixed: " . basename($file) . "\n";
    }
}
echo "Done.\n";
