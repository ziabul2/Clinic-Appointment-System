<?php
$page_title = "Appointments Management";
require_once '../includes/header.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

try {
    // Handle deletion
    if (isset($_GET['delete_id'])) {
        $appointment_id = sanitizeInput($_GET['delete_id']);
        $query = "DELETE FROM appointments WHERE appointment_id = :appointment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':appointment_id', $appointment_id);
        if ($stmt->execute()) {
            logAction("APPOINTMENT_DELETED", "Appointment ID: $appointment_id deleted");
            $_SESSION['success'] = "Appointment deleted successfully!";
        } else {
            throw new Exception("Failed to delete appointment");
        }
        redirect('appointments.php');
    }

    // Handle status update
    if (isset($_POST['update_status'])) {
        $appointment_id = sanitizeInput($_POST['appointment_id']);
        $status = sanitizeInput($_POST['status']);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        $query = "UPDATE appointments SET status = :status, notes = :notes WHERE appointment_id = :appointment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':appointment_id', $appointment_id);
        
        if ($stmt->execute()) {
            logAction("APPOINTMENT_STATUS_UPDATED", "Appointment ID: $appointment_id status changed to: $status");
            $_SESSION['success'] = "Appointment status updated successfully!";
        } else {
            throw new Exception("Failed to update appointment status");
        }
        redirect('appointments.php');
    }

    // Search and filtering
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $doctor_filter = isset($_GET['doctor']) ? sanitizeInput($_GET['doctor']) : '';
    $date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

    $where_clause = 'WHERE 1=1';
    $params = [];

    if (!empty($search)) {
        $where_clause .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search OR p.email LIKE :search OR p.phone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($status_filter)) {
        $where_clause .= " AND a.status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($doctor_filter)) {
        $where_clause .= " AND a.doctor_id = :doctor_id";
        $params[':doctor_id'] = $doctor_filter;
    }

    if (!empty($date_filter)) {
        $where_clause .= " AND a.appointment_date = :appointment_date";
        $params[':appointment_date'] = $date_filter;
    }

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total 
                   FROM appointments a
                   LEFT JOIN patients p ON a.patient_id = p.patient_id
                   LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                   $where_clause";
    
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $k => $v) $count_stmt->bindValue($k, $v);
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 15;
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;

    // Main query with joins
    $query = "SELECT a.*, 
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
              $where_clause 
              ORDER BY a.appointment_date DESC, a.appointment_time DESC 
              LIMIT :offset, :per_page";

    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get doctors for filter dropdown
    $doctors_query = "SELECT doctor_id, first_name, last_name, specialization FROM doctors ORDER BY first_name, last_name";
    $doctors_stmt = $db->prepare($doctors_query);
    $doctors_stmt->execute();
    $doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logAction("APPOINTMENTS_PAGE_ERROR", "Database error: " . $e->getMessage());
    $error = "Failed to load appointments data.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-check"></i> Appointments Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_appointment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Appointment
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Search and Filter Section -->
<div class="list-container mb-4">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filters & Search</h5>
    </div>
    <div class="list-body p-3">
        <form method="GET" action="" class="row g-2">
            <div class="col-md-4">
                <label class="form-label small">Search Patient</label>
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">All</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Doctor</label>
                <select class="form-select form-select-sm" name="doctor">
                    <option value="">All</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['doctor_id']; ?>" <?php echo $doctor_filter == $doc['doctor_id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars(substr($doc['first_name'], 0, 1) . '. ' . $doc['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date</label>
                <input type="date" class="form-control form-control-sm" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
        <div class="mt-2 d-flex gap-2 justify-content-between">
            <?php if (!empty($search) || !empty($status_filter) || !empty($doctor_filter) || !empty($date_filter)): ?>
                <a href="appointments.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear All
                </a>
            <?php endif; ?>
            <span class="text-muted small align-self-center">Total: <?php echo $total; ?> appointments</span>
        </div>
    </div>
</div>

