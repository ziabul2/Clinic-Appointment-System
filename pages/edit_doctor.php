<?php
$page_title = "Edit Doctor";
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No doctor specified.";
    redirect('doctors.php');
}

$doctor_id = sanitizeInput($_GET['id']);

try {
    $query = "SELECT * FROM doctors WHERE doctor_id = :doctor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->execute();
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        $_SESSION['error'] = "Doctor not found.";
        redirect('doctors.php');
    }

    if ($_POST) {
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

        // Handle optional profile picture upload
        $new_profile = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/doctors/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_profile = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $upload_dir . $new_profile;
            if (@move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                // attempt to remove old file
                if (!empty($doctor['profile_picture']) && file_exists($upload_dir . $doctor['profile_picture'])) {
                    @unlink($upload_dir . $doctor['profile_picture']);
                }
            } else {
                $new_profile = null;
            }
        }
        // Before including profile_picture in the UPDATE, confirm the column exists in the DB
        try {
            $colChk = $db->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'profile_picture'");
            $colChk->execute();
            $rowChk = $colChk->fetch(PDO::FETCH_ASSOC);
            $hasProfileColumn = !empty($rowChk) && intval($rowChk['cnt']) > 0;
        } catch (Exception $e) {
            $hasProfileColumn = false;
        }

        $includeProfile = ($new_profile && $hasProfileColumn);

        $update = "UPDATE doctors SET first_name=:first_name,last_name=:last_name,specialization=:specialization,email=:email,phone=:phone,license_number=:license_number,available_days=:available_days,consultation_fee=:consultation_fee,available_time_start=:available_time_start,available_time_end=:available_time_end" . ($includeProfile ? ", profile_picture = :profile_picture" : "") . " WHERE doctor_id=:doctor_id";
        $u = $db->prepare($update);
        $u->bindParam(':first_name',$first_name);
        $u->bindParam(':last_name',$last_name);
        $u->bindParam(':specialization',$specialization);
        $u->bindParam(':email',$email);
        $u->bindParam(':phone',$phone);
        $u->bindParam(':license_number',$license_number);
        $u->bindParam(':available_days',$available_days);
        $u->bindParam(':consultation_fee',$consultation_fee);
        $u->bindParam(':available_time_start',$available_time_start);
        $u->bindParam(':available_time_end',$available_time_end);
        if ($includeProfile) $u->bindParam(':profile_picture', $new_profile);
        $u->bindParam(':doctor_id',$doctor_id);

        if ($u->execute()) {
            logAction('DOCTOR_UPDATED', "Doctor ID: $doctor_id updated");
            $_SESSION['success'] = "Doctor updated successfully.";
            redirect('doctors.php');
        } else {
            $error = "Failed to update doctor.";
        }
    }

} catch (PDOException $e) {
    logAction('EDIT_DOCTOR_ERROR', $e->getMessage());
    $error = "Database error.";
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Doctor</h1>
    <div>
        <a href="view_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Edit Doctor</div>
            <div class="card-body">
                <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php echo csrf_input(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input class="form-control" name="first_name" required value="<?php echo htmlspecialchars($doctor['first_name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input class="form-control" name="last_name" required value="<?php echo htmlspecialchars($doctor['last_name']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Specialization</label>
                            <input class="form-control" name="specialization" required value="<?php echo htmlspecialchars($doctor['specialization']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">License Number</label>
                            <input class="form-control" name="license_number" required value="<?php echo htmlspecialchars($doctor['license_number']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Available From</label>
                            <input type="time" class="form-control" name="available_time_start" value="<?php echo htmlspecialchars($doctor['available_time_start']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Available Until</label>
                            <input type="time" class="form-control" name="available_time_end" value="<?php echo htmlspecialchars($doctor['available_time_end']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Consultation Fee</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="consultation_fee" value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Days</label>
                        <div class="row">
                            <?php
                            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                            $selected = explode(',', $doctor['available_days']);
                            foreach ($days as $d): ?>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="available_days[]" value="<?php echo $d; ?>" <?php echo in_array($d,$selected)?'checked':''; ?>>
                                        <label class="form-check-label"><?php echo $d; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture (optional)</label>
                        <?php if (!empty($doctor['profile_picture']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doctor['profile_picture'])): ?>
                            <div class="mb-2"><img src="../uploads/doctors/<?php echo htmlspecialchars($doctor['profile_picture']); ?>" alt="Profile" style="width:96px;height:96px;object-fit:cover;border-radius:6px;"></div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="profile_picture" accept="image/*">
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="doctors.php" class="btn btn-secondary me-2">Cancel</a>
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
