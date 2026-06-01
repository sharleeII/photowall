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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        width: 100%; height: 100%;
        background: #000;
        overflow: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
        cursor: none;
        -webkit-font-smoothing: antialiased;
    }

    /* ─── Stage ─── */
    #stage {
        position: fixed;
        inset: 0;
    }

    /* ─── Slide ─── */
    .slide {
        position: absolute;
        inset: 0;
        opacity: 0;
        transition: opacity 1.4s ease;
        overflow: hidden;
    }
    .slide.active { opacity: 1; }

    .slide-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        will-change: transform;
    }
    .slide.active .slide-img {
        animation: kb 14s ease-in-out forwards;
    }
    .slide.new-photo .slide-img {
        animation: kb-new 9s ease-in-out forwards;
    }
    @keyframes kb {
        from { transform: scale(1.00); }
        to   { transform: scale(1.08) translateY(-1%); }
    }
    @keyframes kb-new {
        from { transform: scale(1.06) translateY(1%); }
        to   { transform: scale(1.14); }
    }

    /* Bottom gradient + uploader info */
    .slide-footer {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 100px 44px 44px;
        background: linear-gradient(
            to bottom,
            transparent 0%,
            rgba(0,0,0,0.25) 40%,
            rgba(0,0,0,0.72) 80%,
            rgba(0,0,0,0.82) 100%
        );
        display: flex;
        align-items: flex-end;
        gap: 20px;
        opacity: 0;
        transform: translateY(6px);
        transition: opacity 0.9s ease 0.5s, transform 0.9s ease 0.5s;
    }
    .slide.active .slide-footer {
        opacity: 1;
        transform: translateY(0);
    }

    .avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        border: 2.5px solid rgba(255,255,255,0.35);
        letter-spacing: -0.02em;
    }
    .uploader-block { flex: 1; min-width: 0; }
    .uploader-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: rgba(255,255,255,0.55);
        margin-bottom: 5px;
    }
    .uploader-name {
        font-size: clamp(26px, 4vw, 46px);
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
        text-shadow: 0 3px 16px rgba(0,0,0,0.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .anon .uploader-label { display: none; }
    .anon .uploader-name {
        font-size: 18px;
        font-weight: 400;
        color: rgba(255,255,255,0.4);
    }

    /* ─── Top bar ─── */
    #topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        padding: env(safe-area-inset-top, 0px) 32px 0;
        padding-top: max(env(safe-area-inset-top, 0px), 20px);
        padding-bottom: 0;
        padding-left: 32px;
        padding-right: 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, transparent 100%);
        z-index: 10;
        pointer-events: none;
        padding-bottom: 40px;
    }
    .live-pill {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 999px;
        padding: 5px 13px 5px 10px;
    }
    .live-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #ff3b30;
        animation: blink 1.6s ease-in-out infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(255,59,48,0.5); }
        50% { opacity: 0.45; box-shadow: 0 0 0 5px rgba(255,59,48,0); }
    }
    .live-text {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #fff;
    }
    #event-name-top {
        font-size: 15px;
        font-weight: 700;
        color: rgba(255,255,255,0.88);
        letter-spacing: 0.01em;
        text-align: center;
        flex: 1;
        padding: 0 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #photo-counter {
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,0.55);
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 50px;
        justify-content: flex-end;
    }

    /* ─── New-photo toast ─── */
    #toast {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(-16px);
        padding: 11px 22px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        white-space: nowrap;
        box-shadow: 0 8px 30px rgba(0,0,0,0.45);
        opacity: 0;
        transition: opacity 0.35s ease, transform 0.35s ease;
        pointer-events: none;
        z-index: 20;
        backdrop-filter: blur(4px);
    }
    #toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* ─── Empty state ─── */
    #empty {
        position: fixed;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
        color: rgba(255,255,255,0.25);
        z-index: 5;
    }
    #empty .e-icon { font-size: 72px; }
    #empty .e-text { font-size: 22px; font-weight: 500; }
    #empty .e-sub  { font-size: 14px; opacity: .6; margin-top: -6px; }

    /* ─── Fullscreen button ─── */
    #fs-btn {
        position: fixed;
        bottom: 28px;
        right: 28px;
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.18);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 30;
        opacity: 0;
        transition: opacity 0.3s;
    }
    body:hover #fs-btn { opacity: 1; cursor: default; }
    #fs-btn svg { width: 20px; height: 20px; fill: rgba(255,255,255,0.85); }
    </style>
