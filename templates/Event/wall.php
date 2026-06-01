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
<style>
/* ─── Reset ─── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    width: 100%; height: 100%;
    overflow: hidden;
    background: #080808;
    font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    -webkit-font-smoothing: antialiased;
    color: #fff;
}

/* ─── Stage ─── */
#stage {
    position: fixed;
    inset: 0;
    background: radial-gradient(ellipse 120% 80% at 50% 60%, #0f0f14 0%, #080808 100%);
    z-index: 1;
}

/* ─── Slide ─── */
.slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    overflow: hidden;
    z-index: 2;
    will-change: opacity, transform, clip-path;
}

/* Outgoing slide fades to nothing */
.slide.leaving {
    opacity: 0;
    transition: opacity 1s ease;
    z-index: 3;
    /* disable any incoming transition classes */
    animation: none;
}

/* ─── Transition effects — applied to incoming slide ─── */

/* tx-fade */
.tx-fade {
    opacity: 0;
    transition: opacity 1.4s ease;
}
.tx-fade.active {
    opacity: 1;
}

/* tx-iris */
.tx-iris {
    opacity: 1;
    clip-path: circle(0% at 50% 50%);
    transition: clip-path 1.2s ease;
}
.tx-iris.active {
    clip-path: circle(150% at 50% 50%);
}

/* tx-slide */
.tx-slide {
    opacity: 0;
    transform: translateX(8%);
    transition: transform 1s cubic-bezier(0.22, 1, 0.36, 1),
                opacity   1s cubic-bezier(0.22, 1, 0.36, 1);
}
.tx-slide.active {
    opacity: 1;
    transform: translateX(0);
}

/* tx-zoom */
.tx-zoom {
    opacity: 0;
    transform: scale(0.94);
    transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1),
                opacity   1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.tx-zoom.active {
    opacity: 1;
    transform: scale(1);
}

/* tx-wipe */
.tx-wipe {
    opacity: 1;
    clip-path: inset(0 100% 0 0);
    transition: clip-path 1.1s cubic-bezier(0.4, 0, 0.2, 1);
}
.tx-wipe.active {
    clip-path: inset(0 0% 0 0);
}

/* ─── Bokeh background ─── */
.slide-bg {
    position: absolute;
    inset: -40px;
    background-size: cover;
    background-position: center;
    filter: blur(32px) brightness(0.28) saturate(1.5);
    transform: scale(1.15);
    pointer-events: none;
}

/* ─── Photo wrapper — Ken Burns applied here ─── */
.slide-photo-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    /* clearance: 80px topbar, 140px footer */
    padding: 80px 32px 140px;
    z-index: 2;
}

/* Ken Burns variants */
.kb1 .slide-photo-wrap { animation: kb1 14s ease-in-out forwards; }
.kb2 .slide-photo-wrap { animation: kb2 14s ease-in-out forwards; }
.kb3 .slide-photo-wrap { animation: kb3 14s ease-in-out forwards; }
.kb4 .slide-photo-wrap { animation: kb4 14s ease-in-out forwards; }

@keyframes kb1 {
    from { transform: scale(1.00) translate(0%,     0%);    }
    to   { transform: scale(1.07) translate(-1.5%, -1.0%);  }
}
@keyframes kb2 {
    from { transform: scale(1.00) translate(0%,    0%);   }
    to   { transform: scale(1.07) translate(1.5%,  1.0%); }
}
@keyframes kb3 {
    from { transform: scale(1.07) translate(-1.0%,  1.0%); }
    to   { transform: scale(1.00) translate( 1.0%, -1.0%); }
}
@keyframes kb4 {
    from { transform: scale(1.08) translate(0%, 0%); }
    to   { transform: scale(1.00) translate(0%, 0%); }
}

