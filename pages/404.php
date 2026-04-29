<?php
http_response_code(404);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=Outfit:wght@300;400;500&display=swap');

    :root {
        --cream:   #f5f0e8;
        --warm:    #ede8dd;
        --ink:     #1c1a16;
        --muted:   #8a8070;
        --accent:  #c0874a;
        --accent2: #2a6b8a;
        --line:    rgba(28,26,22,0.12);
    }

    .wrap-404 {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--cream);
        position: relative;
        overflow: hidden;
        font-family: 'Outfit', sans-serif;
    }

    /* Noise grain texture */
    .wrap-404::before {
        content: '';
        position: absolute;
        inset: -50%;
        width: 200%;
        height: 200%;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
        opacity: 0.5;
        animation: grainShift 0.5s steps(1) infinite;
    }
    @keyframes grainShift {
        0%  { transform: translate(0, 0); }
        25% { transform: translate(-2px, 1px); }
        50% { transform: translate(1px, -2px); }
        75% { transform: translate(-1px, 2px); }
    }

    /* Soft watermark circles */
    .bg-circle {
        position: absolute;
        border-radius: 50%;
        border: 1px solid var(--line);
        pointer-events: none;
        animation: breathe 8s ease-in-out infinite;
    }
    .bg-circle:nth-child(1) { width: 500px; height: 500px; top: -150px; right: -100px; animation-delay: 0s; }
    .bg-circle:nth-child(2) { width: 320px; height: 320px; bottom: -80px; left: -60px; animation-delay: 2s; }
    .bg-circle:nth-child(3) { width: 180px; height: 180px; top: 30%; left: 8%; animation-delay: 4s; }

    @keyframes breathe {
        0%, 100% { transform: scale(1);    opacity: 0.5; }
        50%       { transform: scale(1.06); opacity: 1;   }
    }

    /* Floating paper shreds */
    .shred {
        position: absolute;
        width: 40px;
        height: 54px;
        background: var(--warm);
        border: 1px solid rgba(28,26,22,0.08);
        border-radius: 2px;
        pointer-events: none;
        transform-origin: center;
        animation: floatShred var(--dur) ease-in-out var(--delay) infinite alternate;
    }
    .shred::after {
        content: '';
        display: block;
        margin: 9px 7px 0;
        height: 2px;
        background: var(--line);
        box-shadow: 0 5px 0 var(--line), 0 10px 0 var(--line), 0 15px 0 var(--line), 0 20px 0 var(--line);
    }
    .shred:nth-child(4)  { top: 12%; left: 6%;  --dur: 5s; --delay: 0s;    transform: rotate(-12deg); }
    .shred:nth-child(5)  { top: 65%; left: 4%;  --dur: 6s; --delay: 1s;    transform: rotate(8deg); }
    .shred:nth-child(6)  { top: 20%; right: 5%; --dur: 7s; --delay: 0.5s;  transform: rotate(14deg); }
    .shred:nth-child(7)  { top: 70%; right: 6%; --dur: 5s; --delay: 1.5s;  transform: rotate(-6deg); }
    .shred:nth-child(8)  { top: 44%; left: 2%;  --dur: 8s; --delay: 2s;    transform: rotate(5deg); }

    @keyframes floatShred {
        from { transform: rotate(var(--r, -12deg)) translateY(0); }
        to   { transform: rotate(calc(var(--r, -12deg) + 6deg)) translateY(-14px); }
    }

    /* ─── Card ─── */
    .card-404 {
        position: relative;
        z-index: 10;
        background: #fff;
        border: 1px solid rgba(28,26,22,0.1);
        border-radius: 4px;
        padding: 64px 56px 52px;
        max-width: 580px;
        width: 100%;
        text-align: center;
        box-shadow:
            6px 6px 0 0 rgba(28,26,22,0.06),
            12px 12px 0 0 rgba(28,26,22,0.03),
            0 30px 60px rgba(28,26,22,0.08);
        animation: cardIn 0.9s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(50px) rotate(-1deg); }
        to   { opacity: 1; transform: translateY(0) rotate(0deg); }
    }

    /* Stamp in top corner */
    .stamp {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 56px;
        height: 56px;
        border: 2px solid rgba(192,135,74,0.4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: stampSpin 20s linear infinite;
    }
    .stamp svg { animation: stampCounter 20s linear infinite; }
    @keyframes stampSpin   { to { transform: rotate(360deg); } }
    @keyframes stampCounter { to { transform: rotate(-360deg); } }

    /* Search icon */
    .search-wrap {
        width: 80px;
        height: 80px;
        margin: 0 auto 28px;
        position: relative;
    }
    .search-wrap svg.search-icon {
        width: 80px;
        height: 80px;
        animation: searchSway 4s ease-in-out infinite;
    }
    @keyframes searchSway {
        0%, 100% { transform: rotate(-8deg) scale(1); }
        50%       { transform: rotate(5deg) scale(1.05); }
    }

    /* Scanning beam */
    .scan-ring {
        position: absolute;
        inset: -12px;
        border-radius: 50%;
        border: 1.5px dashed rgba(42, 107, 138, 0.35);
        animation: scanSpin 5s linear infinite;
    }
    .scan-ring::before {
        content: '';
        position: absolute;
        top: -4px;
        left: 50%;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--accent2);
        transform: translateX(-50%);
        box-shadow: 0 0 8px var(--accent2);
    }
    @keyframes scanSpin { to { transform: rotate(360deg); } }

    /* Big number */
    .num-404 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 9rem;
        font-weight: 300;
        line-height: 0.9;
        color: var(--ink);
        letter-spacing: -4px;
        position: relative;
        display: inline-block;
        margin-bottom: 0;
        animation: numIn 0.7s 0.2s both cubic-bezier(0.34,1.3,0.64,1);
    }
    .num-404 em {
        font-style: italic;
        color: var(--accent);
    }
    @keyframes numIn {
        from { opacity: 0; transform: scale(0.8); }
        to   { opacity: 1; transform: scale(1); }
    }

    /* Wavy underline */
    .wavy-line {
        display: block;
        margin: 6px auto 22px;
        width: 100px;
        height: 10px;
        animation: waveIn 0.6s 0.5s both;
    }
    .wavy-path {
        fill: none;
        stroke: var(--accent);
        stroke-width: 2;
        stroke-dasharray: 120;
        stroke-dashoffset: 120;
        animation: waveDraw 0.8s 0.6s ease forwards;
    }
    @keyframes waveDraw { to { stroke-dashoffset: 0; } }
    @keyframes waveIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    /* Label */
    .not-found-label {
        font-family: 'Outfit', sans-serif;
        font-size: 0.65rem;
        font-weight: 500;
        letter-spacing: 5px;
        text-transform: uppercase;
        color: var(--accent);
        margin-bottom: 14px;
        animation: fadeUp 0.6s 0.6s both;
    }

    /* Title */
    .not-found-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.7rem;
        font-weight: 600;
        color: var(--ink);
        margin-bottom: 14px;
        animation: fadeUp 0.6s 0.75s both;
    }

    /* Desc */
    .not-found-desc {
        font-size: 0.9rem;
        color: var(--muted);
        line-height: 1.75;
        margin-bottom: 36px;
        animation: fadeUp 0.6s 0.9s both;
    }

    /* Divider */
    .divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 0 32px;
        animation: fadeUp 0.6s 1s both;
    }
    .divider::before, .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--line);
    }
    .divider span {
        font-size: 0.65rem;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: var(--muted);
        opacity: 0.7;
    }

    /* Button */
    .btn-go-home {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 36px;
        background: var(--ink);
        color: var(--cream);
        font-family: 'Outfit', sans-serif;
        font-size: 0.88rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        text-decoration: none;
        border-radius: 2px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        animation: fadeUp 0.6s 1.1s both;
    }
    .btn-go-home::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--accent2);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
    }
    .btn-go-home:hover::before { transform: scaleX(1); }
    .btn-go-home:hover { color: #fff; box-shadow: 4px 4px 0 var(--accent2); transform: translate(-2px,-2px); }
    .btn-go-home span, .btn-go-home svg { position: relative; z-index: 1; }

    /* Footer note */
    .error-ref {
        margin-top: 28px;
        font-size: 0.7rem;
        color: rgba(28,26,22,0.25);
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        letter-spacing: 1px;
        animation: fadeUp 0.6s 1.3s both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="wrap-404">
    <!-- Background decorations -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <!-- Floating paper shreds -->
    <div class="shred"></div>
    <div class="shred"></div>
    <div class="shred"></div>
    <div class="shred"></div>
    <div class="shred"></div>

    <div class="card-404">

        <!-- Rotating stamp -->
        <div class="stamp">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(192,135,74,0.6)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>

        <!-- Scanning search icon -->
        <div class="search-wrap">
            <div class="scan-ring"></div>
            <svg class="search-icon" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="34" cy="34" r="20" stroke="#1c1a16" stroke-width="2.5" fill="rgba(245,240,232,0.8)"/>
                <line x1="49" y1="49" x2="62" y2="62" stroke="#c0874a" stroke-width="3" stroke-linecap="round"/>
                <!-- Empty document lines inside lens -->
                <line x1="26" y1="30" x2="42" y2="30" stroke="rgba(28,26,22,0.2)" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="26" y1="35" x2="38" y2="35" stroke="rgba(28,26,22,0.15)" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="26" y1="40" x2="40" y2="40" stroke="rgba(28,26,22,0.1)" stroke-width="1.5" stroke-linecap="round"/>
                <!-- Question mark -->
                <text x="34" y="38" text-anchor="middle" font-size="14" font-family="Cormorant Garamond, serif" fill="rgba(192,135,74,0.5)" font-style="italic">?</text>
            </svg>
        </div>

        <div class="not-found-label">Page Not Found</div>

        <div class="num-404">4<em>0</em>4</div>

        <svg class="wavy-line" viewBox="0 0 120 12">
            <path class="wavy-path" d="M0,6 C10,1 20,11 30,6 C40,1 50,11 60,6 C70,1 80,11 90,6 C100,1 110,11 120,6"/>
        </svg>

        <div class="not-found-title">This record doesn't exist</div>

        <p class="not-found-desc">
            The page or resource you're looking for has been moved,<br>
            removed, or never existed. Check the URL and try again.
        </p>

        <div class="divider"><span>or navigate back</span></div>

        <a class="btn-go-home" href="/clinicapp/index.php">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Go to Home</span>
        </a>

        <p class="error-ref">HTTP 404 — The requested URI was not found on this server.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>