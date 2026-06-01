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
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        cursor: none;
        -webkit-font-smoothing: antialiased;
    }

    /* ─── Stage ─── */
    #stage { position: fixed; inset: 0; }

    /* ─── Film-grain overlay (very subtle) ─── */
    #grain {
        position: fixed; inset: 0; z-index: 50;
        pointer-events: none;
        opacity: .035;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        background-repeat: repeat;
        background-size: 180px 180px;
    }

    /* ─── Slide base ─── */
    .slide {
        position: absolute;
        inset: 0;
        overflow: hidden;
        /* entrance handled by effect classes below */
    }

    /* ─── Bokeh background (blurred, fills screen) ─── */
    .slide-bg {
        position: absolute;
        inset: -30px;
        background-size: cover;
        background-position: center;
        filter: blur(28px) brightness(.38) saturate(1.3);
        transform: scale(1.08);
        will-change: transform;
        animation: bg-drift 16s ease-in-out forwards;
    }
    @keyframes bg-drift {
        from { transform: scale(1.08) translate(0,0); }
        to   { transform: scale(1.14) translate(-1.5%,-1%); }
    }

    /* Vignette */
    .slide-vignette {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,.55) 100%);
        pointer-events: none;
        z-index: 1;
    }

    /* ─── Main photo (centered, full visible) ─── */
    .slide-photo {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 24px 130px;
        z-index: 2;
        will-change: transform;
    }
    .slide-photo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 6px;
        box-shadow: 0 24px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.07);
        display: block;
    }

    /* ─── Bottom info ─── */
    .slide-footer {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 90px 44px 40px;
        background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,.22) 40%, rgba(0,0,0,.72) 100%);
        display: flex;
        align-items: flex-end;
        gap: 18px;
        z-index: 3;
        /* animated in by JS after slide becomes active */
        opacity: 0;
        transform: translateY(12px);
        transition: opacity .8s ease .45s, transform .8s ease .45s;
    }
    .slide.visible .slide-footer { opacity: 1; transform: translateY(0); }

    .avatar {
        width: 58px; height: 58px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,.3);
    }
    .uploader-block { flex: 1; min-width: 0; }
    .uploader-label {
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .13em;
        color: rgba(255,255,255,.52);
        margin-bottom: 4px;
    }
    .uploader-name {
        font-size: clamp(26px, 3.8vw, 46px);
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
        text-shadow: 0 3px 16px rgba(0,0,0,.5);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .anon .uploader-label { display: none; }
    .anon .uploader-name  { font-size: 16px; font-weight: 400; color: rgba(255,255,255,.35); }

    /* ───────── TRANSITION EFFECTS ───────── */

    /* 1 — simple crossfade (default) */
    .fx-fade   { opacity: 0; transition: opacity 1.3s ease; }
    .fx-fade.visible { opacity: 1; }

    /* 2 — zoom in from center */
    .fx-zoom   { opacity: 0; transform: scale(.94); transition: opacity 1.2s ease, transform 1.4s cubic-bezier(.22,1,.36,1); }
    .fx-zoom.visible { opacity: 1; transform: scale(1); }

    /* 3 — slide from right */
    .fx-right  { opacity: 0; transform: translateX(7%); transition: opacity .9s ease, transform 1s cubic-bezier(.22,1,.36,1); }
    .fx-right.visible { opacity: 1; transform: translateX(0); }

    /* 4 — slide from bottom */
    .fx-up     { opacity: 0; transform: translateY(6%); transition: opacity .9s ease, transform 1s cubic-bezier(.22,1,.36,1); }
    .fx-up.visible { opacity: 1; transform: translateY(0); }

    /* 5 — flip (horizontal scale) */
    .fx-flip   { opacity: 0; transform: scaleX(.88) translateX(-3%); transition: opacity .9s ease, transform 1.1s cubic-bezier(.34,1.56,.64,1); }
    .fx-flip.visible { opacity: 1; transform: scaleX(1) translateX(0); }

    /* ─── Ken Burns variations (applied to .slide-photo) ─── */
    .kb-1 .slide-photo { animation: kb1 14s ease-in-out forwards; }
    .kb-2 .slide-photo { animation: kb2 14s ease-in-out forwards; }
    .kb-3 .slide-photo { animation: kb3 14s ease-in-out forwards; }
    .kb-4 .slide-photo { animation: kb4 14s ease-in-out forwards; }

    @keyframes kb1 { from{transform:scale(1) translate(0,0)}        to{transform:scale(1.07) translate(-1.5%,-1%)} }
    @keyframes kb2 { from{transform:scale(1) translate(0,0)}        to{transform:scale(1.07) translate(1.5%,1%)}  }
    @keyframes kb3 { from{transform:scale(1.06) translate(-1%,1%)}  to{transform:scale(1)    translate(1%,-1%)}   }
    @keyframes kb4 { from{transform:scale(1.08) translate(1%,-1%)}  to{transform:scale(1)    translate(-1%,1%)}   }

    /* ─── NEW PHOTO — flash pulse ─── */
    #flash {
        position: fixed; inset: 0; z-index: 40;
        background: #fff;
        opacity: 0;
        pointer-events: none;
        transition: opacity .08s ease;
    }
    #flash.pop { opacity: .18; }

    /* ─── Toast ─── */
    #toast {
        position: fixed;
        top: 76px; left: 50%;
        transform: translateX(-50%) translateY(-14px);
        padding: 10px 22px;
        border-radius: 999px;
        font-size: 14px; font-weight: 700;
        color: #fff;
        white-space: nowrap;
        box-shadow: 0 8px 32px rgba(0,0,0,.45);
        opacity: 0;
        transition: opacity .35s ease, transform .35s ease;
        pointer-events: none;
        z-index: 60;
    }
    #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    /* ─── Top bar ─── */
    #topbar {
        position: fixed; top: 0; left: 0; right: 0;
        padding: max(env(safe-area-inset-top,0px),18px) 28px 36px;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(to bottom, rgba(0,0,0,.6) 0%, transparent 100%);
        z-index: 55; pointer-events: none;
    }
    .live-pill {
        display: flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,.11);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 999px;
        padding: 5px 14px 5px 10px;
    }
    .live-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #ff3b30;
        animation: blink 1.6s ease-in-out infinite;
    }
    @keyframes blink {
        0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(255,59,48,.5)}
        50%{opacity:.4;box-shadow:0 0 0 5px rgba(255,59,48,0)}
    }
    .live-text { font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #fff; }
    #event-name-top {
        font-size: 15px; font-weight: 700; color: rgba(255,255,255,.88);
        flex: 1; padding: 0 14px; text-align: center;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    #photo-counter {
        font-size: 13px; font-weight: 600;
        color: rgba(255,255,255,.5);
        display: flex; align-items: center; gap: 5px;
        min-width: 52px; justify-content: flex-end;
    }

    /* ─── Empty state ─── */
    #empty {
        position: fixed; inset: 0; z-index: 5;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 16px;
        color: rgba(255,255,255,.25);
    }
    #empty .e-ico { font-size: 72px; }
    #empty .e-txt { font-size: 22px; font-weight: 500; }
    #empty .e-sub { font-size: 14px; opacity: .6; margin-top: -6px; }

    /* ─── Fullscreen btn ─── */
    #fs-btn {
        position: fixed; bottom: 26px; right: 26px;
        width: 42px; height: 42px; border-radius: 11px;
        background: rgba(255,255,255,.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.16);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        z-index: 70; opacity: 0; transition: opacity .3s;
    }
    body:hover #fs-btn { opacity: 1; cursor: default; }
    #fs-btn svg { width: 20px; height: 20px; fill: rgba(255,255,255,.8); }
    </style>
