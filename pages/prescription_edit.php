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

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <?php if (!$appointment_id || !$patient): ?>
                    <div class="alert alert-warning">No appointment selected or appointment not found.</div>
                <?php else: ?>
                    <form method="post" action="../process.php?action=save_prescription">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Patient: <span class="text-primary"><?php echo htmlspecialchars($patient['name']); ?></span></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prescription Editor</label>
                            <textarea name="prescription_html" id="prescription_html" class="form-control" rows="12"><?php echo $prescription_html; ?></textarea>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary px-4" type="submit">
                                <i class="fas fa-save me-2"></i>Save Prescription
                            </button>
                            <button type="button" id="loadTemplate" class="btn btn-outline-info">
                                <i class="fas fa-file-invoice me-2"></i>Load Template
                            </button>
                            <button type="button" id="sendPresc" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Send to Patient
                            </button>
                            <a href="prescription_print.php?appointment_id=<?php echo $appointment_id; ?>" target="_blank" class="btn btn-outline-secondary">
                                <i class="fas fa-print me-2"></i>Print
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Medicine Search Tool -->
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0"><i class="fas fa-search-plus me-2"></i>Medicine Directory</h5>
            </div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" id="medSearchInput" class="form-control" placeholder="Search brand or generic...">
                    <button class="btn btn-primary" id="medSearchBtn"><i class="fas fa-search"></i></button>
                </div>
                
                <div id="medSearchResults" class="list-group list-group-flush overflow-auto" style="max-height: 500px;">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-pills fa-2x mb-2"></i>
                        <p class="small mb-0">Search medicines to add them to the Rx table.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared text cleaner
    function cleanText(text) {
        if (!text) return 'N/A';
        const div = document.createElement('div');
        div.innerHTML = text;
        return div.textContent || div.innerText || '';
    }

    // --- Save and Send Logic ---
    const sendBtn = document.getElementById('sendPresc');
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            var btn = this;
            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Sending...';
            const form = document.querySelector('form');
            const data = new FormData(form);
            
            fetch('../process.php?action=save_prescription', {
                method: 'POST', 
                body: data, 
                headers: {'X-Requested-With':'XMLHttpRequest'}, 
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(res => {
                if (!res || !res.ok) throw new Error(res.message || 'Save failed');
                
                // Now trigger mail send
                return fetch('../process.php?action=send_prescription', {
                    method: 'POST', 
                    body: data, 
                    headers: {'X-Requested-With':'XMLHttpRequest'}, 
                    credentials: 'same-origin'
                });
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.ok) {
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Sent!';
                    btn.classList.replace('btn-success', 'btn-info');
                    if (window.flashNotify) flashNotify('success', 'Sent', 'Prescription emailed to patient.');
                } else {
                    throw new Error(res.message || 'Send failed');
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                btn.classList.add('btn-danger');
                setTimeout(() => { 
                    btn.disabled = false; 
                    btn.innerHTML = original; 
                    btn.classList.remove('btn-danger'); 
                }, 3000);
            });
        });
    }

    // --- Template Loading Logic ---
    const templateBtn = document.getElementById('loadTemplate');
    if (templateBtn) {
        templateBtn.addEventListener('click', function() {
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
<div style="width: 100%; max-width: 800px; margin: auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; padding: 20px; position: relative;">
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;">
        <div style="flex: 1;">
            <h2 style="margin: 0; color: #1a73e8; font-size: 20px;">Dr. ${doctorName}</h2>
            <p style="margin: 2px 0; font-size: 13px; font-weight: 600;">${doctorSpec}</p>
            <p style="margin: 2px 0; font-size: 11px; color: #555;">Phone: ${doctorPhone}</p>
        </div>
        <div style="flex: 1; text-align: right;">
            <h3 style="margin: 0; color: #333; font-size: 18px;">${clinicName}</h3>
            <p style="margin: 2px 0; font-size: 11px; color: #555;">Medical Services & Care</p>
        </div>
    </div>
    <div style="background: #f8f9fa; padding: 8px 12px; border-radius: 5px; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 15px; font-size: 12px; border: 1px solid #eee;">
        <span><strong>Patient:</strong> ${patientName}</span>
        <span><strong>Age/Sex:</strong> ${patientAge} / ${patientGender}</span>
        <span><strong>Date:</strong> ${today}</span>
    </div>
    <div style="display: flex; min-height: 600px;">
        <div style="width: 25%; border-right: 1px solid #eee; padding-right: 12px; font-size: 12px;">
            <div style="margin-bottom: 15px;">
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 3px; font-size: 13px; margin-bottom: 8px;">VITALS</h4>
                <p style="margin: 4px 0;">BP: ${"<?php echo $row['bp'] ?? ''; ?>"} ________</p>
                <p style="margin: 4px 0;">Pulse: ${"<?php echo $row['pulse'] ?? ''; ?>"} ____</p>
                <p style="margin: 4px 0;">Weight: ${"<?php echo $row['weight'] ?? ''; ?>"} ___</p>
                <p style="margin: 4px 0;">Temp: ${"<?php echo $row['temperature'] ?? ''; ?>"} °F</p>
            </div>
            <div style="margin-bottom: 15px;">
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 3px; font-size: 13px; margin-bottom: 8px;">SYMPTOMS</h4>
                <p style="color: #ccc; font-style: italic;">(Write here...)</p>
                <br>
            </div>
            <div>
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 3px; font-size: 13px; margin-bottom: 8px;">DIAGNOSIS</h4>
                <p style="color: #ccc; font-style: italic;">(Write here...)</p>
            </div>
        </div>
        <div style="width: 75%; padding-left: 20px;">
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px; color: #1a73e8;">Rx</div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; text-align: left; font-size: 12px;">
                        <th style="padding: 8px 5px; width: 50%;">Medicine Name</th>
                        <th style="padding: 8px 5px; width: 25%;">Dosage</th>
                        <th style="padding: 8px 5px; width: 25%;">Duration</th>
                    </tr>
                </thead>
                <tbody>
                    ${Array(6).fill(0).map(() => `
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 10px 5px;">&nbsp;</td>
                        <td style="padding: 10px 5px;">&nbsp;</td>
                        <td style="padding: 10px 5px;">&nbsp;</td>
                    </tr>`).join('')}
                </tbody>
            </table>
            <div style="margin-top: 20px; font-size: 12px;">
                <h4 style="border-bottom: 1px solid #eee; padding-bottom: 3px; margin-bottom: 8px;">ADVICE / INSTRUCTIONS</h4>
                <ul style="padding-left: 15px; color: #555;">
                    <li></li>
                    <li></li>
                </ul>
            </div>
        </div>
    </div>
    <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #eee; padding-top: 15px; font-size: 11px; color: #888;">
        <div>
            <p style="margin: 0;">This is a computer-generated prescription.</p>
            <p style="margin: 0;">Powered by ${clinicName}</p>
        </div>
        <div style="text-align: center; width: 180px;">
            <div style="border-bottom: 1px solid #333; margin-bottom: 3px;"></div>
            <p style="margin: 0; color: #333; font-weight: bold;">Dr. ${doctorName}</p>
            <p style="margin: 0; font-size: 9px;">Signature & Seal</p>
        </div>
    </div>
</div>`;
                const editor = tinymce.get('prescription_html');
                if (editor) editor.setContent(template);
            }
        });
    }

    // --- Medicine Search Module ---
    const medInput = document.getElementById('medSearchInput');
    const medBtn = document.getElementById('medSearchBtn');
    const medResults = document.getElementById('medSearchResults');

    function performMedSearch() {
        if (!medInput) return;
        const q = medInput.value.trim();
        if (q.length < 2) return;
        
        medResults.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div><p class="small mt-2">Searching...</p></div>';
        
        fetch(`../process.php?action=search_medicine&q=${encodeURIComponent(q)}`, {credentials: 'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (data && data.ok === false) throw new Error(data.message);
            if (!Array.isArray(data) || data.length === 0) {
                medResults.innerHTML = '<div class="text-center py-4 text-muted small">No medicines found.</div>';
                return;
            }
            
            let html = '';
            data.forEach(med => {
                const brand = cleanText(med.brand_name);
                const generic = cleanText(med.generic_name);
                const strength = cleanText(med.strength);
                const form = cleanText(med.dosage_form);
                const isRelated = med.type === 'related';

                html += `
                <div class="list-group-item p-2 border-start-0 border-end-0 ${isRelated ? 'bg-light' : ''}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="max-width: 80%;">
                            ${isRelated ? '<span class="badge bg-info text-white mb-1" style="font-size: 8px;">ALTERNATIVE</span>' : ''}
                            <h6 class="mb-0 fw-bold text-dark text-truncate small">${brand}</h6>
                            <p class="mb-0 text-muted text-truncate" style="font-size: 10px;">${generic}</p>
                            <div class="d-flex gap-2 mt-1" style="font-size: 9px;">
                                <span class="text-primary">${strength}</span>
                                <span class="text-secondary">${form}</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary p-1 px-2 insert-med" 
                                data-brand="${brand}" 
                                data-strength="${strength}" 
                                data-generic="${generic}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>`;
            });
            medResults.innerHTML = html;
            
            // Re-bind insert buttons
            medResults.querySelectorAll('.insert-med').forEach(btn => {
                btn.onclick = function() {
                    insertMedicine(this.dataset.brand, this.dataset.strength, this.dataset.generic);
                };
            });
        })
        .catch(err => {
            console.error(err);
            medResults.innerHTML = `<div class="text-center py-4 text-danger small">Error: ${err.message}</div>`;
        });
    }

    if (medBtn) medBtn.onclick = performMedSearch;
    if (medInput) medInput.onkeypress = e => { if (e.key === 'Enter') { e.preventDefault(); performMedSearch(); } };

    function insertMedicine(brand, strength, generic) {
        const editor = tinymce.get('prescription_html');
        if (!editor) return;
        
        let content = editor.getContent();
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');
        const rows = doc.querySelectorAll('tbody tr');
        let found = false;

        rows.forEach(row => {
            if (!found) {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 1 && (cells[0].innerHTML.trim() === '&nbsp;' || cells[0].textContent.trim() === '')) {
                    cells[0].innerHTML = `<strong>${brand}</strong> <span style="font-size: 11px;">(${strength})</span><br><small style="color: #666;">${generic}</small>`;
                    cells[1].innerHTML = `1+0+1`;
                    cells[2].innerHTML = `7 days`;
                    found = true;
                }
            }
        });

        if (!found) {
            const table = doc.querySelector('tbody');
            if (table) {
                const newRow = doc.createElement('tr');
                newRow.style.borderBottom = '1px solid #f9f9f9';
                newRow.innerHTML = `
                    <td style="padding: 10px 5px;"><strong>${brand}</strong> <span style="font-size: 11px;">(${strength})</span><br><small style="color: #666;">${generic}</small></td>
                    <td style="padding: 10px 5px;">1+0+1</td>
                    <td style="padding: 10px 5px;">7 days</td>
                `;
                table.appendChild(newRow);
                found = true;
            }
        }

        if (found) {
            editor.setContent(doc.body.innerHTML);
            if (window.flashNotify) flashNotify('success', 'Medicine Added', `${brand} inserted into Rx.`);
        }
    }
});
</script>

<!-- TinyMCE WYSIWYG editor (CDN) -->
<script src="https://cdn.tiny.cloud/1/y3irs33ukd0vndlen466fxx7t6m1ug2lsjhge489ct6i1rkh/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php require_once '../includes/footer.php'; ?>
