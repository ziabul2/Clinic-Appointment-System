<?php
/**
 * Cron script to expand recurrence rules into concrete appointments.
 * Run from CLI or schedule via Windows Task Scheduler / cron.
 * Example: php cron_generate_recurrences.php --days=90
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/recurring.php';

$opts = getopt('', ['days::','dry::']);
$days = isset($opts['days']) ? intval($opts['days']) : 90;
$dry = isset($opts['dry']) ? true : false;

echo "Generating recurrences for next {$days} days (dry run: " . ($dry? 'yes':'no') . ")\n";
try {
    $res = recur_generate_upcoming($db, $days, $dry);
    echo "Rules processed: " . intval($res['rules_processed']) . "\n";
    echo "Appointments created: " . intval($res['created']) . "\n";
    echo "Appointments skipped (conflicts/failed): " . intval($res['skipped']) . "\n";
    // Optionally dump details when run interactively
    if (!empty($res['details'])) {
        foreach ($res['details'] as $d) {
            echo "Rule: " . ($d['recurrence_id'] ?? 'n/a') . " created: " . count($d['created']) . " skipped: " . count($d['skipped']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error during recurrence generation: " . $e->getMessage() . "\n";
    if (function_exists('logAction')) logAction('CRON_RECUR_ERROR', $e->getMessage());
    exit(1);
}

echo "Done.\n";

?>
