<?php
$page_title = 'Staff Messenger';
$hide_footer = true;
$container_class = 'container-fluid p-0 m-0';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

/* ══════════════════════════════════════════
   DESIGN TOKENS
══════════════════════════════════════════ */
:root {
    --sb-bg:        #1a1f2e;
    --sb-bg2:       #141824;
    --sb-hover:     rgba(255,255,255,0.06);
    --sb-active:    rgba(93,121,255,0.18);
    --sb-border:    rgba(255,255,255,0.07);
    --sb-text:      #e2e6f0;
    --sb-muted:     #7c84a0;

    --accent:       #5d79ff;
    --accent-light: #7b93ff;
    --accent-glow:  rgba(93,121,255,0.35);
    --accent2:      #22d3b0;

    --chat-bg:      #f0f2f8;
    --chat-canvas:  #e8ecf5;
    --bubble-sent:  #5d79ff;
    --bubble-recv:  #ffffff;
    --chat-border:  #e1e5ef;

    --white:        #ffffff;
    --ink:          #1a1f2e;
    --text-body:    #374063;
    --text-muted:   #8a91aa;

    --radius-sm:    8px;
    --radius-md:    14px;
    --radius-lg:    20px;
    --radius-xl:    28px;

    --shadow-card:  0 4px 24px rgba(26,31,46,0.12);
    --shadow-float: 0 8px 40px rgba(26,31,46,0.18);
    --shadow-glow:  0 0 0 3px var(--accent-glow);

    --transition:   0.2s cubic-bezier(0.4,0,0.2,1);
}

/* ══════════════════════════════════════════
   GLOBAL RESET
══════════════════════════════════════════ */
body { 
    background: #0f1219 !important; 
    font-family: 'Plus Jakarta Sans', sans-serif; 
    overflow: hidden !important; /* Force no scrolling on body */
    margin: 0;
    padding: 0;
}

.container-fluid.p-0.m-0 {
    padding: 0 !important;
    margin: 0 !important;
}

*, *::before, *::after { box-sizing: border-box; }

/* ══════════════════════════════════════════
   SHELL
══════════════════════════════════════════ */
.messenger-layout {
    height: calc(100vh - 68px); /* Height minus desktop navbar */
    width: 100%;
    display: flex;
    background: var(--sb-bg2);
    overflow: hidden;
    animation: shellIn 0.5s cubic-bezier(0.34,1.3,0.64,1) both;
}
@media (max-width: 991px) {
    .messenger-layout {
        height: calc(100vh - 58px); /* Height minus mobile navbar */
    }
}
@keyframes shellIn {
    from { opacity: 0; transform: translateY(18px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ══════════════════════════════════════════
   SIDEBAR
══════════════════════════════════════════ */
.messenger-sidebar {
    width: 340px;
    min-width: 340px;
    border-right: 1px solid var(--sb-border);
    display: flex;
    flex-direction: column;
    background: var(--sb-bg);
    flex-shrink: 0;
}

/* Sidebar header */
.sidebar-header {
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--sb-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--sb-bg2);
}
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
}
.sidebar-brand-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px var(--accent-glow);
}
.sidebar-brand-icon svg { color: #fff; }
.sidebar-brand-text {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--sb-text);
    letter-spacing: -0.3px;
}
.btn-settings-icon {
    width: 36px;
    height: 36px;
    background: var(--sb-hover);
    border: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sb-muted);
    cursor: pointer;
    transition: background var(--transition), color var(--transition), transform var(--transition);
}
.btn-settings-icon:hover {
    background: var(--sb-active);
    color: var(--accent-light);
    transform: rotate(22deg);
}

