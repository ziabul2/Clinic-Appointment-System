<?php
    // Footer content
?>
    </div> <!-- End container -->

    <!-- Modern Animated Footer -->
    <footer class="footer mt-auto bg-dark text-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="footer-brand d-flex align-items-center">
                        <div class="hospital-icon me-2">
                            <i class="fas fa-hospital-alt heartbeat"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 gradient-text"><?php echo SITE_NAME; ?></h5>
                            <small class="text-muted slide-in">Efficient Clinic Management System</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 text-md-end">
                    <div class="footer-info">
                        <div class="admin-badge float-md-end">
                            <span class="badge bg-primary glow">
                                <i class="fas fa-user-shield me-1"></i>Admin: Ziabul Islam
                            </span>
                        </div>
                        <div class="footer-meta mt-2">
                            <small class="text-muted fade-in">
                                <i class="fas fa-code me-1"></i>Version 2.1 
                                <span class="mx-2">|</span>
                                <i class="fas fa-copyright me-1"></i><?php echo date('Y'); ?> All Rights Reserved
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Wave Separator Removed for Ultra-Compact Look -->
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>

    <!-- Footer Interaction Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add scroll effect to footer
            const footer = document.querySelector('.footer');
            
            window.addEventListener('scroll', function() {
                const scrollPosition = window.scrollY;
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                
                // Add shadow when scrolled to bottom
                if (scrollPosition + windowHeight >= documentHeight - 100) {
                    footer.style.boxShadow = '0 -5px 30px rgba(13, 110, 253, 0.3)';
                } else {
                    footer.style.boxShadow = 'none';
                }
            });

            // Add click animation to admin badge
            const adminBadge = document.querySelector('.admin-badge .badge');
            if (adminBadge) {
                adminBadge.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            }

            // Add hover sound effect (optional)
            const hospitalIcon = document.querySelector('.hospital-icon');
            if (hospitalIcon) {
                hospitalIcon.addEventListener('mouseenter', function() {
                    // You can add a subtle sound effect here if desired
                    console.log('Hospital icon hovered!');
                });
            }

            // Dynamic year update (in case of long sessions)
            function updateYear() {
                const yearElements = document.querySelectorAll('[data-year]');
                const currentYear = new Date().getFullYear();
                yearElements.forEach(el => {
                    el.textContent = currentYear;
                });
            }
            
            // Update year every minute (in case of long sessions spanning midnight)
            setInterval(updateYear, 60000);
        });

        // Add loading animation
        window.addEventListener('load', function() {
            const footer = document.querySelector('.footer');
            footer.style.opacity = '0';
            footer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                footer.style.transition = 'all 0.5s ease-out';
                footer.style.opacity = '1';
                footer.style.transform = 'translateY(0)';
            }, 500);
        });

        // Mobile Actions Dropdown Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.actions-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const collapse = this.nextElementSibling;
                    if (collapse && collapse.classList.contains('actions-collapse')) {
                        // close other open panels first
                        document.querySelectorAll('.actions-collapse.show').forEach(c => {
                            if (c !== collapse) c.classList.remove('show');
                        });
                        collapse.classList.toggle('show');
                        this.classList.toggle('collapsed');
                    }
                });
            });

            // Close any open action panels when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.actions-toggle') && !e.target.closest('.actions-collapse')) {
                    document.querySelectorAll('.actions-collapse.show').forEach(c => {
                        c.classList.remove('show');
                        const t = c.previousElementSibling;
                        if (t && t.classList && t.classList.contains('actions-toggle')) t.classList.add('collapsed');
                    });
                }
            });
        });

        // Navbar scroll effect: add .scrolled when page is scrolled
        document.addEventListener('DOMContentLoaded', function() {
            const nav = document.querySelector('.navbar-transparent');
            if (!nav) return;
            function onScroll() {
                if (window.scrollY > 30) nav.classList.add('scrolled'); else nav.classList.remove('scrolled');
            }
            onScroll();
            window.addEventListener('scroll', onScroll);
        });

        // Theme toggle: initialize and persist theme (light/dark)
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            // Determine initial theme: localStorage -> prefers-color-scheme -> default light
            const stored = localStorage.getItem('clinic_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            let theme = stored || (prefersDark ? 'dark' : 'light');

            function applyTheme(t) {
                if (t === 'dark') {
                    body.classList.add('theme-dark');
                    body.classList.remove('theme-light');
                    if (themeToggle) themeToggle.checked = true;
                } else {
                    body.classList.remove('theme-dark');
                    body.classList.add('theme-light');
                    if (themeToggle) themeToggle.checked = false;
                }
                localStorage.setItem('clinic_theme', t);
                // notify listeners that theme changed (so dynamic measurements can update)
                try { document.dispatchEvent(new Event('clinic:themeChanged')); } catch (e) { /* ignore */ }
            }

            applyTheme(theme);

            if (themeToggle) {
                themeToggle.addEventListener('change', function() {
                    theme = this.checked ? 'dark' : 'light';
                    applyTheme(theme);
                });
            }
        });

        // When theme changes, persist preference to server (best-effort)
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            if (!themeToggle) return;
            themeToggle.addEventListener('change', function() {
                const theme = this.checked ? 'dark' : 'light';
                try {
                    fetch('../process.php?action=save_theme', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                        body: 'theme=' + encodeURIComponent(theme)
                    }).then(res => res.json()).then(data => {
                        // no-op for now; session updated on server
                    }).catch(()=>{/*silent*/});
                } catch(e) { /* ignore */ }
            });
        });

        // Intercept send-mail forms and print links to provide AJAX feedback and notifications
        document.addEventListener('DOMContentLoaded', function() {
                // helper to update notif badge (increment)
                function bumpNotifCount(delta) {
                    try {
                        const el = document.getElementById('notifCountBadge');
                        if (!el) return;
                        const cur = parseInt(el.textContent||'0',10) || 0;
                        const next = Math.max(0, cur + (delta||1));
                        el.textContent = next;
                        el.style.display = next>0? 'inline-block' : 'none';
                    } catch(e) {}
                }

                // helper to set notif badge to absolute value
                function setNotifBadge(count) {
                    try {
                        const el = document.getElementById('notifCountBadge');
                        if (!el) return;
                        const n = parseInt(count||0,10) || 0;
                        el.textContent = n;
                        el.style.display = n>0? 'inline-block' : 'none';
                    } catch(e) {}
                }

                // Fetch unread count from server and update badge
                function fetchUnreadCount() {
                    try {
                        fetch('../process.php?action=notifications_unread_count', { headers: {'X-Requested-With':'XMLHttpRequest'} })
                            .then(r => r.json())
                            .then(json => {
                                if (json && typeof json.unread_count !== 'undefined') setNotifBadge(json.unread_count);
                            }).catch(()=>{/*silent*/});
                    } catch(e) {}
                }

                // Fetch notifications list and render inside dropdown
                function fetchNotificationsAndRender() {
                    const container = document.getElementById('notifDropdownContent');
                    if (!container) return;
                    container.innerHTML = '<div class="text-center text-muted small">Loading...</div>';
                    fetch('../process.php?action=notifications_fetch', { headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(r => r.json())
                        .then(json => {
                            if (!json || !Array.isArray(json.notifications)) {
                                container.innerHTML = '<div class="text-center text-muted small">No notifications</div>';
                                return;
                            }
                            if (json.notifications.length === 0) {
                                container.innerHTML = '<div class="text-center text-muted small">No notifications</div>';
                                return;
                            }
                            container.innerHTML = '';
                            json.notifications.forEach(n => {
                                const item = document.createElement('div');
                                item.className = 'dropdown-item d-flex align-items-start';
                                item.style.cursor = 'default';
                                item.dataset.notifId = n.id;
                                const inner = `
                                    <div style="flex:1">
                                        <div class="fw-bold">${escapeHtml(n.title||'Notification')}</div>
                                        <div class="small text-muted">${escapeHtml(n.message||'')}</div>
                                        <div class="small text-muted mt-1">${escapeHtml(n.created_at||'')}</div>
                                    </div>
                                    <div class="ms-2">
                                        ${ n.is_read ? '<span class="badge bg-light text-dark">Read</span>' : '<button class="btn btn-sm btn-outline-primary mark-read-btn">Mark</button>' }
                                    </div>
                                `;
                                item.innerHTML = inner;
                                container.appendChild(item);
                            });
                            // attach mark handlers
                            container.querySelectorAll('.mark-read-btn').forEach(btn => {
                                btn.addEventListener('click', function(e){
                                    e.preventDefault(); e.stopPropagation();
                                    const p = this.closest('[data-notif-id]');
                                    if (!p) return;
                                    const id = p.dataset.notifId;
                                    markNotificationsRead([id], function(ok){ if (ok) { fetchNotificationsAndRender(); fetchUnreadCount(); } });
                                });
                            });
                        }).catch(err => {
                            container.innerHTML = '<div class="text-center text-danger small">Error loading notifications</div>';
                            console.error('Fetch notifications error', err);
                        });
                }

                // mark notifications read by ids (array). callback receives boolean ok
                function markNotificationsRead(ids, cb) {
                    try {
                        const body = new URLSearchParams();
                        ids.forEach(i => body.append('ids[]', i));
                        fetch('../process.php?action=notifications_mark_read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: body.toString()
                        }).then(r => r.json()).then(json => { if (cb) cb(json && json.ok); }).catch(()=>{ if (cb) cb(false); });
                    } catch(e) { if (cb) cb(false); }
                }

                // small HTML-escape helper for inserting text
                function escapeHtml(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

            // Attach submit handlers to send_appointment_mail forms
            document.querySelectorAll('form[action*="send_appointment_mail"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = form.querySelector('button[type=submit]');
                    const apId = form.querySelector('input[name="appointment_id"]') ? form.querySelector('input[name="appointment_id"]').value : null;
                    if (!apId) {
                        window.flashNotify('error','Error','Missing appointment id');
                        return;
                    }
                    // show sending notification
                    const sendingNotify = flashNotify('info','Sending','Sending appointment email...', 0);
                    // build form data
                    const fd = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    }).then(r => r.json()).then(json => {
                        // remove sending notification by showing an immediate success/error (notifications auto-hide)
                        if (json && json.ok) {
                            window.flashNotify('success','Mail Sent', json.message || 'Appointment email sent');
                            bumpNotifCount(1);
                        } else {
                            window.flashNotify('error','Mail Failed', (json && json.message) ? json.message : 'Failed to send appointment email');
                        }
                    }).catch(err => {
                        window.flashNotify('error','Mail Error','Network or server error while sending mail');
                        console.error('Send mail error', err);
                    });
                });
            });

            // Intercept print links to log print attempts and show notifications
            document.querySelectorAll('a[target="_blank"][href*="print_appointment.php"]').forEach(a => {
                a.addEventListener('click', function(e) {
                    // allow opening the print window, then also send a background log
                    const href = this.href;
                    // extract appointment id from href query
                    const url = new URL(href, window.location.origin);
                    const id = url.searchParams.get('id') || url.searchParams.get('appointment_id') || url.searchParams.get('appointment');
                    // show notification that print started
                    window.flashNotify('info','Printing','Opening print view...', 3000);
                    if (id) {
                        // send background log request
                        fetch('../process.php?action=log_print', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: 'appointment_id=' + encodeURIComponent(id)
                        }).then(r => r.json()).then(json => {
                            if (json && json.ok) {
                                window.flashNotify('success','Print','Print logged successfully', 3000);
                                bumpNotifCount(1);
                            } else {
                                window.flashNotify('error','Print Error', (json && json.message) ? json.message : 'Failed to log print');
                            }
                        }).catch(err => {
                            window.flashNotify('error','Print Error','Network error logging print');
                            console.error('Log print error', err);
                        });
                    }
                });

                // Initialize unread count on load
                fetchUnreadCount();

                // When bell dropdown is opened, fetch notifications
                const bell = document.getElementById('notificationBell');
                if (bell) {
                    bell.addEventListener('show.bs.dropdown', function(e){
                        fetchNotificationsAndRender();
                    });
                }

                // mark all read button
                const markAllBtn = document.getElementById('markAllReadBtn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        // fetch current list then mark all ids
                        fetch('../process.php?action=notifications_fetch', { headers: {'X-Requested-With':'XMLHttpRequest'} })
                            .then(r => r.json()).then(json => {
                                if (json && Array.isArray(json.notifications) && json.notifications.length>0) {
                                    const ids = json.notifications.filter(n=>!n.is_read).map(n=>n.id);
                                    if (ids.length===0) return;
                                    markNotificationsRead(ids, function(ok){ if (ok) { fetchNotificationsAndRender(); fetchUnreadCount(); } });
                                }
                            }).catch(()=>{});
                    });
                }
            });
        });

        // Dynamic navbar height adjustment to prevent content hiding under fixed navbar
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.body;
            const nav = document.querySelector('.navbar-transparent');

            function adjustNavbarPadding() {
                const navEl = document.querySelector('.navbar-transparent');
                if (!navEl) return;
                const h = Math.ceil(navEl.getBoundingClientRect().height);
                // set CSS variable and inline padding to be precise across pages
                document.body.style.setProperty('--navbar-height', h + 'px');
                if (body.classList.contains('has-fixed-navbar')) {
                    body.style.paddingTop = h + 'px';
                }
            }

            // run at least once on load
            adjustNavbarPadding();

            // update on resize to handle responsive navbar height changes
            window.addEventListener('resize', function() {
                adjustNavbarPadding();
            });

            // Observe class changes on navbar (to react to .scrolled toggling)
            const navEl = document.querySelector('.navbar-transparent');
            if (navEl && window.MutationObserver) {
                const mo = new MutationObserver(function() { adjustNavbarPadding(); });
                mo.observe(navEl, { attributes: true, attributeFilter: ['class'] });
            }

            // Also adjust after theme changes (theme toggle code calls applyTheme)
            document.addEventListener('clinic:themeChanged', adjustNavbarPadding);
        });
    </script>
</body>
</html>