<!-- Appointments List -->
<div class="list-container">
    <div class="list-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> Appointments List</h5>
    </div>
    <div class="list-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                <p>No appointments found. <a href="add_appointment.php">Schedule one now</a></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:6%;">ID</th>
                            <th style="width:28%;">Patient</th>
                            <th class="d-none d-sm-table-cell" style="width:18%;">Doctor</th>
                            <th class="col-datetime d-none d-sm-table-cell" style="width:14%;">Date & Time</th>
                            <th class="col-type d-none d-md-table-cell" style="width:10%;">Type</th>
                            <th class="col-status d-none d-md-table-cell" style="width:10%;">Status</th>
                            <th style="width:18%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
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
                                    <!-- Icon buttons visible on md+ -->
                                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                                        <a href="appointment_view.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="appointment_actions.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="print_appointment.php?id=<?php echo $apt['appointment_id']; ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Print"><i class="fas fa-print"></i></a>
                                        <form method="POST" action="../process.php?action=send_appointment_mail" style="display:inline;" class="send-mail-form">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary mail-btn" title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </form>
                                        <a href="appointments.php?delete_id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>

                                    <!-- Mobile: show a compact toggle and an actions panel (only on xs/sm) -->
                                    <button class="actions-toggle collapsed d-inline-block d-md-none" type="button" aria-expanded="false" aria-label="Toggle actions"></button>
                                    <div class="actions-collapse d-md-none">
                                        <div class="collapse-details mb-2">
                                            <div><strong><?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?></strong> <span class="muted">at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span></div>
                                            <div class="muted">Type: <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $apt['consultation_type']))); ?></div>
                                            <div class="muted">Status: <?php echo ucfirst($apt['status']); ?></div>
                                        </div>
                                        <a href="appointment_view.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-primary w-100 mb-1"><i class="fas fa-eye me-1"></i> View</a>
                                        <a href="appointment_actions.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-warning w-100 mb-1"><i class="fas fa-edit me-1"></i> Edit</a>
                                        <a href="print_appointment.php?id=<?php echo $apt['appointment_id']; ?>" target="_blank" class="btn btn-outline-info w-100 mb-1"><i class="fas fa-print me-1"></i> Print</a>
                                        <form method="POST" action="../process.php?action=send_appointment_mail" class="send-mail-form">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary w-100 mb-1 mail-btn"><i class="fas fa-envelope me-1"></i> Send Mail</button>
                                        </form>
                                        <a href="appointments.php?delete_id=<?php echo $apt['appointment_id']; ?>" class="btn btn-outline-danger w-100" onclick="return false;"><i class="fas fa-trash me-1"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="list-footer">
                    <nav aria-label="Appointments pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($doctor_filter) ? '&doctor=' . urlencode($doctor_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($doctor_filter) ? '&doctor=' . urlencode($doctor_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($doctor_filter) ? '&doctor=' . urlencode($doctor_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/notifications.js"></script>
<script>
    (function(){
        // Handle send mail button animation (loading spinner -> checkmark)
        var forms = document.querySelectorAll('.send-mail-form');
        forms.forEach(function(form){
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var btn = form.querySelector('.mail-btn');
                if (!btn) return;
                
                var originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
                
                var action = form.getAttribute('action');
                var formData = new FormData(form);
                
                fetch(action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(function(resp){
                    return resp.text().then(function(text){
                        try {
                            var j = JSON.parse(text);
                            if (j && j.ok) {
                                // Success: show checkmark
                                btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                                if (btn.classList.contains('btn-sm')) {
                                    // Desktop (small icon button)
                                    btn.classList.remove('btn-outline-secondary');
                                    btn.classList.add('btn-success');
                                } else {
                                    // Mobile (full-width button)
                                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Email Sent';
                                    btn.classList.remove('btn-outline-secondary');
                                    btn.classList.add('btn-success');
                                }
                                if (j.toast === true && window.showFlashToast) {
                                    window.showFlashToast({ success: j.message || 'Email sent successfully.' });
                                }
                                // Keep button disabled and showing checkmark
                            } else if (j && j.error) {
                                // Error: show error message
                                if (btn.classList.contains('btn-sm')) {
                                    btn.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                                } else {
                                    btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Send Failed';
                                }
                                btn.classList.remove('btn-outline-secondary');
                                btn.classList.add('btn-danger');
                                setTimeout(function(){ btn.disabled = false; btn.innerHTML = originalText; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); }, 3000);
                            } else {
                                // Unexpected response
                                btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                                btn.classList.remove('btn-outline-secondary');
                                btn.classList.add('btn-success');
                            }
                        } catch (err) {
                            // Not JSON response
                            btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                            btn.classList.remove('btn-outline-secondary');
                            btn.classList.add('btn-success');
                        }
                    });
                }).catch(function(err){
                    console.error('Send mail error', err);
                    if (btn.classList.contains('btn-sm')) {
                        btn.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                    } else {
                        btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Send Failed';
                    }
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-danger');
                    setTimeout(function(){ btn.disabled = false; btn.innerHTML = originalText; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); }, 3000);
                });
            });
        });
    })();
</script>

<?php require_once '../includes/footer.php'; ?>