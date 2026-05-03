<?php
$db = new PDO("sqlite:DatabaseSQL/clinic_offline.db");
echo $db->query("SELECT sql FROM sqlite_master WHERE name='users'")->fetchColumn();
echo "\n---\n";
echo $db->query("SELECT sql FROM sqlite_master WHERE name='doctors'")->fetchColumn();
