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

    // Mobile Actions Toggle
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('actions-toggle')) {
            const btn = e.target;
            const collapse = btn.nextElementSibling;
            if (collapse && collapse.classList.contains('actions-collapse')) {
                const isCollapsed = btn.classList.contains('collapsed');
                if (isCollapsed) {
                    btn.classList.remove('collapsed');
                    btn.setAttribute('aria-expanded', 'true');
                    collapse.classList.add('show');
                } else {
                    btn.classList.add('collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                    collapse.classList.remove('show');
                }
            }
        }
    });

    // Global AJAX form handler for data-ajax="true" forms
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.getAttribute('data-ajax') === 'true') {
            e.preventDefault();
            
            // Basic validation check
            if (form.classList.contains('needs-validation') && !form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const submitBtn = form.querySelector('[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }

            const formData = new FormData(form);
            const action = form.getAttribute('action') || window.location.href;

            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json();
                }
                throw new Error('Unexpected response format');
            })
            .then(data => {
                if (data.ok) {
                    if (window.flashNotify) {
                        flashNotify('success', 'Success', data.message || 'Operation completed successfully');
                    }
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    if (window.flashNotify) {
                        flashNotify('error', 'Error', data.error || data.message || 'An error occurred');
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                }
            })
            .catch(error => {
                console.error('Form submission error:', error);
                if (window.flashNotify) {
                    flashNotify('error', 'Connection Error', 'Failed to communicate with server');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        }
    });

    console.log('Clinic Appointment System JS loaded successfully');

    // Real-time AJAX Search Logic
    const realtimeSearchInputs = document.querySelectorAll('.realtime-search');
    realtimeSearchInputs.forEach(input => {
        const type = input.getAttribute('data-type');
        const searchForm = input.closest('form');

        // Prevent form submission when pressing Enter to stay on the same page
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                return false;
            });
        }

        input.addEventListener('input', debounce(function(e) {
            const query = e.target.value.trim();
            const targetBody = document.getElementById(type + 'TableBody');
            const pagination = document.getElementById('paginationContainer');
            
            if (!targetBody) return;

            // Determine base path for AJAX
            const currentPath = window.location.pathname;
            const ajaxPath = currentPath.includes('/pages/') ? '../ajax/search.php' : 'ajax/search.php';
            const url = `${ajaxPath}?type=${type}&query=${encodeURIComponent(query)}`;

            // Visual feedback: show searching state
            targetBody.style.opacity = '0.4';
            targetBody.style.transition = 'opacity 0.2s';

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Search request failed');
                return response.text();
            })
            .then(html => {
                targetBody.innerHTML = html;
                targetBody.style.opacity = '1';
                
                // Toggle pagination visibility
                if (pagination) {
                    pagination.style.display = query.length > 0 ? 'none' : '';
                }
            })
            .catch(err => {
                console.error('Real-time search error:', err);
                targetBody.style.opacity = '1';
            });
        }, 250)); // Fast response for "real-time" feel
    });
});

// Premium notification system (Toasts)
window.flashNotify = function(type, title, message) {
    const container = document.querySelector('.float-notification-container') || (function() {
        const c = document.createElement('div');
        c.className = 'float-notification-container';
        document.body.appendChild(c);
        return c;
    })();

    const toast = document.createElement('div');
    toast.className = `float-notification ${type}`;
    toast.innerHTML = `
        <div class="title">${title}</div>
        <div class="msg">${message}</div>
    `;

    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
};

// Global handler for send-mail-form (supporting real-time search results via delegation)
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.classList.contains('send-mail-form') || (form.action && form.action.includes('send_appointment_mail'))) {
        e.preventDefault();
        const btn = form.querySelector('.mail-btn') || form.querySelector('button[type="submit"]');
        if (!btn) return;
        
        if (!confirm('Send appointment email?')) return;

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
        
        const action = form.getAttribute('action');
        const formData = new FormData(form);
        
        fetch(action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.ok) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                if (window.flashNotify) {
                    flashNotify('success', 'Email Sent', data.message || 'Email sent successfully.');
                }
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-danger');
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-outline-secondary');
                }, 3000);
                if (data && data.error && window.flashNotify) {
                    flashNotify('error', 'Failed', data.error);
                }
            }
        })
        .catch(err => {
            console.error('Send mail error:', err);
            btn.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-danger');
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-secondary');
            }, 3000);
        });
    }
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

