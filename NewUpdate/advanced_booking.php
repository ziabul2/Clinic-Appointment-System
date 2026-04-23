<?php
// advanced_booking.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['role'];
$patient_id = ($user_role === 'patient') ? getPatientId($pdo, $_SESSION['user_id']) : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? $patient_id;
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $symptoms = $_POST['symptoms'] ?? '';
    $consultation_type = $_POST['consultation_type'] ?? 'general';
    $is_recurring = $_POST['is_recurring'] ?? 0;
    $recurring_type = $_POST['recurring_type'] ?? '';
    $recurring_count = $_POST['recurring_count'] ?? 1;
    
    if (bookAppointment($pdo, $patient_id, $doctor_id, $appointment_date, $appointment_time, $symptoms, $consultation_type, $is_recurring, $recurring_type, $recurring_count)) {
        $success = "Appointment booked successfully!";
    } else {
        $error = "Failed to book appointment. Please try again.";
    }
}

// Get available doctors
$doctors = getAvailableDoctors($pdo);
$patients = ($user_role === 'admin' || $user_role === 'receptionist') ? getPatients($pdo) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Booking - Clinic Management System</title>
    <style>
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .time-slot {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background: #f0f0f0;
        }
        
        .time-slot.selected {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .recurring-options {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <h1>📅 Advanced Appointment Booking</h1>
        
        <?php if (isset($success)): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            ✅ <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            ❌ <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="bookingForm">
            <?php if ($user_role === 'admin' || $user_role === 'receptionist'): ?>
            <div class="form-group">
                <label for="patient_id">Select Patient *</label>
                <select name="patient_id" id="patient_id" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $patient): ?>
                    <option value="<?php echo $patient['patient_id']; ?>">
                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['email'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="doctor_id">Select Doctor *</label>
                <select name="doctor_id" id="doctor_id" required onchange="loadAvailableSlots()">
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctors as $doctor): ?>
                    <option value="<?php echo $doctor['doctor_id']; ?>" data-fee="<?php echo $doctor['consultation_fee']; ?>">
                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?> ($<?php echo $doctor['consultation_fee']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Appointment Date *</label>
                <input type="date" name="appointment_date" id="appointment_date" required min="<?php echo date('Y-m-d'); ?>" onchange="loadAvailableSlots()">
            </div>
            
            <div class="form-group">
                <label>Available Time Slots *</label>
                <div id="timeSlotsContainer">
                    <p>Please select a doctor and date to see available time slots.</p>
                </div>
                <input type="hidden" name="appointment_time" id="appointment_time" required>
            </div>
            
            <div class="form-group">
                <label for="symptoms">Symptoms/Reason for Visit</label>
                <textarea name="symptoms" id="symptoms" rows="3" placeholder="Describe your symptoms or reason for the appointment..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="consultation_type">Consultation Type</label>
                <select name="consultation_type" id="consultation_type">
                    <option value="general">General Consultation</option>
                    <option value="follow-up">Follow-up</option>
                    <option value="emergency">Emergency</option>
                    <option value="routine">Routine Checkup</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_recurring" id="is_recurring" onchange="toggleRecurringOptions()">
                    Recurring Appointment
                </label>
                
                <div class="recurring-options" id="recurringOptions">
                    <div class="form-group">
                        <label for="recurring_type">Recurrence Pattern</label>
                        <select name="recurring_type" id="recurring_type">
                            <option value="weekly">Weekly</option>
                            <option value="bi-weekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="recurring_count">Number of Appointments</label>
                        <input type="number" name="recurring_count" id="recurring_count" min="2" max="12" value="4">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" style="background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                    📅 Book Appointment
                </button>
                
                <a href="dashboard.php" style="padding: 15px 30px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                    ← Back to Dashboard
                </a>
            </div>
        </form>
    </div>

    <script>
        function loadAvailableSlots() {
            const doctorId = document.getElementById('doctor_id').value;
            const appointmentDate = document.getElementById('appointment_date').value;
            const container = document.getElementById('timeSlotsContainer');
            
            if (!doctorId || !appointmentDate) {
                container.innerHTML = '<p>Please select a doctor and date to see available time slots.</p>';
                return;
            }
            
            // Show loading
            container.innerHTML = '<p>Loading available slots...</p>';
            
            // Fetch available slots via AJAX
            fetch(`ajax_get_slots.php?doctor_id=${doctorId}&date=${appointmentDate}`)
                .then(response => response.json())
                .then(slots => {
                    if (slots.length === 0) {
                        container.innerHTML = '<p>No available slots for selected date. Please choose another date.</p>';
                        return;
                    }
                    
                    let html = '<div class="time-slots">';
                    slots.forEach(slot => {
                        html += `<div class="time-slot" onclick="selectTimeSlot('${slot}')">${slot}</div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = '<p>Error loading available slots. Please try again.</p>';
                    console.error('Error:', error);
                });
        }
        
        function selectTimeSlot(time) {
            // Remove selected class from all slots
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Add selected class to clicked slot
            event.target.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('appointment_time').value = time;
        }
        
        function toggleRecurringOptions() {
            const recurringOptions = document.getElementById('recurringOptions');
            recurringOptions.style.display = document.getElementById('is_recurring').checked ? 'block' : 'none';
        }
        
        // Initialize date picker with minimum date as today
        document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>

<?php
// Helper functions
function getAvailableDoctors($pdo) {
    $stmt = $pdo->query("SELECT * FROM doctors WHERE available_days IS NOT NULL AND available_days != ''");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatients($pdo) {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY first_name, last_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatientId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT patient_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function bookAppointment($pdo, $patient_id, $doctor_id, $date, $time, $symptoms, $type, $is_recurring, $recurring_type, $recurring_count) {
    try {
        $pdo->beginTransaction();
        
        $appointment_ids = [];
        
        for ($i = 0; $i < ($is_recurring ? $recurring_count : 1); $i++) {
            $current_date = $date;
            
            if ($i > 0) {
                // Calculate next appointment date based on recurrence pattern
                switch ($recurring_type) {
                    case 'weekly':
                        $current_date = date('Y-m-d', strtotime("+{$i} week", strtotime($date)));
                        break;
                    case 'bi-weekly':
                        $current_date = date('Y-m-d', strtotime("+" . ($i * 2) . " week", strtotime($date)));
                        break;
                    case 'monthly':
                        $current_date = date('Y-m-d', strtotime("+{$i} month", strtotime($date)));
                        break;
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, symptoms, consultation_type, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$patient_id, $doctor_id, $current_date, $time, $symptoms, $type])) {
                $appointment_ids[] = $pdo->lastInsertId();
            }
        }
        
        $pdo->commit();
        
        // Send email notifications
        require_once 'includes/EmailService.php';
        $emailService = new EmailService($pdo);
        foreach ($appointment_ids as $appointment_id) {
            $emailService->sendAppointmentConfirmation($appointment_id);
        }
        
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Booking error: " . $e->getMessage());
        return false;
    }
}
?>