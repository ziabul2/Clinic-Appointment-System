<?php
$argv = $_SERVER['argv'];
$fn = $argv[1] ?? 'process.php';
$ln = intval($argv[2] ?? 1);
$lines = file($fn);
if (!isset($lines[$ln-1])) { echo "No such line\n"; exit(1); }
echo $ln . ': ' . rtrim($lines[$ln-1]) . "\n";
?>
