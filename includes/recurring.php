<?php
/**
 * Recurring appointments helper
 * Provides functions to expand recurrence rules into concrete appointments.
 * This is a conservative, best-effort implementation designed as a backend stub
 * that can be improved later (duration handling, timezone rules, complex RRULE support).
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if a candidate time slot is free for the doctor on a given date, considering
 * duration (minutes) and doctor's buffer_before/after (minutes).
 * Returns true if available, false if conflict found.
 */
function recur_slot_is_available(PDO $db, $doctor_id, $date, $time, $duration_minutes = 15) {
    // fetch doctor's buffers
    $bufBefore = 0; $bufAfter = 0;
    try {
        $d = $db->prepare('SELECT buffer_before_minutes, buffer_after_minutes FROM doctors WHERE doctor_id = :id LIMIT 1');
        $d->bindParam(':id', $doctor_id);
        $d->execute();
        $dr = $d->fetch(PDO::FETCH_ASSOC);
        if ($dr) {
            $bufBefore = intval($dr['buffer_before_minutes'] ?? 0);
            $bufAfter = intval($dr['buffer_after_minutes'] ?? 0);
        }
    } catch (Exception $e) {
        // ignore and use defaults
    }

    $fmt = 'H:i:s';
    $candStart = DateTime::createFromFormat($fmt, $time);
    if (!$candStart) return false;
    $candEnd = clone $candStart;
    $candEnd->modify("+{$duration_minutes} minutes");

    // apply buffers
    $candStartWithBuf = clone $candStart; $candStartWithBuf->modify("-{$bufBefore} minutes");
    $candEndWithBuf = clone $candEnd; $candEndWithBuf->modify("+{$bufAfter} minutes");

    $q = $db->prepare('SELECT appointment_time FROM appointments WHERE doctor_id = :doc AND appointment_date = :date AND status != "cancelled"');
    $q->bindParam(':doc', $doctor_id);
    $q->bindParam(':date', $date);
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $existingTime = $r['appointment_time'];
        $exStart = DateTime::createFromFormat($fmt, $existingTime);
        if (!$exStart) continue;
        // conservative assumption: existing appointments are 15 minutes unless otherwise known
        $exDuration = 15;
        $exEnd = clone $exStart; $exEnd->modify("+{$exDuration} minutes");

        // overlap check with buffers
        if ($candStartWithBuf < $exEnd && $exStart < $candEndWithBuf) {
            return false;
        }
    }
    return true;
}

/**
 * Insert a generated recurring appointment into the appointments table.
 * Uses the existing appointment_counters logic to allocate per-day serial.
 * Returns inserted appointment_id on success, false on failure.
 */
