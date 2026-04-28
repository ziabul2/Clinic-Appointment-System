<?php
$page_title = "Add New Doctor";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

// Handle form submission
if ($_POST) {
    try {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $specialization = sanitizeInput($_POST['specialization']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $license_number = sanitizeInput($_POST['license_number']);
        $available_days = isset($_POST['available_days']) ? implode(',', $_POST['available_days']) : '';
        $consultation_fee = sanitizeInput($_POST['consultation_fee']);
        $available_time_start = sanitizeInput($_POST['available_time_start']);
        $available_time_end = sanitizeInput($_POST['available_time_end']);

        // Check if email already exists
        if (!empty($email)) {
            $check_query = "SELECT doctor_id FROM doctors WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Email address already exists for another doctor.";
            }
        }

        // Check if license number already exists
        if (!empty($license_number)) {
            $check_query = "SELECT doctor_id FROM doctors WHERE license_number = :license_number";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':license_number', $license_number);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "License number already exists for another doctor.";
            }
        }

        if (!isset($error)) {
            $query = "INSERT INTO doctors (first_name, last_name, specialization, email, phone, license_number, available_days, consultation_fee, available_time_start, available_time_end) 
                     VALUES (:first_name, :last_name, :specialization, :email, :phone, :license_number, :available_days, :consultation_fee, :available_time_start, :available_time_end)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':license_number', $license_number);
            $stmt->bindParam(':available_days', $available_days);
            $stmt->bindParam(':consultation_fee', $consultation_fee);
            $stmt->bindParam(':available_time_start', $available_time_start);
            $stmt->bindParam(':available_time_end', $available_time_end);
            
            if ($stmt->execute()) {
                $doctor_id = $db->lastInsertId();
                logAction("DOCTOR_ADDED", "New doctor: Dr. $first_name $last_name (ID: $doctor_id)");
                $_SESSION['success'] = "Doctor added successfully!";
                redirect('doctors.php');
            } else {
                throw new Exception("Failed to add doctor");
            }
        }
    } catch (PDOException $e) {
        logAction("DOCTOR_ADD_ERROR", "Database error: " . $e->getMessage());
        $error = "System error occurred.";
        if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
            if (strpos($e->getMessage(), 'email') !== false) {
                $error = "Email address already exists for another doctor.";
            } else if (strpos($e->getMessage(), 'license_number') !== false) {
                $error = "License number already exists for another doctor.";
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-plus"></i> Add New Doctor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="doctors.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Doctors
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-md"></i> Doctor Information</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="../process.php?action=add_doctor" enctype="multipart/form-data" data-ajax="true">
                    <?php echo csrf_input(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required 
                                       value="<?php echo $_POST['first_name'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required
                                       value="<?php echo $_POST['last_name'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Create User Account for Doctor</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_user" name="create_user" value="1">
                                    <label class="form-check-label" for="create_user">Create login account and email set-password link</label>
                                </div>
                                <small class="text-muted">If checked, a user account with role <strong>Doctor</strong> will be created and an email with a set-password link will be sent.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialization" class="form-label">Specialization *</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" required
                                       value="<?php echo $_POST['specialization'] ?? ''; ?>"
                                       placeholder="e.g., Cardiology, Dermatology, Pediatrics">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">License Number *</label>
                                <input type="text" class="form-control" id="license_number" name="license_number" required
                                       value="<?php echo $_POST['license_number'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required
                                       value="<?php echo $_POST['phone'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="consultation_fee" class="form-label">Consultation Fee ($)</label>
                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" step="0.01" min="0"
                                       value="<?php echo $_POST['consultation_fee'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="available_time_start" class="form-label">Available From</label>
                                <input type="time" class="form-control" id="available_time_start" name="available_time_start"
                                       value="<?php echo $_POST['available_time_start'] ?? '09:00'; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="available_time_end" class="form-label">Available Until</label>
                                <input type="time" class="form-control" id="available_time_end" name="available_time_end"
                                       value="<?php echo $_POST['available_time_end'] ?? '17:00'; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Available Days *</label>
                        <div class="row">
                            <?php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $selected_days = isset($_POST['available_days']) ? $_POST['available_days'] : [];
                            foreach ($days as $day): ?>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="available_days[]" 
                                               value="<?php echo $day; ?>" 
                                               id="day_<?php echo strtolower($day); ?>"
                                               <?php echo in_array($day, $selected_days) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day_<?php echo strtolower($day); ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="doctors.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Doctor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>