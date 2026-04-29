<?php
http_response_code(403);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=DM+Sans:wght@300;400;600&display=swap');

    .error-403-wrap {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0a0e1a;
        position: relative;
        overflow: hidden;
        font-family: 'DM Sans', sans-serif;
    }

    /* Animated grid background */
    .error-403-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(0, 195, 137, 0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 195, 137, 0.04) 1px, transparent 1px);
        background-size: 40px 40px;
        animation: gridDrift 20s linear infinite;
    }

    /* Radial glow */
    .error-403-wrap::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 600px;
        height: 600px;
        background: radial-gradient(ellipse, rgba(220, 38, 38, 0.08) 0%, transparent 70%);
        animation: glowPulse 3s ease-in-out infinite;
        pointer-events: none;
    }

    @keyframes gridDrift {
        0%   { background-position: 0 0; }
        100% { background-position: 40px 40px; }
    }
    @keyframes glowPulse {
        0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
        50%       { opacity: 1;   transform: translate(-50%, -50%) scale(1.15); }
    }

    /* Card */
    .error-card {
        position: relative;
        z-index: 10;
        background: rgba(15, 20, 35, 0.9);
        border: 1px solid rgba(0, 195, 137, 0.15);
        border-radius: 16px;
        padding: 60px 48px;
        max-width: 560px;
        width: 100%;
        text-align: center;
        box-shadow:
            0 0 0 1px rgba(0, 195, 137, 0.05),
            0 30px 80px rgba(0, 0, 0, 0.6),
            inset 0 1px 0 rgba(255,255,255,0.04);
        animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes cardEntrance {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0)    scale(1); }
    }

    /* Lock icon */
    .lock-ring {
        width: 90px;
        height: 90px;
        margin: 0 auto 28px;
        position: relative;
    }

    .lock-ring svg {
        width: 100%;
        height: 100%;
        animation: lockShake 6s ease-in-out infinite;
    }

    .lock-ring::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        border: 2px solid rgba(220, 38, 38, 0.3);
        animation: ringPulse 2s ease-out infinite;
    }
    .lock-ring::after {
        content: '';
        position: absolute;
        inset: -20px;
        border-radius: 50%;
        border: 1px solid rgba(220, 38, 38, 0.15);
        animation: ringPulse 2s ease-out 0.4s infinite;
    }

    @keyframes ringPulse {
        0%   { transform: scale(0.9); opacity: 1; }
        100% { transform: scale(1.5); opacity: 0; }
    }
    @keyframes lockShake {
        0%, 90%, 100% { transform: rotate(0deg); }
        92%           { transform: rotate(-4deg); }
        94%           { transform: rotate(4deg); }
        96%           { transform: rotate(-3deg); }
        98%           { transform: rotate(3deg); }
    }

    /* Error code */
    .error-code {
        font-family: 'Share Tech Mono', monospace;
        font-size: 5.5rem;
        line-height: 1;
        color: #dc2626;
        letter-spacing: -2px;
        margin-bottom: 4px;
        animation: glitch 5s steps(1) infinite;
        position: relative;
    }

    .error-code::before,
    .error-code::after {
        content: '403';
        position: absolute;
        left: 50%;
        top: 0;
        transform: translateX(-50%);
        width: 100%;
    }
    .error-code::before {
        color: #00c389;
        animation: glitchTop 5s steps(1) infinite;
        clip-path: polygon(0 0, 100% 0, 100% 40%, 0 40%);
    }
    .error-code::after {
        color: #3b82f6;
        animation: glitchBot 5s steps(1) 0.1s infinite;
        clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
    }

    @keyframes glitch {
        0%,  80%, 100% { transform: none; }
        82%            { transform: skewX(-1deg); }
        84%            { transform: skewX(1.5deg); }
        86%            { transform: none; }
    }
    @keyframes glitchTop {
        0%,  80%, 100% { transform: translateX(-50%) translateX(0); opacity: 0; }
        82%            { transform: translateX(-50%) translateX(-3px); opacity: 0.8; }
        86%            { transform: translateX(-50%) translateX(3px); opacity: 0; }
    }
    @keyframes glitchBot {
        0%,  80%, 100% { transform: translateX(-50%) translateX(0); opacity: 0; }
        82%            { transform: translateX(-50%) translateX(3px); opacity: 0.8; }
        86%            { transform: translateX(-50%) translateX(-3px); opacity: 0; }
    }

    /* Divider / flatline */
    .flatline-wrap {
        margin: 18px 0 24px;
        height: 36px;
        position: relative;
    }
    .flatline-wrap svg {
        width: 100%;
        height: 100%;
        overflow: visible;
    }
    .flatline-path {
        stroke: #dc2626;
        stroke-width: 1.5;
        fill: none;
        stroke-dasharray: 600;
        stroke-dashoffset: 600;
        animation: draw 2s 0.5s ease forwards, flatlinePulse 3s 2.5s ease-in-out infinite;
        filter: drop-shadow(0 0 4px rgba(220,38,38,0.7));
    }

    @keyframes draw {
        to { stroke-dashoffset: 0; }
    }
    @keyframes flatlinePulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.4; }
    }

    /* Text */
    .error-label {
        font-family: 'Share Tech Mono', monospace;
        font-size: 0.7rem;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #dc2626;
        margin-bottom: 10px;
        animation: fadeUp 0.6s 0.3s both;
    }

    .error-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 12px;
        animation: fadeUp 0.6s 0.5s both;
    }

    .error-desc {
        font-size: 0.95rem;
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 32px;
        animation: fadeUp 0.6s 0.7s both;
    }

    .error-desc a {
        color: #00c389;
        text-decoration: none;
        border-bottom: 1px solid rgba(0,195,137,0.3);
        transition: border-color 0.2s;
    }
    .error-desc a:hover { border-color: #00c389; }

    /* Button */
    .btn-home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 13px 32px;
        background: transparent;
        border: 1px solid rgba(0, 195, 137, 0.5);
        color: #00c389;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: color 0.3s, border-color 0.3s;
        animation: fadeUp 0.6s 0.9s both;
        letter-spacing: 0.5px;
    }

    .btn-home::before {
        content: '';
        position: absolute;
        inset: 0;
        background: #00c389;
        transform: translateX(-102%);
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 0;
    }

    .btn-home:hover::before { transform: translateX(0); }
    .btn-home:hover { color: #0a0e1a; border-color: #00c389; }

    .btn-home span, .btn-home svg { position: relative; z-index: 1; }

    /* Status badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Share Tech Mono', monospace;
        font-size: 0.68rem;
        color: #475569;
        margin-top: 24px;
        animation: fadeUp 0.6s 1.1s both;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #dc2626;
        animation: blink 1.4s step-start infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0; }
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="error-403-wrap">
    <div class="error-card">

        <!-- Lock icon -->
        <div class="lock-ring">
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="38" stroke="rgba(220,38,38,0.2)" stroke-width="1"/>
                <rect x="22" y="38" width="36" height="26" rx="5" fill="rgba(220,38,38,0.12)" stroke="#dc2626" stroke-width="1.5"/>
                <path d="M28 38V30a12 12 0 0 1 24 0v8" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="40" cy="52" r="4" fill="#dc2626"/>
                <line x1="40" y1="55" x2="40" y2="59" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="error-label">System Alert</div>
        <div class="error-code">403</div>

        <!-- Flatline animation -->
        <div class="flatline-wrap">
            <svg viewBox="0 0 460 36" preserveAspectRatio="none">
                <path class="flatline-path"
                    d="M0,18 L80,18 L100,18 L115,4 L125,32 L135,4 L145,32 L155,4 L165,18 L185,18 L380,18 L460,18"/>
            </svg>
        </div>

        <div class="error-title">Access Denied</div>
        <p class="error-desc">
            You don't have permission to view this resource.<br>
            If you think this is a mistake, please <a href="#">contact the administrator</a>.
        </p>

        <a class="btn-home" href="/clinicapp/index.php">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Return to Home</span>
        </a>

        <div class="status-badge">
            <span class="status-dot"></span>
            ERROR CODE 403 &nbsp;·&nbsp; FORBIDDEN &nbsp;·&nbsp; ACCESS BLOCKED
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>