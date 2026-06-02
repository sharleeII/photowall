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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($event->title) ?> · Mosaico</title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        width: 100%; height: 100%;
        background: #111;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        overflow: hidden;
        -webkit-font-smoothing: antialiased;
        color: #fff;
    }

    /* ─── Top bar ─── */
    #topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        background: rgba(10,10,10,0.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255,255,255,0.08);
        z-index: 10;
    }
    .live-pill {
        display: flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .1em;
    }
    .live-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: #ff3b30;
        animation: blink 1.5s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
    #ev-title { font-size: 15px; font-weight: 700; color: rgba(255,255,255,.88); }
    #count-pill { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.5); display:flex; align-items:center; gap:5px; }

    /* ─── Grid: 4 cols × 3 rows filling viewport below topbar ─── */
    #grid {
        position: fixed;
        inset: 56px 0 0;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(3, 1fr);
        gap: 4px;
        padding: 4px;
    }

    /* ─── Cell ─── */
    .cell {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        background: #1e1e1e;
        cursor: pointer;
        transition: box-shadow .3s ease;
    }
    .cell img { width:100%; height:100%; object-fit:cover; display:block; }
    .cell-footer {
        position: absolute;
        bottom:0; left:0; right:0;
        padding: 28px 10px 8px;
        background: linear-gradient(to top, rgba(0,0,0,.8), transparent);
        font-size: 12px; font-weight: 700; color:#fff;
        white-space: nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .cell-empty { background: #1a1a1a; border-radius:8px; border:1.5px dashed rgba(255,255,255,.07); }

    .cell.entering { animation: cell-in .5s cubic-bezier(0.34,1.56,0.64,1) forwards; }
    @keyframes cell-in { from{opacity:0;transform:scale(.85)} to{opacity:1;transform:scale(1)} }

    .cell.newest { box-shadow: 0 0 0 3px var(--accent), 0 0 20px rgba(0,0,0,.5); }
    .cell.newest::after {
        content:'NUEVA'; position:absolute; top:8px; right:8px;
        background:var(--accent); color:#fff;
        font-size:9px; font-weight:900; letter-spacing:.1em;
        padding:2px 7px; border-radius:999px;
    }

    /* ─── Spotlight dim layer ─── */
    #sp-dim {
        position: fixed; inset: 56px 0 0;
        background: rgba(0,0,0,0);
        pointer-events: none; z-index: 20;
        transition: background .45s ease;
    }
    #sp-dim.on { background: rgba(0,0,0,.78); }

    /* ─── Spotlight card (the photo that zooms) ─── */
    #sp-card {
        position: fixed;
        display: none;
        z-index: 30;
        border-radius: 8px;
        overflow: hidden;
        pointer-events: none;
        transition:
            left .5s cubic-bezier(.4,0,.2,1),
            top .5s cubic-bezier(.4,0,.2,1),
            width .5s cubic-bezier(.4,0,.2,1),
            height .5s cubic-bezier(.4,0,.2,1),
            border-radius .5s ease,
            box-shadow .5s ease;
    }
    #sp-card img { width:100%; height:100%; object-fit:contain; background:#000; display:block; }
    #sp-card.open { box-shadow: 0 40px 100px rgba(0,0,0,.9); border-radius: 14px; }

    /* ─── Uploader name overlay when spotlighted ─── */
    #sp-name {
        position: fixed;
        bottom: 10vh; left: 0; right: 0;
        text-align: center;
        z-index: 31;
        pointer-events: none;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity .4s ease .3s, transform .4s ease .3s;
        display: none;
    }
    #sp-name.visible { opacity: 1; transform: translateY(0); }
    #sp-name .sp-label {
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .16em;
        color: rgba(255,255,255,.5); margin-bottom: 6px;
    }
    #sp-name .sp-uname {
        font-size: clamp(32px, 5vw, 68px);
        font-weight: 900; color: #fff;
        text-shadow: 0 4px 28px rgba(0,0,0,.8);
        line-height: 1;
    }

    /* ─── Toast ─── */
    #toast {
        position: fixed; top: 68px; left: 50%;
        transform: translateX(-50%) translateY(-20px);
        padding: 9px 20px; border-radius: 999px;
        font-size: 13px; font-weight: 700; color: #fff;
        white-space: nowrap; box-shadow: 0 6px 24px rgba(0,0,0,.4);
        opacity: 0; transition: opacity .3s, transform .3s;
        pointer-events: none; z-index: 50;
    }
    #toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

    /* ─── Fullscreen button ─── */
    #fs-btn {
        position: fixed; bottom: 18px; right: 18px;
        padding: 8px 16px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px;
        font-size: 12px; font-weight: 700; color: rgba(255,255,255,.8);
        cursor: pointer; z-index: 60; transition: background .2s;
    }
    #fs-btn:hover { background: rgba(255,255,255,.22); }

    /* ─── Empty state ─── */
    #empty {
        position: fixed; inset: 56px 0 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 14px;
        color: rgba(255,255,255,.2); z-index: 5;
    }
    #empty .e-ico { font-size: 64px; }
    #empty .e-txt { font-size: 20px; font-weight: 500; }
    </style>
