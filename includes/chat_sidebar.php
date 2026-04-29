<!-- Chat Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start chat-sidebar shadow" tabindex="-1" id="chatSidebar" aria-labelledby="chatSidebarLabel">
    <div class="offcanvas-header bg-gradient-primary text-white border-bottom border-light d-flex align-items-center">
        <h5 class="offcanvas-title fw-bold m-0 d-flex align-items-center" id="chatSidebarLabel">
            <i class="fas fa-comments me-2"></i>Staff Messenger
        </h5>
        <div class="ms-auto d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-link text-white p-1 me-2" id="chatSettingsBtn" title="Settings">
                <i class="fas fa-cog fa-lg"></i>
            </button>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
        
        <!-- View: User List -->
        <div id="chatUserListView" class="flex-grow-1 overflow-auto h-100">
            <div class="p-3 border-bottom bg-light sticky-top">
                <input type="text" id="chatSearchInput" class="form-control rounded-pill border-0 shadow-sm" placeholder="Search staff...">
            </div>
            
            <!-- Pending Requests Section -->
            <div id="chatPendingRequests" class="d-none px-3 pt-3">
                <h6 class="text-muted small fw-bold mb-2">Pending Requests</h6>
                <div id="chatPendingRequestsList" class="mb-3"></div>
                <hr>
            </div>

            <div class="px-3 pt-3">
                <h6 class="text-muted small fw-bold mb-2">Active Staff</h6>
                <div id="chatUserList" class="list-group list-group-flush border-0">
                    <!-- Users loaded via AJAX -->
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading...
                    </div>
                </div>
            </div>
        </div>

        <!-- View: Chat Room -->
        <div id="chatRoomView" class="flex-grow-1 d-flex flex-column h-100 d-none bg-white">
            <div class="p-3 border-bottom d-flex align-items-center bg-light shadow-sm">
                <button class="btn btn-sm btn-link text-dark p-0 me-3" id="chatBackBtn">
                    <i class="fas fa-arrow-left fa-lg"></i>
                </button>
                <div class="position-relative">
                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;" id="chatActiveUserInitials">?</div>
                    <span class="position-absolute bottom-0 end-0 p-1 border border-light rounded-circle" id="chatActiveUserStatus" style="background-color: #198754;"></span>
                </div>
                <div class="ms-2">
                    <h6 class="mb-0 fw-bold" id="chatActiveUserName">User Name</h6>
                    <small class="text-muted" id="chatActiveUserRole" style="font-size: 0.75rem;">Role</small>
                </div>
            </div>
            
            <div id="chatMessages" class="flex-grow-1 overflow-auto p-3 d-flex flex-column" style="background-color: #f8f9fa;">
                <!-- Messages go here -->
            </div>
            
            <!-- File Attachment Preview -->
            <div id="chatFilePreview" class="d-none px-3 py-2 bg-light border-top d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center overflow-hidden">
                    <i class="fas fa-paperclip text-muted me-2"></i>
                    <span id="chatFilePreviewName" class="text-truncate small fw-bold text-dark"></span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="chatFileRemoveBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-3 border-top bg-white">
                <form id="chatMessageForm" class="d-flex align-items-center position-relative">
                    <input type="hidden" id="chatActiveUserId">
                    
                    <label for="chatFileInput" class="btn btn-link text-secondary p-0 me-2" style="cursor: pointer;">
                        <i class="fas fa-paperclip fa-lg"></i>
                    </label>
                    <input type="file" id="chatFileInput" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">

                    <input type="text" id="chatMessageInput" class="form-control rounded-pill pe-5 bg-light border-0" placeholder="Type a message..." autocomplete="off">
                    
                    <button type="submit" class="btn btn-primary rounded-circle position-absolute end-0 me-1" style="width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-paper-plane" style="font-size: 0.8rem; margin-left: -2px;"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- View: Settings & Profile -->
        <div id="chatSettingsView" class="flex-grow-1 h-100 d-none bg-white flex-column">
            <div class="p-3 border-bottom d-flex align-items-center bg-light shadow-sm">
                <button class="btn btn-sm btn-link text-dark p-0 me-3" id="chatSettingsBackBtn">
                    <i class="fas fa-arrow-left fa-lg"></i>
                </button>
                <h6 class="mb-0 fw-bold">Settings & Profile</h6>
            </div>
            <div class="p-4 flex-grow-1 overflow-auto text-center">
                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-3 shadow" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?>
                </div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h5>
                <p class="text-muted small mb-4"><?= ucfirst($_SESSION['role'] ?? 'Staff') ?></p>

                <hr class="mb-4">
                
                <div class="text-start">
                    <h6 class="fw-bold text-muted mb-3"><i class="fas fa-bell me-2"></i>Notifications</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="chatSoundToggle" checked>
                        <label class="form-check-label" for="chatSoundToggle">Play sounds for new messages</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="chatTitleToggle" checked>
                        <label class="form-check-label" for="chatTitleToggle">Flash browser tab title</label>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4 text-start small">
                    <i class="fas fa-info-circle me-2"></i> Messages and files are automatically deleted after 2 days to save server storage.
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
/* Chat Sidebar Styles */
.chat-sidebar {
    width: 350px !important;
    border-right: none;
}
.chat-user-item {
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 10px;
    margin-bottom: 5px;
}
.chat-user-item:hover {
    background-color: #f1f3f5;
    transform: translateX(3px);
}
.chat-bubble {
    max-width: 80%;
    padding: 10px 15px;
    border-radius: 20px;
    margin-bottom: 10px;
    position: relative;
    word-wrap: break-word;
    animation: fadeInBubble 0.3s ease-out;
}
@keyframes fadeInBubble {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.chat-bubble.sent {
    align-self: flex-end;
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    color: white;
    border-bottom-right-radius: 4px;
}
.chat-bubble.received {
    align-self: flex-start;
    background-color: white;
    color: #333;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}
.chat-time {
    font-size: 0.65rem;
    opacity: 0.7;
    margin-top: 3px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-bubble .delete-msg-btn {
    display: none;
    cursor: pointer;
    opacity: 0.6;
    transition: 0.2s;
}
.chat-bubble:hover .delete-msg-btn {
    display: inline-block;
}
.chat-bubble .delete-msg-btn:hover {
    opacity: 1;
    color: #dc3545 !important;
}
.chat-file-attachment {
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
    padding: 10px;
    margin-top: 5px;
    display: flex;
    align-items: center;
}
.chat-bubble.sent .chat-file-attachment {
    background: rgba(255,255,255,0.2);
}
.chat-image-preview {
    max-width: 100%;
    border-radius: 8px;
    margin-top: 5px;
    cursor: pointer;
}
.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.status-dot.online {
    background-color: #198754;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2);
}
.status-dot.offline {
    background-color: #adb5bd;
}
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}
.unread-badge {
    animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes popIn {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}
</style>