.slide-photo-wrap img {
    display: block;
    max-height: 75vh;
    max-width: 85vw;
    object-fit: contain;
    border-radius: 6px;
    box-shadow:
        0 32px 80px rgba(0, 0, 0, 0.8),
        0 0 0 1px rgba(255, 255, 255, 0.06);
    user-select: none;
    pointer-events: none;
    will-change: transform;
}

/* ─── Vignette ─── */
.slide-vignette {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 55%,
        transparent 28%,
        rgba(0,0,0,0.28) 62%,
        rgba(0,0,0,0.68) 100%);
    pointer-events: none;
    z-index: 3;
}

/* ─── Footer (uploader info) ─── */
.slide-footer {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 100px 48px 44px;
    background: linear-gradient(to bottom,
        transparent 0%,
        rgba(0,0,0,0.15) 30%,
        rgba(0,0,0,0.72) 100%);
    z-index: 4;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.7s ease 0.5s, transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.5s;
}
.slide.active .slide-footer {
    opacity: 1;
    transform: translateY(0);
}

.footer-inner {
    display: inline-block;
    border-left: 4px solid var(--accent);
    border-radius: 2px;
    padding-left: 16px;
}
.footer-label {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    margin-bottom: 6px;
}
.footer-name {
    font-size: clamp(30px, 4.2vw, 52px);
    font-weight: 900;
    color: #fff;
    line-height: 1.02;
    text-shadow: 0 4px 24px rgba(0,0,0,0.6);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 80vw;
}

/* ─── Film grain overlay ─── */
#grain {
    position: fixed;
    inset: 0;
    z-index: 80;
    pointer-events: none;
    opacity: 0.03;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.82' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 220px 220px;
}

/* ─── White flash ─── */
#flash {
    position: fixed;
    inset: 0;
    z-index: 85;
    background: #fff;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.08s ease;
}
#flash.pop { opacity: 0.18; }

/* ─── Toast pill ─── */
#toast {
    position: fixed;
    top: 88px;
    left: 50%;
    transform: translateX(-50%) translateY(-28px);
    padding: 10px 22px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    box-shadow: 0 8px 32px rgba(0,0,0,0.55);
    opacity: 0;
    transition: opacity 0.35s cubic-bezier(0.22,1,0.36,1),
                transform 0.35s cubic-bezier(0.22,1,0.36,1);
    pointer-events: none;
    z-index: 95;
    letter-spacing: 0.01em;
}
#toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* ─── Top bar ─── */
#topbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 80px;
    padding: max(env(safe-area-inset-top, 0px), 18px) 28px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(to bottom, rgba(0,0,0,0.70) 0%, transparent 100%);
    z-index: 90;
    pointer-events: none;
    gap: 12px;
}

.live-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,0.10);
    backdrop-filter: blur(10px) saturate(1.8);
    -webkit-backdrop-filter: blur(10px) saturate(1.8);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 999px;
    padding: 6px 14px 6px 10px;
    flex-shrink: 0;
}
.live-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #ff3b30;
    animation: livepulse 1.6s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes livepulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(255,59,48,0.55); }
    50%       { opacity: 0.45; box-shadow: 0 0 0 6px rgba(255,59,48,0); }
}
.live-text {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #fff;
}

#event-title {
    flex: 1;
    font-size: 16px;
    font-weight: 300;
    letter-spacing: 0.04em;
    color: rgba(255,255,255,0.85);
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
    font-weight: 500;
    color: rgba(255,255,255,0.50);
    flex-shrink: 0;
    min-width: 48px;
    justify-content: flex-end;
}

/* ─── Fullscreen button ─── */
#fs-btn {
    position: fixed;
    bottom: 28px; right: 28px;
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255,255,255,0.09);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 96;
    opacity: 0;
    transition: opacity 0.3s ease, background 0.2s ease;
}
body:hover #fs-btn { opacity: 1; }
#fs-btn:hover { background: rgba(255,255,255,0.18); cursor: pointer; }
#fs-btn svg { width: 20px; height: 20px; fill: rgba(255,255,255,0.82); }

