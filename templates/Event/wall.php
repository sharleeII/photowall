<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var array<\App\Model\Entity\Photo> $photos
 */
$this->disableAutoLayout();

$initialPhotos = array_map(fn ($p) => [
    'id'       => $p->id,
    'thumb'    => '/files/' . $event->id . '/thumb/' . $p->filename_thumb,
    'uploader' => $p->uploader_name,
    'ts'       => $p->created->getTimestamp(),
], $photos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= h($event->title) ?> · Live Wall</title>

    <!-- Swiper 11 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <style>
    /* ─── Reset ─── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        width: 100%; height: 100%;
        background: #000;
        overflow: hidden;
        font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        -webkit-font-smoothing: antialiased;
        color: #fff;
        cursor: none;
    }

    /* ─── Swiper container fills the screen ─── */
    .swiper {
        position: fixed !important;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }
    .swiper-wrapper { width: 100%; height: 100%; }
    .swiper-slide {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        /* Swiper creative adds its own transforms — overflow:hidden clips correctly */
    }

    /* ─── Bokeh background (blurred fill) ─── */
    .slide-bg {
        position: absolute;
        inset: -30px;
        background-size: cover;
        background-position: center;
        filter: blur(28px) brightness(0.38) saturate(1.4);
        transform: scale(1.1);
        will-change: transform;
    }
    /* A gentle drift on the active slide's bg */
    .swiper-slide-active .slide-bg {
        animation: bg-drift 20s ease-in-out infinite alternate;
    }
    @keyframes bg-drift {
        from { transform: scale(1.10) translate(0, 0); }
        to   { transform: scale(1.17) translate(-2%, -1.5%); }
    }

    /* ─── Vignette ─── */
    .slide-vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 50%,
            transparent 30%,
            rgba(0,0,0,.32) 65%,
            rgba(0,0,0,.72) 100%);
        pointer-events: none;
        z-index: 2;
    }

    /* ─── Main photo — object-fit:contain, centered, leave room for footer ─── */
    .slide-photo {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        /* 80px top-bar clearance + 130px footer clearance */
        padding: 80px 32px 130px;
        z-index: 3;
        will-change: transform;
    }
    .slide-photo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 4px;
        box-shadow: 0 24px 80px rgba(0,0,0,.7),
                    0 4px  20px rgba(0,0,0,.5),
                    0 0    0 1px rgba(255,255,255,.06);
        display: block;
        user-select: none;
        pointer-events: none;
    }

    /* ─── Ken Burns variants (4 rotating animations on .slide-photo wrapper) ─── */
    .kb-1 .slide-photo { animation: kb1 16s ease-in-out forwards; }
    .kb-2 .slide-photo { animation: kb2 16s ease-in-out forwards; }
    .kb-3 .slide-photo { animation: kb3 16s ease-in-out forwards; }
    .kb-4 .slide-photo { animation: kb4 16s ease-in-out forwards; }

    @keyframes kb1 {
        from { transform: scale(1.00) translate( 0%,    0%); }
        to   { transform: scale(1.07) translate(-1.2%, -0.8%); }
    }
    @keyframes kb2 {
        from { transform: scale(1.00) translate(  0%,   0%); }
        to   { transform: scale(1.07) translate( 1.2%,  0.8%); }
    }
    @keyframes kb3 {
        from { transform: scale(1.07) translate(-1%,  1%); }
        to   { transform: scale(1.00) translate( 1%, -1%); }
    }
    @keyframes kb4 {
        from { transform: scale(1.09) translate( 1%, -1%); }
        to   { transform: scale(1.00) translate(-1%,  0.5%); }
    }

    /* ─── Footer (uploader info) ─── */
    .slide-footer {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 90px 44px 42px;
        background: linear-gradient(to bottom,
            transparent 0%,
            rgba(0,0,0,.18) 35%,
            rgba(0,0,0,.70) 100%);
        display: flex;
        align-items: flex-end;
        gap: 20px;
        z-index: 4;
        /* Hidden by default — animated in when slide becomes active */
        opacity: 0;
        transform: translateY(16px);
        transition: opacity .75s ease .5s, transform .75s cubic-bezier(.22,1,.36,1) .5s;
    }
    /* Trigger: JS adds .swiper-slide-active which CSS picks up */
    .swiper-slide-active .slide-footer {
        opacity: 1;
        transform: translateY(0);
    }

    .avatar {
        width: 60px; height: 60px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.28);
        box-shadow: 0 4px 16px rgba(0,0,0,.4);
        letter-spacing: -.5px;
    }
    .uploader-block { flex: 1; min-width: 0; }
    .uploader-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: rgba(255,255,255,.50);
        margin-bottom: 5px;
    }
    .uploader-name {
        font-size: clamp(26px, 3.6vw, 44px);
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
        text-shadow: 0 3px 20px rgba(0,0,0,.55);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .slide-footer.anon .uploader-label { display: none; }
    .slide-footer.anon .uploader-name  {
        font-size: 15px;
        font-weight: 400;
        color: rgba(255,255,255,.3);
    }

    /* ─── Film-grain overlay ─── */
    #grain {
        position: fixed;
        inset: 0;
        z-index: 90;
        pointer-events: none;
        opacity: .035;
        /* Static SVG noise tile */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        background-size: 200px 200px;
    }

    /* ─── White flash on new photo ─── */
    #flash {
        position: fixed;
        inset: 0;
        z-index: 80;
        background: #fff;
        opacity: 0;
        pointer-events: none;
        transition: opacity .08s ease;
    }
    #flash.pop { opacity: .15; }

    /* ─── Toast pill ─── */
    #toast {
        position: fixed;
        top: 72px;
        left: 50%;
        transform: translateX(-50%) translateY(-24px);
        padding: 10px 24px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        box-shadow: 0 8px 32px rgba(0,0,0,.5);
        opacity: 0;
        transition: opacity .35s cubic-bezier(.22,1,.36,1), transform .35s cubic-bezier(.22,1,.36,1);
        pointer-events: none;
        z-index: 95;
    }
    #toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* ─── Top bar ─── */
    #topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        padding: max(env(safe-area-inset-top, 0px), 18px) 28px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(to bottom, rgba(0,0,0,.65) 0%, transparent 100%);
        z-index: 92;
        pointer-events: none;
        gap: 12px;
    }
    .live-pill {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(255,255,255,.10);
        backdrop-filter: blur(10px) saturate(1.8);
        -webkit-backdrop-filter: blur(10px) saturate(1.8);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 999px;
        padding: 6px 14px 6px 10px;
        flex-shrink: 0;
    }
    .live-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #ff3b30;
        animation: live-pulse 1.6s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes live-pulse {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(255,59,48,.55); }
        50%       { opacity: .45; box-shadow: 0 0 0 6px rgba(255,59,48,.0); }
    }
    .live-text {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #fff;
    }
    #event-name-top {
        flex: 1;
        font-size: 15px;
        font-weight: 700;
        color: rgba(255,255,255,.88);
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 8px;
    }
    #photo-counter {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,.50);
        flex-shrink: 0;
        min-width: 48px;
        justify-content: flex-end;
    }

    /* ─── Empty state ─── */
    #empty {
        position: fixed;
        inset: 0;
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        background: #0a0a0a;
        color: rgba(255,255,255,.22);
    }
    #empty .e-ico { font-size: 80px; line-height: 1; }
    #empty .e-txt { font-size: 24px; font-weight: 600; }
    #empty .e-sub { font-size: 14px; opacity: .6; margin-top: -4px; }
    #empty .e-pulse {
        width: 14px; height: 14px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,.18);
        border-top-color: rgba(255,255,255,.55);
        animation: spin 1s linear infinite;
        margin-top: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ─── Fullscreen button ─── */
    #fs-btn {
        position: fixed;
        bottom: 28px; right: 28px;
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,.09);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 96;
        opacity: 0;
        transition: opacity .3s ease, background .2s ease;
    }
    body:hover #fs-btn { opacity: 1; cursor: default; }
    #fs-btn:hover { background: rgba(255,255,255,.18); cursor: pointer !important; }
    #fs-btn svg { width: 20px; height: 20px; fill: rgba(255,255,255,.82); }
    </style>