</head>
<body>

<div id="stage"></div>

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

<div id="empty" <?= !empty($photos) ? 'style="display:none"' : '' ?>>
    <div class="e-icon">📷</div>
    <div class="e-text">Esperando las primeras fotos</div>
    <div class="e-sub">Escanea el QR y sube la primera</div>
</div>

<button id="fs-btn" onclick="toggleFs()" title="Pantalla completa">
    <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
</button>

<script>
(function () {
    'use strict';
    const SLUG     = '<?= h($event->slug) ?>';
    const ACCENT   = '<?= h($event->theme_color) ?>';
    const INTERVAL = 7000;
    const POLL_MS  = 3000;

    let pool     = <?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES) ?>;
    let latestTs = pool.length ? Math.max(...pool.map(p => p.ts)) : 0;
    let idx      = 0;
    let timer    = null;
    let current  = null;

    const stage   = document.getElementById('stage');
    const toast   = document.getElementById('toast');
    const empty   = document.getElementById('empty');
    const countEl = document.getElementById('count-num');

    function initials(name) {
        if (!name) return '?';
        return name.trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }

    function updateCount() {
        countEl.textContent = pool.length;
    }

    function buildSlide(photo, isNew) {
        const wrap = document.createElement('div');
        wrap.className = 'slide' + (isNew ? ' new-photo' : '');

        const img = document.createElement('img');
        img.className = 'slide-img';
        img.src = photo.thumb;
        img.alt = '';
        img.loading = 'eager';
        wrap.appendChild(img);

        const footer = document.createElement('div');
        const hasName = !!photo.uploader;
        footer.className = 'slide-footer' + (hasName ? '' : ' anon');

        if (hasName) {
            const av = document.createElement('div');
            av.className = 'avatar';
            av.style.background = ACCENT;
            av.textContent = initials(photo.uploader);
            footer.appendChild(av);
        }

        const blk = document.createElement('div');
        blk.className = 'uploader-block';

        const lbl = document.createElement('div');
        lbl.className = 'uploader-label';
        lbl.textContent = 'Foto de';
        blk.appendChild(lbl);

        const nm = document.createElement('div');
        nm.className = 'uploader-name';
        nm.textContent = photo.uploader || 'Anónimo';
        blk.appendChild(nm);

        footer.appendChild(blk);
        wrap.appendChild(footer);
        return wrap;
    }

    function showSlide(photo, isNew = false) {
        const prev = current;
        if (prev) {
            prev.classList.remove('active');
            setTimeout(() => prev.remove(), 1800);
        }
        if (empty) empty.style.display = 'none';

        const el = buildSlide(photo, isNew);
        stage.appendChild(el);
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('active')));
        current = el;

        if (isNew) showToast(photo.uploader);
    }

    function showToast(name) {
        toast.textContent = name ? `📸 Nueva foto de ${name}` : '📸 Nueva foto';
        toast.style.background = ACCENT;
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('show'), 4500);
    }

    function next() {
        if (!pool.length) return;
        showSlide(pool[idx % pool.length]);
        idx++;
    }

    function startLoop() {
        if (timer) clearInterval(timer);
        if (pool.length) next();
        timer = setInterval(next, INTERVAL);
    }

    async function poll() {
        try {
            const r = await fetch(`/e/${SLUG}/photos/since?since=${latestTs}`, { cache: 'no-store' });
            if (!r.ok) return;
            const data = await r.json();
            if (!data.photos?.length) return;

            data.photos.forEach(p => {
                if (!pool.find(x => x.id === p.id)) {
                    pool.push(p);
                    if (p.ts > latestTs) latestTs = p.ts;
                }
            });
            updateCount();
            showSlide(data.photos.at(-1), true);
            clearInterval(timer);
            timer = setInterval(next, INTERVAL);
        } catch (_) {}
    }

    startLoop();
    setInterval(poll, POLL_MS);

    window.toggleFs = function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    };

    // Click anywhere = fullscreen (ideal for projector setup)
    document.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        }
    });
})();
</script>
</body>
</html>
