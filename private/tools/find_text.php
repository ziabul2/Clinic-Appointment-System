<?php
$fn = $argv[1] ?? 'process.php';
$search = $argv[2] ?? 'switch';
$lines = file($fn);
foreach ($lines as $i => $l) {
    if (strpos($l, $search) !== false) {
        echo ($i+1) . ": " . rtrim($l) . "\n";
    }
}
?>