</head>
<body>

<!-- Swiper -->
<div class="swiper" id="wall-swiper">
    <div class="swiper-wrapper" id="slides-wrapper">
        <!-- Slides injected by JS -->
    </div>
</div>

<div id="grain"></div>
<div id="flash"></div>

<!-- Top bar -->
<div id="topbar">
    <div class="live-pill">
        <div class="live-dot"></div>
        <span class="live-text">Live</span>
    </div>
    <div id="event-name-top"><?= h($event->title) ?></div>
    <div id="photo-counter">
        <span>📸</span>
        <span id="count-num"><?= count($photos) ?></span>
    </div>
</div>

<div id="toast"></div>

<!-- Empty state (shown only while pool is empty) -->
<div id="empty" <?= !empty($photos) ? 'style="display:none"' : '' ?>>
    <div class="e-ico">📷</div>
    <div class="e-txt">Esperando las primeras fotos</div>
    <div class="e-sub">Escanea el QR y sube la primera</div>
    <div class="e-pulse"></div>
</div>

<!-- Fullscreen button -->
<button id="fs-btn" onclick="toggleFs()" title="Pantalla completa" aria-label="Pantalla completa">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
    </svg>
</button>

<!-- Swiper 11 JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
(function () {
    'use strict';

    /* ── Config ── */
    const SLUG       = <?= json_encode($event->slug) ?>;
    const ACCENT     = <?= json_encode($event->theme_color) ?>;
    const INTERVAL   = 7000;   // ms per slide (our own autoplay)
    const POLL_MS    = 3000;   // new-photo polling interval
    const SPEED      = 1400;   // Swiper transition speed

    /* ── State ── */
    let pool     = <?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    let latestTs = pool.length ? Math.max(...pool.map(p => p.ts)) : 0;
    let kbIdx    = 0;
    let autoTimer = null;

    /* ── DOM refs ── */
    const wrapper  = document.getElementById('slides-wrapper');
    const toastEl  = document.getElementById('toast');
    const flashEl  = document.getElementById('flash');
    const emptyEl  = document.getElementById('empty');
    const countEl  = document.getElementById('count-num');

    /* ── Ken Burns cycle (4 variants) ── */
    const KB = ['kb-1', 'kb-2', 'kb-3', 'kb-4'];
    function nextKb() { return KB[kbIdx++ % KB.length]; }

    /* ── Helpers ── */
    function initials(name) {
        if (!name) return '?';
        return name.trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }
    function updateCount() { countEl.textContent = pool.length; }

    /* ── Build a Swiper slide DOM node ── */
    function buildSlide(photo) {
        const slide = document.createElement('div');
        slide.className = 'swiper-slide ' + nextKb();

        /* Bokeh bg */
        const bg = document.createElement('div');
        bg.className = 'slide-bg';
        bg.style.backgroundImage = `url(${CSS.escape ? photo.thumb : photo.thumb})`;
        // CSS.escape not needed for URLs set via style — just assign directly
        bg.style.backgroundImage = `url("${photo.thumb}")`;
        slide.appendChild(bg);

        /* Vignette */
        const vig = document.createElement('div');
        vig.className = 'slide-vignette';
        slide.appendChild(vig);

        /* Main photo */
        const pw = document.createElement('div');
        pw.className = 'slide-photo';
        const img = document.createElement('img');
        img.src = photo.thumb;
        img.alt = photo.uploader ? `Foto de ${photo.uploader}` : 'Foto';
        img.loading = 'eager';
        img.decoding = 'async';
        pw.appendChild(img);
        slide.appendChild(pw);

        /* Footer */
        const hasName = !!(photo.uploader && photo.uploader.trim());
        const ft = document.createElement('div');
        ft.className = 'slide-footer' + (hasName ? '' : ' anon');

        if (hasName) {
            const av = document.createElement('div');
            av.className = 'avatar';
            av.style.background = ACCENT;
            av.textContent = initials(photo.uploader);
            ft.appendChild(av);
        }

        const blk = document.createElement('div');
        blk.className = 'uploader-block';

        const lbl = document.createElement('div');
        lbl.className = 'uploader-label';
        lbl.textContent = 'Foto de';

        const nm = document.createElement('div');
        nm.className = 'uploader-name';
        nm.textContent = photo.uploader || 'Anónimo';

        blk.appendChild(lbl);
        blk.appendChild(nm);
        ft.appendChild(blk);
        slide.appendChild(ft);

        return slide;
    }

    /* ── Initialise Swiper with creative effect ── */
    let swiper = null;

    function initSwiper() {
        swiper = new Swiper('#wall-swiper', {
            effect: 'creative',
            creativeEffect: {
                prev: {
                    shadow: true,
                    translate: [0, 0, -800],
                    rotate: [0, -8, 0],
                    opacity: 0.2,
                },
                next: {
                    translate: ['100%', 0, 0],
                },
            },
            speed: SPEED,
            allowTouchMove: false,
            loop: false,
            /* We drive autoplay ourselves — do NOT use Swiper's autoplay */
        });
    }

    /* ── Populate initial slides then boot Swiper ── */
    function bootstrap() {
        if (pool.length === 0) {
            /* Stay on empty state; Swiper still initialised (0 slides) */
            initSwiper();
            startAutoplay();
            return;
        }

        pool.forEach(photo => wrapper.appendChild(buildSlide(photo)));
        updateCount();
        emptyEl.style.display = 'none';

        initSwiper();
        startAutoplay();
    }

    /* ── Our own autoplay: setInterval calls swiper.slideNext() ── */
    function startAutoplay() {
        stopAutoplay();
        if (!swiper || swiper.slides.length < 1) return;
        autoTimer = setInterval(() => {
            if (!swiper || swiper.slides.length === 0) return;
            if (swiper.isEnd) {
                swiper.slideTo(0, SPEED);
            } else {
                swiper.slideNext(SPEED);
            }
        }, INTERVAL);
    }

    function stopAutoplay() {
        if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    /* ── Flash ── */
    function triggerFlash() {
        flashEl.classList.add('pop');
        setTimeout(() => flashEl.classList.remove('pop'), 220);
    }

    /* ── Toast pill from top ── */
    let toastTimer = null;
    function showToast(uploaderName) {
        const msg = uploaderName ? `📸 Nueva foto de ${uploaderName}` : '📸 Nueva foto';
        toastEl.textContent = msg;
        toastEl.style.background = ACCENT;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('show'), 5000);
    }

    /* ── Polling for new photos ── */
    async function poll() {
        try {
            const url = `/e/${encodeURIComponent(SLUG)}/photos/since?since=${latestTs}`;
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.photos || data.photos.length === 0) return;

            const genuinelyNew = [];
            data.photos.forEach(p => {
                if (!pool.find(x => x.id === p.id)) {
                    pool.push(p);
                    if (p.ts > latestTs) latestTs = p.ts;
                    genuinelyNew.push(p);
                }
            });
            if (genuinelyNew.length === 0) return;

            updateCount();
            emptyEl.style.display = 'none';

            /* If Swiper not yet initialised with real slides, rebuild */
            if (!swiper || swiper.slides.length === 0) {
                /* Clear wrapper and rebuild from full pool */
                wrapper.innerHTML = '';
                pool.forEach(photo => wrapper.appendChild(buildSlide(photo)));
                if (swiper) swiper.destroy(true, true);
                kbIdx = 0;
                initSwiper();
                startAutoplay();
                return;
            }

            /* Append each new slide */
            genuinelyNew.forEach(p => {
                swiper.appendSlide(buildSlide(p));
            });

            const lastIdx = swiper.slides.length - 1;

            /* Bump autoplay timer reset so we get a clean 7s after landing */
            stopAutoplay();

            /* Flash + slide to new photo */
            triggerFlash();
            swiper.slideTo(lastIdx, SPEED);

            /* Toast for the last uploader */
            showToast(genuinelyNew.at(-1).uploader);

            /* Restart autoplay after transition settles */
            setTimeout(startAutoplay, SPEED + 200);

        } catch (_) { /* network hiccup — silently ignore */ }
    }

    /* ── Fullscreen ── */
    window.toggleFs = function () {
        if (document.fullscreenElement) {
            document.exitFullscreen?.();
        } else {
            document.documentElement.requestFullscreen?.();
        }
    };
    document.addEventListener('click', function (e) {
        if (e.target.closest('#fs-btn')) return; /* handled by its own onclick */
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        }
    });

    /* ── Boot ── */
    bootstrap();
    setInterval(poll, POLL_MS);

})();
</script>
</body>
</html>
