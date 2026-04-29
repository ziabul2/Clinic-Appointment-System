<?php
$mysqlPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$outputFile = 'c:\\xampp\\htdocs\\clinicApp\\sqls_DB\\clinic_management.sql';
$command = "\"$mysqlPath\" -u root clinic_management > \"$outputFile\"";
system($command, $ret);
echo "Exit code: $ret\n";
if ($ret === 0) {
    echo "Done! Size: " . round(filesize($outputFile) / 1024 / 1024, 2) . " MB\n";
} else {
    echo "Failed.\n";
}
