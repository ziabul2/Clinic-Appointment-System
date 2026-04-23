<?php
$page_title = "Print Appointment";
require_once __DIR__ . '/../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No appointment specified.";
    redirect('appointments.php');
}

$appointment_id = sanitizeInput($_GET['id']);

try {
    // Fetch appointment details
    $query = "SELECT a.*, 
                     p.first_name as patient_first_name, 
                     p.last_name as patient_last_name,
                     p.phone as patient_phone,
                     p.gender as patient_gender,
                     p.emergency_contact,
                     p.date_of_birth,
                     d.first_name as doctor_first_name,
                     d.last_name as doctor_last_name,
                     d.specialization as doctor_specialization,
                     d.license_number,
                     d.consultation_fee
              FROM appointments a
              LEFT JOIN patients p ON a.patient_id = p.patient_id
              LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
              WHERE a.appointment_id = :appointment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->execute();
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $_SESSION['error'] = "Appointment not found.";
        redirect('appointments.php');
    }

    // Calculate patient age if DOB is available
    if (!empty($appointment['date_of_birth'])) {
        $dob = new DateTime($appointment['date_of_birth']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    } else {
        $age = 'N/A';
    }

} catch (PDOException $e) {
    logAction("PRINT_APPOINTMENT_ERROR", "Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load appointment data.";
    redirect('appointments.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation - <?php echo SITE_NAME; ?></title>
    <style>
        /* Base Styles */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        /* Hide header elements from print page (and allow UI toggle) */
        nav.navbar,
        .float-notification-container,
        .container.mt-4,
        script {
            /* keep visible by default on screen; print rules will hide them */
        }
        body { 
            padding: 20px; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container { 
            max-width: 800px; 
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Watermark Background */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(0,0,0,0.03);
            z-index: 0;
            white-space: nowrap;
            pointer-events: none;
        }

        /* UI hide class for interactive toggle (hides external chrome when set) */
        body.ui-hidden nav.navbar,
        body.ui-hidden .float-notification-container,
        body.ui-hidden .container.mt-4 {
            display: none !important;
        }

        /* Header Styles */
        .clinic-header { 
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .clinic-name { 
            font-size: 28px; 
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        
        .clinic-tagline {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .clinic-address { 
            font-size: 12px; 
            opacity: 0.8;
            line-height: 1.4;
        }

        /* Appointment Header */
        .appointment-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }
        
        .appointment-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .appointment-id {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .appointment-serial {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 18px;
            margin-top: 5px;
        }

        /* Content Sections */
        .content-section {
            padding: 25px;
            position: relative;
            z-index: 1;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: #3498db;
        }

        /* Grid Layout */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #3498db;
            transition: transform 0.2s ease;
        }
        
        .info-card:hover {
            transform: translateY(-2px);
        }
        
        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e9ecef;
        }
        
        .info-label {
            font-weight: 500;
            color: #2c3e50;
            font-size: 13px;
        }
        
        .info-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-align: right;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        /* Medical Info */
        .medical-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .medical-content {
            font-size: 13px;
            line-height: 1.6;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }

        /* Instructions */
        .instructions-section {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }
        
        .instructions-title {
            font-size: 14px;
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .instructions-list {
            list-style: none;
            font-size: 12px;
            color: #856404;
        }
        
        .instructions-list li {
            margin-bottom: 6px;
            padding-left: 20px;
            position: relative;
        }
        
        .instructions-list li:before {
            content: "•";
            color: #f39c12;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            line-height: 1.4;
        }
        
        .footer strong {
            color: #3498db;
        }

        /* Page Margin and Header/Footer Control */
        @page {
            margin: 0;
            size: A4;
            /* Disable browser headers and footers (URL, page number, date) */
            @top-left { content: none; }
            @top-center { content: none; }
            @top-right { content: none; }
            @bottom-left { content: none; }
            @bottom-center { content: none; }
            @bottom-right { content: none; }
        }

        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            /* nav.navbar { display: none !important; }
            .float-notification-container { display: none !important; }
            .container.mt-4 { display: none !important; } */
            html, body { 
                margin: 0 !important; 
                padding: 0 !important; 
                background: white !important;
                font-size: 12px;
                width: 100%;
                height: auto;
                page-break-after: avoid;
                position: absolute;
                top: 0;
                left: 0;
            }
            .container { 
                width: 100% !important; 
                max-width: none !important;
                margin: 0 !important;
                padding: 5px !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                top: 0;
                position: relative;
            }
            .watermark {
                display: none;
                font-size: 80px;
            }
            /* Ensure header/navbar and notification UI are hidden when printing */
            nav.navbar,
            .float-notification-container,
            .container.mt-4,
            script {
                display: none !important;
            }
            .clinic-header {
                padding: 10px 5px !important;
                page-break-inside: avoid;
                margin: 0 !important;
            }
            .appointment-header {
                page-break-inside: avoid;
                margin: 0 !important;
                padding: 10px 5px !important;
            }
            .content-section {
                padding: 10px !important;
                page-break-inside: avoid;
                margin: 0 !important;
            }
            .info-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }
        }

        /* Button Styles */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .btn-outline-primary {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }
        
        .btn-outline-primary:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Watermark -->
        <div class="watermark">CONFIRMED</div>

        <!-- Action Buttons -->
        <div class="no-print action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Slip
            </button>
            <button id="toggleUiBtn" class="btn btn-outline-primary">
                <i class="fas fa-minus-square"></i> Hide UI
            </button>
            <a href="appointments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Appointments
            </a>
            <a href="add_appointment.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Appointment
            </a>

            <!-- Send email form (AJAX: no confirmation, direct send with loading spinner and checkmark) -->
            <form id="sendMailForm" method="POST" action="../process.php?action=send_appointment_mail" style="display:contents">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment_id); ?>">
                <button type="submit" id="mailBtn" class="btn btn-info">
                    <i class="fas fa-envelope"></i> Send Email
                </button>
            </form>
        </div>

        <!-- Clinic Header -->
        <div class="clinic-header">
            <div class="clinic-name">HEALTHCARE MEDICAL CLINIC</div>
            <div class="clinic-tagline">Quality Healthcare You Can Trust</div>
            <div class="clinic-address">
                123 Medical Road, Dhaka 1207 | Phone: +880 13120-63209<br>
            </div>
        </div>

        <!-- Appointment Header -->
        <div class="appointment-header">
            <div class="appointment-title">APPOINTMENT CONFIRMATION</div>
            <div class="appointment-id">Reference: #<?php echo $appointment['appointment_id']; ?></div>
            <?php if (!empty($appointment['appointment_serial'])): ?>
                <div class="appointment-serial">
                    Serial: <?php echo htmlspecialchars(sprintf('%03d', $appointment['appointment_serial'])); ?>
                </div>
            <?php endif; ?>
            <div style="margin-top: 10px;">
                <span class="status-badge status-confirmed">
                    <i class="fas fa-check-circle"></i> CONFIRMED
                </span>
            </div>
        </div>

        <div class="content-section">
            <!-- Patient & Doctor Information -->
            <div class="section-title">
                <i class="fas fa-user-injured"></i>
                PATIENT & DOCTOR INFORMATION
            </div>
            
            <div class="info-grid">
                <!-- Patient Information Card -->
                <div class="info-card">
                    <div class="card-title">Patient Details</div>
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['patient_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender & Age:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['patient_gender'] ?? 'N/A'); ?> / <?php echo $age; ?></span>
                    </div>
                    <?php if (!empty($appointment['emergency_contact'])): ?>
                    <?php endif; ?>
                </div>

                <!-- Doctor Information Card -->
                <div class="info-card">
                    <div class="card-title">Doctor Details</div>
                    <div class="info-row">
                        <span class="info-label">Doctor:</span>
                        <span class="info-value">Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Specialization:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">License No:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['license_number']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Appointment Details -->
            <div class="section-title">
                <i class="fas fa-calendar-alt"></i>
                APPOINTMENT DETAILS
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Appointment Date:</span>
                        <span class="info-value"><?php echo date('F j, Y (l)', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Appointment Time:</span>
                        <span class="info-value"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Consultation Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars(ucfirst($appointment['consultation_type'])); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Consultation Fee:</span>
                        <span class="info-value">৳<?php echo number_format($appointment['consultation_fee'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Room No:</span>
                        <span class="info-value">Consultation Room 2</span>
                    </div>
                </div>
            </div>

            
            <!-- Important Instructions -->
            <div class="instructions-section">
                <div class="instructions-title">
                    <i class="fas fa-exclamation-circle"></i>
                    IMPORTANT INSTRUCTIONS FOR PATIENT
                </div>
                <ul class="instructions-list">
                    <li>Bring all relevant medical reports, prescriptions, and insurance documents</li>
                    <li>Emergency contact: +880 13120-63209 (Available 24/7)</li>
                    <li>Late arrivals may result in rescheduling of your appointment</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <!-- <div class="footer">
            <strong>COMPUTER GENERATED SLIP - NO SIGNATURE REQUIRED</strong><br>
            Generated on: <?php echo date('F j, Y \a\t h:i A'); ?> | 
            Clinic Hours: 9:00 AM - 6:00 PM (Saturday - Thursday) | 
            Closed on Fridays and Public Holidays<br>
            <em>Thank you for choosing Healthcare Medical Clinic. Your health is our priority.</em>
        </div> -->
    </div>

    <script>
        // Auto-print when page loads (optional)
        window.onload = function() {
            // Uncomment the line below to auto-print when page loads
            // window.print();
            
            // Add some interactive effects
            const cards = document.querySelectorAll('.info-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        };

        // Close window after printing (optional)
        window.onafterprint = function() {
            // setTimeout(function() {
            //     window.close();
            // }, 1000);
        };

        // Toggle UI chrome (nav/notifications) for cleaner printing or screenshots
        (function(){
            var btn = document.getElementById('toggleUiBtn');
            if (!btn) return;
            function setHidden(hidden) {
                if (hidden) document.body.classList.add('ui-hidden');
                else document.body.classList.remove('ui-hidden');
                btn.innerHTML = hidden ? '<i class="fas fa-plus-square"></i> Show UI' : '<i class="fas fa-minus-square"></i> Hide UI';
            }

            var hidden = false;
            btn.addEventListener('click', function(e){ e.preventDefault(); hidden = !hidden; setHidden(hidden); });

            // Auto-hide UI when print dialog opens
            if (window.matchMedia) {
                window.addEventListener('beforeprint', function(){ setHidden(true); });
                window.addEventListener('afterprint', function(){ setHidden(false); });
            }
        })();
    </script>
    <!-- Notifications script (for toast) -->
    <script src="../assets/js/notifications.js"></script>
    <script>
        (function(){
            var form = document.getElementById('sendMailForm');
            if (!form) return;
            var btn = document.getElementById('mailBtn');
            form.addEventListener('submit', function(e){
                e.preventDefault();
                if (!btn) return;
                var originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Sending...';

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
                                // Success: show checkmark and display toast
                                btn.innerHTML = '<i class="fas fa-check-circle"></i> Email Sent';
                                btn.classList.remove('btn-info');
                                btn.classList.add('btn-success');
                                if (j.toast === true && window.showFlashToast) {
                                    window.showFlashToast({ success: j.message || 'Email sent successfully.' });
                                }
                                // Keep button disabled and showing checkmark
                            } else if (j && j.error) {
                                // Error: show error message (no toast for non-critical errors)
                                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Send Failed';
                                btn.classList.remove('btn-info');
                                btn.classList.add('btn-danger');
                                setTimeout(function(){ btn.disabled = false; btn.innerHTML = originalText; btn.classList.remove('btn-danger'); btn.classList.add('btn-info'); }, 3000);
                            } else {
                                // Unexpected response
                                btn.innerHTML = '<i class="fas fa-check-circle"></i> Email Sent';
                                btn.classList.remove('btn-info');
                                btn.classList.add('btn-success');
                            }
                        } catch (err) {
                            // Not JSON response
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> Email Sent';
                            btn.classList.remove('btn-info');
                            btn.classList.add('btn-success');
                        }
                    });
                }).catch(function(err){
                    console.error('Send mail error', err);
                    btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Send Failed';
                    btn.classList.remove('btn-info');
                    btn.classList.add('btn-danger');
                    setTimeout(function(){ btn.disabled = false; btn.innerHTML = originalText; btn.classList.remove('btn-danger'); btn.classList.add('btn-info'); }, 3000);
                });
            });
        })();
    </script>
</body>
</html>