/* Search */
.sidebar-search {
    padding: 14px 16px;
    background: var(--sb-bg);
    border-bottom: 1px solid var(--sb-border);
}
.search-field-wrap {
    position: relative;
}
.search-field-wrap svg {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sb-muted);
    pointer-events: none;
}
#chatSearchInput {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--sb-border);
    border-radius: 12px;
    padding: 10px 14px 10px 40px;
    color: var(--sb-text);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.875rem;
    outline: none;
    transition: background var(--transition), border-color var(--transition), box-shadow var(--transition);
}
#chatSearchInput::placeholder { color: var(--sb-muted); }
#chatSearchInput:focus {
    background: rgba(255,255,255,0.09);
    border-color: var(--accent);
    box-shadow: var(--shadow-glow);
}

/* User list scroll area */
.sidebar-scroll {
    flex-grow: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0;
}
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

/* Pending requests */
#chatPendingRequests {
    padding: 10px 16px 6px;
}
.section-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--sb-muted);
    padding: 4px 16px 8px;
}
.pending-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: var(--radius-md);
    background: rgba(93,121,255,0.08);
    border: 1px solid rgba(93,121,255,0.18);
    margin: 0 10px 8px;
    animation: itemSlideIn 0.3s ease both;
}
@keyframes itemSlideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* Chat user list items */
#chatUserList {
    padding: 0 8px;
}
.chat-user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: var(--radius-md);
    cursor: pointer;
    position: relative;
    transition: background var(--transition);
    margin-bottom: 2px;
}
.chat-user-item:hover  { background: var(--sb-hover); }
.chat-user-item.active { background: var(--sb-active); }
.chat-user-item.active .cui-name { color: var(--accent-light); }

.cui-avatar {
    position: relative;
    flex-shrink: 0;
}
.cui-avatar-circle {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: #fff;
    letter-spacing: 0.5px;
}
.cui-status-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #22d3b0;
    border: 2px solid var(--sb-bg);
    box-shadow: 0 0 5px rgba(34, 211, 176, 0.5);
    animation: cui-pulse 2s infinite;
}
.cui-status-dot.offline { 
    background: var(--sb-muted); 
    box-shadow: none;
    animation: none;
}
@keyframes cui-pulse {
    0% { box-shadow: 0 0 0 0 rgba(34, 211, 176, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(34, 211, 176, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 211, 176, 0); }
}

.cui-body { flex: 1; min-width: 0; }
.cui-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--sb-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.cui-preview {
    font-size: 0.78rem;
    color: var(--sb-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cui-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    flex-shrink: 0;
}
.cui-time {
    font-size: 0.7rem;
    color: var(--sb-muted);
}
.cui-badge {
    background: var(--accent);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 20px;
    padding: 2px 7px;
    min-width: 20px;
    text-align: center;
    box-shadow: 0 2px 8px var(--accent-glow);
}

/* Loading spinner */
.sidebar-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 32px;
    color: var(--sb-muted);
    font-size: 0.85rem;
}
.spin-ring {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.1);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ══════════════════════════════════════════
   MAIN CHAT AREA
══════════════════════════════════════════ */
.messenger-main {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    background: var(--chat-bg);
    position: relative;
}

/* Chat topbar */
.chat-topbar {
    padding: 0 20px;
    height: 68px;
    border-bottom: 1px solid var(--chat-border);
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--white);
    box-shadow: 0 2px 10px rgba(26,31,46,0.06);
    z-index: 5;
    flex-shrink: 0;
}
#chatBackBtn {
    display: none;
    width: 34px;
    height: 34px;
    background: var(--chat-canvas);
    border: none;
    border-radius: 10px;
    align-items: center;
    justify-content: center;
    color: var(--text-body);
    cursor: pointer;
    transition: background var(--transition);
}
#chatBackBtn:hover { background: var(--chat-border); }

.topbar-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
#chatActiveUserInitials {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--accent), #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #fff;
}
#chatActiveUserStatus {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: #22d3b0;
    border: 2px solid #fff;
}
.topbar-info { flex: 1; min-width: 0; }
#chatActiveUserName {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#chatActiveUserRole {
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 500;
}
.topbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.topbar-icon-btn {
    width: 36px;
    height: 36px;
    background: var(--chat-canvas);
    border: 1px solid var(--chat-border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    cursor: pointer;
    transition: all var(--transition);
}
.topbar-icon-btn:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--accent-glow);
}

