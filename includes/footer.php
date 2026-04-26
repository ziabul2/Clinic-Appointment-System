<?php
    // Footer content
?>
    </div> <!-- End container -->

    <!-- Modern Animated Footer -->
    <footer class="footer bg-dark text-light">
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
        
        <!-- Scripts moved inside footer to prevent grid-item issues -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/script.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const footer = document.querySelector('.footer');
                window.addEventListener('scroll', function() {
                    const scrollPosition = window.scrollY;
                    const windowHeight = window.innerHeight;
                    const documentHeight = document.documentElement.scrollHeight;
                    if (scrollPosition + windowHeight >= documentHeight - 100) {
                        footer.style.boxShadow = '0 -5px 30px rgba(13, 110, 253, 0.3)';
                    } else {
                        footer.style.boxShadow = 'none';
                    }
                });
                const adminBadge = document.querySelector('.admin-badge .badge');
                if (adminBadge) {
                    adminBadge.addEventListener('click', function() {
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => { this.style.transform = ''; }, 150);
                    });
                }
                function updateYear() {
                    const yearElements = document.querySelectorAll('[data-year]');
                    const currentYear = new Date().getFullYear();
                    yearElements.forEach(el => { el.textContent = currentYear; });
                }
                setInterval(updateYear, 60000);
            });
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
            document.addEventListener('DOMContentLoaded', function() {
                const toggles = document.querySelectorAll('.actions-toggle');
                toggles.forEach(toggle => {
                    toggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const collapse = this.nextElementSibling;
                        if (collapse && collapse.classList.contains('actions-collapse')) {
                            document.querySelectorAll('.actions-collapse.show').forEach(c => {
                                if (c !== collapse) c.classList.remove('show');
                            });
                            collapse.classList.toggle('show');
                            this.classList.toggle('collapsed');
                        }
                    });
                });
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
            document.addEventListener('DOMContentLoaded', function() {
                const nav = document.querySelector('.navbar-transparent');
                if (!nav) return;
                function onScroll() {
                    if (window.scrollY > 30) nav.classList.add('scrolled'); else nav.classList.remove('scrolled');
                }
                onScroll();
                window.addEventListener('scroll', onScroll);
            });
            document.addEventListener('DOMContentLoaded', function() {
                const themeToggle = document.getElementById('themeToggle');
                const body = document.body;
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
                    try { document.dispatchEvent(new Event('clinic:themeChanged')); } catch (e) { }
                }
                applyTheme(theme);
                if (themeToggle) {
                    themeToggle.addEventListener('change', function() {
                        theme = this.checked ? 'dark' : 'light';
                        applyTheme(theme);
                    });
                }
            });
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
                        }).then(res => res.json()).catch(()=>{});
                    } catch(e) { }
                });
            });
            document.addEventListener('DOMContentLoaded', function() {
                function setNotifBadge(count) {
                    try {
                        const el = document.getElementById('notifCountBadge');
                        if (!el) return;
                        const n = parseInt(count||0,10) || 0;
                        el.textContent = n;
                        el.style.display = n>0? 'inline-block' : 'none';
                    } catch(e) {}
                }
                function fetchUnreadCount() {
                    try {
                        fetch('../process.php?action=notifications_unread_count', { headers: {'X-Requested-With':'XMLHttpRequest'} })
                            .then(r => r.json()).then(json => { if (json && typeof json.unread_count !== 'undefined') setNotifBadge(json.unread_count); }).catch(()=>{});
                    } catch(e) {}
                }
                fetchUnreadCount();
            });
            document.addEventListener('DOMContentLoaded', function() {
                const body = document.body;
                function adjustNavbarPadding() {
                    const navEl = document.querySelector('.navbar-transparent');
                    if (!navEl) return;
                    const h = Math.ceil(navEl.getBoundingClientRect().height);
                    document.body.style.setProperty('--navbar-height', h + 'px');
                    if (body.classList.contains('has-fixed-navbar')) {
                        body.style.paddingTop = h + 'px';
                    }
                }
                adjustNavbarPadding();
                window.addEventListener('resize', adjustNavbarPadding);
                const navEl = document.querySelector('.navbar-transparent');
                if (navEl && window.MutationObserver) {
                    const mo = new MutationObserver(() => adjustNavbarPadding());
                    mo.observe(navEl, { attributes: true, attributeFilter: ['class'] });
                }
                document.addEventListener('clinic:themeChanged', adjustNavbarPadding);
            });
        </script>
    </footer>
</body>
</html>