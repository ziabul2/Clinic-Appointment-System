<?php
$page_title = "Appointment Details";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Get appointment ID
    $appointment_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
    
    if (!$appointment_id) {
        throw new Exception("Invalid appointment ID");
    }

    // Fetch appointment details with all related info
    $query = "SELECT a.*, 
                     p.first_name as patient_first_name, 
                     p.last_name as patient_last_name,
                     p.email as patient_email,
                     p.phone as patient_phone,
                     p.gender as patient_gender,
                     p.date_of_birth as patient_dob,
                    p.address as patient_address,
                     d.first_name as doctor_first_name,
                     d.last_name as doctor_last_name,
                     d.specialization as doctor_specialization,
                     d.phone as doctor_phone,
                     d.email as doctor_email
              FROM appointments a
              LEFT JOIN patients p ON a.patient_id = p.patient_id
              LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
              WHERE a.appointment_id = :appointment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->execute();
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        throw new Exception("Appointment not found");
    }

    logAction("APPOINTMENT_VIEWED", "Appointment ID: $appointment_id viewed");

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect('appointments.php');
}
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-calendar-check"></i> Appointment Details</h4>
                        <small>ID: #<?php echo htmlspecialchars($appointment['appointment_id']); ?></small>
                    </div>
                    <div>
                        <span class="badge <?php 
                            $status_badge = [
                                'scheduled' => 'bg-primary',
                                'confirmed' => 'bg-info',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            echo $status_badge[$appointment['status']] ?? 'bg-secondary';
                        ?>" style="font-size: 1em;">
                            <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Appointment Date & Time -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2"><i class="fas fa-calendar"></i> Appointment Date</h6>
                            <p class="h5 mb-0"><?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                            <small class="text-muted">Time: <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2"><i class="fas fa-hourglass-end"></i> Time Status</h6>
                            <?php
                            $apt_time = strtotime($appointment['appointment_date']);
                            $today = strtotime('today');
                            if ($apt_time < $today) {
                                echo '<p class="h5 mb-0"><span class="badge bg-secondary">Past Appointment</span></p>';
                            } elseif ($apt_time == $today) {
                                echo '<p class="h5 mb-0"><span class="badge bg-warning">Today\'s Appointment</span></p>';
                            } else {
                                echo '<p class="h5 mb-0"><span class="badge bg-success">Upcoming</span></p>';
                            }
                            ?>
                        </div>
                    </div>

                    <hr>

                    <!-- Patient Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-user-circle"></i> Patient Information</h6>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Name:</div>
                                <div class="col-sm-8"><strong><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Email:</div>
                                <div class="col-sm-8"><a href="mailto:<?php echo htmlspecialchars($appointment['patient_email']); ?>"><?php echo htmlspecialchars($appointment['patient_email']); ?></a></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Phone:</div>
                                <div class="col-sm-8"><a href="tel:<?php echo htmlspecialchars($appointment['patient_phone']); ?>"><?php echo htmlspecialchars($appointment['patient_phone']); ?></a></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Gender:</div>
                                <div class="col-sm-8"><?php echo htmlspecialchars(ucfirst($appointment['patient_gender'])); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Date of Birth:</div>
                                <div class="col-sm-8">
                                    <?php 
                                    if ($appointment['patient_dob']) {
                                        $dob = new DateTime($appointment['patient_dob']);
                                        $age = (new DateTime())->diff($dob)->y;
                                        echo htmlspecialchars($dob->format('M d, Y')) . " (Age: $age)";
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (!empty($appointment['patient_address'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Address:</div>
                                <div class="col-sm-8">
                                    <?php echo htmlspecialchars($appointment['patient_address']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Doctor Information -->
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-stethoscope"></i> Doctor Information</h6>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Name:</div>
                                <div class="col-sm-8"><strong>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Specialization:</div>
                                <div class="col-sm-8"><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Email:</div>
                                <div class="col-sm-8"><a href="mailto:<?php echo htmlspecialchars($appointment['doctor_email']); ?>"><?php echo htmlspecialchars($appointment['doctor_email']); ?></a></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 text-muted">Phone:</div>
                                <div class="col-sm-8"><a href="tel:<?php echo htmlspecialchars($appointment['doctor_phone']); ?>"><?php echo htmlspecialchars($appointment['doctor_phone']); ?></a></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Consultation Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-notes-medical"></i> Consultation Details</h6>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Consultation Type:</div>
                                <div class="col-sm-7"><strong><?php echo htmlspecialchars(ucfirst($appointment['consultation_type'])); ?></strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Consultation Fee:</div>
                                <div class="col-sm-7"><strong><?php echo isset($appointment['consultation_fee']) ? '$' . number_format($appointment['consultation_fee'], 2) : 'N/A'; ?></strong></div>
                            </div>
                            <?php if (!empty($appointment['symptoms'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Symptoms:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['symptoms']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-file-alt"></i> Medical Information</h6>
                            <?php if (!empty($appointment['diagnosis'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Diagnosis:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['diagnosis']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($appointment['prescription'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Prescription:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['prescription']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($appointment['is_admitted'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Admission Status:</div>
                                <div class="col-sm-7">
                                    <span class="badge bg-warning">Admitted</span>
                                    <?php if (!empty($appointment['admission_date'])): ?>
                                    <small class="d-block mt-1">Date: <?php echo htmlspecialchars($appointment['admission_date']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($appointment['admission_notes'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Admission Notes:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['admission_notes']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($appointment['notes'])): ?>
                    <hr>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-sticky-note"></i> Additional Notes</h6>
                            <div class="alert alert-info"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($appointment['payment_status'])): ?>
                    <hr>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-credit-card"></i> Payment Information</h6>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Status:</div>
                                <div class="col-sm-7">
                                    <span class="badge <?php echo $appointment['payment_status'] === 'paid' ? 'bg-success' : ($appointment['payment_status'] === 'pending' ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($appointment['payment_status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (!empty($appointment['payment_method'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Method:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['payment_method']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($appointment['amount_paid'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Amount Paid:</div>
                                <div class="col-sm-7"><strong>$<?php echo number_format($appointment['amount_paid'], 2); ?></strong></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($appointment['payment_date'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Payment Date:</div>
                                <div class="col-sm-7"><?php echo date('M d, Y', strtotime($appointment['payment_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($appointment['payment_notes'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">Payment Notes:</div>
                                <div class="col-sm-7"><?php echo htmlspecialchars($appointment['payment_notes']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between">
                        <div>
                            <a href="appointments.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                            <a href="appointment_actions.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Appointment</a>
                        </div>
                        <div>
                            <form method="POST" action="../process.php?action=send_appointment_mail" style="display:inline;">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                <button type="submit" class="btn btn-info"><i class="fas fa-envelope"></i> Send Email</button>
                            </form>
                            <a href="print_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Print</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-body .row {
        align-items: flex-start;
    }
    .text-muted {
        font-weight: 500;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
