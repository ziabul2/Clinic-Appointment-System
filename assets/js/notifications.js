(function(){
    // No automatic toasts: avoid showing server flash as floating popups.

    // --- Dropdown / persistent notifications widget ---
    function truncateText(s, len) {
        if (!s) return '';
        s = String(s);
        if (s.length <= len) return s;
        return s.slice(0, len - 1) + '…';
    }

    function renderDropdown(notifs) {
        var container = document.getElementById('notifDropdownContent');
        var badge = document.getElementById('notifCountBadge');
        if (!container) return;
        container.innerHTML = '';

        if (!Array.isArray(notifs) || notifs.length === 0) {
            container.innerHTML = '<div class="text-center text-muted small p-3">No notifications</div>';
            return;
        }

        notifs.slice(0, 50).forEach(function(n) {
            var isRead = parseInt(n.is_read, 10) === 1;
            var item = document.createElement('div');
            item.className = 'notification-item';
            if (!isRead) item.classList.add('unread');

            // Left: compact title + message (single-line each) with tooltip for full text
            var left = document.createElement('div');
            left.className = 'notif-left';
            var title = document.createElement('span');
            title.className = 'notif-title';
            title.textContent = truncateText(n.title || '', 70);
            if (n.title) title.title = n.title;

            var msg = document.createElement('span');
            msg.className = 'notif-msg';
            msg.textContent = truncateText(n.message || '', 100);
            if (n.message) msg.title = n.message;

            // small time stamp as muted tiny text (also truncated)
            var time = document.createElement('span');
            time.className = 'text-muted small d-block';
            time.style.fontSize = '0.7rem';
            time.textContent = n.created_at || '';

            left.appendChild(title);
            left.appendChild(msg);
            left.appendChild(time);

            // Right: compact action (mark read) or read badge
            var right = document.createElement('div');
            right.className = 'notif-action';
            if (!isRead) {
                var btn = document.createElement('button');
                btn.className = 'btn btn-primary btn-xs';
                btn.style.fontSize = '0.7rem';
                btn.style.padding = '3px 6px';
                btn.textContent = '✓';
                btn.title = 'Mark as read';
                btn.addEventListener('click', function(e){
                    e.preventDefault(); e.stopPropagation();
                    markRead([n.id]);
                });
                right.appendChild(btn);
            } else {
                var span = document.createElement('span'); span.className = 'badge bg-secondary'; span.textContent = 'Read'; right.appendChild(span);
            }

            // set tooltip on whole item to show title + message
            item.title = ((n.title||'') + (n.message ? ' - ' + n.message : '')).slice(0, 1000);

            item.appendChild(left);
            item.appendChild(right);
            container.appendChild(item);
        });

        // Display numeric unread badge in header
        if (badge) {
            var unread = (notifs || []).filter(n => parseInt(n.is_read, 10) === 0).length;
            if (unread > 0) {
                badge.textContent = unread;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function apiPath(action) {
        // Build a path relative to current location so it works from pages/ and root
        var base = 'process.php';
        if (window.location.pathname.indexOf('/pages/') !== -1) base = '../process.php';
        return base + '?action=' + action;
    }

    /* Single server-side flash toast: Now re-enabled and linked to script.js system */
    function showFlashToast(flash) {
        if (!flash) return;
        var type = flash.success ? 'success' : (flash.error ? 'error' : 'info');
        var message = flash.message || flash.success || flash.error || '';
        var title = flash.success ? 'Success' : (flash.error ? 'Error' : 'Notification');
        
        if (window.flashNotify) {
            window.flashNotify(type, title, message);
        } else {
            console.log('flashNotify not found, falling back to alert:', message);
        }
    }

    // expose to global so pages without header can reuse the toast
    try { window.showFlashToast = showFlashToast; } catch (e) { /* ignore if environment prevents */ }

    function fetchNotifications() {
        var path = apiPath('notifications_fetch');
        var container = document.getElementById('notifDropdownContent');
        if (container) container.innerHTML = '<div class="text-center text-muted small p-3">Loading...</div>';
        fetch(path, { credentials: 'same-origin' })
            .then(function(resp){ return resp.json(); })
            .then(function(data){
                var badge = document.getElementById('notifCountBadge');
                if (data && data.ok) {
                    renderDropdown(data.notifications || []);
                    // Update unread badge if provided
                    try {
                        var unread = 0;
                        if (typeof data.unread !== 'undefined') unread = parseInt(data.unread, 10) || 0;
                        else if (Array.isArray(data.notifications)) unread = (data.notifications||[]).filter(n=>parseInt(n.is_read,10)===0).length;
                        if (badge) {
                            if (unread > 0) { badge.textContent = unread; badge.style.display = 'inline-block'; }
                            else { badge.style.display = 'none'; }
                        }
                    } catch (e) { if (badge) badge.style.display = 'none'; }
                } else {
                    renderDropdown([]);
                    var badge = document.getElementById('notifCountBadge'); if (badge) badge.style.display = 'none';
                }
            }).catch(function(err){
                console.error('Fetch notifications error:', err);
                renderDropdown([]);
            });
    }

    function markRead(ids) {
        var path = apiPath('notifications_mark_read');
        var form = new FormData();
        form.append('csrf_token', window.__CSRF_TOKEN || '');
        ids.forEach(function(i){ form.append('ids[]', i); });
        fetch(path, { method: 'POST', credentials: 'same-origin', body: form })
            .then(function(r){ return r.json(); })
            .then(function(j){ if (j && j.ok) fetchNotifications(); })
            .catch(function(e){ console.error('Mark read error:', e); });
    }
    
    var lastNotifId = 0;
    var isPolling = false;

    function pollNotifications() {
        if (isPolling) return;
        isPolling = true;

        var path = apiPath('notifications_poll') + '&after_id=' + lastNotifId;
        fetch(path, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.ok && Array.isArray(data.notifications) && data.notifications.length > 0) {
                    data.notifications.forEach(function(n) {
                        // Update last ID
                        var nid = parseInt(n.id, 10);
                        if (nid > lastNotifId) lastNotifId = nid;

                        // Show toast
                        var type = 'info';
                        if (n.type === 'auth') type = 'warning';
                        if (n.type === 'queue') type = 'success';
                        if (n.type === 'status') type = 'info';
                        if (n.type === 'announcement') type = 'announcement';

                        if (window.flashNotify) {
                            window.flashNotify(type, n.title || 'Notification', n.message || '');
                        }
                    });
                    // Refresh unread count badge
                    updateUnreadCount();
                }
            })
            .catch(function(err) { console.error('Poll error:', err); })
            .finally(function() {
                isPolling = false;
            });
    }

    function updateUnreadCount() {
        var path = apiPath('notifications_unread_count');
        fetch(path, { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                var badge = document.getElementById('notifCountBadge');
                if (badge && data && data.ok) {
                    var cnt = parseInt(data.count, 10) || 0;
                    if (cnt > 0) {
                        badge.textContent = cnt;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
    }

    // Fetch notifications only when the dropdown is opened (user action)
    document.addEventListener('DOMContentLoaded', function(){
        // Initial fetch to get the current state and set lastNotifId
        var path = apiPath('notifications_fetch') + '&limit=1';
        fetch(path, { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data && data.ok && data.notifications && data.notifications.length > 0) {
                    lastNotifId = parseInt(data.notifications[0].id, 10);
                }
                updateUnreadCount();
            });

        // Start polling every 10 seconds
        setInterval(pollNotifications, 10000);

        // show single server-side flash toast if provided by server
        try { if (window.__FLASH && window.__FLASH.toast === true) { showFlashToast(window.__FLASH); } } catch (e) { console.error('flash-toast error', e); }
        var bell = document.getElementById('notificationBell');
        if (!bell) return;
        bell.addEventListener('click', function(e){
            // Fetch and render notifications on click (when user opens dropdown)
            try { fetchNotifications(); } catch(err) { console.error(err); }
        });

        // Also wire "Mark all read" to call API and refresh
        var markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e){
                e.preventDefault();
                // fetch then mark
                var path = apiPath('notifications_fetch');
                fetch(path, { credentials: 'same-origin' })
                    .then(function(r){ return r.json(); })
                    .then(function(d){ if (d && d.ok) { var ids = (d.notifications||[]).filter(n=>parseInt(n.is_read,10)===0).map(n=>n.id); if (ids.length) markRead(ids); else { var c = document.getElementById('notifDropdownContent'); if(c) c.innerHTML = '<div class="text-center text-muted small p-3">No notifications</div>'; } } })
                    .catch(function(){ var c = document.getElementById('notifDropdownContent'); if(c) c.innerHTML = '<div class="text-center text-muted small p-3">No notifications</div>'; });
            });
        }

        // Test Toast Button
        var testBtn = document.getElementById('testToastBtn');
        if (testBtn) {
            testBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showFlashToast({ success: 'This is a test notification! System is working properly.', toast: true });
            });
        }
            // Intercept forms with data-ajax="true" and submit via fetch, showing a single toast result
            try {
                var ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
                ajaxForms.forEach(function(f){
                    f.addEventListener('submit', function(ev){
                        ev.preventDefault();
                        var btn = f.querySelector('button[type="submit"]');
                        var original = btn ? btn.innerHTML : null;
                        var ajaxResult = { button_success: false, redirect: false };
                        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Sending...'; }
                        var action = f.getAttribute('action') || window.location.href;
                        var method = (f.getAttribute('method') || 'POST').toUpperCase();
                        var formData = new FormData(f);
                        fetch(action, {
                            method: method,
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
                            body: formData
                        }).then(function(resp){ return resp.text(); })
                          .then(function(text){
                               try { var j = JSON.parse(text); } catch(e) { j = null; }
                            if (j && j.ok) {
                                // If server requested a button success visual, apply it
                                if (j.button_success) {
                                    ajaxResult.button_success = true;
                                    if (btn) {
                                        btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                                        btn.classList.remove('btn-primary','btn-secondary','btn-outline-secondary');
                                        btn.classList.add('btn-success');
                                        btn.disabled = true; // keep as success state
                                    }
                                }
                                if (j.toast === true && window.showFlashToast) window.showFlashToast({ success: j.message || 'Success' });
                                if (j.redirect) { ajaxResult.redirect = true; setTimeout(function(){ window.location = j.redirect; }, 350); }
                            } else if (j && (j.error || j.message)) {
                                if (j.toast === true && window.showFlashToast) window.showFlashToast({ error: j.error || j.message });
                                // show temporary error state on button
                                if (btn) {
                                    var prev = btn.innerHTML;
                                    btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed';
                                    btn.classList.remove('btn-primary','btn-secondary','btn-outline-secondary');
                                    btn.classList.add('btn-danger');
                                    setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); }, 3000);
                                }
                            } else {
                                // Do not show generic toasts for non-explicit server responses
                            }
                          }).catch(function(err){ console.error('AJAX form error', err); if (window.showFlashToast) window.showFlashToast({ error: 'Request failed.' });
                              if (btn) {
                                  btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed';
                                  btn.classList.remove('btn-primary','btn-secondary','btn-outline-secondary');
                                  btn.classList.add('btn-danger');
                                  setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); }, 3000);
                              }
                          })
                          .finally(function(){ if (btn) { if (!ajaxResult.button_success && !ajaxResult.redirect) { setTimeout(function(){ btn.disabled = false; btn.innerHTML = original; }, 600); } } });
                    });
                });
        } catch (e) { console.error('bind-ajax-forms failed', e); }
    });
})();
