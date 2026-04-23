<?php
// import_cli.php - simulate CSV import without web UI
require_once __DIR__ . '/config/config.php';

$csv = __DIR__ . '/archive/test_users_import.csv';
if (!file_exists($csv)) {
    echo "CSV file not found: $csv\n";
    exit(1);
}

$replace = true; // set true to replace existing users
$fh = fopen($csv, 'r');
if (!$fh) { echo "Failed to open CSV\n"; exit(1); }
// Ensure users table has doctor_id column (some DBs may be missing it)
try {
    $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'doctor_id'");
    $colChk->execute();
    $rowChk = $colChk->fetch(PDO::FETCH_ASSOC);
    if (empty($rowChk) || intval($rowChk['cnt']) === 0) {
        echo "Notice: 'doctor_id' column not found in users table — adding it now...\n";
        try {
            $db->exec("ALTER TABLE users ADD COLUMN doctor_id INT NULL AFTER role");
            echo "Added 'doctor_id' column to users table.\n";
            // try to add FK if doctors table exists
            $tblChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors'");
            $tblChk->execute();
            $t = $tblChk->fetch(PDO::FETCH_ASSOC);
            if (!empty($t) && intval($t['cnt']) > 0) {
                try {
                    $db->exec("ALTER TABLE users ADD CONSTRAINT fk_users_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL ON UPDATE CASCADE");
                    echo "Added foreign key fk_users_doctor.\n";
                } catch (Exception $e) {
                    echo "Warning: could not add FK fk_users_doctor: " . $e->getMessage() . "\n";
                }
            }
        } catch (Exception $e) {
            echo "Warning: failed to add doctor_id column: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Warning: could not check information_schema: " . $e->getMessage() . "\n";
}
$row = 0; $created = 0; $updated = 0; $skipped = 0;
while (($data = fgetcsv($fh)) !== false) {
    $row++;
    if (count($data) < 1) continue;
    if ($row == 1 && preg_match('/username/i', implode(',', $data))) continue;
    $username = isset($data[0]) ? sanitizeInput($data[0]) : null;
    $email = isset($data[1]) ? sanitizeInput($data[1]) : null;
    $role = isset($data[2]) ? sanitizeInput($data[2]) : 'Receptionist';
    $doctor_id = isset($data[3]) && $data[3] !== '' ? sanitizeInput($data[3]) : null;
    if (empty($username)) { $skipped++; continue; }
    $q = $db->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
    $q->bindParam(':username', $username);
    $q->execute();
    if ($q->rowCount() > 0) {
        $existing = $q->fetch(PDO::FETCH_ASSOC);
        if ($replace) {
            $up = $db->prepare('UPDATE users SET email = :email, role = :role' . ($doctor_id ? ', doctor_id = :doctor_id' : '') . ' WHERE user_id = :id');
            $up->bindParam(':email', $email);
            $up->bindParam(':role', $role);
            if ($doctor_id) $up->bindParam(':doctor_id', $doctor_id);
            $up->bindParam(':id', $existing['user_id']);
            $up->execute();
            $updated++;
            if (!empty($email)) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $ins = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                $ins->bindParam(':user_id', $existing['user_id']);
                $ins->bindParam(':token', $token);
                $ins->bindParam(':expires_at', $expires);
                $ins->execute();
                $uqq = $db->prepare('SELECT username FROM users WHERE user_id = :id');
                $uqq->bindParam(':id', $existing['user_id']); $uqq->execute(); $urow = $uqq->fetch(PDO::FETCH_ASSOC);
                sendPasswordResetLink($email, $urow['username'], $token);
            }
        } else {
            $skipped++;
        }
    } else {
        $random = bin2hex(random_bytes(8));
        $hash = password_hash($random, PASSWORD_DEFAULT);
        $ins = $db->prepare('INSERT INTO users (username, password, email, role, created_at' . ($doctor_id ? ', doctor_id' : '') . ') VALUES (:username, :password, :email, :role, NOW()' . ($doctor_id ? ', :doctor_id' : '') . ')');
        $ins->bindParam(':username', $username);
        $ins->bindParam(':password', $hash);
        $ins->bindParam(':email', $email);
        $ins->bindParam(':role', $role);
        if ($doctor_id) $ins->bindParam(':doctor_id', $doctor_id);
        $ins->execute();
        $newId = $db->lastInsertId();
        $created++;
        if (!empty($email)) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $ins2 = $db->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
            $ins2->bindParam(':user_id', $newId);
            $ins2->bindParam(':token', $token);
            $ins2->bindParam(':expires_at', $expires);
            $ins2->execute();
            sendPasswordResetLink($email, $username, $token);
        }
    }
}
fclose($fh);
echo "Import complete. Created: $created, Updated: $updated, Skipped: $skipped\n";
// exit with success code
exit(0);
?>