function recur_create_appointment(PDO $db, $rule, $date, $time, $instance_index) {
    try {
        $db->beginTransaction();

        $patient_id = $rule['patient_id'] ?? null;
        $doctor_id = $rule['doctor_id'] ?? null;
        $consultation_type = $rule['consultation_type'] ?? 'recurring';
        $notes = $rule['notes'] ?? 'Recurring appointment (rule ' . ($rule['recurrence_id'] ?? '') . ')';

        // Idempotency check: if an appointment for this recurrence + date + time already exists,
        // return the existing appointment id and mark as not newly created.
        $rid = $rule['recurrence_id'] ?? null;
        if ($rid) {
            $check = $db->prepare('SELECT appointment_id FROM appointments WHERE recurrence_id = :rid AND appointment_date = :adate AND appointment_time = :atime LIMIT 1');
            $check->bindParam(':rid', $rid);
            $check->bindParam(':adate', $date);
            $check->bindParam(':atime', $time);
            $check->execute();
            if ($check->rowCount() > 0) {
                $exist = $check->fetch(PDO::FETCH_ASSOC);
                $db->commit();
                return ['id' => $exist['appointment_id'], 'created' => false];
            }
        }

        $ins = $db->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, notes, status, recurrence_id, recurrence_instance_index, source) VALUES (:patient_id, :doctor_id, :adate, :atime, :ctype, :notes, :status, :rid, :rindex, :source)');
        $status = 'scheduled';
        $rid = $rule['recurrence_id'] ?? null;
        $ins->bindParam(':patient_id', $patient_id);
        $ins->bindParam(':doctor_id', $doctor_id);
        $ins->bindParam(':adate', $date);
        $ins->bindParam(':atime', $time);
        $ins->bindParam(':ctype', $consultation_type);
        $ins->bindParam(':notes', $notes);
        $ins->bindParam(':status', $status);
        $ins->bindParam(':rid', $rid);
        $ins->bindParam(':rindex', $instance_index);
        $source = 'recurring';
        $ins->bindParam(':source', $source);

        if (!$ins->execute()) {
            $db->rollBack();
            return false;
        }

        $appointment_id = $db->lastInsertId();

        // allocate per-day serial similar to booking flow (per-doctor)
        $serial = 1;
        $cnt = $db->prepare('SELECT last_serial FROM appointment_counters WHERE `date` = :date AND doctor_id = :did FOR UPDATE');
        $cnt->bindParam(':date', $date);
        $cnt->bindParam(':did', $doctor_id);
        $cnt->execute();
        if ($cnt->rowCount() > 0) {
            $crow = $cnt->fetch(PDO::FETCH_ASSOC);
            $serial = intval($crow['last_serial']) + 1;
            $up = $db->prepare('UPDATE appointment_counters SET last_serial = :s WHERE `date` = :date AND doctor_id = :did');
            $up->bindParam(':s', $serial);
            $up->bindParam(':date', $date);
            $up->bindParam(':did', $doctor_id);
            $up->execute();
        } else {
            $serial = 1;
            $insc = $db->prepare('INSERT INTO appointment_counters (`date`, doctor_id, last_serial) VALUES (:date, :did, :s)');
            $insc->bindParam(':date', $date);
            $insc->bindParam(':did', $doctor_id);
            $insc->bindParam(':s', $serial);
            $insc->execute();
        }

        $uap = $db->prepare('UPDATE appointments SET appointment_serial = :serial WHERE appointment_id = :id');
        $uap->bindParam(':serial', $serial);
        $uap->bindParam(':id', $appointment_id);
        $uap->execute();

        $db->commit();
        return ['id' => $appointment_id, 'created' => true];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        if (function_exists('logAction')) logAction('RECUR_CREATE_ERROR', $e->getMessage());
        return false;
    }
}

/**
 * Generate occurrences for a single recurrence rule between $from_date and $to_date (inclusive)
 * Rule should be an array as returned from recurrence_rules table.
 * Returns an array of created appointment_ids (or an array of skipped/conflict entries).
 */
