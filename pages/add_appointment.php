<?php
$page_title = "Add New Appointment";
// Load config (do not include full header yet so we can handle POST redirects before any output)
require_once __DIR__ . '/../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Fetch doctors for dropdown
    $doctors_query = "SELECT doctor_id, first_name, last_name, specialization, available_days, available_time_start, available_time_end, consultation_fee 
                     FROM doctors WHERE available_days != '' ORDER BY first_name, last_name";
    $doctors_stmt = $db->prepare($doctors_query);
    $doctors_stmt->execute();
    $doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch ONLY today's admitted patients for dropdown
    $patients_query = "SELECT patient_id, first_name, last_name, phone, email 
                      FROM patients 
                      WHERE DATE(admitted_at) = CURDATE() 
                      ORDER BY first_name, last_name";
    $patients_stmt = $db->prepare($patients_query);
    $patients_stmt->execute();
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_POST) {
        $patient_id = sanitizeInput($_POST['patient_id']);
        $doctor_id = sanitizeInput($_POST['doctor_id']);
        $appointment_date = sanitizeInput($_POST['appointment_date']);
        $appointment_time = sanitizeInput($_POST['appointment_time']);
        $consultation_type = sanitizeInput($_POST['consultation_type']);
        $symptoms = sanitizeInput($_POST['symptoms'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $status = 'scheduled';
        $payment_status = 'pending';

        // Debug: Check if required fields are present
        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($consultation_type)) {
            throw new Exception("Required fields are missing");
        }

        // Get doctor's consultation fee
        $fee_query = "SELECT consultation_fee FROM doctors WHERE doctor_id = :doctor_id";
        $fee_stmt = $db->prepare($fee_query);
        $fee_stmt->bindParam(':doctor_id', $doctor_id);
        $fee_stmt->execute();
        $doctor_fee = $fee_stmt->fetch(PDO::FETCH_ASSOC);
        $consultation_fee = $doctor_fee['consultation_fee'] ?? 0;

        // Validate appointment date and time
        $appointment_datetime = $appointment_date . ' ' . $appointment_time;
        $current_datetime = date('Y-m-d H:i:s');
        
        if ($appointment_date !== date('Y-m-d')) {
            $error = "Appointments can only be scheduled for TODAY (" . date('M j, Y') . ").";
        }

        if (!isset($error) && strtotime($appointment_datetime) < strtotime($current_datetime)) {
            $error = "Appointment time cannot be in the past.";
        }

        // Check for scheduling conflicts
        $conflict_query = "SELECT appointment_id FROM appointments 
                          WHERE doctor_id = :doctor_id 
                          AND appointment_date = :appointment_date 
                          AND appointment_time = :appointment_time 
                          AND status NOT IN ('cancelled', 'completed')";
        $conflict_stmt = $db->prepare($conflict_query);
        $conflict_stmt->bindParam(':doctor_id', $doctor_id);
        $conflict_stmt->bindParam(':appointment_date', $appointment_date);
        $conflict_stmt->bindParam(':appointment_time', $appointment_time);
        $conflict_stmt->execute();
        
        if ($conflict_stmt->rowCount() > 0) {
            $error = "This time slot is already booked for the selected doctor. Please choose a different time.";
        }

        // Check doctor's availability
        $day_query = "SELECT available_days, available_time_start, available_time_end FROM doctors WHERE doctor_id = :doctor_id";
        $day_stmt = $db->prepare($day_query);
        $day_stmt->bindParam(':doctor_id', $doctor_id);
        $day_stmt->execute();
        $doctor_availability = $day_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doctor_availability) {
            $available_days = explode(',', $doctor_availability['available_days']);
            $appointment_day = date('l', strtotime($appointment_date));
            
            if (!in_array($appointment_day, $available_days)) {
                $error = "Doctor is not available on $appointment_day. Available days: " . implode(', ', $available_days);
            }
            
            // Check if appointment time is within doctor's available hours
            // Normalize both to HH:MM format for proper comparison
            $apptTimeNorm = substr($appointment_time, 0, 5); // HH:MM
            $startTimeNorm = substr($doctor_availability['available_time_start'], 0, 5); // HH:MM from TIME field
            $endTimeNorm = substr($doctor_availability['available_time_end'], 0, 5); // HH:MM from TIME field
            
            if ($apptTimeNorm < $startTimeNorm || $apptTimeNorm > $endTimeNorm) {
                $error = "Doctor is only available from " . $startTimeNorm . " to " . $endTimeNorm;
            }
        }

        if (!isset($error)) {
            // Use only columns that definitely exist in your appointments table
            $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, symptoms, notes, status) 
                     VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :consultation_type, :symptoms, :notes, :status)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':patient_id', $patient_id);
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->bindParam(':appointment_date', $appointment_date);
            $stmt->bindParam(':appointment_time', $appointment_time);
            $stmt->bindParam(':consultation_type', $consultation_type);
            $stmt->bindParam(':symptoms', $symptoms);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                $appointment_id = $db->lastInsertId();
                
                // Log the action
                $patient_name = "";
                $doctor_name = "";
                $patient_email = '';
                $doctor_email = '';
                
                foreach ($patients as $p) {
                    if ($p['patient_id'] == $patient_id) {
                        $patient_name = $p['first_name'] . ' ' . $p['last_name'];
                        $patient_email = $p['email'] ?? '';
                        break;
                    }
                }
                
                foreach ($doctors as $d) {
                    if ($d['doctor_id'] == $doctor_id) {
                        $doctor_name = $d['first_name'] . ' ' . $d['last_name'];
                        $doctor_email = $d['email'] ?? '';
                        break;
                    }
                }
                
                logAction("APPOINTMENT_ADDED", "New appointment: $patient_name with Dr. $doctor_name on $appointment_date at $appointment_time (ID: $appointment_id)");
                $_SESSION['success'] = "Appointment scheduled successfully!";
                
                // Create notifications: notify the doctor user (if exists) and create an admin notification
                try {
                    // Try find a user account for the doctor
                    $uq = $db->prepare("SELECT user_id FROM users WHERE doctor_id = :doctor_id LIMIT 1");
                    $uq->bindParam(':doctor_id', $doctor_id);
                    $uq->execute();
                    $title = 'New appointment scheduled';
                    $message = "Appointment for $patient_name on $appointment_date at $appointment_time.";
                    $meta = ['appointment_id' => $appointment_id, 'patient_id' => $patient_id, 'doctor_id' => $doctor_id];
                    if ($uq->rowCount() > 0) {
                        $ur = $uq->fetch(PDO::FETCH_ASSOC);
                        createNotification($db, $ur['user_id'], 'appointment_created', $title, $message, $meta);
                    } else {
                        // No doctor user account — create a system-level notification (user_id NULL) for admins to pick up
                        createNotification($db, null, 'appointment_created', "Appointment scheduled (Dr. $doctor_name)", "Appointment for $patient_name with Dr. $doctor_name on $appointment_date at $appointment_time", $meta);
                    }

                    // Also notify receptionist/admin users about the new appointment
                    $aq = $db->query("SELECT user_id FROM users WHERE role IN ('admin','receptionist')");
                    if ($aq) {
                        foreach ($aq->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            createNotification($db, $row['user_id'], 'appointment_created', $title, $message, $meta);
                        }
                    }
                    // Also notify patient if they have a linked user account
                    try {
                        $patientUserId = null;
                        // First, try to find a user linked by patient_id column (if it exists)
                        try {
                            $pu = $db->prepare('SELECT user_id FROM users WHERE patient_id = :pid LIMIT 1');
                            $pu->bindParam(':pid', $patient_id);
                            $pu->execute();
                            if ($pu->rowCount() > 0) {
                                $pr = $pu->fetch(PDO::FETCH_ASSOC);
                                $patientUserId = $pr['user_id'];
                            }
                        } catch (Exception $inner) {
                            // Column may not exist; fall back to matching by email
                            if (!empty($patient_email)) {
                                $pu = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                                $pu->bindParam(':email', $patient_email);
                                $pu->execute();
                                if ($pu->rowCount() > 0) {
                                    $pr = $pu->fetch(PDO::FETCH_ASSOC);
                                    $patientUserId = $pr['user_id'];
                                }
                            }
                        }

                        if ($patientUserId) {
                            createNotification($db, $patientUserId, 'appointment_created', 'Appointment Scheduled', "Your appointment with Dr. $doctor_name on $appointment_date at $appointment_time has been scheduled.", $meta);
                        }
                    } catch (Exception $e) {
                        logAction('NOTIF_ERROR', 'Failed to notify patient user: ' . $e->getMessage());
                    }
                } catch (Exception $e) {
                    logAction('NOTIF_ERROR', 'Failed to create appointment notifications: ' . $e->getMessage());
                }

                // Optionally send an email confirmation to the patient (if email present)
                if (!empty($patient_email)) {
                    try {
                        sendAppointmentNotificationToPatient($patient_email, $patient_name, $doctor_name, $appointment_date, $appointment_time, $notes);
                    } catch (Exception $e) {
                        logAction('EMAIL_ERROR', 'Failed to send appointment email to patient: ' . $e->getMessage());
                    }
                }

                // Redirect to print page
                redirect('print_appointment.php?id=' . $appointment_id);
            } else {
                // Get more detailed error information
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database error: " . $errorInfo[2]);
            }
        }
    }

} catch (PDOException $e) {
    logAction("APPOINTMENT_ADD_ERROR", "Database error: " . $e->getMessage());
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    logAction("APPOINTMENT_ADD_ERROR", "General error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<?php
// Now include header (outputs HTML head). We include it after POST handling to allow redirects.
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-plus"></i> Today's Appointment</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="appointments.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Appointments
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-check"></i> Appointment Information</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="appointmentForm">
                    <?php echo csrf_input(); ?>
                    <div class="row">
                        <!-- Patient Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Select Patient *</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Choose a patient...</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['patient_id']; ?>" 
                                                <?php echo ($_POST['patient_id'] ?? '') == $patient['patient_id'] ? 'selected' : ''; ?>
                                                data-phone="<?php echo htmlspecialchars($patient['phone']); ?>"
                                                data-email="<?php echo htmlspecialchars($patient['email']); ?>">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                            (<?php echo htmlspecialchars($patient['phone']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="patientInfo">
                                    Select a patient to view contact information
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="doctor_id" class="form-label">Select Doctor *</label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                    <option value="">Choose a doctor...</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['doctor_id']; ?>" 
                                                <?php echo ($_POST['doctor_id'] ?? '') == $doctor['doctor_id'] ? 'selected' : ''; ?>
                                                data-fee="<?php echo $doctor['consultation_fee']; ?>"
                                                data-days="<?php echo htmlspecialchars($doctor['available_days']); ?>"
                                                data-time-start="<?php echo $doctor['available_time_start']; ?>"
                                                data-time-end="<?php echo $doctor['available_time_end']; ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" id="doctorInfo">
                                    Select a doctor to view availability and fees
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Appointment Date -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Appointment Date *</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                       min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required
                                       value="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text text-primary" id="dateInfo">
                                    <i class="fas fa-info-circle"></i> Only today's appointments are accepted.
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Time -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Appointment Time *</label>
                                <input type="hidden" id="appointment_time" name="appointment_time" value="">
                                <div id="slotPicker" class="mt-2"></div>
                                <div class="form-text" id="timeInfo">
                                    Select appointment time
                                </div>
                            </div>
                        </div>

                        <!-- Consultation Type -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="consultation_type" class="form-label">Consultation Type *</label>
                                <select class="form-select" id="consultation_type" name="consultation_type" required>
                                    <option value="">Select type...</option>
                                    <option value="checkup" <?php echo ($_POST['consultation_type'] ?? '') == 'checkup' ? 'selected' : ''; ?>>Regular Checkup</option>
                                    <option value="followup" <?php echo ($_POST['consultation_type'] ?? '') == 'followup' ? 'selected' : ''; ?>>Follow-up Visit</option>
                                    <option value="emergency" <?php echo ($_POST['consultation_type'] ?? '') == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                                    <option value="consultation" <?php echo ($_POST['consultation_type'] ?? '') == 'consultation' ? 'selected' : ''; ?>>Consultation</option>
                                    <option value="surgery" <?php echo ($_POST['consultation_type'] ?? '') == 'surgery' ? 'selected' : ''; ?>>Surgery</option>
                                    <option value="therapy" <?php echo ($_POST['consultation_type'] ?? '') == 'therapy' ? 'selected' : ''; ?>>Therapy Session</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Symptoms -->
                    <div class="mb-3">
                        <label for="symptoms" class="form-label">Symptoms / Reason for Visit *</label>
                        <textarea class="form-control" id="symptoms" name="symptoms" rows="3" required
                                  placeholder="Describe the symptoms or reason for the appointment..."><?php echo $_POST['symptoms'] ?? ''; ?></textarea>
                        <div class="form-text">
                            Provide details about symptoms, pain levels, duration, etc.
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Any additional information or special requirements..."><?php echo $_POST['notes'] ?? ''; ?></textarea>
                    </div>

                    <!-- Appointment Summary -->
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-receipt"></i> Appointment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Patient:</strong> <span id="summaryPatient">-</span></p>
                                    <p><strong>Doctor:</strong> <span id="summaryDoctor">-</span></p>
                                    <p><strong>Specialization:</strong> <span id="summarySpecialization">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Date & Time:</strong> <span id="summaryDateTime">-</span></p>
                                    <p><strong>Consultation Type:</strong> <span id="summaryType">-</span></p>
                                    <p><strong>Consultation Fee:</strong> ৳<span id="summaryFee">0.00</span></p>
                                </div>
                            </div>
                            <div class="alert alert-info mb-0" id="availabilityAlert">
                                <small><i class="fas fa-info-circle"></i> Please select a patient, doctor, date, and time to see availability information.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="appointments.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Schedule Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const patientSelect = document.getElementById('patient_id');
    const doctorSelect = document.getElementById('doctor_id');
    const appointmentDate = document.getElementById('appointment_date');
    const appointmentTime = document.getElementById('appointment_time');
    const consultationType = document.getElementById('consultation_type');
    const doctorInfo = document.getElementById('doctorInfo');
    const dateInfo = document.getElementById('dateInfo');
    const timeInfo = document.getElementById('timeInfo');
    
    const summaryPatient = document.getElementById('summaryPatient');
    const summaryDoctor = document.getElementById('summaryDoctor');
    const summarySpecialization = document.getElementById('summarySpecialization');
    const summaryDateTime = document.getElementById('summaryDateTime');
    const summaryType = document.getElementById('summaryType');
    const summaryFee = document.getElementById('summaryFee');
    const availabilityAlert = document.getElementById('availabilityAlert');

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    appointmentDate.min = today;

    // Helper function to format time as 12-hour AM/PM
    function format12Hour(timeStr) {
        // timeStr format: HH:MM
        const [h, m] = timeStr.split(':');
        let hour = parseInt(h, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return hour.toString().padStart(2, '0') + ':' + m + ' ' + ampm;
    }

    // Update patient information
    patientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const phone = selectedOption.getAttribute('data-phone');
            const email = selectedOption.getAttribute('data-email');
            
            patientInfo.innerHTML = `<i class="fas fa-phone"></i> ${phone} | <i class="fas fa-envelope"></i> ${email}`;
            summaryPatient.textContent = selectedOption.textContent.split(' (')[0];
        } else {
            patientInfo.textContent = 'Select a patient to view contact information';
            summaryPatient.textContent = '-';
        }
        updateSummary();
    });

    // Update doctor information
    doctorSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const fee = selectedOption.getAttribute('data-fee');
            const days = selectedOption.getAttribute('data-days');
            const timeStart = selectedOption.getAttribute('data-time-start');
            const timeEnd = selectedOption.getAttribute('data-time-end');
            
            const displayStart = format12Hour(timeStart);
            const displayEnd = format12Hour(timeEnd);
            doctorInfo.innerHTML = `<i class="fas fa-money-bill"></i> Fee: ৳${fee} | <i class="fas fa-calendar"></i> Available: ${days} | <i class="fas fa-clock"></i> ${displayStart} - ${displayEnd}`;
            
            const doctorText = selectedOption.textContent;
            summaryDoctor.textContent = doctorText.split(' - ')[0];
            summarySpecialization.textContent = doctorText.split(' - ')[1] || '-';
            summaryFee.textContent = parseFloat(fee).toFixed(2);
            
            // Set suggested time based on doctor's availability
            if (timeStart && !appointmentTime.value) {
                appointmentTime.value = timeStart;
            }
        } else {
            doctorInfo.textContent = 'Select a doctor to view availability and fees';
            summaryDoctor.textContent = '-';
            summarySpecialization.textContent = '-';
            summaryFee.textContent = '0.00';
        }
        updateSummary();
        checkAvailability();
    });

    // Update date information
    appointmentDate.addEventListener('change', function() {
        if (this.value) {
            const dateObj = new Date(this.value);
            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
            dateInfo.innerHTML = `<i class="fas fa-calendar-day"></i> ${dayName}`;
        } else {
            dateInfo.textContent = 'Select an appointment date';
        }
        updateSummary();
        checkAvailability();
    });

    // Update time information
    appointmentTime.addEventListener('change', function() {
        if (this.value) {
            const timeString = this.value;
            const [hours, minutes] = timeString.split(':');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hours12 = hours % 12 || 12;
            timeInfo.innerHTML = `<i class="fas fa-clock"></i> ${hours12}:${minutes} ${ampm}`;
        } else {
            timeInfo.textContent = 'Select appointment time';
        }
        updateSummary();
        checkAvailability();
    });

    // Update consultation type in summary
    consultationType.addEventListener('change', function() {
        summaryType.textContent = this.options[this.selectedIndex].text || '-';
        updateSummary();
    });

    // Update full summary
    function updateSummary() {
        const date = appointmentDate.value;
        const time = appointmentTime.value;
        
        if (date && time) {
            const dateObj = new Date(date);
            const timeLabel = format12Hour(time);
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            };
            const dateStr = dateObj.toLocaleDateString('en-US', options);
            summaryDateTime.textContent = dateStr + ', ' + timeLabel;
        } else {
            summaryDateTime.textContent = '-';
        }
    }

    // Check availability and fetch live slots from AJAX endpoint
    // New behavior: first populate client-side slots from doctor's working hours so the user immediately sees time options.
    // Then call the server to determine which slots are actually available and mark unavailable ones.
    function checkAvailability() {
        const doctorId = doctorSelect.value;
        const date = appointmentDate.value;

        const slotPicker = document.getElementById('slotPicker');

        if (!doctorId) {
            availabilityAlert.className = 'alert alert-info mb-0';
            availabilityAlert.innerHTML = '<small><i class="fas fa-info-circle"></i> Please select a doctor to see availability information.</small>';
            return;
        }

        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
        const availableDays = (selectedOption.getAttribute('data-days') || '').split(',');
        const timeStart = selectedOption.getAttribute('data-time-start');
        const timeEnd = selectedOption.getAttribute('data-time-end');

        // If no date selected, use today so times appear immediately when a doctor is selected
        const useDate = date || today;
        const dateObj = new Date(useDate);
        const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });

        // Normalize and parse available days robustly (accept names, short names, numbers)
        const availableDaysRaw = (selectedOption.getAttribute('data-days') || '').trim();
        let doctorAvailable = true;
        if (availableDaysRaw !== '') {
            const parts = availableDaysRaw.split(',').map(p=>p.trim()).filter(Boolean);
            const nameMap = { mon:'monday', tue:'tuesday', wed:'wednesday', thu:'thursday', fri:'friday', sat:'saturday', sun:'sunday' };
            const numToName = {1:'monday',2:'tuesday',3:'wednesday',4:'thursday',5:'friday',6:'saturday',7:'sunday'};
            const allowed = new Set();
            parts.forEach(function(p){
                const low = p.toLowerCase();
                if (/^\d+$/.test(low)) {
                    const n = parseInt(low,10);
                    if (numToName[n]) allowed.add(numToName[n]);
                } else if (nameMap[low.substr(0,3)]) {
                    allowed.add(nameMap[low.substr(0,3)]);
                } else {
                    allowed.add(low);
                }
            });
            doctorAvailable = allowed.has(dayName.toLowerCase());
        }

        if (!doctorAvailable) {
            availabilityAlert.className = 'alert alert-warning mb-0';
            availabilityAlert.innerHTML = `<small><i class="fas fa-exclamation-triangle"></i> Doctor is not available on ${dayName}. Available days: ${availableDaysRaw || 'Not configured'}</small>`;
            return;
        }

        // decide duration based on consultation type
        let duration = 15;
        const type = consultationType.value;
        const durationMap = { 'checkup':15, 'followup':15, 'emergency':30, 'consultation':30, 'surgery':60, 'therapy':45 };
        if (durationMap[type]) duration = durationMap[type];

        // Step for walking the slots client-side (default small step so options are fine-grained)
        const clientStep = 15;

        // Build client-side slot list immediately so user sees choices
        const clientSlots = [];
        if (timeStart && timeEnd) {
            // normalize HH:MM[:SS]
            function normalizeHM(s) { const p = (s||'').split(':'); return (p[0]||'00').padStart(2,'0') + ':' + (p[1]||'00').padStart(2,'0'); }
            const sHM = normalizeHM(timeStart);
            const eHM = normalizeHM(timeEnd);
            let cur = new Date(useDate + 'T' + sHM + ':00');
            const endDT = new Date(useDate + 'T' + eHM + ':00');
            while (cur < endDT) {
                const hm = cur.toTimeString().slice(0,5);
                clientSlots.push(hm);
                cur.setMinutes(cur.getMinutes() + clientStep);
            }
        }

        if (clientSlots.length === 0) {
            availabilityAlert.className = 'alert alert-warning mb-0';
            availabilityAlert.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> No configured working hours for selected doctor.</small>';
            return;
        }

        // Render client-side slots as neutral (will be refined by server)
        const fragInit = document.createDocumentFragment();
        clientSlots.forEach(function(t) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm me-1 mb-1 slot-btn slot-pending';
            const label = format12Hour(t);
            btn.textContent = label;
            btn.dataset.time = t;
            btn.addEventListener('click', function() {
                // If server later marks it unavailable, the button will be disabled
                if (this.disabled) return;
                // If no date selected by the user, set the appointment date to the one used to render slots
                if (!appointmentDate.value) {
                    appointmentDate.value = useDate;
                    // update date info and trigger change handlers
                    appointmentDate.dispatchEvent(new Event('change'));
                }
                appointmentTime.value = this.dataset.time;
                document.querySelectorAll('.slot-btn').forEach(function(b){ b.classList.remove('active'); });
                this.classList.add('active');
                // Update time info display
                const label = format12Hour(this.dataset.time);
                timeInfo.innerHTML = '<i class="fas fa-clock"></i> ' + label;
                updateSummary();
            });
            fragInit.appendChild(btn);
        });
        slotPicker.appendChild(fragInit);

        availabilityAlert.className = 'alert alert-info mb-0';
        availabilityAlert.innerHTML = '<small><i class="fas fa-spinner fa-pulse"></i> Checking live availability...</small>';

        // call the availability endpoint for the single date to refine which slots are allowed
        // Use relative path to avoid cross-host/port issues and gracefully handle failures
        fetch('../ajax/check_availability.php?doctor_id=' + encodeURIComponent(doctorId) + '&start_date=' + encodeURIComponent(useDate) + '&end_date=' + encodeURIComponent(useDate) + '&duration=' + encodeURIComponent(duration) + '&step=' + encodeURIComponent(clientStep))
            .then(resp => resp.json())
            .then(data => {
                if (!data || !data.ok) {
                    availabilityAlert.className = 'alert alert-danger mb-0';
                    availabilityAlert.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> Unable to fetch availability.</small>';
                    return;
                }
                const available = new Set((data.slots && data.slots[useDate]) ? data.slots[useDate] : []);

                // update UI: mark available slots (primary) and mark unavailable (disabled)
                const btns = slotPicker.querySelectorAll('.slot-btn');
                let anyAvailable = false;
                btns.forEach(function(b){
                    const t = b.dataset.time;
                    if (available.has(t)) {
                        b.disabled = false;
                        b.classList.remove('btn-outline-secondary','btn-outline-light','text-muted','disabled');
                        b.classList.add('btn-outline-primary');
                        b.classList.remove('slot-pending');
                        b.removeAttribute('title');
                        b.style.cursor = 'pointer';
                        anyAvailable = true;
                    } else {
                        b.disabled = true;
                        b.classList.remove('btn-outline-secondary','btn-outline-primary');
                        b.classList.add('btn-outline-light','text-muted','disabled');
                        b.setAttribute('title', 'This time slot is already booked');
                        b.style.cursor = 'not-allowed';
                        b.style.opacity = '0.6';
                    }
                });

                if (!anyAvailable) {
                    availabilityAlert.className = 'alert alert-warning mb-0';
                    availabilityAlert.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> No available slots on selected date.</small>';
                } else {
                    availabilityAlert.className = 'alert alert-success mb-0';
                    availabilityAlert.innerHTML = '<small><i class="fas fa-check-circle"></i> Available slots loaded. Click a slot to select it.</small>';
                }
            }).catch(err => {
                console.error('Availability fetch failed:', err);
                // If live check fails, leave client-side slots enabled so user can still select and save
                const btns = slotPicker.querySelectorAll('.slot-btn');
                btns.forEach(function(b){
                    b.disabled = false;
                    b.classList.remove('btn-outline-secondary','slot-pending');
                    b.classList.add('btn-outline-primary');
                    b.removeAttribute('title');
                    b.style.cursor = 'pointer';
                    b.style.opacity = '1';
                });
                availabilityAlert.className = 'alert alert-warning mb-0';
                availabilityAlert.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> Live availability check failed — showing configured times only.</small>';
            });
    }

    // Initialize summary and availability check
    updateSummary();
    checkAvailability();

    // Ensure appointment_time is set before submit. Pick the active slot button and set the hidden input.
    const appointmentForm = document.getElementById('appointmentForm');
    appointmentForm.addEventListener('submit', function(ev) {
        // Try to find an active slot button
        const activeBtn = document.querySelector('.slot-btn.active');
        if (activeBtn) {
            appointmentTime.value = activeBtn.dataset.time;
            return; // allow submit
        }

        // no active slot — prevent submit and show inline error
        ev.preventDefault();
        availabilityAlert.className = 'alert alert-danger mb-0';
        availabilityAlert.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> Please select an appointment time before submitting.</small>';
        availabilityAlert.scrollIntoView({ behavior: 'smooth' });
    });

    // Trigger change events for any pre-selected values
    if (patientSelect.value) patientSelect.dispatchEvent(new Event('change'));
    if (doctorSelect.value) doctorSelect.dispatchEvent(new Event('change'));
    if (appointmentDate.value) appointmentDate.dispatchEvent(new Event('change'));
    if (appointmentTime.value) appointmentTime.dispatchEvent(new Event('change'));
});
</script>

<?php require_once '../includes/footer.php'; ?>