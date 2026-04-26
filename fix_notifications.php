<?php
require_once __DIR__ . '/config/config.php';
try {
    echo "Checking notifications table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        meta JSON NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "Notifications table is ready.\n";

    echo "Checking for foreign key...\n";
    try {
        $db->exec("ALTER TABLE notifications ADD CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE");
        echo "Foreign key added.\n";
    } catch (Exception $e) {
        echo "Foreign key might already exist or users table issue: " . $e->getMessage() . "\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
