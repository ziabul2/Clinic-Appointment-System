<?php
$page_title = "Edit Prescription";
require_once '../includes/header.php';

if (!isLoggedIn()) redirect('../index.php');

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$prescription_html = '';
$patient = null;

if ($appointment_id) {
    $q = $db->prepare('SELECT a.*, p.first_name AS pfn, p.last_name AS pln, p.email AS pemail FROM appointments a LEFT JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_id = :id LIMIT 1');
    $q->bindParam(':id', $appointment_id);
    $q->execute();
    if ($q->rowCount()) {
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $patient = ['name' => trim(($row['pfn'] ?? '') . ' ' . ($row['pln'] ?? '')), 'email' => $row['pemail'] ?? ''];
    }
    // Look for existing prescription files (latest)
    $prescDir = __DIR__ . '/../prescriptions';
    if (is_dir($prescDir)) {
        $files = glob($prescDir . "/prescription_{$appointment_id}_*.html");
        if ($files) {
            usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
            $prescription_html = file_get_contents($files[0]);
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
