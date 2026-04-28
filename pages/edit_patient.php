<?php
$page_title = "Edit Patient";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No patient specified.";
    redirect('patients.php');
}

$patient_id = sanitizeInput($_GET['id']);

try {
    // Fetch existing
    $query = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        redirect('patients.php');
    }

    if ($_POST) {
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $date_of_birth = sanitizeInput($_POST['date_of_birth']);
        $gender = sanitizeInput($_POST['gender']);
        $emergency_contact = sanitizeInput($_POST['emergency_contact']);
        $medical_history = sanitizeInput($_POST['medical_history']);

        $update = "UPDATE patients SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address, date_of_birth = :date_of_birth, gender = :gender, emergency_contact = :emergency_contact, medical_history = :medical_history WHERE patient_id = :patient_id";
        $u_stmt = $db->prepare($update);
        $u_stmt->bindParam(':first_name', $first_name);
        $u_stmt->bindParam(':last_name', $last_name);
        $u_stmt->bindParam(':email', $email);
        $u_stmt->bindParam(':phone', $phone);
        $u_stmt->bindParam(':address', $address);
        $u_stmt->bindParam(':date_of_birth', $date_of_birth);
        $u_stmt->bindParam(':gender', $gender);
        $u_stmt->bindParam(':emergency_contact', $emergency_contact);
        $u_stmt->bindParam(':medical_history', $medical_history);
        $u_stmt->bindParam(':patient_id', $patient_id);

        if ($u_stmt->execute()) {
            logAction('PATIENT_UPDATED', "Patient ID: $patient_id updated");
            $_SESSION['success'] = "Patient updated successfully.";
            redirect('patients.php');
        } else {
            $error = "Failed to update patient.";
        }
    }

} catch (PDOException $e) {
    logAction('EDIT_PATIENT_ERROR', $e->getMessage());
    $error = "Database error occurred.";
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        if (strpos($e->getMessage(), 'email') !== false) {
            $error = "A patient with this email address already exists.";
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Patient</h1>
    <div>
        <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Edit Patient</div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST" action="">
                    <?php echo csrf_input(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input class="form-control" name="first_name" required value="<?php echo htmlspecialchars($patient['first_name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input class="form-control" name="last_name" required value="<?php echo htmlspecialchars($patient['last_name']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" required value="<?php echo htmlspecialchars($patient['phone']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select</option>
                                <option value="Male" <?php echo $patient['gender']=='Male'?'selected':''; ?>>Male</option>
                                <option value="Female" <?php echo $patient['gender']=='Female'?'selected':''; ?>>Female</option>
                                <option value="Other" <?php echo $patient['gender']=='Other'?'selected':''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Emergency Contact</label>
                            <input class="form-control" name="emergency_contact" value="<?php echo htmlspecialchars($patient['emergency_contact']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medical History</label>
                        <textarea class="form-control" name="medical_history" rows="4"><?php echo htmlspecialchars($patient['medical_history']); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="patients.php" class="btn btn-secondary me-2">Cancel</a>
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
