<?php
require_once 'config/config.php';
$res = $db->query("SHOW CREATE TABLE consultation_history")->fetch(PDO::FETCH_ASSOC);
echo $res['Create Table'];