</head>
<body>

<div id="stage"></div>
<div id="grain"></div>
<div id="flash"></div>

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
    <div class="e-ico">📷</div>
    <div class="e-txt">Esperando las primeras fotos</div>
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
    const INTERVAL = 7000;   // ms per slide
    const POLL_MS  = 3000;   // polling

    const FX  = ['fx-fade','fx-zoom','fx-right','fx-up','fx-flip'];
    const KBS = ['kb-1','kb-2','kb-3','kb-4'];

    let pool     = <?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES) ?>;
    let latestTs = pool.length ? Math.max(...pool.map(p => p.ts)) : 0;
    let idx      = 0;
    let timer    = null;
    let current  = null;
    let fxIdx    = 0;
    let kbIdx    = 0;

    const stage    = document.getElementById('stage');
    const toast    = document.getElementById('toast');
    const flash    = document.getElementById('flash');
    const empty    = document.getElementById('empty');
    const countEl  = document.getElementById('count-num');

    function initials(name) {
        if (!name) return '?';
        return name.trim().split(/\s+/).slice(0,2).map(w=>w[0].toUpperCase()).join('');
    }
    function updateCount() { countEl.textContent = pool.length; }

    function nextFx()  { const v = FX[fxIdx % FX.length];  fxIdx++;  return v; }
    function nextKb()  { const v = KBS[kbIdx % KBS.length]; kbIdx++;  return v; }

    function buildSlide(photo) {
        const el = document.createElement('div');
        el.className = `slide ${nextFx()} ${nextKb()}`;

        // Bokeh background
        const bg = document.createElement('div');
        bg.className = 'slide-bg';
        bg.style.backgroundImage = `url(${photo.thumb})`;
        el.appendChild(bg);

        // Vignette
        const vig = document.createElement('div');
        vig.className = 'slide-vignette';
        el.appendChild(vig);

        // Main photo (contain — portrait safe)
        const pw = document.createElement('div');
        pw.className = 'slide-photo';
        const img = document.createElement('img');
        img.src = photo.thumb;
        img.alt = '';
        img.loading = 'eager';
        pw.appendChild(img);
        el.appendChild(pw);

        // Footer
        const ft = document.createElement('div');
        const hasName = !!photo.uploader;
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
        blk.append(lbl, nm);
        ft.appendChild(blk);
        el.appendChild(ft);

        return el;
    }

    function triggerFlash() {
        flash.classList.add('pop');
        setTimeout(() => flash.classList.remove('pop'), 200);
    }

    function showSlide(photo, isNew = false) {
        const prev = current;
        if (prev) {
            prev.classList.remove('visible');
            setTimeout(() => prev.remove(), 1800);
        }
        if (empty) empty.style.display = 'none';
        if (isNew) triggerFlash();

        const el = buildSlide(photo);
        stage.appendChild(el);
        // Double RAF ensures transition triggers
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('visible')));
        current = el;

        if (isNew) showToast(photo.uploader);
    }

    function showToast(name) {
        toast.textContent = name ? `📸 Nueva foto de ${name}` : '📸 Nueva foto';
        toast.style.background = ACCENT;
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('show'), 5000);
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

    // ─── Fullscreen ───
    window.toggleFs = function() {
        document.fullscreenElement
            ? document.exitFullscreen?.()
            : document.documentElement.requestFullscreen?.();
    };
    document.addEventListener('click', () => {
        if (!document.fullscreenElement)
            document.documentElement.requestFullscreen?.();
    });
})();
</script>
</body>
</html>
