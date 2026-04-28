<?php
$page_title = "Edit Prescription";
require_once '../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$prescription_html = '';
$patient = null;

if ($appointment_id) {
    // Fetch appointment, patient and doctor details
    $q = $db->prepare('SELECT a.*, 
                       p.first_name AS pfn, p.last_name AS pln, p.email AS pemail, p.gender AS pgender, p.date_of_birth AS pdob,
                       d.first_name AS dfn, d.last_name AS dln, d.specialization AS dspec, d.phone AS dphone
                       FROM appointments a 
                       LEFT JOIN patients p ON a.patient_id = p.patient_id 
                       LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                       WHERE a.appointment_id = :id LIMIT 1');
    $q->bindParam(':id', $appointment_id);
    $q->execute();
    if ($q->rowCount()) {
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $patient = [
            'name' => trim(($row['pfn'] ?? '') . ' ' . ($row['pln'] ?? '')), 
            'email' => $row['pemail'] ?? '',
            'gender' => $row['pgender'] ?? '',
            'age' => $row['pdob'] ? date_diff(date_create($row['pdob']), date_create('today'))->y : ''
        ];
        $doctor = [
            'name' => trim(($row['dfn'] ?? '') . ' ' . ($row['dln'] ?? '')),
            'specialization' => $row['dspec'] ?? '',
            'phone' => $row['dphone'] ?? ''
        ];
    }

    // Try to fetch from database first
    $dbPresc = $db->prepare('SELECT content FROM prescriptions WHERE appointment_id = :aid ORDER BY created_at DESC LIMIT 1');
    $dbPresc->bindParam(':aid', $appointment_id);
    $dbPresc->execute();
    if ($dbPresc->rowCount()) {
        $prescription_html = $dbPresc->fetchColumn();
    } else {
        // Fallback: Look for existing prescription files
        $prescDir = __DIR__ . '/../prescriptions';
        if (is_dir($prescDir)) {
            $files = glob($prescDir . "/prescription_{$appointment_id}_*.html");
            if ($files) {
                usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
                $prescription_html = file_get_contents($files[0]);
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><i class="fas fa-file-prescription"></i> Edit Prescription</h1>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
    </div>

<div class="card">
    <div class="card-body">
        <?php if (!$appointment_id || !$patient): ?>
            <div class="alert alert-warning">No appointment selected or appointment not found.</div>
        <?php else: ?>
            <form method="post" action="../process.php?action=save_prescription">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <div class="mb-2">
                    <label class="form-label">Patient</label>
                    <input class="form-control" value="<?php echo htmlspecialchars($patient['name']); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prescription (HTML allowed)</label>
                    <textarea name="prescription_html" id="prescription_html" class="form-control" rows="12"><?php echo $prescription_html; ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Prescription</button>
                    <button type="button" id="loadTemplate" class="btn btn-outline-info">Load Template</button>
                    <button type="button" id="sendPresc" class="btn btn-success">Send to Patient</button>
                    <a href="prescription_print.php?appointment_id=<?php echo $appointment_id; ?>" target="_blank" class="btn btn-outline-secondary">Print</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('sendPresc')?.addEventListener('click', function(){
    var btn = this;
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Sending...';
    const form = document.querySelector('form');
    const data = new FormData(form);
    fetch('../process.php?action=save_prescription', {method: 'POST', body: data, headers: {'X-Requested-With':'XMLHttpRequest'}, credentials: 'same-origin'})
    .then(r=>r.json()).then(res=>{
        if (!res || !res.ok) {
            btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed';
            btn.classList.add('btn-danger');
            setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); }, 3000);
            return;
        }
        // now send
        const sendData = new FormData();
        sendData.append('appointment_id', <?php echo $appointment_id; ?>);
        sendData.append('csrf_token', '<?php echo csrf_token(); ?>');
        fetch('../process.php?action=send_prescription_mail', {method:'POST', body: sendData, headers:{'X-Requested-With':'XMLHttpRequest'}, credentials: 'same-origin'})
        .then(r=>r.json()).then(sres=>{
            if (sres && sres.ok) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Sent';
                btn.classList.add('btn-success');
                btn.disabled = true;
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed';
                btn.classList.add('btn-danger');
                setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); }, 3000);
            }
        }).catch(function(err){ console.error(err); btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed'; btn.classList.add('btn-danger'); setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); }, 3000); });
    }).catch(function(err){ console.error(err); btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed'; btn.classList.add('btn-danger'); setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); }, 3000); });
});

document.getElementById('loadTemplate')?.addEventListener('click', function() {
    if (confirm('This will replace your current content with the template. Continue?')) {
        const patientName = "<?php echo addslashes($patient['name'] ?? ''); ?>";
        const patientAge = "<?php echo addslashes($patient['age'] ?? ''); ?>";
        const patientGender = "<?php echo addslashes($patient['gender'] ?? ''); ?>";
        const doctorName = "<?php echo addslashes($doctor['name'] ?? ''); ?>";
        const doctorSpec = "<?php echo addslashes($doctor['specialization'] ?? ''); ?>";
        const doctorPhone = "<?php echo addslashes($doctor['phone'] ?? ''); ?>";
        const today = "<?php echo date('d-m-Y'); ?>";
        const clinicName = "<?php echo addslashes(SITE_NAME); ?>";

        let template = `
<div style="width: 100%; max-width: 800px; margin: auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; padding: 40px; min-height: 1000px; position: relative;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
        <div style="flex: 1;">
            <h2 style="margin: 0; color: #1a73e8;">Dr. ${doctorName}</h2>
            <p style="margin: 2px 0; font-size: 14px; font-weight: 600;">${doctorSpec}</p>
            <p style="margin: 2px 0; font-size: 12px; color: #555;">Phone: ${doctorPhone}</p>
        </div>
        <div style="flex: 1; text-align: right;">
            <h3 style="margin: 0; color: #333;">${clinicName}</h3>
            <p style="margin: 2px 0; font-size: 12px; color: #555;">Medical Services & Care</p>
        </div>
    </div>

    <!-- Patient Compact Info -->
    <div style="background: #f8f9fa; padding: 10px 15px; border-radius: 5px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 20px; font-size: 13px; border: 1px solid #eee;">
        <span><strong>Patient:</strong> ${patientName}</span>
        <span><strong>Age/Sex:</strong> ${patientAge} / ${patientGender}</span>
        <span><strong>Date:</strong> ${today}</span>
    </div>

    <div style="display: flex; min-height: 700px;">
        <!-- Left Sidebar (Vitals/Notes) -->
        <div style="width: 25%; border-right: 1px solid #eee; padding-right: 15px; font-size: 13px;">
            <div style="margin-bottom: 20px;">
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">VITALS</h4>
                <p style="margin: 8px 0;">BP: ___________</p>
                <p style="margin: 8px 0;">Pulse: _________</p>
                <p style="margin: 8px 0;">Weight: ________</p>
            </div>
            <div style="margin-bottom: 20px;">
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">SYMPTOMS</h4>
                <p style="color: #ccc;">(Write here...)</p>
                <br><br>
            </div>
            <div>
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px;">DIAGNOSIS</h4>
                <p style="color: #ccc;">(Write here...)</p>
            </div>
        </div>

        <!-- Right Main Area (Rx) -->
        <div style="width: 75%; padding-left: 25px;">
            <div style="font-size: 24px; font-weight: bold; margin-bottom: 15px; color: #1a73e8;">Rx</div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; text-align: left; font-size: 13px;">
                        <th style="padding: 10px 5px; width: 50%;">Medicine Name</th>
                        <th style="padding: 10px 5px; width: 25%;">Dosage</th>
                        <th style="padding: 10px 5px; width: 25%;">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    ${Array(7).fill(0).map(() => `
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 12px 5px;">&nbsp;</td>
                        <td style="padding: 12px 5px;">&nbsp;</td>
                        <td style="padding: 12px 5px;">&nbsp;</td>
                    </tr>`).join('')}
                </tbody>
            </table>

            <div style="margin-top: 30px; font-size: 14px;">
                <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">ADVICE / INSTRUCTIONS</h4>
                <ul style="padding-left: 20px; color: #555;">
                    <li></li>
                    <li></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="position: absolute; bottom: 40px; left: 40px; right: 40px; display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #eee; pt-20px; font-size: 12px; color: #888;">
        <div>
            <p style="margin: 0;">This is a computer-generated prescription.</p>
            <p style="margin: 0;">Powered by ${clinicName}</p>
        </div>
        <div style="text-align: center; width: 200px;">
            <div style="border-bottom: 1px solid #333; margin-bottom: 5px;"></div>
            <p style="margin: 0; color: #333; font-weight: bold;">Dr. ${doctorName}</p>
            <p style="margin: 0; font-size: 10px;">Signature & Seal</p>
        </div>
    </div>
</div>`;
        tinymce.get('prescription_html').setContent(template);
    }
});
</script>

<!-- TinyMCE WYSIWYG editor (CDN) -->
<script src="https://cdn.tiny.cloud/1/y3irs33ukd0vndlen466fxx7t6m1ug2lsjhge489ct6i1rkh/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#prescription_html',
    height: 500,
    menubar: true,
    plugins: [
        'advlist autolink lists link image charmap preview anchor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table paste imagetools help wordcount'
    ],
    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image media | removeformat | code | fullscreen | help',
    paste_data_images: true,
    automatic_uploads: true,
    images_upload_url: '/process.php?action=upload_image',
    images_upload_credentials: true,
    content_style: 'body { font-family:Arial,Helvetica,sans-serif; font-size:14px }'
});
</script>

<?php require_once '../includes/footer.php'; ?>
