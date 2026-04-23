<?php
// Simulate booking flow non-destructively: starts a transaction, performs the booking and serial allocation, then rolls back.
require_once __DIR__ . '/../config/config.php';

function out($s) { echo $s . PHP_EOL; }

try {
    out('Starting non-destructive booking simulation...');

    // Pick a patient and doctor
    $p = $db->query('SELECT patient_id, first_name, last_name FROM patients ORDER BY patient_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $d = $db->query('SELECT doctor_id, first_name, last_name FROM doctors ORDER BY doctor_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$p || !$d) throw new Exception('Need at least one patient and one doctor in DB to simulate.');

    $patient_id = $p['patient_id'];
    $doctor_id = $d['doctor_id'];
    $appointment_date = date('Y-m-d', strtotime('+1 day'));
    $appointment_time = '09:00';
    $consultation_type = 'checkup';
    $notes = 'SIMULATION - do not persist';

    out("Using patient {$p['first_name']} {$p['last_name']} (id={$patient_id}) and doctor {$d['first_name']} {$d['last_name']} (id={$doctor_id})");

    // Check conflicts
    $chk = $db->prepare('SELECT appointment_id FROM appointments WHERE doctor_id = :doctor_id AND appointment_date = :d AND appointment_time = :t AND status != "cancelled"');
    $chk->bindParam(':doctor_id', $doctor_id);
    $chk->bindParam(':d', $appointment_date);
    $chk->bindParam(':t', $appointment_time);
    $chk->execute();
    if ($chk->rowCount() > 0) {
        out('Conflict detected: time slot already booked. Choose another time or modify script.');
        exit(2);
    }

    // Start transaction
    $db->beginTransaction();
    out('Transaction started. Inserting appointment (will rollback later)...');

    $ins = $db->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, notes, status) VALUES (:patient_id, :doctor_id, :date, :time, :ctype, :notes, :status)');
    $status = 'scheduled';
    $ins->bindParam(':patient_id', $patient_id);
    $ins->bindParam(':doctor_id', $doctor_id);
    $ins->bindParam(':date', $appointment_date);
    $ins->bindParam(':time', $appointment_time);
    $ins->bindParam(':ctype', $consultation_type);
    $ins->bindParam(':notes', $notes);
    $ins->bindParam(':status', $status);
    if (!$ins->execute()) {
        $err = $ins->errorInfo();
        throw new Exception('Insert failed: ' . ($err[2] ?? 'unknown'));
    }
    $appointment_id = $db->lastInsertId();
    out("Inserted appointment id: $appointment_id (not yet committed)");

    // Allocate per-day serial using appointment_counters with FOR UPDATE
    $serial = 1;
    $cnt = $db->prepare('SELECT last_serial FROM appointment_counters WHERE `date` = :date FOR UPDATE');
    $cnt->bindParam(':date', $appointment_date);
    $cnt->execute();
    if ($cnt->rowCount() > 0) {
        $crow = $cnt->fetch(PDO::FETCH_ASSOC);
        $serial = intval($crow['last_serial']) + 1;
        $up = $db->prepare('UPDATE appointment_counters SET last_serial = :s WHERE `date` = :date');
        $up->bindParam(':s', $serial);
        $up->bindParam(':date', $appointment_date);
        $up->execute();
    } else {
        $serial = 1;
        $insc = $db->prepare('INSERT INTO appointment_counters (`date`, last_serial) VALUES (:date, :s)');
        $insc->bindParam(':date', $appointment_date);
        $insc->bindParam(':s', $serial);
        $insc->execute();
    }

    // Update appointment with serial
    $uap = $db->prepare('UPDATE appointments SET appointment_serial = :serial WHERE appointment_id = :id');
    $uap->bindParam(':serial', $serial);
    $uap->bindParam(':id', $appointment_id);
    $uap->execute();
    out("Allocated appointment_serial: $serial for appointment id: $appointment_id");

    // Instead of commit, roll back since this is a simulation
    $db->rollBack();
    out('Rolled back transaction. No changes persisted. Simulation complete.');

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    out('Simulation error: ' . $e->getMessage());
    exit(1);
}

?>