/* Messages canvas */
#chatMessages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: var(--chat-canvas);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(93,121,255,0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(34,211,176,0.03) 0%, transparent 50%);
}
#chatMessages::-webkit-scrollbar { width: 4px; }
#chatMessages::-webkit-scrollbar-track { background: transparent; }
#chatMessages::-webkit-scrollbar-thumb { background: rgba(26,31,46,0.12); border-radius: 4px; }

/* Chat bubbles */
.messenger-main .chat-bubble {
    max-width: 68%;
    padding: 10px 14px;
    border-radius: var(--radius-xl);
    font-size: 0.9rem;
    line-height: 1.55;
    position: relative;
    animation: bubbleIn 0.25s cubic-bezier(0.34,1.4,0.64,1) both;
    word-break: break-word;
}
@keyframes bubbleIn {
    from { opacity: 0; transform: scale(0.88) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.messenger-main .chat-bubble.sent {
    background: linear-gradient(135deg, var(--accent), #7b5ff5);
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 6px;
    box-shadow: 0 4px 18px rgba(93,121,255,0.35);
}
.messenger-main .chat-bubble.received {
    background: var(--bubble-recv);
    color: var(--ink);
    align-self: flex-start;
    border-bottom-left-radius: 6px;
    box-shadow: 0 2px 10px rgba(26,31,46,0.08);
}
.messenger-main .chat-time {
    font-size: 0.68rem;
    opacity: 0.65;
    margin-top: 4px;
    display: block;
}
.messenger-main .chat-bubble.sent .chat-time  { text-align: right; color: rgba(255,255,255,0.7) !important; }
.messenger-main .chat-bubble.received .chat-time { color: var(--text-muted) !important; }

/* Read receipt style */
.messenger-main .chat-bubble.sent .read-receipt {
    display: block;
    font-size: 0.7rem;
    text-align: right;
    margin-top: 2px;
    opacity: 0.8;
}
.messenger-main .chat-bubble.sent .read-receipt i {
    margin-left: -4px;
}
.messenger-main .chat-bubble.sent .read-receipt i:first-child {
    margin-left: 0;
}

/* Delete button on bubbles */
.messenger-main .chat-bubble .delete-msg-btn {
    opacity: 0;
    transition: opacity var(--transition);
}
.messenger-main .chat-bubble:hover .delete-msg-btn {
    opacity: 1;
    color: rgba(255,255,255,0.7);
}
.messenger-main .chat-bubble.received:hover .delete-msg-btn { color: var(--text-muted) !important; }
.messenger-main .chat-bubble .delete-msg-btn:hover { color: #ef4444 !important; }

/* Date separator */
.date-separator {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 12px 0;
}
.date-separator::before, .date-separator::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(26,31,46,0.1);
}
.date-separator span {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
    background: rgba(255,255,255,0.7);
    padding: 3px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(26,31,46,0.06);
}

/* Placeholder */
.chat-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--text-muted);
    gap: 12px;
    animation: fadeIn 0.6s ease both;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
.placeholder-icon-wrap {
    width: 96px;
    height: 96px;
    background: linear-gradient(135deg, rgba(93,121,255,0.1), rgba(34,211,176,0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed rgba(93,121,255,0.2);
    animation: floatGently 4s ease-in-out infinite;
    margin-bottom: 8px;
}
@keyframes floatGently {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-8px); }
}
.placeholder-icon-wrap svg { color: var(--accent); opacity: 0.6; }
.placeholder-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ink);
    opacity: 0.75;
}
.placeholder-sub {
    font-size: 0.85rem;
    color: var(--text-muted);
    max-width: 260px;
    line-height: 1.6;
}
.placeholder-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34,211,176,0.1);
    border: 1px solid rgba(34,211,176,0.25);
    color: #0d9488;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 20px;
    margin-top: 8px;
}

