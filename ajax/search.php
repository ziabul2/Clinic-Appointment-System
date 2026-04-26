<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$type = $_GET['type'] ?? '';
$query_str = $_GET['query'] ?? '';
$clean_query = trim($query_str);
$search = "%$clean_query%";

// Robust ID cleaning
$numeric_part = preg_replace('/[^0-9]/', '', $clean_query);

try {
    if ($type === 'patients') {
        $where = "WHERE (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.email LIKE :s3 OR p.phone LIKE :s4 OR CAST(p.patient_id AS CHAR) LIKE :s5)";
        $params = [
            ':s1' => $search,
            ':s2' => $search,
            ':s3' => $search,
            ':s4' => $search,
            ':s5' => $search
        ];
        
        $order_by = "p.admitted_at DESC";
        if (!empty($numeric_part)) {
            $where = "WHERE (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.email LIKE :s3 OR p.phone LIKE :s4 OR CAST(p.patient_id AS CHAR) LIKE :s5 OR p.patient_id = :id_exact1)";
            $params[':id_exact1'] = $numeric_part;
            $params[':id_exact2'] = $numeric_part;
            $order_by = "(p.patient_id = :id_exact2) DESC, p.admitted_at DESC";
        }
        
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.patient_id) as total_appointments,
                       (SELECT MAX(appointment_date) FROM appointments a WHERE a.patient_id = p.patient_id) as last_visit
                FROM patients p 
                $where 
                ORDER BY $order_by LIMIT 50";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            echo "<tr><td colspan='10' class='text-center py-4 text-muted'>No patients found matching \"".htmlspecialchars($clean_query)."\"</td></tr>";
        } else {
            foreach ($results as $patient) {
                ?>
                <tr>
                    <td><span class="badge bg-secondary">#<?php echo $patient['patient_id']; ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                        <?php if ($patient['gender']): ?>
                            <br><small class="text-muted"><?php echo $patient['gender']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <i class="far fa-clock me-1"></i>
                            <?php echo date('g:i A', strtotime($patient['admitted_at'])); ?>
                        </span>
                    </td>
                    <td>
                        <small>
                            <?php if ($patient['email']): ?>
                                <div><?php echo htmlspecialchars($patient['email']); ?></div>
                            <?php endif; ?>
                            <?php if ($patient['phone']): ?>
                                <div class="text-muted"><?php echo htmlspecialchars($patient['phone']); ?></div>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <small>
                            <?php if ($patient['date_of_birth']): ?>
                                <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <small>
                            <?php if ($patient['emergency_contact']): ?>
                                <?php echo htmlspecialchars($patient['emergency_contact']); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td><span class="badge bg-info"><?php echo $patient['total_appointments']; ?></span></td>
                    <td>
                        <small>
                            <?php if ($patient['last_visit']): ?>
                                <?php echo date('M j, Y', strtotime($patient['last_visit'])); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                            <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info" title="New Appointment"><i class="fas fa-calendar-plus"></i></a>
                            <a href="../process.php?action=archive_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-secondary" onclick="return confirm('Archive patient?');" title="Archive"><i class="fas fa-archive"></i></a>
                            <a href="patients.php?delete_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete patient?');" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                        <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                        <div class="actions-collapse d-md-none">
                            <div class="collapse-details mb-2">
                                <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                <div class="text-muted small"><?php echo htmlspecialchars($patient['phone'] ?? 'No phone'); ?></div>
                            </div>
                            <a href="view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-2"></i> View Profile</a>
                            <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-2"></i> Edit Patient</a>
                            <a href="add_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-info w-100 mb-1"><i class="fas fa-calendar-plus me-2"></i> New Appointment</a>
                            <a href="../process.php?action=archive_patient&id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-secondary w-100 mb-1" onclick="return confirm('Archive patient?');"><i class="fas fa-archive me-2"></i> Archive</a>
                            <a href="patients.php?delete_id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline-danger w-100" onclick="return confirm('Delete patient?');"><i class="fas fa-trash me-2"></i> Delete</a>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
    } elseif ($type === 'doctors') {
        $where = "WHERE (first_name LIKE :s1 OR last_name LIKE :s2 OR specialization LIKE :s3 OR email LIKE :s4 OR CAST(doctor_id AS CHAR) LIKE :s5)";
        $params = [':s1' => $search, ':s2' => $search, ':s3' => $search, ':s4' => $search, ':s5' => $search];
        $order_by = "d.created_at DESC";
        if (!empty($numeric_part)) {
            $where = "WHERE (first_name LIKE :s1 OR last_name LIKE :s2 OR specialization LIKE :s3 OR email LIKE :s4 OR CAST(doctor_id AS CHAR) LIKE :s5 OR doctor_id = :id_exact1)";
            $params[':id_exact1'] = $numeric_part;
            $params[':id_exact2'] = $numeric_part;
            $order_by = "(d.doctor_id = :id_exact2) DESC, d.created_at DESC";
        }
        
        $sql = "SELECT d.*, (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.doctor_id) as total_appointments 
                FROM doctors d $where ORDER BY $order_by LIMIT 50";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            echo "<tr><td colspan='10' class='text-center py-4 text-muted'>No doctors found matching \"".htmlspecialchars($clean_query)."\"</td></tr>";
        } else {
            foreach ($results as $doc) {
                ?>
                <tr>
                    <td><span class="badge bg-secondary">#<?php echo $doc['doctor_id']; ?></span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if (!empty($doc['profile_picture']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doc['profile_picture'])): ?>
                                <img src="../uploads/doctors/<?php echo htmlspecialchars($doc['profile_picture']); ?>" alt="Doctor" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;font-size:18px;"><i class="fas fa-user-md"></i></div>
                            <?php endif; ?>
                            <strong>Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></strong>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                    <td>
                        <small>
                            <div><?php echo htmlspecialchars($doc['email']); ?></div>
                            <div class="text-muted"><?php echo htmlspecialchars($doc['phone']); ?></div>
                        </small>
                    </td>
                    <td>
                        <small>
                            <div><strong><?php echo htmlspecialchars($doc['available_days']); ?></strong></div>
                            <div class="text-muted"><?php echo date('H:i', strtotime($doc['available_time_start'])) . ' - ' . date('H:i', strtotime($doc['available_time_end'])); ?></div>
                        </small>
                    </td>
                    <td><span class="badge bg-info"><?php echo $doc['total_appointments']; ?></span></td>
                    <td>
                        <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                            <a href="view_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <a href="edit_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="doctors.php?delete_id=<?php echo $doc['doctor_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                        <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                        <div class="actions-collapse d-md-none">
                            <a href="view_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-1"></i> View</a>
                            <a href="edit_doctor.php?id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-1"></i> Edit</a>
                            <a href="doctors.php?delete_id=<?php echo $doc['doctor_id']; ?>" class="btn btn-outline-danger w-100" onclick="return false;"><i class="fas fa-trash me-1"></i> Delete</a>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
    } elseif ($type === 'appointments') {
        $where = "WHERE (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.email LIKE :s3 OR p.phone LIKE :s4 OR CAST(a.appointment_id AS CHAR) LIKE :s5)";
        $params = [':s1' => $search, ':s2' => $search, ':s3' => $search, ':s4' => $search, ':s5' => $search];
        $order_by = "a.appointment_date DESC, a.appointment_time DESC";
        if (!empty($numeric_part)) {
            $where = "WHERE (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.email LIKE :s3 OR p.phone LIKE :s4 OR CAST(a.appointment_id AS CHAR) LIKE :s5 OR a.appointment_id = :id_exact1)";
            $params[':id_exact1'] = $numeric_part;
            $params[':id_exact2'] = $numeric_part;
            $order_by = "(a.appointment_id = :id_exact2) DESC, a.appointment_date DESC, a.appointment_time DESC";
        }
        
        $sql = "SELECT a.*, 
                         p.first_name as patient_first_name, 
                         p.last_name as patient_last_name,
                         p.email as patient_email,
                         p.phone as patient_phone,
                         p.date_of_birth as patient_dob,
                         p.gender as patient_gender,
                         d.first_name as doctor_first_name,
                         d.last_name as doctor_last_name,
                         d.specialization as doctor_specialization,
                         d.consultation_fee
                  FROM appointments a
                  LEFT JOIN patients p ON a.patient_id = p.patient_id
                  LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                  $where 
                  ORDER BY $order_by LIMIT 50";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) {
            echo "<tr><td colspan='10' class='text-center py-4 text-muted'>No appointments found matching \"".htmlspecialchars($clean_query)."\"</td></tr>";
        } else {
            foreach ($results as $apt) {
                ?>
                <tr>
                    <td><span class="badge bg-secondary">#<?php echo $apt['appointment_id']; ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($apt['patient_phone']); ?></small>
                    </td>
                    <td>
                        <strong>Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($apt['doctor_specialization'], 0, 15)); ?></small>
                    </td>
                    <td class="col-datetime">
                        <strong><?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?></strong>
                        <br><small><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
                    </td>
                    <td class="col-type">
                        <small><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $apt['consultation_type']))); ?></small>
                    </td>
                    <td class="col-status">
                        <span class="badge bg-<?php echo $apt['status']=='scheduled'?'primary':($apt['status']=='completed'?'success':($apt['status']=='cancelled'?'danger':'info')); ?>">
                            <?php echo ucfirst($apt['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                            <a href="appointment_view.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                            <a href="appointment_actions.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="print_appointment.php?id=<?php echo $apt['appointment_id']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Print"><i class="fas fa-print"></i></a>
                            <form method="POST" action="../process.php?action=send_appointment_mail" style="display:inline;" class="send-mail-form">
                                <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary mail-btn" title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                            </form>
                            <a href="appointments.php?delete_id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                        <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                        <div class="actions-collapse d-md-none">
                            <a href="appointment_view.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-1"></i> View</a>
                            <a href="appointment_actions.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-1"></i> Edit</a>
                            <a href="print_appointment.php?id=<?php echo $apt['appointment_id']; ?>" target="_blank" class="btn btn-outline-info w-100 mb-1"><i class="fas fa-print me-1"></i> Print</a>
                            <a href="appointments.php?delete_id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-danger w-100" onclick="return false;"><i class="fas fa-trash me-1"></i> Delete</a>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
    }
} catch (Exception $e) {
    echo "<tr><td colspan='10' class='text-center text-danger py-4'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