</head>
<body>

<div id="topbar">
    <div class="live-pill"><div class="live-dot"></div><span>Live</span></div>
    <div id="ev-title"><?= h($event->title) ?></div>
    <div id="count-pill">📸 <span id="count-num">0</span></div>
</div>

<div id="grid"></div>

<div id="sp-dim"></div>
<div id="sp-card"><img id="sp-img" src="" alt=""></div>
<div id="sp-name">
    <div class="sp-label">Foto de</div>
    <div class="sp-uname" id="sp-uname-txt"></div>
</div>

<div id="toast"></div>

<div id="empty">
    <div class="e-ico">📷</div>
    <div class="e-txt">Esperando las primeras fotos...</div>
</div>

<button id="fs-btn" onclick="toggleFs()">⛶ Pantalla completa</button>

<script>
(function () {
    'use strict';

    const SLUG       = '<?= h($event->slug) ?>';
    const ACCENT     = '<?= h($event->theme_color) ?>';
    const POLL_MS    = 3000;
    const COLS = 4, ROWS = 3, MAX = 12;
    const SHOW_MS    = 5000;   // photo stays big
    const TRANS_MS   = 500;    // zoom transition
    const PAUSE_MS   = 1200;   // between spotlights

    document.documentElement.style.setProperty('--accent', ACCENT);

    let pool = [], latestTs = 0, spotIdx = 0, spotTimer = null;

    const gridEl    = document.getElementById('grid');
    const dim       = document.getElementById('sp-dim');
    const spCard    = document.getElementById('sp-card');
    const spImg     = document.getElementById('sp-img');
    const spName    = document.getElementById('sp-name');
    const spUname   = document.getElementById('sp-uname-txt');
    const toast     = document.getElementById('toast');
    const countEl   = document.getElementById('count-num');
    const emptyEl   = document.getElementById('empty');

    /* Build 12 empty placeholder cells */
    const cells = [];
    for (let i = 0; i < MAX; i++) {
        const d = document.createElement('div');
        d.className = 'cell-empty';
        gridEl.appendChild(d);
        cells.push({ el: d, photo: null });
    }

    function updateCount() { countEl.textContent = pool.length; }

    function pickCell() {
        const emp = cells.findIndex(c => !c.photo);
        if (emp !== -1) return emp;
        let oldest = 0, minTs = Infinity;
        cells.forEach((c,i) => { if (c.photo?.ts < minTs) { minTs = c.photo.ts; oldest = i; } });
        return oldest;
    }

    function placePhoto(photo, isNew) {
        const ci = pickCell();
        const cell = cells[ci];

        const div = document.createElement('div');
        div.className = 'cell' + (isNew ? ' entering newest' : '');

        const img = document.createElement('img');
        img.src = photo.thumb; img.alt = ''; img.loading = 'eager';
        div.appendChild(img);

        if (photo.uploader) {
            const ft = document.createElement('div');
            ft.className = 'cell-footer';
            ft.textContent = photo.uploader;
            div.appendChild(ft);
        }

        if (isNew) setTimeout(() => div.classList.remove('newest'), 8000);

        gridEl.replaceChild(div, cell.el);
        cell.el = div; cell.photo = photo;
    }

    function bootstrap(photos) {
        [...photos].sort((a,b)=>a.ts-b.ts).forEach(p => { pool.push(p); placePhoto(p, false); });
        if (photos.length) {
            latestTs = Math.max(...photos.map(p=>p.ts));
            emptyEl.style.display = 'none';
        }
        updateCount();
    }

    function addPhoto(photo) {
        if (pool.find(p=>p.id===photo.id)) return;
        pool.push(photo);
        if (photo.ts > latestTs) latestTs = photo.ts;
        placePhoto(photo, true);
        updateCount();
        emptyEl.style.display = 'none';
        showToast(photo.uploader);
    }

    /* ── Spotlight: zoom cell to center ── */
    function getTargetRect() {
        const vw = window.innerWidth, vh = window.innerHeight - 56;
        const size = Math.min(vw * 0.75, vh * 0.82);
        return { left:(vw-size)/2, top:56+(vh-size)/2, width:size, height:size };
    }

    function doSpotlight() {
        const occupied = cells.filter(c => c.photo);
        if (!occupied.length) { spotTimer = setTimeout(doSpotlight, 2000); return; }

        const c = occupied[spotIdx % occupied.length];
        spotIdx++;
        const photo = c.photo;
        const from  = c.el.getBoundingClientRect();
        const to    = getTargetRect();

        /* Place card at cell position, no transition */
        spCard.style.transition = 'none';
        spCard.style.left   = from.left   + 'px';
        spCard.style.top    = from.top    + 'px';
        spCard.style.width  = from.width  + 'px';
        spCard.style.height = from.height + 'px';
        spCard.style.borderRadius = '8px';
        spCard.style.display = 'block';
        spImg.src = photo.thumb;
        dim.classList.add('on');

        /* Animate to center */
        requestAnimationFrame(() => requestAnimationFrame(() => {
            spCard.style.transition =
                `left ${TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                `top ${TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                `width ${TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                `height ${TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                `border-radius ${TRANS_MS}ms ease,` +
                `box-shadow ${TRANS_MS}ms ease`;
            spCard.style.left   = to.left   + 'px';
            spCard.style.top    = to.top    + 'px';
            spCard.style.width  = to.width  + 'px';
            spCard.style.height = to.height + 'px';
            spCard.classList.add('open');

            if (photo.uploader) {
                spUname.textContent = photo.uploader;
                spName.style.display = 'block';
                setTimeout(() => spName.classList.add('visible'), 350);
            }
        }));

        /* Return to grid after SHOW_MS */
        spotTimer = setTimeout(() => closeSpotlight(from), SHOW_MS + TRANS_MS);
    }

    function closeSpotlight(from) {
        spName.classList.remove('visible');
        setTimeout(() => { spName.style.display = 'none'; }, 400);

        spCard.style.left   = from.left   + 'px';
        spCard.style.top    = from.top    + 'px';
        spCard.style.width  = from.width  + 'px';
        spCard.style.height = from.height + 'px';
        spCard.style.borderRadius = '8px';
        spCard.classList.remove('open');
        dim.classList.remove('on');

        setTimeout(() => {
            spCard.style.display = 'none';
            spotTimer = setTimeout(doSpotlight, PAUSE_MS);
        }, TRANS_MS + 80);
    }

    /* ── Toast ── */
    function showToast(name) {
        toast.textContent = name ? `📸 Nueva foto de ${name}` : '📸 Nueva foto';
        toast.style.background = ACCENT;
        toast.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    /* ── Poll ── */
    async function poll() {
        try {
            const r = await fetch(`/e/${SLUG}/photos/since?since=${latestTs}`, {cache:'no-store'});
            if (!r.ok) return;
            const data = await r.json();
            data.photos?.forEach(p => addPhoto(p));
        } catch(_) {}
    }

    /* ── Init ── */
    bootstrap(<?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES) ?>);
    spCard.style.display = 'none';
    setTimeout(() => { if (cells.some(c=>c.photo)) doSpotlight(); }, 2500);
    setInterval(poll, POLL_MS);

    /* ── Fullscreen ── */
    window.toggleFs = function() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    };
    document.addEventListener('fullscreenchange', () => {
        document.getElementById('fs-btn').textContent =
            document.fullscreenElement ? '✕ Salir' : '⛶ Pantalla completa';
    });
})();
</script>
</body>
</html>