/* ══════════════════════════════════════════
   FILE ATTACHMENT PREVIEW
══════════════════════════════════════════ */
#chatFilePreview {
    padding: 10px 20px;
    background: rgba(93,121,255,0.06);
    border-top: 1px solid rgba(93,121,255,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.file-preview-inner {
    display: flex;
    align-items: center;
    gap: 10px;
    overflow: hidden;
}
.file-preview-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--accent), #7b5ff5);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
}
#chatFilePreviewName {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-body);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#chatFileRemoveBtn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    line-height: 1;
    transition: color var(--transition), background var(--transition);
    flex-shrink: 0;
}
#chatFileRemoveBtn:hover { color: #ef4444; background: rgba(239,68,68,0.08); }

/* ══════════════════════════════════════════
   MESSAGE INPUT BAR
══════════════════════════════════════════ */
.chat-input-bar {
    padding: 14px 20px;
    background: var(--white);
    border-top: 1px solid var(--chat-border);
    flex-shrink: 0;
}
#chatMessageForm {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--chat-canvas);
    border: 1.5px solid var(--chat-border);
    border-radius: var(--radius-xl);
    padding: 6px 6px 6px 16px;
    transition: border-color var(--transition), box-shadow var(--transition);
}
#chatMessageForm:focus-within {
    border-color: var(--accent);
    box-shadow: var(--shadow-glow);
}
.input-icon-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color var(--transition), background var(--transition);
    flex-shrink: 0;
}
.input-icon-btn:hover { color: var(--accent); background: rgba(93,121,255,0.08); }
#chatMessageInput {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.92rem;
    color: var(--ink);
    padding: 6px 0;
}
#chatMessageInput::placeholder { color: var(--text-muted); }
.send-btn {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--accent), #7b5ff5);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 4px 14px var(--accent-glow);
    transition: transform var(--transition), box-shadow var(--transition);
    flex-shrink: 0;
}
.send-btn:hover  { transform: scale(1.08); box-shadow: 0 6px 20px var(--accent-glow); }
.send-btn:active { transform: scale(0.95); }

/* ══════════════════════════════════════════
   SETTINGS PANEL
══════════════════════════════════════════ */
#chatSettingsView {
    position: absolute;
    top: 0;
    left: 0;
    width: 340px;
    height: 100%;
    background: var(--sb-bg);
    border-right: 1px solid var(--sb-border);
    z-index: 20;
    display: flex;
    flex-direction: column;
    animation: settingsSlideIn 0.3s cubic-bezier(0.4,0,0.2,1) both;
}
@keyframes settingsSlideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
.settings-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px;
    background: linear-gradient(135deg, #1e2d5a, #2d1b6e);
    border-bottom: 1px solid var(--sb-border);
}
.btn-settings-back {
    width: 34px;
    height: 34px;
    background: rgba(255,255,255,0.12);
    border: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    transition: background var(--transition);
    flex-shrink: 0;
}
.btn-settings-back:hover { background: rgba(255,255,255,0.2); }
.settings-topbar-title {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.settings-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
}
.settings-scroll::-webkit-scrollbar { width: 4px; }
.settings-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.settings-avatar-card {
    text-align: center;
    padding: 28px 16px;
    background: rgba(255,255,255,0.04);
    border-radius: var(--radius-md);
    border: 1px solid var(--sb-border);
    margin-bottom: 20px;
}
.settings-avatar {
    width: 84px;
    height: 84px;
    background: linear-gradient(135deg, var(--accent), #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px var(--accent-glow);
    border: 3px solid rgba(255,255,255,0.15);
    letter-spacing: 1px;
}
.settings-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--sb-text);
    margin-bottom: 4px;
}
.settings-role {
    font-size: 0.8rem;
    color: var(--sb-muted);
    background: rgba(255,255,255,0.06);
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    border: 1px solid var(--sb-border);
}

