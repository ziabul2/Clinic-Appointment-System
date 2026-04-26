<?php
$lines = file('process.php');
$level = 0;
$start = 880; $end = 980;
foreach ($lines as $i => $line) {
    $lineNum = $i+1;
    $chars = str_split($line);
    foreach ($chars as $c) {
        if ($c === '{') $level++;
        if ($c === '}') $level--;
    }
    if ($lineNum >= $start && $lineNum <= $end) {
        echo str_pad($lineNum,4,' ',STR_PAD_LEFT) . ' Lvl:' . str_pad($level,3,' ',STR_PAD_LEFT) . ' | ' . rtrim($line) . "\n";
    }
}
echo "Final level: $level\n";
?>