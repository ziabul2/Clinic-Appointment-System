<?php
/**
 * AJAX endpoint: check_availability.php
 * Params (GET or POST): doctor_id (required), start_date (Y-m-d, default today), end_date (Y-m-d, default +7 days), duration (minutes, default 15), step (minutes, default = duration)
 * Returns JSON: { ok: true, doctor_id, slots: { '2025-11-25': ['09:00','09:15', ...], ... } }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    // Accept GET or POST
    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $doctor_id = isset($input['doctor_id']) ? intval($input['doctor_id']) : 0;
    if (!$doctor_id) throw new Exception('doctor_id is required');

    $start_date = isset($input['start_date']) ? $input['start_date'] : date('Y-m-d');
    $end_date = isset($input['end_date']) ? $input['end_date'] : date('Y-m-d', strtotime('+7 days'));
    $duration = isset($input['duration']) ? max(5, intval($input['duration'])) : 15;
    $step = isset($input['step']) ? max(1, intval($input['step'])) : $duration;

    // Validate dates
    $sd = DateTime::createFromFormat('Y-m-d', $start_date);
    $ed = DateTime::createFromFormat('Y-m-d', $end_date);
    if (!$sd || !$ed) throw new Exception('Invalid date format. Use Y-m-d');
    if ($ed < $sd) throw new Exception('end_date must be >= start_date');

    // Fast path: check availability_cache for each date and use if fresh
    $slots_by_date = [];
    $use_cache = true;

    // load doctor info
    $dq = $db->prepare('SELECT available_days, available_time_start, available_time_end, buffer_before_minutes, buffer_after_minutes FROM doctors WHERE doctor_id = :id LIMIT 1');
    $dq->bindParam(':id', $doctor_id);
    $dq->execute();
    $doctor = $dq->fetch(PDO::FETCH_ASSOC);
    if (!$doctor) throw new Exception('Doctor not found');

    // prepare map of existing appointments for date range to avoid N queries
    $apptStmt = $db->prepare('SELECT a.appointment_date, a.appointment_time, COALESCE(rr.duration_minutes, 15) AS duration_minutes FROM appointments a LEFT JOIN recurrence_rules rr ON a.recurrence_id = rr.recurrence_id WHERE a.doctor_id = :doc AND a.appointment_date BETWEEN :sd AND :ed AND a.status != "cancelled"');
    $apptStmt->bindParam(':doc', $doctor_id);
    $sdf = $sd->format('Y-m-d'); $edf = $ed->format('Y-m-d');
    $apptStmt->bindParam(':sd', $sdf);
    $apptStmt->bindParam(':ed', $edf);
    $apptStmt->execute();
    $appts = $apptStmt->fetchAll(PDO::FETCH_ASSOC);
    $appts_by_date = [];
    foreach ($appts as $a) {
        $d = $a['appointment_date'];
        if (!isset($appts_by_date[$d])) $appts_by_date[$d] = [];
        $appts_by_date[$d][] = ['time'=>$a['appointment_time'],'duration'=>intval($a['duration_minutes'] ?? 15)];
    }

    // parse available_days from doctors.available_days (CSV). Accept names or numbers
    $available_days_raw = trim($doctor['available_days'] ?? '');
    $available_days_map = null; // null = all days available
    if ($available_days_raw !== '') {
        $parts = array_filter(array_map('trim', explode(',', strtoupper($available_days_raw))));
        $available_days_map = [];
        $nameToNum = ['MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6,'SUN'=>7,'MONDAY'=>1,'TUESDAY'=>2,'WEDNESDAY'=>3,'THURSDAY'=>4,'FRIDAY'=>5,'SATURDAY'=>6,'SUNDAY'=>7];
        foreach ($parts as $p) {
            if (is_numeric($p)) $available_days_map[intval($p)] = true; else if (isset($nameToNum[$p])) $available_days_map[$nameToNum[$p]] = true;
        }
        if (empty($available_days_map)) $available_days_map = null;
    }

    $bufBefore = intval($doctor['buffer_before_minutes'] ?? 0);
    $bufAfter = intval($doctor['buffer_after_minutes'] ?? 0);

    // determine working hours
    $work_start = $doctor['available_time_start'] ?? '09:00:00';
    $work_end = $doctor['available_time_end'] ?? '17:00:00';

    // iterate dates
    $cursor = clone $sd;
    while ($cursor <= $ed) {
        $dStr = $cursor->format('Y-m-d');
        $weekday = intval($cursor->format('N'));

        // skip if doctor not available this weekday
        if ($available_days_map !== null && !isset($available_days_map[$weekday])) {
            $cursor->modify('+1 day');
            continue;
        }

        // check cache
        $use_cached = false;
        $cacheStmt = $db->prepare('SELECT timeslots, generated_at, ttl_seconds FROM availability_cache WHERE doctor_id = :doc AND date = :d LIMIT 1');
        $cacheStmt->bindParam(':doc', $doctor_id);
        $cacheStmt->bindParam(':d', $dStr);
        $cacheStmt->execute();
        $c = $cacheStmt->fetch(PDO::FETCH_ASSOC);
        if ($c) {
            $gen = strtotime($c['generated_at']);
            $ttl = intval($c['ttl_seconds']);
            if ($gen + $ttl >= time()) {
                $slots_by_date[$dStr] = json_decode($c['timeslots'], true);
                $cursor->modify('+1 day');
                continue;
            }
        }

        // compute timeslots for this date
        $slots = [];
        $startDT = DateTime::createFromFormat('Y-m-d H:i:s', $dStr . ' ' . $work_start);
        $endDT = DateTime::createFromFormat('Y-m-d H:i:s', $dStr . ' ' . $work_end);
        if (!$startDT || !$endDT) { $cursor->modify('+1 day'); continue; }

        // build existing appointments for this date
        $existing = $appts_by_date[$dStr] ?? [];

        $slot = clone $startDT;
        while (true) {
            $slotEnd = (clone $slot)->modify("+{$duration} minutes");
            if ($slotEnd > $endDT) break;

            // check overlaps
            $conflict = false;
            // candidate with buffers
            $candStartBuf = (clone $slot)->modify("-{$bufBefore} minutes");
            $candEndBuf = (clone $slotEnd)->modify("+{$bufAfter} minutes");

            foreach ($existing as $ex) {
                $exStart = DateTime::createFromFormat('Y-m-d H:i:s', $dStr . ' ' . $ex['time']);
                if (!$exStart) continue;
                $exEnd = (clone $exStart)->modify('+' . intval($ex['duration']) . ' minutes');
                if ($candStartBuf < $exEnd && $exStart < $candEndBuf) { $conflict = true; break; }
            }

            if (!$conflict) {
                $slots[] = $slot->format('H:i');
            }

            // advance slot by step
            $slot->modify('+' . $step . ' minutes');
        }

        // store slots
        $slots_by_date[$dStr] = $slots;

        // update cache (upsert)
        try {
            $tsJson = json_encode($slots);
            $up = $db->prepare('INSERT INTO availability_cache (doctor_id, date, timeslots, generated_at, ttl_seconds) VALUES (:doc, :d, :ts, NOW(), 300) ON DUPLICATE KEY UPDATE timeslots = :ts2, generated_at = NOW(), ttl_seconds = 300');
            $up->bindParam(':doc', $doctor_id);
            $up->bindParam(':d', $dStr);
            $up->bindParam(':ts', $tsJson);
            $up->bindParam(':ts2', $tsJson);
            $up->execute();
        } catch (Exception $e) {
            // ignore cache write failures
        }

        $cursor->modify('+1 day');
    }

    echo json_encode(['ok'=>true,'doctor_id'=>$doctor_id,'start_date'=>$start_date,'end_date'=>$end_date,'duration'=>$duration,'slots'=>$slots_by_date]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    exit;
}

?>