.settings-section-label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--sb-muted);
    margin-bottom: 10px;
    padding-left: 2px;
}

.settings-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--sb-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    margin-bottom: 20px;
}
.settings-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--sb-border);
    gap: 12px;
}
.settings-row:last-child { border-bottom: none; }
.settings-row-left { flex: 1; }
.settings-row-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--sb-text);
    margin-bottom: 2px;
}
.settings-row-sub {
    font-size: 0.75rem;
    color: var(--sb-muted);
    line-height: 1.4;
}

/* Custom toggle switch */
.toggle-wrap {
    position: relative;
    flex-shrink: 0;
}
.toggle-wrap input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}
.toggle-track {
    display: block;
    width: 44px;
    height: 24px;
    background: rgba(255,255,255,0.1);
    border-radius: 24px;
    cursor: pointer;
    transition: background 0.25s;
    position: relative;
    border: 1px solid rgba(255,255,255,0.08);
}
.toggle-track::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    top: 2px;
    left: 2px;
    transition: transform 0.25s cubic-bezier(0.34,1.4,0.64,1), background 0.25s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.toggle-wrap input:checked + .toggle-track { background: var(--accent); border-color: var(--accent); }
.toggle-wrap input:checked + .toggle-track::after { transform: translateX(20px); }

.settings-policy-box {
    background: rgba(255,193,7,0.07);
    border: 1px solid rgba(255,193,7,0.2);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-top: 4px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.settings-policy-box svg { color: #f59e0b; flex-shrink: 0; margin-top: 1px; }
.settings-policy-box p {
    font-size: 0.78rem;
    color: var(--sb-muted);
    margin: 0;
    line-height: 1.55;
}
.settings-policy-box strong { color: #f59e0b; }

/* ══════════════════════════════════════════
   CONTACT INFO PANEL (RIGHT)
══════════════════════════════════════════ */
.messenger-info-pane {
    width: 340px;
    min-width: 340px;
    background: var(--sb-bg);
    border-left: 1px solid var(--sb-border);
    display: flex;
    flex-direction: column;
    animation: infoSlideIn 0.3s cubic-bezier(0.4,0,0.2,1) both;
    z-index: 10;
}
@keyframes infoSlideIn {
    from { margin-right: -340px; opacity: 0; }
    to   { margin-right: 0; opacity: 1; }
}
.info-header {
    height: 68px;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--sb-bg2);
    border-bottom: 1px solid var(--sb-border);
    flex-shrink: 0;
}
.info-header-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--sb-text);
}
.btn-info-close {
    background: none;
    border: none;
    color: var(--sb-muted);
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background var(--transition), color var(--transition);
}
.btn-info-close:hover { background: rgba(255,255,255,0.06); color: var(--sb-text); }
.info-scroll {
    flex: 1;
    overflow-y: auto;
    background: var(--sb-bg2);
}
.info-scroll::-webkit-scrollbar { width: 4px; }
.info-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
.info-card {
    background: var(--sb-bg);
    padding: 24px 20px;
    margin-bottom: 10px;
    border-bottom: 1px solid var(--sb-border);
}
.info-card.text-center {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.info-avatar-wrap {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
    box-shadow: 0 10px 30px var(--accent-glow);
    border: 4px solid rgba(255,255,255,0.1);
    overflow: hidden;
}
.info-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
.info-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--sb-text);
    margin-bottom: 4px;
}
.info-phone {
    font-size: 0.9rem;
    color: var(--sb-muted);
}
.info-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--sb-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}
.info-text {
    font-size: 0.95rem;
    color: var(--sb-text);
    line-height: 1.5;
}
.info-action-row {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    width: 100%;
}
.info-action-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--sb-border);
    border-radius: var(--radius-md);
    color: var(--accent-light);
    cursor: pointer;
    transition: all var(--transition);
}
.info-action-btn:hover { background: rgba(93,121,255,0.08); border-color: var(--accent); }
.info-action-btn svg { color: var(--accent); }
.info-action-btn span { font-size: 0.8rem; font-weight: 600; color: var(--sb-text); }

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media (max-width: 768px) {
    .messenger-sidebar { width: 100%; min-width: 100%; }
    .messenger-main {
        position: absolute;
        inset: 0;
        z-index: 10;
        display: none !important;
    }
    .messenger-main.active { display: flex !important; }
    #chatBackBtn { display: flex !important; }
    #chatSettingsView { width: 100%; }
    .messenger-info-pane { position: absolute; right: 0; height: 100%; width: 100%; min-width: 100%; z-index: 30; }
}
@media (min-width: 769px) {
    #chatBackBtn { display: none !important; }
    #chatRoomView { display: flex !important; }
    /* Topbar clickable on desktop */
    .chat-topbar-clickable { cursor: pointer; transition: background var(--transition); border-radius: 8px; padding: 4px 10px; margin-left: -10px; }
    .chat-topbar-clickable:hover { background: rgba(0,0,0,0.04); }
}
</style>

