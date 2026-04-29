<?php
require 'config/config.php';
try {
    // Add new columns to staff_chat_messages
    $db->exec("
        ALTER TABLE staff_chat_messages 
        ADD COLUMN file_path VARCHAR(255) NULL AFTER message,
        ADD COLUMN file_name VARCHAR(255) NULL AFTER file_path,
        ADD COLUMN file_type VARCHAR(50) NULL AFTER file_name,
        ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER is_read
    ");
    echo "Table altered successfully.\n";
} catch (Exception $e) {
    echo "Error altering table: " . $e->getMessage() . "\n";
}

// Create directory
$dir = __DIR__ . '/../uploads/chat_files';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
    echo "Directory created.\n";
} else {
    echo "Directory already exists.\n";
}