/* ─── Empty state ─── */
#empty {
    position: fixed;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    pointer-events: none;
}
#empty .e-ico { font-size: 64px; line-height: 1; opacity: 0.18; }
#empty .e-txt {
    font-size: 22px;
    font-weight: 300;
    letter-spacing: 0.06em;
    color: rgba(255,255,255,0.22);
}
#empty .e-spinner {
    width: 20px; height: 20px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.12);
    border-top-color: rgba(255,255,255,0.4);
    animation: spin 1.2s linear infinite;
    margin-top: 4px;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div id="stage"></div>

<div id="grain"></div>
<div id="flash"></div>

<!-- Top bar -->
<div id="topbar">
    <div class="live-pill">
        <div class="live-dot"></div>
        <span class="live-text">Live</span>
    </div>
    <div id="event-title"><?= h($event->title) ?></div>
    <div id="photo-counter">
        <span>📸</span>
        <span id="count-num"><?= count($photos) ?></span>
    </div>
</div>

<div id="toast"></div>

<!-- Empty state -->
<div id="empty"<?= !empty($photos) ? ' style="display:none"' : '' ?>>
    <div class="e-ico">📷</div>
    <div class="e-txt">Esperando las primeras fotos</div>
    <div class="e-spinner"></div>
</div>

<!-- Fullscreen button -->
<button id="fs-btn" title="Pantalla completa" aria-label="Pantalla completa">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
    </svg>
</button>