function recur_generate_for_rule(PDO $db, $rule, $from_date, $to_date) {
    $created = [];
    $skipped = [];

    $freq = $rule['frequency'];
    $interval = max(1, intval($rule['interval'] ?? 1));
    $duration = intval($rule['duration_minutes'] ?? 15);

    $start = new DateTime($rule['start_date']);
    $cursor = new DateTime(max($from_date, $rule['start_date']));
    $endLimit = new DateTime($to_date);

    $occIndex = 0;

    while ($cursor <= $endLimit) {
        // Skip until start_date
        if ($cursor < $start) { $cursor->modify('+1 day'); continue; }

        $shouldCreate = false;

        if ($freq === 'daily') {
            // Every N days
            $diff = (int)$start->diff($cursor)->days;
            if ($diff % $interval === 0) $shouldCreate = true;
        } elseif ($freq === 'weekly') {
            // by_weekdays CSV e.g. MON,TUE
            $by = strtoupper(trim($rule['by_weekdays'] ?? ''));
            if (empty($by)) {
                // if no weekdays specified, assume same weekday as start
                if ($cursor->format('N') == $start->format('N')) $shouldCreate = true;
            } else {
                $wds = array_map('trim', explode(',', $by));
                $map = ['MON'=>'1','TUE'=>'2','WED'=>'3','THU'=>'4','FRI'=>'5','SAT'=>'6','SUN'=>'7'];
                $curWd = $cursor->format('N');
                foreach ($wds as $w) { if (isset($map[$w]) && intval($map[$w]) == $curWd) { $shouldCreate = true; break; } }
            }
            // ensure weekly interval: check week count difference
            if ($shouldCreate && $interval > 1) {
                $weeks = floor($start->diff($cursor)->days / 7);
                if ($weeks % $interval !== 0) $shouldCreate = false;
            }
        } elseif ($freq === 'monthly') {
            $by_md = trim($rule['by_monthday'] ?? '');
            if (!empty($by_md)) {
                $days = array_map('intval', array_filter(array_map('trim', explode(',', $by_md))));
                if (in_array(intval($cursor->format('j')), $days)) $shouldCreate = true;
            } else {
                // default: match same day number as start
                if ($cursor->format('j') == $start->format('j')) $shouldCreate = true;
            }
            if ($shouldCreate && $interval > 1) {
                $months = ($cursor->format('Y') - $start->format('Y'))*12 + ($cursor->format('n') - $start->format('n'));
                if ($months % $interval !== 0) $shouldCreate = false;
            }
        } elseif ($freq === 'yearly') {
            if ($cursor->format('m-d') == $start->format('m-d')) {
                $years = intval($cursor->format('Y')) - intval($start->format('Y'));
                if ($years % $interval === 0) $shouldCreate = true;
            }
        }

        // Stop if rule end_date reached or occurrences limit
        if (!empty($rule['end_date']) && $cursor > new DateTime($rule['end_date'])) break;
        if (!empty($rule['occurrences']) && $occIndex >= intval($rule['occurrences'])) break;

        if ($shouldCreate) {
            $time = $rule['appointment_time'] ?? null;
            if ($time) {
                // check availability
                $dateStr = $cursor->format('Y-m-d');
                if (recur_slot_is_available($db, $rule['doctor_id'], $dateStr, $time, $duration)) {
                    $apptRes = recur_create_appointment($db, $rule, $dateStr, $time, $occIndex+1);
                    if ($apptRes === false) {
                        $skipped[] = ['date'=>$dateStr,'time'=>$time,'reason'=>'insert_failed'];
                    } else {
                        // apptRes is ['id'=>..., 'created'=>bool]
                        if (!empty($apptRes['created'])) {
                            $created[] = $apptRes['id'];
                        } else {
                            $skipped[] = ['date'=>$dateStr,'time'=>$time,'reason'=>'exists','appointment_id'=>$apptRes['id']];
                        }
                        // count existing or newly created towards occurrences
                        $occIndex++;
                    }
                } else {
                    $skipped[] = ['date'=>$dateStr,'time'=>$time,'reason'=>'conflict'];
                }
            } else {
                $skipped[] = ['date'=>$cursor->format('Y-m-d'),'reason'=>'no_time_specified'];
            }
        }

        // advance cursor according to frequency
        if ($freq === 'daily') $cursor->modify("+{$interval} days");
        elseif ($freq === 'weekly') $cursor->modify('+1 day');
        elseif ($freq === 'monthly') $cursor->modify('+1 day');
        elseif ($freq === 'yearly') $cursor->modify('+1 day');
        else $cursor->modify('+1 day');
    }

    return ['created'=>$created,'skipped'=>$skipped];
}

/**
 * Generate upcoming occurrences for all active recurrence rules for a horizon (days).
 * Default horizon: 90 days.
 * Returns summary array.
 */
function recur_generate_upcoming(PDO $db, $horizon_days = 90, $dryRun = false) {
    $today = new DateTime();
    $from = $today->format('Y-m-d');
    $to = (clone $today)->modify("+{$horizon_days} days")->format('Y-m-d');

    $q = $db->prepare('SELECT * FROM recurrence_rules WHERE active = 1 AND (end_date IS NULL OR end_date >= :from)');
    $q->bindParam(':from', $from);
    $q->execute();
    $rules = $q->fetchAll(PDO::FETCH_ASSOC);

    $summary = ['rules_processed'=>0,'created'=>0,'skipped'=>0,'details'=>[]];
    foreach ($rules as $rule) {
        $summary['rules_processed']++;
        $res = recur_generate_for_rule($db, $rule, $from, $to);
        $summary['created'] += count($res['created']);
        $summary['skipped'] += count($res['skipped']);
        $summary['details'][] = ['recurrence_id'=>$rule['recurrence_id'],'created'=>$res['created'],'skipped'=>$res['skipped']];
        if ($dryRun) {
            // If dry run, rollback any inserts done by helper (generate_for_rule currently inserts directly), so dry run isn't fully supported yet.
        }
    }

    return $summary;
}

?>
