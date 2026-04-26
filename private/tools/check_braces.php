<?php
$lines = file('process.php');
$level = 0;
foreach ($lines as $i => $line) {
    $chars = str_split($line);
    foreach ($chars as $c) {
        if ($c === '{') $level++;
        if ($c === '}') $level--;
    }
    if ($level < 0) {
        echo "Brace level negative at line " . ($i+1) . "\n";
        echo "Line content: " . trim($line) . "\n";
        exit(1);
    }
    if (($i+1) % 50 == 0) echo "Line " . ($i+1) . " level: $level\n";
}
echo "Final level: $level\n";
?>