<?php
$page_title = "Update Appointment";
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

    // Fetch appointment details
    $query = "SELECT a.*, 
                     p.first_name as patient_first_name, 
                     p.last_name as patient_last_name,
                     d.first_name as doctor_first_name,
                     d.last_name as doctor_last_name
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

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token (use global helper)
        if (!verify_csrf()) {
            throw new Exception("Invalid CSRF token");
        }

        $status = sanitizeInput($_POST['status']);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $diagnosis = sanitizeInput($_POST['diagnosis'] ?? '');
        $prescription = sanitizeInput($_POST['prescription'] ?? '');
        $is_admitted = isset($_POST['is_admitted']) ? 1 : 0;
        $admission_notes = sanitizeInput($_POST['admission_notes'] ?? '');
        $admission_date = !empty($_POST['admission_date']) ? sanitizeInput($_POST['admission_date']) : null;
        $payment_status = sanitizeInput($_POST['payment_status'] ?? '');
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        $amount_paid = !empty($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
        $payment_date = !empty($_POST['payment_date']) ? sanitizeInput($_POST['payment_date']) : null;
        $payment_notes = sanitizeInput($_POST['payment_notes'] ?? '');

        // Update appointment
        $update_query = "UPDATE appointments SET 
                         status = :status,
                         notes = :notes,
                         diagnosis = :diagnosis,
                         prescription = :prescription,
                         is_admitted = :is_admitted,
                         admission_notes = :admission_notes,
                         admission_date = :admission_date,
                         payment_status = :payment_status,
                         payment_method = :payment_method,
                         amount_paid = :amount_paid,
                         payment_date = :payment_date,
                         payment_notes = :payment_notes,
                         updated_at = NOW()
                         WHERE appointment_id = :appointment_id";

        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':status', $status);
        $update_stmt->bindParam(':notes', $notes);
        $update_stmt->bindParam(':diagnosis', $diagnosis);
        $update_stmt->bindParam(':prescription', $prescription);
        $update_stmt->bindParam(':is_admitted', $is_admitted, PDO::PARAM_INT);
        $update_stmt->bindParam(':admission_notes', $admission_notes);
        $update_stmt->bindParam(':admission_date', $admission_date);
        $update_stmt->bindParam(':payment_status', $payment_status);
        $update_stmt->bindParam(':payment_method', $payment_method);
        $update_stmt->bindParam(':amount_paid', $amount_paid);
        $update_stmt->bindParam(':payment_date', $payment_date);
        $update_stmt->bindParam(':payment_notes', $payment_notes);
        $update_stmt->bindParam(':appointment_id', $appointment_id);

        if ($update_stmt->execute()) {
            logAction("APPOINTMENT_UPDATED", "Appointment ID: $appointment_id updated");
            $_SESSION['success'] = "Appointment updated successfully!";
            redirect("appointment_view.php?id=$appointment_id");
        } else {
            throw new Exception("Failed to update appointment");
        }
    }

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
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0"><i class="fas fa-edit"></i> Update Appointment</h4>
                    <small>ID: #<?php echo htmlspecialchars($appointment['appointment_id']); ?> | 
                    Patient: <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?> | 
                    Doctor: Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></small>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php echo csrf_input(); ?>

                        <!-- Status Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-check-circle"></i> Appointment Status</h6>
                                <div class="mb-3">
                                    <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="scheduled" <?php echo $appointment['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-sticky-note"></i> General Notes</h6>
                                <div class="mb-3">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes or comments..."><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Medical Information Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-stethoscope"></i> Medical Information</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="diagnosis">Diagnosis</label>
                                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="2" placeholder="Enter diagnosis..."><?php echo htmlspecialchars($appointment['diagnosis']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="prescription">Prescription</label>
                                    <textarea class="form-control" id="prescription" name="prescription" rows="2" placeholder="Enter prescription details..."><?php echo htmlspecialchars($appointment['prescription']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Admission Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-hospital"></i> Admission Information</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_admitted" name="is_admitted" value="1" 
                                               <?php echo $appointment['is_admitted'] ? 'checked' : ''; ?> 
                                               onchange="document.getElementById('admissionDetails').style.display = this.checked ? 'block' : 'none';">
                                        <label class="form-check-label" for="is_admitted">
                                            Patient is Admitted
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="admissionDetails" style="display: <?php echo $appointment['is_admitted'] ? 'block' : 'none'; ?>;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="admission_date">Admission Date</label>
                                        <input type="datetime-local" class="form-control" id="admission_date" name="admission_date" 
                                               value="<?php echo $appointment['admission_date'] ? date('Y-m-d\TH:i', strtotime($appointment['admission_date'])) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="admission_notes">Admission Notes</label>
                                        <textarea class="form-control" id="admission_notes" name="admission_notes" rows="3" placeholder="Enter admission notes..."><?php echo htmlspecialchars($appointment['admission_notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Payment Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-uppercase text-muted mb-3"><i class="fas fa-credit-card"></i> Payment Information</h6>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label" for="payment_status">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status">
                                        <option value="">-- Not specified --</option>
                                        <option value="pending" <?php echo $appointment['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo $appointment['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="cancelled" <?php echo $appointment['payment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label" for="payment_method">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method">
                                        <option value="">-- Not specified --</option>
                                        <option value="cash" <?php echo $appointment['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="card" <?php echo $appointment['payment_method'] === 'card' ? 'selected' : ''; ?>>Card</option>
                                        <option value="check" <?php echo $appointment['payment_method'] === 'check' ? 'selected' : ''; ?>>Check</option>
                                        <option value="online" <?php echo $appointment['payment_method'] === 'online' ? 'selected' : ''; ?>>Online</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label" for="amount_paid">Amount Paid ($)</label>
                                    <input type="number" class="form-control" id="amount_paid" name="amount_paid" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($appointment['amount_paid']); ?>" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label" for="payment_date">Payment Date</label>
                                    <input type="datetime-local" class="form-control" id="payment_date" name="payment_date" 
                                           value="<?php echo $appointment['payment_date'] ? date('Y-m-d\TH:i', strtotime($appointment['payment_date'])) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="payment_notes">Payment Notes</label>
                                    <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2" placeholder="Additional payment details..."><?php echo htmlspecialchars($appointment['payment_notes']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 justify-content-between">
                            <div>
                                <a href="appointment_view.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                            </div>
                            <div>
                                <button type="reset" class="btn btn-outline-secondary"><i class="fas fa-redo"></i> Reset</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle admission details visibility on page load and checkbox change
    document.addEventListener('DOMContentLoaded', function() {
        const admissionCheckbox = document.getElementById('is_admitted');
        const admissionDetails = document.getElementById('admissionDetails');
        
        if (admissionCheckbox && admissionDetails) {
            admissionCheckbox.addEventListener('change', function() {
                admissionDetails.style.display = this.checked ? 'block' : 'none';
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
