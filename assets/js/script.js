/**
 * Clinic Appointment System - JavaScript Functions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Form validation enhancement
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Dynamic time slot generation
    const timeSlotGenerator = {
        generateSlots: function(startTime, endTime, interval = 30) {
            const slots = [];
            let currentTime = this.parseTime(startTime);
            const end = this.parseTime(endTime);

            while (currentTime < end) {
                slots.push(this.formatTime(currentTime));
                currentTime = this.addMinutes(currentTime, interval);
            }

            return slots;
        },

        parseTime: function(timeString) {
            const [time, modifier] = timeString.split(' ');
            let [hours, minutes] = time.split(':').map(Number);

            if (modifier === 'PM' && hours < 12) hours += 12;
            if (modifier === 'AM' && hours === 12) hours = 0;

            return new Date(0, 0, 0, hours, minutes);
        },

        formatTime: function(date) {
            let hours = date.getHours();
            let minutes = date.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;

            return hours + ':' + minutes + ' ' + ampm;
        },

        addMinutes: function(date, minutes) {
            return new Date(date.getTime() + minutes * 60000);
        }
    };

    // Doctor availability check
    window.checkDoctorAvailability = function(doctorId, date) {
        if (!doctorId || !date) return;

        const timeSelect = document.getElementById('appointment_time');
        if (!timeSelect) return;

        // Show loading
        timeSelect.innerHTML = '<option value="">Checking availability...</option>';
        timeSelect.disabled = true;

        // Simulate API call (replace with actual AJAX call)
        setTimeout(() => {
            const startTime = '09:00 AM';
            const endTime = '05:00 PM';
            const availableSlots = timeSlotGenerator.generateSlots(startTime, endTime, 30);

            timeSelect.innerHTML = '';
            availableSlots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot;
                option.textContent = slot;
                timeSelect.appendChild(option);
            });

            timeSelect.disabled = false;
            
            if (availableSlots.length === 0) {
                timeSelect.innerHTML = '<option value="">No available slots</option>';
            }
        }, 1000);
    };

    // Print functionality
    window.printAppointment = function(appointmentId) {
        const printContent = document.getElementById('appointment-' + appointmentId);
        if (printContent) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Appointment Details</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        ${printContent.innerHTML}
                        <div class="no-print mt-3">
                            <button onclick="window.print()" class="btn btn-primary">Print</button>
                            <button onclick="window.close()" class="btn btn-secondary">Close</button>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    };

    // Search functionality
    window.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const table = e.target.closest('.card').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }, 300));
    });

    // Date validation: enforce future dates for scheduling inputs, but allow past dates for date-of-birth fields
    const allDateInputs = document.querySelectorAll('input[type="date"]');
    allDateInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (!this.value) { this.setCustomValidity(''); return; }
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Explicit rules:
            // - If the input name is exactly 'date_of_birth', disallow future dates.
            // - If the input name or id contains 'appointment' or its name is 'date' (filter), require future (no past).
            // - Otherwise, do not enforce future-date requirement.
            const name = (this.name || '').toLowerCase();
            const id = (this.id || '').toLowerCase();

            if (name === 'date_of_birth') {
                if (selectedDate > today) {
                    this.setCustomValidity('Date of birth cannot be in the future');
                } else {
                    this.setCustomValidity('');
                }
            } else if (name.includes('appointment') || id.includes('appointment') || name === 'date') {
                if (selectedDate < today) {
                    this.setCustomValidity('Please select a future date');
                } else {
                    this.setCustomValidity('');
                }
            } else {
                // no custom restriction
                this.setCustomValidity('');
            }
        });
    });

    // Date of birth fields should not allow future dates but must allow past dates
    const dobInputs = document.querySelectorAll('input[name="date_of_birth"]');
    dobInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (!this.value) { this.setCustomValidity(''); return; }
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0,0,0,0);
            if (selectedDate > today) {
                this.setCustomValidity('Date of birth cannot be in the future');
            } else {
                this.setCustomValidity('');
            }

            // Auto-calculate age from date of birth (if field present)
            const ageField = document.getElementById('patient_age');
            if (ageField && this.value) {
                const birthDate = new Date(this.value);
                const today2 = new Date();
                let age = today2.getFullYear() - birthDate.getFullYear();
                const monthDiff = today2.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today2.getDate() < birthDate.getDate())) {
                    age--;
                }
                ageField.value = age;
            }
        });
    });

    // Dynamic appointment view/status modal handling
    const viewButtons = document.querySelectorAll('.view-appointment-btn');
    const dynamicViewModalEl = document.getElementById('dynamicViewModal');
    const dynamicViewBody = document.getElementById('dynamicViewBody');
    const dynamicViewTitle = document.getElementById('dynamicViewTitle');
    const dynamicSendForm = document.getElementById('dynamicSendMailForm');
    const dynamicSendAppointmentId = document.getElementById('dynamic_send_appointment_id');
    const dynamicPrintBtn = document.getElementById('dynamicPrintBtn');

    if (viewButtons && dynamicViewModalEl) {
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                let data = null;
                try { var raw = this.getAttribute('data-apt') || ''; data = JSON.parse(atob(raw)); } catch(e) { data = null; }
                if (!data) return;

                // Populate modal content
                dynamicViewTitle.textContent = 'Appointment Details #' + (data.appointment_id || '');
                dynamicViewBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Patient Information</h6>
                            <p><strong>Name:</strong> ${escapeHtml((data.patient_first_name||'') + ' ' + (data.patient_last_name||''))}</p>
                            <p><strong>Contact:</strong> ${escapeHtml(data.patient_phone||'')} | ${escapeHtml(data.patient_email||'')}</p>
                            <p><strong>Gender:</strong> ${escapeHtml(data.patient_gender||'N/A')}</p>
                            <p><strong>DOB:</strong> ${data.patient_dob ? new Date(data.patient_dob).toLocaleDateString() : 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Appointment Details</h6>
                            <p><strong>Doctor:</strong> Dr. ${escapeHtml((data.doctor_first_name||'') + ' ' + (data.doctor_last_name||''))}</p>
                            <p><strong>Specialization:</strong> ${escapeHtml(data.doctor_specialization||'')}</p>
                            <p><strong>Date & Time:</strong> ${data.appointment_date ? new Date(data.appointment_date).toLocaleDateString() : ''} at ${data.appointment_time || ''}</p>
                            <p><strong>Consultation Type:</strong> ${escapeHtml(data.consultation_type || '')}</p>
                            <p><strong>Fee:</strong> $${Number(data.consultation_fee||0).toFixed(2)}</p>
                            ${data.symptoms ? `<p><strong>Symptoms:</strong><br>${escapeHtml(data.symptoms)}</p>` : ''}
                        </div>
                    </div>
                    ${data.notes ? `<div class="row mt-3"><div class="col-12"><h6>Additional Notes</h6><p>${escapeHtml(data.notes)}</p></div></div>` : ''}
                `;

                // Set send mail hidden id
                if (dynamicSendAppointmentId) dynamicSendAppointmentId.value = data.appointment_id;

                // Set print action to open a window with the modal content
                if (dynamicPrintBtn) {
                    dynamicPrintBtn.onclick = function() {
                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(`
                            <html><head><title>Appointment #${data.appointment_id}</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                            </head><body style="padding:20px">${dynamicViewBody.innerHTML}<div class="mt-3"><button onclick="window.print()" class="btn btn-primary">Print</button> <button onclick="window.close()" class="btn btn-secondary">Close</button></div></body></html>`);
                        printWindow.document.close();
                    };
                }

                // Show bootstrap modal
                const bsModal = bootstrap.Modal.getOrCreateInstance(dynamicViewModalEl);
                bsModal.show();
            });
        });
    }

    // Toggle update form area inside dynamic modal
    const toggleUpdateBtn = document.getElementById('toggleUpdateBtn');
    const updateFormArea = document.getElementById('updateFormArea');
    const cancelUpdateBtn = document.getElementById('cancelUpdateBtn');
    const dynamicUpdateAppointmentId = document.getElementById('dynamic_update_appointment_id');
    if (toggleUpdateBtn && updateFormArea) {
        toggleUpdateBtn.addEventListener('click', function() {
            if (updateFormArea.style.display === 'none' || updateFormArea.style.display === '') {
                updateFormArea.style.display = 'block';
                // set appointment id from send mail hidden input
                if (dynamicSendAppointmentId && dynamicUpdateAppointmentId) dynamicUpdateAppointmentId.value = dynamicSendAppointmentId.value;
            } else {
                updateFormArea.style.display = 'none';
            }
        });
    }
    if (cancelUpdateBtn && updateFormArea) {
        cancelUpdateBtn.addEventListener('click', function() { updateFormArea.style.display = 'none'; });
    }

    // Status update button handling
    const statusButtons = document.querySelectorAll('.status-update-btn');
    const dynamicStatusModalEl = document.getElementById('dynamicStatusModal');
    const statusAppointmentId = document.getElementById('status_appointment_id');
    const statusSelect = document.getElementById('status_select');
    const statusNotes = document.getElementById('status_notes');
    const dynamicStatusForm = document.getElementById('dynamicStatusForm');

    if (statusButtons && dynamicStatusModalEl) {
        statusButtons.forEach(sb => {
            sb.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const st = this.getAttribute('data-status') || 'scheduled';
                if (statusAppointmentId) statusAppointmentId.value = id;
                if (statusSelect) statusSelect.value = st;
                if (statusNotes) statusNotes.value = '';
                const bs = bootstrap.Modal.getOrCreateInstance(dynamicStatusModalEl);
                bs.show();
            });
        });

        // Ensure the dynamic status form posts to the same handler (the form already has name=update_status when submitted)
        dynamicStatusForm.addEventListener('submit', function() {
            // form will submit normally to the current page which will be processed by server
        });
    }

    // Small helper to escape HTML when injecting into modal
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s];
        });
    }

    console.log('Clinic Appointment System JS loaded successfully');
});

// AJAX handler for per-row send mail forms
document.addEventListener('DOMContentLoaded', function() {
    var mailForms = document.querySelectorAll('form[action*="send_appointment_mail"]');
    mailForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Send appointment email to patient and doctor?')) return;
            var fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(resp) {
                var ct = resp.headers.get('Content-Type') || '';
                if (ct.indexOf('application/json') !== -1) return resp.json();
                return resp.text().then(function(txt){ return { ok:false, message: 'Unexpected response from server' }; });
            }).then(function(json) {
                if (json && json.ok) {
                    if (window.flashNotify) flashNotify('success', 'Mail Sent', json.message || 'Mail sent successfully');
                } else {
                    if (window.flashNotify) flashNotify('error', 'Mail Failed', json.message || 'Failed to send mail');
                }
            }).catch(function(err) {
                if (window.flashNotify) flashNotify('error', 'Mail Error', 'Network or server error');
                console.error('Mail send error', err);
            });
        });
    });
});

// Utility functions
window.formatPhoneNumber = function(phone) {
    const cleaned = ('' + phone).replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
        return '(' + match[1] + ') ' + match[2] + '-' + match[3];
    }
    return phone;
};

window.formatDate = function(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};