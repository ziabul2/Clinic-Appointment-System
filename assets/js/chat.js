document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('chatSidebar') && !document.getElementById('chatUserListView')) return;

    const chatAPI = '../chat_process.php';
    let pollInterval = null;
    let currentChatUserId = null;
    let lastMessageId = 0;
    let recipientOnline = false;

    // DOM Elements
    const userListView = document.getElementById('chatUserListView');
    const roomView = document.getElementById('chatRoomView');
    const settingsView = document.getElementById('chatSettingsView');
    const userList = document.getElementById('chatUserList');
    const pendingList = document.getElementById('chatPendingRequestsList');
    const pendingContainer = document.getElementById('chatPendingRequests');
    const searchInput = document.getElementById('chatSearchInput');
    
    // Chat Room Elements
    const chatBackBtn = document.getElementById('chatBackBtn');
    const chatMessages = document.getElementById('chatMessages');
    const messageForm = document.getElementById('chatMessageForm');
    const messageInput = document.getElementById('chatMessageInput');
    const activeUserIdInput = document.getElementById('chatActiveUserId');
    const activeUserName = document.getElementById('chatActiveUserName');
    const activeUserRole = document.getElementById('chatActiveUserRole');
    const activeUserInitials = document.getElementById('chatActiveUserInitials');
    const activeUserStatus = document.getElementById('chatActiveUserStatus');

    // File Elements
    const fileInput = document.getElementById('chatFileInput');
    const filePreview = document.getElementById('chatFilePreview');
    const filePreviewName = document.getElementById('chatFilePreviewName');
    const fileRemoveBtn = document.getElementById('chatFileRemoveBtn');

    // Contact Info Elements
    const topbarInfo = document.getElementById('chatTopbarInfo');
    const infoView = document.getElementById('chatInfoView');
    const infoCloseBtn = document.getElementById('chatInfoCloseBtn');
    const infoAvatar = document.getElementById('infoAvatar');
    const infoName = document.getElementById('infoName');
    const infoPhone = document.getElementById('infoPhone');
    const infoAbout = document.getElementById('infoAbout');
    const infoJoinDate = document.getElementById('infoJoinDate');

    // Settings
    const settingsBtn = document.getElementById('chatSettingsBtn');
    const settingsBackBtn = document.getElementById('chatSettingsBackBtn');
    const soundToggle = document.getElementById('chatSoundToggle');
    const titleToggle = document.getElementById('chatTitleToggle');

    // Global State
    window.chatUnreadCounts = {};

    // CSRF Token
    const csrfToken = window.__CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Title Notification
    let originalTitle = document.title;
    let titleInterval = null;
    let previousUnread = 0;
    let previousRequests = 0;

    function notifyTitle(msg) {
        if (!titleToggle.checked || titleInterval) return; // already notifying or disabled
        let toggle = false;
        titleInterval = setInterval(() => {
            document.title = toggle ? msg : originalTitle;
            toggle = !toggle;
        }, 1000);
    }
    
    function playSound() {
        if (!soundToggle.checked) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gainNode = ctx.createGain();
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(400, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0, ctx.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            
            osc.connect(gainNode);
            gainNode.connect(ctx.destination);
            
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
        } catch (e) {
            console.error('Audio play failed', e);
        }
    }

    function clearTitleNotification() {
        if (titleInterval) {
            clearInterval(titleInterval);
            titleInterval = null;
        }
        document.title = originalTitle;
    }

    window.addEventListener('focus', clearTitleNotification);
    document.addEventListener('click', clearTitleNotification);

    // Initialize
    function initChat() {
        fetchUsers();
        startPolling();
    }

    // Settings View Toggle
    if (settingsBtn) {
        settingsBtn.addEventListener('click', () => {
            if (!window.__IS_DEDICATED_MESSENGER) {
                userListView.classList.add('d-none');
                roomView.classList.add('d-none');
            }
            settingsView.classList.remove('d-none');
            settingsView.classList.add('d-flex');
        });
    }
    if (settingsBackBtn) {
        settingsBackBtn.addEventListener('click', () => {
            settingsView.classList.remove('d-flex');
            settingsView.classList.add('d-none');
            if (!window.__IS_DEDICATED_MESSENGER) {
                userListView.classList.remove('d-none');
            }
        });
    }

    // Toggle Sidebar listener
    const toggleBtn = document.getElementById('toggleChatBtn');
    const floatingBtn = document.getElementById('floatingChatBtn');
    
    function toggleChatSidebar(e) {
        if (e) e.preventDefault();
        const sidebarEl = document.getElementById('chatSidebar');
        if (sidebarEl) {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarEl);
            bsOffcanvas.toggle();
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleChatSidebar);
    if (floatingBtn) floatingBtn.addEventListener('click', toggleChatSidebar);

    // Contact Info View Toggle
    if (topbarInfo) {
        topbarInfo.addEventListener('click', () => {
            if (!currentChatUserId) return;
            if (infoView) infoView.classList.remove('d-none');
            
            // Loading state
            if (infoName) infoName.textContent = 'Loading...';
            if (infoPhone) infoPhone.textContent = '...';
            if (infoAbout) infoAbout.textContent = '...';
            if (infoJoinDate) infoJoinDate.textContent = '...';
            if (infoAvatar) infoAvatar.innerHTML = '';
            
            fetch(`${chatAPI}?action=fetch_user_profile&target_id=${currentChatUserId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok && data.profile) {
                        if (infoName) infoName.textContent = data.profile.name;
                        if (infoPhone) infoPhone.textContent = data.profile.phone;
                        if (infoAbout) infoAbout.textContent = data.profile.about;
                        if (infoJoinDate) infoJoinDate.textContent = data.profile.join_date;
                        
                        if (infoAvatar) {
                            if (data.profile.picture) {
                                infoAvatar.innerHTML = `<img src="${data.profile.picture}" alt="Profile" style="width:100%; height:100%; object-fit:cover;">`;
                            } else {
                                const initials = data.profile.name.replace('Dr. ', '').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                                infoAvatar.innerHTML = initials;
                            }
                        }
                    }
                })
                .catch(err => console.error('Error fetching profile:', err));
        });
    }

    if (infoCloseBtn) {
        infoCloseBtn.addEventListener('click', () => {
            if (infoView) infoView.classList.add('d-none');
        });
    }

    // Fetch User List
    function fetchUsers() {
        fetch(`${chatAPI}?action=fetch_users`)
            .then(res => res.json())
            .then(data => {
                if (data.ok) renderUsers(data.users);
            })
            .catch(err => console.error('Chat error:', err));
    }

    // Render User List
    function renderUsers(users) {
        userList.innerHTML = '';
        const filter = searchInput.value.toLowerCase();

        users.forEach(u => {
            if (filter && !u.name.toLowerCase().includes(filter)) return;

            const isOnline = u.online ? 'online' : 'offline';
            const initials = u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const unreadCount = window.chatUnreadCounts[u.id] || 0;
            const pictureHtml = u.picture 
                ? `<img src="${u.picture}" class="cui-avatar-circle" style="object-fit:cover;">`
                : `<div class="cui-avatar-circle">${initials}</div>`;
            const pictureHtmlSmall = u.picture
                ? `<img src="${u.picture}" class="avatar-sm rounded-circle border" style="width: 40px; height: 40px; object-fit:cover;">`
                : `<div class="avatar-sm bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold border" style="width: 40px; height: 40px;">${initials}</div>`;
            
            const item = document.createElement('div');
            
            if (window.__IS_DEDICATED_MESSENGER) {
                // Premium WhatsApp style
                item.className = `chat-user-item ${u.permission === 'accepted' ? 'chat-btn' : ''} ${currentChatUserId == u.id ? 'active' : ''}`;
                
                if (u.permission === 'accepted') {
                    item.setAttribute('data-id', u.id);
                    item.setAttribute('data-name', u.name);
                    item.setAttribute('data-role', u.role);
                    item.setAttribute('data-online', isOnline);
                    item.setAttribute('data-picture', u.picture || '');
                    
                    let badgeHtml = '';
                    if (unreadCount > 0) {
                        badgeHtml = `<div class="cui-badge">${unreadCount}</div>`;
                    }
                    
                    item.innerHTML = `
                        <div class="cui-avatar">
                            ${pictureHtml}
                            <span class="cui-status-dot ${isOnline}"></span>
                        </div>
                        <div class="cui-body">
                            <div class="cui-name">${u.name}</div>
                            <div class="cui-preview">${u.role}</div>
                        </div>
                        <div class="cui-meta">
                            ${badgeHtml}
                        </div>
                    `;
                } else if (u.permission === 'pending') {
                    let actionHtml = '';
                    if (u.is_requester) {
                        actionHtml = `<span class="badge bg-secondary text-light">Pending</span>`;
                    } else {
                        actionHtml = `
                            <button class="btn btn-sm btn-success rounded-circle me-1" onclick="event.stopPropagation(); respondPermission(${u.id}, 'accepted')" title="Accept" style="width:28px;height:28px;padding:0;z-index:2;"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger rounded-circle" onclick="event.stopPropagation(); respondPermission(${u.id}, 'rejected')" title="Reject" style="width:28px;height:28px;padding:0;z-index:2;"><i class="fas fa-times"></i></button>
                        `;
                    }
                    item.className = 'chat-user-item';
                    item.innerHTML = `
                        <div class="cui-avatar">
                            ${pictureHtml}
                            <span class="cui-status-dot ${isOnline}"></span>
                        </div>
                        <div class="cui-body">
                            <div class="cui-name">${u.name}</div>
                            <div class="cui-preview" style="color:var(--accent);">Wants to chat</div>
                        </div>
                        <div class="cui-meta d-flex flex-row align-items-center">
                            ${actionHtml}
                        </div>
                    `;
                } else {
                    item.className = 'chat-user-item';
                    item.innerHTML = `
                        <div class="cui-avatar">
                            ${pictureHtml}
                            <span class="cui-status-dot ${isOnline}"></span>
                        </div>
                        <div class="cui-body">
                            <div class="cui-name">${u.name}</div>
                            <div class="cui-preview">${u.role}</div>
                        </div>
                        <div class="cui-meta">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 ask-btn" data-id="${u.id}" style="z-index:2;" onclick="event.stopPropagation();">Ask</button>
                        </div>
                    `;
                }
            } else {
                // Standard sidebar style
                let actionHtml = '';
                if (u.permission === 'accepted') {
                    item.className = `chat-user-item chat-btn d-flex align-items-center justify-content-between p-2 ${currentChatUserId == u.id ? 'bg-light' : ''}`;
                    item.setAttribute('data-id', u.id);
                    item.setAttribute('data-name', u.name);
                    item.setAttribute('data-role', u.role);
                    item.setAttribute('data-online', isOnline);
                    item.setAttribute('data-picture', u.picture || '');
                    // Hide the separate chat button and make the whole row clickable
                    actionHtml = ``; 
                } else if (u.permission === 'pending') {
                    item.className = 'chat-user-item d-flex align-items-center justify-content-between p-2';
                    if (u.is_requester) {
                        actionHtml = `<span class="badge bg-secondary text-light">Pending</span>`;
                    } else {
                        actionHtml = `
                            <button class="btn btn-sm btn-success rounded-circle me-1" onclick="respondPermission(${u.id}, 'accepted')" title="Accept" style="width:28px;height:28px;padding:0;"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger rounded-circle" onclick="respondPermission(${u.id}, 'rejected')" title="Reject" style="width:28px;height:28px;padding:0;"><i class="fas fa-times"></i></button>
                        `;
                    }
                } else {
                    item.className = 'chat-user-item d-flex align-items-center justify-content-between p-2';
                    actionHtml = `<button class="btn btn-sm btn-outline-primary rounded-pill px-2 ask-btn" data-id="${u.id}">Ask to Chat</button>`;
                }

                item.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="position-relative me-3">
                            ${pictureHtmlSmall}
                            <span class="status-dot ${isOnline} position-absolute bottom-0 end-0"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold fs-6">${u.name}</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">${u.role}</small>
                        </div>
                    </div>
                    <div>${actionHtml}</div>
                `;
                
                if (unreadCount > 0 && u.permission === 'accepted') {
                    const unreadBadge = document.createElement('span');
                    unreadBadge.className = 'badge bg-danger rounded-pill ms-2 unread-badge shadow-sm';
                    unreadBadge.textContent = unreadCount;
                    item.querySelector('h6').appendChild(unreadBadge);
                }
            }

            userList.appendChild(item);
        });

        attachListListeners();
    }

    // Attach listeners to dynamically generated list buttons
    function attachListListeners() {
        document.querySelectorAll('.ask-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                requestPermission(id);
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
            });
        });

        document.querySelectorAll('.chat-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const role = btn.getAttribute('data-role');
                const isOnline = btn.getAttribute('data-online');
                const picture = btn.getAttribute('data-picture');
                openChatRoom(id, name, role, isOnline, picture);
            });
        });
    }

    // Request Permission
    function requestPermission(targetId) {
        const formData = new FormData();
        formData.append('action', 'request_permission');
        formData.append('target_id', targetId);
        formData.append('csrf_token', csrfToken);

        fetch(chatAPI, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) fetchUsers(); // refresh list
            });
    }

    // Respond Permission
    window.respondPermission = function(requesterId, status) {
        const formData = new FormData();
        formData.append('action', 'respond_permission');
        formData.append('requester_id', requesterId);
        formData.append('status', status);
        formData.append('csrf_token', csrfToken);

        fetch(chatAPI, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) fetchUsers(); // refresh list
            });
    }

    // Search filter
    searchInput.addEventListener('keyup', fetchUsers);

    // Navigation
    if (chatBackBtn) {
        chatBackBtn.addEventListener('click', () => {
            currentChatUserId = null;
            if (window.__IS_DEDICATED_MESSENGER) {
                roomView.classList.remove('active');
            } else {
                roomView.classList.add('d-none');
                userListView.classList.remove('d-none');
            }
            fetchUsers();
        });
    }

    // Open Chat Room
    window.openChatRoom = function(id, name, role, isOnline, picture) {
        currentChatUserId = id;
        lastMessageId = 0;
        
        activeUserIdInput.value = id;
        activeUserName.textContent = name;
        activeUserRole.textContent = role;
        
        if (picture) {
            activeUserInitials.innerHTML = `<img src="${picture}" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
        } else {
            activeUserInitials.textContent = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }
        
        if (isOnline === true || isOnline === 'online' || isOnline === 1) {
            activeUserStatus.classList.remove('offline');
        } else {
            activeUserStatus.classList.add('offline');
        }

        if (window.__IS_DEDICATED_MESSENGER) {
            roomView.classList.remove('d-none');
            roomView.classList.add('active'); // mobile view handling
            
            // hide placeholder if exists
            const placeholder = roomView.querySelector('.chat-placeholder');
            if (placeholder) placeholder.classList.add('d-none');
        } else {
            userListView.classList.add('d-none');
            roomView.classList.remove('d-none');
        }
        
        chatMessages.innerHTML = '<div class="text-center text-muted my-3 small">Loading messages...</div>';
        
        fetchMessages();
        // Refresh users list to clear the unread badge for this user
        fetchUsers();
    }

    // Fetch Messages
    function fetchMessages() {
        if (!currentChatUserId) return;

        fetch(`${chatAPI}?action=fetch_messages&with_user=${currentChatUserId}&last_id=${lastMessageId}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    recipientOnline = data.recipient_online || false;
                    // Sync real-time deletions for the other user
                    if (data.deleted_ids && data.deleted_ids.length > 0) {
                        data.deleted_ids.forEach(id => {
                            const bubble = document.querySelector(`.chat-bubble[data-msg-id="${id}"]`);
                            if (bubble && !bubble.querySelector('.fa-ban')) {
                                const timeSpan = bubble.querySelector('.chat-time span');
                                const timeTxt = timeSpan ? timeSpan.textContent : '';
                                bubble.innerHTML = `
                                    <span class="fst-italic" style="opacity: 0.75;"><i class="fas fa-ban me-1"></i>This message was deleted.</span>
                                    <div class="chat-time"><span>${timeTxt}</span></div>
                                `;
                            }
                        });
                    }

                    // Update existing sent messages' ticks if recipient status changed
                    const maxReadId = parseInt(data.max_read_id || 0);
                    
                    document.querySelectorAll('.chat-bubble.sent').forEach(bubble => {
                        const receipt = bubble.querySelector('.read-receipt');
                        if (!receipt) return;
                        
                        const msgId = parseInt(bubble.getAttribute('data-msg-id'));
                        
                        if (maxReadId > 0 && msgId <= maxReadId) {
                            // Upgrade to 3 ticks (Seen)
                            if (receipt.children.length < 3) {
                                receipt.classList.add('text-info');
                                receipt.innerHTML = '<i class="fas fa-check"></i><i class="fas fa-check"></i><i class="fas fa-check"></i>';
                            }
                        } else if (recipientOnline) {
                            // Upgrade to 2 ticks (Received)
                            if (receipt.children.length === 1) {
                                receipt.innerHTML = '<i class="fas fa-check"></i><i class="fas fa-check"></i>';
                            }
                        }
                    });

                    if (data.messages && data.messages.length > 0) {
                        if (lastMessageId === 0) chatMessages.innerHTML = ''; // Clear loading
                        
                        let needsScroll = (chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 20);

                        data.messages.forEach(m => {
                            appendMessage(m);
                            lastMessageId = Math.max(lastMessageId, parseInt(m.id));
                        });

                        // Scroll to bottom if we were already at bottom or just opened
                        if (needsScroll || lastMessageId === parseInt(data.messages[data.messages.length-1].id)) {
                            scrollToBottom();
                        }
                    } else if (lastMessageId === 0) {
                        chatMessages.innerHTML = '<div class="text-center text-muted my-5 small"><i class="fas fa-lock mb-2 fa-2x opacity-50"></i><br>Connection established.<br>Say hello!</div>';
                    }
                }
            });
    }

    // Append Message to UI
    function appendMessage(m) {
        const isMe = m.sender_id != currentChatUserId;
        const div = document.createElement('div');
        div.className = `chat-bubble ${isMe ? 'sent' : 'received'}`;
        div.setAttribute('data-msg-id', m.id);
        
        const time = new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        const basePath = chatAPI.replace('chat_process.php', '');
        let safePath = m.file_path ? basePath + m.file_path : null;
        let contentHtml = m.message || '';

        if (m.is_deleted == 1) {
            contentHtml = `<span class="fst-italic" style="opacity: 0.75;"><i class="fas fa-ban me-1"></i>This message was deleted.</span>`;
        } else if (safePath) {
            if (m.file_type === 'image') {
                contentHtml += `<div class="mt-2"><a href="${safePath}" target="_blank"><img src="${safePath}" class="chat-image-preview shadow-sm" alt="Image"></a></div>`;
            } else {
                contentHtml += `
                    <div class="chat-file-attachment">
                        <i class="fas fa-file-${m.file_type === 'pdf' ? 'pdf text-danger' : 'alt text-secondary'} fa-2x me-3"></i>
                        <div class="overflow-hidden">
                            <div class="text-truncate fw-bold small">${m.file_name}</div>
                            <a href="${safePath}" target="_blank" class="small fw-bold text-decoration-none">Download</a>
                        </div>
                    </div>
                `;
            }
        }

        let deleteBtn = '';
        if (isMe && m.is_deleted == 0) {
            deleteBtn = `<i class="fas fa-trash-alt delete-msg-btn ms-2" title="Delete" onclick="deleteMessage(${m.id})"></i>`;
        }
        
        let ticksHtml = '';
        if (isMe && m.is_deleted == 0) {
            if (parseInt(m.is_read) === 1) {
                // 3 Ticks for Seen
                ticksHtml = '<div class="read-receipt text-info"><i class="fas fa-check"></i><i class="fas fa-check"></i><i class="fas fa-check"></i></div>';
            } else if (recipientOnline) {
                // 2 Ticks for Received (Recipient Online)
                ticksHtml = '<div class="read-receipt"><i class="fas fa-check"></i><i class="fas fa-check"></i></div>';
            } else {
                // 1 Tick for Sent
                ticksHtml = '<div class="read-receipt"><i class="fas fa-check"></i></div>';
            }
        }
        
        div.innerHTML = `
            ${contentHtml}
            <div class="chat-time">
                <span>${time}</span>
                ${deleteBtn}
            </div>
            ${ticksHtml}
        `;
        chatMessages.appendChild(div);
    }

    window.deleteMessage = function(id) {
        if (!confirm('Delete this message for everyone?')) return;
        
        // Optimistic instant UI update
        const bubble = document.querySelector(`.chat-bubble[data-msg-id="${id}"]`);
        if (bubble) {
            const timeSpan = bubble.querySelector('.chat-time span');
            const timeTxt = timeSpan ? timeSpan.textContent : '';
            bubble.innerHTML = `
                <span class="fst-italic" style="opacity: 0.75;"><i class="fas fa-ban me-1"></i>This message was deleted.</span>
                <div class="chat-time"><span>${timeTxt}</span></div>
            `;
        }

        const formData = new FormData();
        formData.append('action', 'delete_message');
        formData.append('message_id', id);
        formData.append('csrf_token', csrfToken);

        fetch(chatAPI, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                // Background success
                if (!data.ok) console.error("Failed to delete message server-side.");
            });
    };

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // File Input Handlers
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            filePreviewName.textContent = fileInput.files[0].name;
            filePreview.classList.remove('d-none');
        } else {
            filePreview.classList.add('d-none');
        }
    });

    fileRemoveBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.add('d-none');
    });

    // Send Message
    messageForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const msg = messageInput.value.trim();
        const receiver = activeUserIdInput.value;
        const file = fileInput.files.length > 0 ? fileInput.files[0] : null;
        
        if ((!msg && !file) || !receiver) return;

        messageInput.value = '';
        fileInput.value = '';
        filePreview.classList.add('d-none');
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('receiver_id', receiver);
        formData.append('message', msg);
        if (file) formData.append('chat_file', file);
        formData.append('csrf_token', csrfToken);

        fetch(chatAPI, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    fetchMessages(); // Immediate fetch to show sent message
                } else {
                    alert(data.error || 'Failed to send message.');
                }
            });
    });

    // Polling System
    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            
            fetch(`${chatAPI}?action=poll`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        // Store unread counts globally for renderUsers
                        window.chatUnreadCounts = data.unread || {};

                        // Handle unread counts globally
                        let totalUnread = 0;
                        if (data.unread) {
                            Object.values(data.unread).forEach(c => totalUnread += parseInt(c));
                        }
                        
                        const badge = document.getElementById('chatGlobalBadge');
                        const badgeMobile = document.getElementById('chatGlobalBadgeMobile');
                        const floatingBadge = document.getElementById('chatFloatingBadge');
                        
                        if (badge) {
                            badge.textContent = totalUnread;
                            badge.style.display = totalUnread > 0 ? 'inline-block' : 'none';
                        }
                        if (badgeMobile) {
                            badgeMobile.textContent = totalUnread;
                            badgeMobile.style.display = totalUnread > 0 ? 'inline-block' : 'none';
                        }
                        if (floatingBadge) {
                            floatingBadge.textContent = totalUnread;
                            floatingBadge.style.display = totalUnread > 0 ? 'inline-block' : 'none';
                        }

                        // Handle pending requests
                        let totalRequests = data.requests ? data.requests.length : 0;
                        if (totalRequests > 0) {
                            pendingContainer.classList.remove('d-none');
                            pendingList.innerHTML = '';
                            data.requests.forEach(req => {
                                pendingList.innerHTML += `
                                    <div class="d-flex align-items-center justify-content-between bg-white p-2 rounded shadow-sm border mb-2">
                                        <div class="small fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>${req.username}</div>
                                        <div>
                                            <button onclick="respondPermission(${req.requester_id}, 'accepted')" class="btn btn-xs btn-success py-0 px-2 rounded-pill"><i class="fas fa-check"></i></button>
                                            <button onclick="respondPermission(${req.requester_id}, 'rejected')" class="btn btn-xs btn-danger py-0 px-2 rounded-pill"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            pendingContainer.classList.add('d-none');
                        }

                        // Notification logic
                        if (totalUnread > previousUnread || totalRequests > previousRequests) {
                            // Flash title only if hidden
                            if (document.hidden) notifyTitle('New Message!');
                            
                            playSound();
                            
                            if (window.flashNotify && totalUnread > previousUnread) {
                                let senderName = 'a staff member';
                                if (data.senders && typeof data.senders === 'object') {
                                    const keys = Object.keys(data.senders);
                                    if (keys.length > 0) {
                                        // Find the sender that actually has an unread message
                                        senderName = data.senders[keys[keys.length - 1]];
                                    }
                                }
                                window.flashNotify('info', 'New Chat Message', `You have unread messages from ${senderName}.`);
                            } else if (window.flashNotify && totalRequests > previousRequests) {
                                window.flashNotify('info', 'Chat Request', 'You have a new staff chat request.');
                            }
                        }
                        previousUnread = totalUnread;
                        previousRequests = totalRequests;

                        // If in chat room, fetch new messages
                        if (currentChatUserId) {
                            fetchMessages();
                        }
                        
                        // Always refresh sidebar softly to update online status and unread counts (unless searching)
                        if (!searchInput.value) fetchUsers();
                    }
                })
                .catch(err => console.error('Poll err', err));
                
        }, 3000); // 3 seconds poll
    }

    // Start
    initChat();
});
