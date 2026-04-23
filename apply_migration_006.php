<?php
/**
 * Apply migration 006: recurrence_rules, waiting_list, availability_cache
 */
require_once __DIR__ . '/config/config.php';

try {
    echo "Applying migration 006...\n";

    // 1. Create recurrence_rules table
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `recurrence_rules` (
  `recurrence_id` INT NOT NULL AUTO_INCREMENT,
  `doctor_id` INT NULL,
  `patient_id` INT NULL,
  `frequency` ENUM('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'weekly',
  `interval` INT NOT NULL DEFAULT 1,
  `by_weekdays` VARCHAR(50) NULL,
  `by_monthday` VARCHAR(20) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `occurrences` INT NULL,
  `appointment_time` TIME NULL,
  `duration_minutes` INT NOT NULL DEFAULT 15,
  `consultation_type` VARCHAR(64) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`recurrence_id`),
  INDEX `ix_recur_doctor_start` (`doctor_id`, `start_date`),
  INDEX `ix_recur_patient` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $db->exec($sql);
    echo "✓ Created recurrence_rules table\n";

    // 2. Create waiting_list table
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `waiting_list` (
  `waiting_id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NULL,
  `preferred_date` DATE NULL,
  `preferred_time` TIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending','notified','processed','cancelled') NOT NULL DEFAULT 'pending',
  `taken_by` INT NULL,
  `appointment_id` INT NULL,
  PRIMARY KEY (`waiting_id`),
  INDEX `ix_wait_doc_date` (`doctor_id`,`preferred_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $db->exec($sql);
    echo "✓ Created waiting_list table\n";

    // 3. Create availability_cache table
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `availability_cache` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `doctor_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `timeslots` JSON NOT NULL,
  `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ttl_seconds` INT NOT NULL DEFAULT 300,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doc_date` (`doctor_id`,`date`),
  INDEX `ix_avail_doc_date` (`doctor_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $db->exec($sql);
    echo "✓ Created availability_cache table\n";

    // 4. Add columns to appointments if not exist
    $cols = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments'");
    $cols->execute();
    $existing = [];
    while ($row = $cols->fetch(PDO::FETCH_ASSOC)) {
        $existing[$row['COLUMN_NAME']] = true;
    }

    if (empty($existing['recurrence_id'])) {
        $db->exec("ALTER TABLE `appointments` ADD COLUMN `recurrence_id` INT NULL");
        echo "✓ Added appointments.recurrence_id\n";
    } else {
        echo "- appointments.recurrence_id already exists\n";
    }

    if (empty($existing['parent_appointment_id'])) {
        $db->exec("ALTER TABLE `appointments` ADD COLUMN `parent_appointment_id` INT NULL");
        echo "✓ Added appointments.parent_appointment_id\n";
    } else {
        echo "- appointments.parent_appointment_id already exists\n";
    }

    if (empty($existing['recurrence_instance_index'])) {
        $db->exec("ALTER TABLE `appointments` ADD COLUMN `recurrence_instance_index` INT NULL");
        echo "✓ Added appointments.recurrence_instance_index\n";
    } else {
        echo "- appointments.recurrence_instance_index already exists\n";
    }

    if (empty($existing['source'])) {
        $db->exec("ALTER TABLE `appointments` ADD COLUMN `source` ENUM('manual','recurring','waitlist') NOT NULL DEFAULT 'manual'");
        echo "✓ Added appointments.source\n";
    } else {
        echo "- appointments.source already exists\n";
    }

    // 5. Add buffer columns to doctors if not exist
    $cols = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors'");
    $cols->execute();
    $existing = [];
    while ($row = $cols->fetch(PDO::FETCH_ASSOC)) {
        $existing[$row['COLUMN_NAME']] = true;
    }

    if (empty($existing['buffer_before_minutes'])) {
        $db->exec("ALTER TABLE `doctors` ADD COLUMN `buffer_before_minutes` INT NOT NULL DEFAULT 0");
        echo "✓ Added doctors.buffer_before_minutes\n";
    } else {
        echo "- doctors.buffer_before_minutes already exists\n";
    }

    if (empty($existing['buffer_after_minutes'])) {
        $db->exec("ALTER TABLE `doctors` ADD COLUMN `buffer_after_minutes` INT NOT NULL DEFAULT 0");
        echo "✓ Added doctors.buffer_after_minutes\n";
    } else {
        echo "- doctors.buffer_after_minutes already exists\n";
    }

    // 6. Try to add FKs (best effort, ignore if they fail)
    try {
        $db->exec("ALTER TABLE `appointments` ADD CONSTRAINT `fk_appointments_recurrence` FOREIGN KEY (`recurrence_id`) REFERENCES `recurrence_rules`(`recurrence_id`) ON DELETE SET NULL ON UPDATE CASCADE");
        echo "✓ Added FK: appointments.recurrence_id -> recurrence_rules\n";
    } catch (Exception $e) {
        echo "- FK appointments.recurrence_id already exists or constraint conflict\n";
    }

    try {
        $db->exec("ALTER TABLE `waiting_list` ADD CONSTRAINT `fk_waiting_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE");
        echo "✓ Added FK: waiting_list.doctor_id -> doctors\n";
    } catch (Exception $e) {
        echo "- FK waiting_list.doctor_id already exists or constraint conflict\n";
    }

    echo "\nMigration 006 applied successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>
