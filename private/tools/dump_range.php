<?php
$fn = $argv[1] ?? 'process.php';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? $start);
$lines = file($fn);
for ($i = $start; $i <= $end && isset($lines[$i-1]); $i++) {
    echo str_pad($i,4,' ',STR_PAD_LEFT) . ' | ' . rtrim($lines[$i-1]) . "\n";
}
?>
