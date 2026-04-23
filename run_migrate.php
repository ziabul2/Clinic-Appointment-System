<?php
// Convenience runner: execute DB migrations from CLI or web
// Run via CLI: C:\xampp\php\php.exe run_migrate.php
require_once __DIR__ . '/migrate_db.php';

// migrate_db.php is CLI friendly and prints output.
echo "Migration script invoked. Check output above." . PHP_EOL;