// =============================================================
// Footer & Navbar Dynamic Scroll Logic
// =============================================================
window.initFooterLogic = function() {
    const footer = document.querySelector('.footer');
    const navEl = document.querySelector('.navbar-transparent');
    if (!footer) return;

    let lastScrollTop = 0;
    const delta = 5;

    // Dynamic Navbar & Footer Padding Adjustment
    function adjustLayoutPadding() {
        const body = document.body;
        
        // Navbar padding
        if (navEl) {
            const navH = Math.ceil(navEl.getBoundingClientRect().height);
            body.style.setProperty('--navbar-height', navH + 'px');
            if (body.classList.contains('has-fixed-navbar')) {
                body.style.paddingTop = navH + 'px';
            }
        }
        
        // Footer padding: Ensure body has enough space for fixed footer
        const footerH = Math.ceil(footer.getBoundingClientRect().height);
        body.style.paddingBottom = (footerH + 40) + 'px';
    }

    let isManualOverride = false;

    // Manual Toggle Logic
    const toggleBtn = document.getElementById('footerToggleBtn');
    
    function applyVisibility(hide) {
        if (hide) {
            footer.classList.add('footer-hidden');
            footer.style.display = 'none';
        } else {
            footer.style.display = '';
            footer.classList.remove('footer-hidden');
        }
        
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.style.transform = hide ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }
        adjustLayoutPadding();
    }

    // Initial check from localStorage
    const shouldHide = localStorage.getItem('hide_footer') === 'true';
    if (shouldHide) {
        applyVisibility(true);
    }

    if (toggleBtn) {
        toggleBtn.onclick = function() {
            isManualOverride = true; // Disable auto-logic once user takes control
            const isHidden = footer.classList.contains('footer-hidden') || footer.style.display === 'none';
            applyVisibility(!isHidden);
            
            // Sync with localStorage if manual toggle used
            localStorage.setItem('hide_footer', !isHidden);
        };
    }

    // Listen for external visibility changes (e.g. from Profile page)
    document.addEventListener('clinic:footerVisibilityChanged', function(e) {
        isManualOverride = true;
        applyVisibility(e.detail.hide);
    });

    window.addEventListener('scroll', function() {
        if (isManualOverride) return; // Respect manual preference
        if (localStorage.getItem('hide_footer') === 'true') return; // Do nothing if hidden by setting
        
        const st = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Handle negative scroll (iOS bounce)
        const currentScroll = st < 0 ? 0 : st;

        // If scroll is less than delta, ignore
        if (Math.abs(lastScrollTop - currentScroll) <= delta) return;

        // At the very top, always show
        if (currentScroll <= 10) {
            footer.classList.remove('footer-hidden');
        } 
        // Always show at the very bottom of the page
        else if (currentScroll + windowHeight >= documentHeight - 50) {
            footer.classList.remove('footer-hidden');
            footer.classList.add('scrolled-bottom');
        }
        else {
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                // Scroll Down - Hide Footer
                footer.classList.add('footer-hidden');
            } else {
                // Scroll Up - Show Footer
                footer.classList.remove('footer-hidden');
            }
            footer.classList.remove('scrolled-bottom');
        }

        lastScrollTop = currentScroll;
    }, { passive: true });

    // Run initial adjustment
    adjustLayoutPadding();
    
    // Auto-hide after 2 seconds if the page is small (not enough scrollable content)
    setTimeout(() => {
        if (isManualOverride) return; // Respect manual preference
        
        const documentHeight = document.documentElement.scrollHeight;
        const windowHeight = window.innerHeight;
        const st = window.pageYOffset || document.documentElement.scrollTop;
        
        // If the page is small OR we are at the top and haven't moved
        if (documentHeight <= windowHeight + 150) {
            footer.classList.add('footer-hidden');
            // Sync icon if it exists
            if (toggleBtn) {
                const icon = toggleBtn.querySelector('i');
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        }
    }, 2000);

    // Show footer if mouse moves to bottom of screen (for recovery)
    document.addEventListener('mousemove', function(e) {
        if (window.innerHeight - e.clientY <= 50) {
            footer.classList.remove('footer-hidden');
        }
    });

    // Listen for resize and other layout changes
    window.addEventListener('resize', adjustLayoutPadding);
    if (navEl && window.MutationObserver) {
        const mo = new MutationObserver(() => adjustLayoutPadding());
        mo.observe(navEl, { attributes: true, attributeFilter: ['class'] });
    }
    document.addEventListener('clinic:themeChanged', adjustLayoutPadding);
};

// Auto-init logic if footer exists
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.footer')) window.initFooterLogic();
    });
} else {
    if (document.querySelector('.footer')) window.initFooterLogic();
}