<div class="messenger-layout" id="messengerApp">

    <!-- ════════ SIDEBAR ════════ -->
    <div class="messenger-sidebar" id="chatUserListView">

        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <span class="sidebar-brand-text">Staff Messenger</span>
            </div>
            <button class="btn-settings-icon" id="chatSettingsBtn" title="Settings">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 9 15a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9z"/>
                </svg>
            </button>
        </div>

        <div class="sidebar-search">
            <div class="search-field-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="chatSearchInput" placeholder="Search staff members…">
            </div>
        </div>

        <div class="sidebar-scroll">
            <div id="chatPendingRequests" class="d-none">
                <div class="section-label px-3 pt-2">Pending Requests</div>
                <div id="chatPendingRequestsList" class="px-2 mb-2"></div>
                <div style="height:1px; background: var(--sb-border); margin: 0 16px 8px;"></div>
            </div>
            <div class="section-label px-3 pt-2">All Conversations</div>
            <div id="chatUserList">
                <div class="sidebar-loading">
                    <div class="spin-ring"></div>
                    Loading conversations…
                </div>
            </div>
        </div>
    </div>

    <!-- ════════ MAIN CHAT ════════ -->
    <div class="messenger-main d-none flex-column" id="chatRoomView">

        <!-- Topbar -->
        <div class="chat-topbar">
            <button class="btn" id="chatBackBtn" style="background:var(--chat-canvas); border:none; border-radius:10px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; color:var(--text-body); padding:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div class="topbar-avatar-wrap">
                <div id="chatActiveUserInitials">?</div>
                <span id="chatActiveUserStatus" style="position:absolute; bottom:1px; right:1px; width:13px; height:13px; border-radius:50%; background:#22d3b0; border:2px solid #fff; display:block;"></span>
            </div>
            <div class="topbar-info chat-topbar-clickable" id="chatTopbarInfo">
                <div id="chatActiveUserName">Select a conversation</div>
                <div id="chatActiveUserRole" style="font-size:0.78rem; color:var(--text-muted); font-weight:500;">—</div>
            </div>
            <div class="topbar-actions">
                <button class="topbar-icon-btn" title="Search in chat">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
                <button class="topbar-icon-btn" title="More options">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="chatMessages" class="flex-grow-1 overflow-auto p-4 d-flex flex-column"></div>

        <!-- File Preview -->
        <div id="chatFilePreview" class="d-none">
            <div class="file-preview-inner">
                <div class="file-preview-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                </div>
                <span id="chatFilePreviewName"></span>
            </div>
            <button type="button" id="chatFileRemoveBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Input Bar -->
        <div class="chat-input-bar">
            <form id="chatMessageForm">
                <input type="hidden" id="chatActiveUserId">

                <label for="chatFileInput" class="input-icon-btn" style="cursor:pointer; margin-bottom:0;" title="Attach file">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                </label>
                <input type="file" id="chatFileInput" style="display:none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">

                <button type="button" class="input-icon-btn" title="Emoji">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </button>

                <input type="text" id="chatMessageInput" placeholder="Write a message…" autocomplete="off">

                <button type="submit" class="send-btn" title="Send">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- ════════ SETTINGS PANEL ════════ -->
    <div id="chatSettingsView" class="d-none">
        <div class="settings-topbar">
            <button class="btn-settings-back" id="chatSettingsBackBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h6 class="settings-topbar-title">Settings & Profile</h6>
        </div>
        <div class="settings-scroll">

            <!-- Profile card -->
            <div class="settings-avatar-card">
                <div class="settings-avatar">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?>
                </div>
                <div class="settings-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
                <span class="settings-role"><?= ucfirst($_SESSION['role'] ?? 'Staff') ?></span>
            </div>

            <!-- Notifications section -->
            <div class="settings-section-label">Notifications</div>
            <div class="settings-card">
                <div class="settings-row">
                    <div class="settings-row-left">
                        <div class="settings-row-title">Notification Sounds</div>
                        <div class="settings-row-sub">Play a sound for incoming messages</div>
                    </div>
                    <div class="toggle-wrap">
                        <input type="checkbox" id="chatSoundToggle" checked>
                        <label class="toggle-track" for="chatSoundToggle"></label>
                    </div>
                </div>
                <div class="settings-row">
                    <div class="settings-row-left">
                        <div class="settings-row-title">Browser Tab Alerts</div>
                        <div class="settings-row-sub">Flash tab title when a message arrives</div>
                    </div>
                    <div class="toggle-wrap">
                        <input type="checkbox" id="chatTitleToggle" checked>
                        <label class="toggle-track" for="chatTitleToggle"></label>
                    </div>
                </div>
            </div>

            <!-- Privacy & data -->
            <div class="settings-section-label">Data & Privacy</div>
            <div class="settings-policy-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p><strong>Data Policy:</strong> Messages and files are automatically deleted after 2 days. All communication is stored locally and encrypted.</p>
            </div>
        </div>
    </div>

    <!-- ════════ CONTACT INFO PANEL ════════ -->
    <div id="chatInfoView" class="messenger-info-pane d-none">
        <div class="info-header">
            <button class="btn-info-close" id="chatInfoCloseBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <span class="info-header-title">Contact info</span>
        </div>
        <div class="info-scroll">
            <div class="info-card text-center" style="padding-top:32px; border-bottom-width:8px;">
                <div id="infoAvatar" class="info-avatar-wrap"></div>
                <h4 id="infoName" class="info-name">—</h4>
                <div id="infoPhone" class="info-phone">—</div>
                
                <div class="info-action-row">
                    <div class="info-action-btn">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span>Search</span>
                    </div>
                    <div class="info-action-btn">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        <span>Add</span>
                    </div>
                </div>
            </div>
            <div class="info-card" style="border-bottom-width:8px;">
                <div class="info-label">About</div>
                <div id="infoAbout" class="info-text">—</div>
                <div class="info-label mt-4">Joined</div>
                <div id="infoJoinDate" class="info-text">—</div>
            </div>
        </div>
    </div>

</div>

<script>
window.__IS_DEDICATED_MESSENGER = true;

document.addEventListener("DOMContentLoaded", () => {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages && chatMessages.innerHTML.trim() === '') {
        chatMessages.innerHTML = `
            <div class="chat-placeholder d-none d-md-flex">
                <div class="placeholder-icon-wrap">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="placeholder-title">ClinicApp Messenger</div>
                <p class="placeholder-sub">Select a staff member from the list to start a secure conversation.</p>
                <div class="placeholder-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    End-to-end encrypted locally
                </div>
            </div>
        `;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>