<script>
(function () {
    'use strict';

    /* ── Config ── */
    const EVENT_ID   = <?= json_encode($event->id) ?>;
    const SLUG       = <?= json_encode($event->slug) ?>;
    const ACCENT     = <?= json_encode($event->theme_color) ?>;
    const SLIDE_MS   = 7000;
    const POLL_MS    = 3000;
    const REMOVE_MS  = 1800; /* ms after going inactive before DOM removal */

    /* ── State ── */
    let pool     = <?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    let latestTs = pool.length ? Math.max(...pool.map(p => p.ts)) : 0;
    let idx      = 0;
    let current  = null; /* DOM element currently visible */

    /* ── Cycling indices ── */
    const TX = ['tx-fade', 'tx-iris', 'tx-slide', 'tx-zoom', 'tx-wipe'];
    const KB = ['kb1', 'kb2', 'kb3', 'kb4'];
    let txIdx = 0;
    let kbIdx = 0;
    function nextTx() { return TX[txIdx++ % TX.length]; }
    function nextKb() { return KB[kbIdx++ % KB.length]; }

    /* ── DOM refs ── */
    const stage    = document.getElementById('stage');
    const emptyEl  = document.getElementById('empty');
    const countEl  = document.getElementById('count-num');
    const toastEl  = document.getElementById('toast');
    const flashEl  = document.getElementById('flash');
    const fsBtn    = document.getElementById('fs-btn');

    /* ── Helpers ── */
    function updateCount() { countEl.textContent = pool.length; }

    /* ── Build one slide DOM node ── */
    function buildSlide(photo, txClass, kbClass) {
        const slide = document.createElement('div');
        slide.className = ['slide', txClass, kbClass].join(' ');
        slide.style.setProperty('--accent', ACCENT);

        /* Bokeh background */
        const bg = document.createElement('div');
        bg.className = 'slide-bg';
        bg.style.backgroundImage = 'url("' + photo.thumb + '")';
        slide.appendChild(bg);

        /* Vignette */
        const vig = document.createElement('div');
        vig.className = 'slide-vignette';
        slide.appendChild(vig);

        /* Photo wrapper (Ken Burns applied to this) */
        const pw = document.createElement('div');
        pw.className = 'slide-photo-wrap';
        const img = document.createElement('img');
        img.src = photo.thumb;
        img.alt = photo.uploader ? ('Foto de ' + photo.uploader) : 'Foto';
        img.loading = 'eager';
        img.decoding = 'async';
        pw.appendChild(img);
        slide.appendChild(pw);

        /* Footer */
        const ft = document.createElement('div');
        ft.className = 'slide-footer';

        const inner = document.createElement('div');
        inner.className = 'footer-inner';

        const lbl = document.createElement('div');
        lbl.className = 'footer-label';
        lbl.textContent = 'Foto de';

        const nm = document.createElement('div');
        nm.className = 'footer-name';
        nm.textContent = (photo.uploader && photo.uploader.trim()) ? photo.uploader.trim() : 'Anónimo';

        inner.appendChild(lbl);
        inner.appendChild(nm);
        ft.appendChild(inner);
        slide.appendChild(ft);

        return slide;
    }

    /* ── Show a slide: fade out current, animate in next ── */
    function showSlide(photo, isNew) {
        const txClass = nextTx();
        const kbClass = nextKb();
        const slide   = buildSlide(photo, txClass, kbClass);

        stage.appendChild(slide);

        /* If there is a currently visible slide, mark it leaving */
        if (current) {
            const old = current;
            old.classList.remove('active');
            old.classList.add('leaving');
            /* Remove from DOM after transition completes */
            setTimeout(() => { if (old.parentNode) old.parentNode.removeChild(old); }, REMOVE_MS);
        }

        current = slide;

        /* rAF trick: let the browser paint opacity:0 first, then apply .active */
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                slide.classList.add('active');
            });
        });
    }

    /* ── Advance to next photo in pool ── */
    function next() {
        if (pool.length === 0) return;
        showSlide(pool[idx]);
        idx = (idx + 1) % pool.length;
    }

    /* ── White flash ── */
    function triggerFlash() {
        flashEl.classList.add('pop');
        setTimeout(function () { flashEl.classList.remove('pop'); }, 220);
    }

    /* ── Toast ── */
    var toastTimer = null;
    function showToast(name) {
        toastEl.textContent = name ? ('📸 Nueva foto de ' + name) : '📸 Nueva foto';
        toastEl.style.background = ACCENT;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 5000);
    }

    /* ── Polling ── */
    async function poll() {
        try {
            const url = '/e/' + encodeURIComponent(SLUG) + '/photos/since?since=' + latestTs;
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.photos || data.photos.length === 0) return;

            const added = [];
            data.photos.forEach(function (p) {
                if (!pool.find(function (x) { return x.id === p.id; })) {
                    pool.push(p);
                    if (p.ts > latestTs) latestTs = p.ts;
                    added.push(p);
                }
            });
            if (added.length === 0) return;

            updateCount();
            emptyEl.style.display = 'none';

            /* Show the newest photo immediately with flash + toast */
            const newest = added[added.length - 1];
            triggerFlash();
            showSlide(newest, true);
            /* Advance idx so next() continues from where we'll be after this */
            idx = pool.length % pool.length; /* wrap */
            /* Find position after newest in pool */
            const newestPoolIdx = pool.findIndex(function (x) { return x.id === newest.id; });
            idx = (newestPoolIdx + 1) % pool.length;

            showToast(newest.uploader);

        } catch (_) { /* network hiccup — silently ignore */ }
    }

    /* ── Fullscreen ── */
    function requestFs() {
        const el = document.documentElement;
        if (!document.fullscreenElement) {
            (el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || function(){}).call(el);
        }
    }
    function exitFs() {
        (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || function(){}).call(document);
    }
    fsBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (document.fullscreenElement) exitFs(); else requestFs();
    });
    document.addEventListener('click', function () {
        if (!document.fullscreenElement) requestFs();
    });

    /* ── Boot ── */
    updateCount();

    if (pool.length > 0) {
        emptyEl.style.display = 'none';
        next(); /* show first slide immediately */
    }

    setInterval(next, SLIDE_MS);
    setInterval(poll, POLL_MS);

})();
</script>
</body>
</html>
