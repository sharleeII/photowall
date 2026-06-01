<?php
/**
 * Instagram Stories-style fullscreen photo wall slideshow.
 *
 * PHP vars:
 *   $event  — entity: ->id, ->title, ->slug, ->theme_color
 *   $photos — array of Photo entities: ->filename_thumb, ->uploader_name, ->created->getTimestamp()
 *
 * Polling: GET /e/{slug}/photos/since?since={ts}
 */
$this->disableAutoLayout();

$themeColor   = h($event->theme_color ?? '#E1306C');
$eventId      = (int) $event->id;
$eventTitle   = h($event->title);
$eventSlug    = h($event->slug);
$hashtag      = '#' . preg_replace('/[^a-zA-Z0-9]/', '', $event->slug);

// Seed JS array from server-side render (first batch, max 20)
$initialPhotos = array_slice($photos, 0, 20);
$photosJson    = json_encode(array_map(function ($p) use ($eventId) {
    return [
        'id'       => (int) ($p->id ?? 0),
        'thumb'    => '/files/' . $eventId . '/thumb/' . h($p->filename_thumb),
        'uploader' => h($p->uploader_name ?? 'Anónimo'),
        'ts'       => (int) $p->created->getTimestamp(),
    ];
}, $initialPhotos), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $eventTitle ?> · Stories</title>
<style>
/* ─── Reset & base ────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --accent:      <?= $themeColor ?>;
    --white:       #ffffff;
    --black:       #000000;
    --bar-h:       3px;
    --bar-gap:     3px;
    --ui-top:      44px;
    --safe-lr:     16px;
    --avatar-size: 36px;
    --font:        -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --duration:    7s;       /* per-story display time */
    --fade:        0.5s;
    --poll:        3000;
}

html, body {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: var(--black);
    font-family: var(--font);
    color: var(--white);
    cursor: none;
    -webkit-font-smoothing: antialiased;
    user-select: none;
}

/* ─── Main stage ──────────────────────────────────────────────────── */
#stage {
    position: fixed;
    inset: 0;
    background: var(--black);
    overflow: hidden;
}

/* ─── Photo layers (A/B crossfade) ───────────────────────────────── */
.photo-layer {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    opacity: 0;
    transition: opacity var(--fade) ease;
    will-change: opacity;
}
.photo-layer.visible  { opacity: 1; }
.photo-layer.hidden   { opacity: 0; }

/* subtle vignette so UI elements always read on any photo */
.photo-layer::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to bottom,
            rgba(0,0,0,.55) 0%,
            transparent 30%,
            transparent 65%,
            rgba(0,0,0,.60) 100%);
    pointer-events: none;
}

/* ─── Progress bars ───────────────────────────────────────────────── */
#progress-track {
    position: fixed;
    top: 10px;
    left: var(--safe-lr);
    right: var(--safe-lr);
    display: flex;
    gap: var(--bar-gap);
    z-index: 100;
    height: var(--bar-h);
}

.bar-slot {
    flex: 1;
    min-width: 0;
    border-radius: 2px;
    background: rgba(255,255,255,.22);
    overflow: hidden;
    position: relative;
}
.bar-slot .bar-fill {
    position: absolute;
    inset: 0;
    background: var(--white);
    transform-origin: left center;
    width: 0%;
}
.bar-slot.done .bar-fill  { width: 100%; }
.bar-slot.active .bar-fill {
    animation: fill var(--duration) linear forwards;
}

@keyframes fill {
    from { width: 0%; }
    to   { width: 100%; }
}

/* ─── Top info bar ────────────────────────────────────────────────── */
#top-bar {
    position: fixed;
    top: 20px;           /* below progress bars */
    left: var(--safe-lr);
    right: var(--safe-lr);
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 100;
    pointer-events: none;
}

#uploader-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

#avatar {
    width:  var(--avatar-size);
    height: var(--avatar-size);
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    color: var(--white);
    text-transform: uppercase;
    border: 2px solid rgba(255,255,255,.7);
    flex-shrink: 0;
    letter-spacing: 0.5px;
    text-shadow: none;
}

#uploader-name {
    font-weight: 800;
    font-size: 15px;
    color: var(--white);
    text-shadow: 0 1px 4px rgba(0,0,0,.6);
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#event-meta {
    text-align: right;
}
#event-meta .ev-title {
    font-size: 11px;
    font-weight: 700;
    color: rgba(255,255,255,.80);
    text-shadow: 0 1px 4px rgba(0,0,0,.6);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
}
#event-meta .ev-time {
    font-size: 11px;
    color: rgba(255,255,255,.60);
    margin-top: 1px;
    text-shadow: 0 1px 4px rgba(0,0,0,.5);
}

/* ─── LIVE pill ───────────────────────────────────────────────────── */
#live-pill {
    position: fixed;
    top: 54px;
    right: var(--safe-lr);
    display: flex;
    align-items: center;
    gap: 5px;
    background: rgba(0,0,0,.45);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 20px;
    padding: 4px 10px 4px 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.2px;
    color: var(--white);
    z-index: 100;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
#live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #FF3040;
    animation: pulse-dot 1.6s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes pulse-dot {
    0%, 100% { opacity: 1;   transform: scale(1);   }
    50%       { opacity: .4; transform: scale(0.65); }
}

/* ─── Photo counter ───────────────────────────────────────────────── */
#photo-counter {
    position: fixed;
    top: 57px;
    left: var(--safe-lr);
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,.50);
    z-index: 100;
    letter-spacing: .5px;
}

/* ─── Bottom hashtag strip ────────────────────────────────────────── */
#hashtag-strip {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    text-align: center;
    padding: 28px var(--safe-lr) 28px;
    background: linear-gradient(transparent, rgba(0,0,0,.40));
    z-index: 100;
    pointer-events: none;
}
#hashtag-strip span {
    font-size: clamp(26px, 5.5vw, 52px);
    font-weight: 900;
    color: rgba(255,255,255,.88);
    letter-spacing: -0.5px;
    text-shadow: 0 2px 16px rgba(0,0,0,.55);
}

/* ─── New-photo notification pill ────────────────────────────────── */
#notif-pill {
    position: fixed;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--accent);
    color: var(--white);
    font-size: 13px;
    font-weight: 700;
    padding: 9px 20px;
    border-radius: 30px;
    z-index: 200;
    white-space: nowrap;
    box-shadow: 0 4px 20px rgba(0,0,0,.4);
    transition: top 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
    max-width: calc(100vw - 32px);
    overflow: hidden;
    text-overflow: ellipsis;
}
#notif-pill.show { top: 72px; }

/* ─── Emoji reactions ─────────────────────────────────────────────── */
#reaction-container {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 150;
    overflow: hidden;
}
.reaction {
    position: absolute;
    font-size: clamp(24px, 4vw, 38px);
    bottom: 20%;
    animation: float-up 2.8s ease-out forwards;
    will-change: transform, opacity;
    line-height: 1;
}
@keyframes float-up {
    0%   { transform: translateY(0)   scale(1);   opacity: 1; }
    40%  { transform: translateY(-110px) scale(1.3); opacity: 1; }
    75%  { transform: translateY(-210px) scale(0.85); opacity: .7; }
    100% { transform: translateY(-250px) scale(0.8); opacity: 0; }
}

/* ─── Tap zones (hover reveal) ────────────────────────────────────── */
.tap-zone {
    position: fixed;
    top: 0;
    bottom: 0;
    width: 80px;
    z-index: 90;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: all;
    display: flex;
    align-items: center;
    cursor: none;
}
.tap-zone:hover { opacity: 1; }

#tap-left {
    left: 0;
    background: linear-gradient(to right, rgba(255,255,255,.12), transparent);
    justify-content: flex-start;
    padding-left: 14px;
}
#tap-right {
    right: 0;
    background: linear-gradient(to left, rgba(255,255,255,.12), transparent);
    justify-content: flex-end;
    padding-right: 14px;
}
.tap-zone svg {
    width: 24px;
    height: 24px;
    fill: rgba(255,255,255,.7);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,.4));
}

/* ─── Empty state ─────────────────────────────────────────────────── */
#empty-state {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    z-index: 50;
    text-align: center;
    padding: 32px;
    display: none;
}
#empty-state .empty-icon {
    font-size: 64px;
    animation: bob 3s ease-in-out infinite;
}
@keyframes bob {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-10px); }
}
#empty-state .empty-title {
    font-size: clamp(20px, 4vw, 32px);
    font-weight: 800;
    color: rgba(255,255,255,.75);
}
#empty-state .empty-sub {
    font-size: clamp(13px, 2vw, 17px);
    color: rgba(255,255,255,.35);
    font-weight: 500;
}
#empty-state .empty-hashtag {
    font-size: clamp(22px, 4.5vw, 40px);
    font-weight: 900;
    color: var(--accent);
    margin-top: 8px;
}
</style>
</head>
<body>

<!-- Photo stage -->
<div id="stage">
    <div class="photo-layer" id="layerA"></div>
    <div class="photo-layer" id="layerB"></div>
</div>

<!-- Progress bars -->
<div id="progress-track"></div>

<!-- Top info bar -->
<div id="top-bar">
    <div id="uploader-info">
        <div id="avatar">??</div>
        <div id="uploader-name">Cargando…</div>
    </div>
    <div id="event-meta">
        <div class="ev-title"><?= $eventTitle ?></div>
        <div class="ev-time" id="ev-time">hace un momento</div>
    </div>
</div>

<!-- LIVE pill -->
<div id="live-pill">
    <div id="live-dot"></div>
    <span>LIVE</span>
</div>

<!-- Photo counter -->
<div id="photo-counter">0 / 0</div>

<!-- Bottom hashtag -->
<div id="hashtag-strip"><span><?= $hashtag ?></span></div>

<!-- New-photo notification -->
<div id="notif-pill">📸 Foto nueva</div>

<!-- Emoji reactions -->
<div id="reaction-container"></div>

<!-- Tap zones (visual only) -->
<div class="tap-zone" id="tap-left">
    <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
</div>
<div class="tap-zone" id="tap-right">
    <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
</div>

<!-- Empty state -->
<div id="empty-state">
    <div class="empty-icon">📷</div>
    <div class="empty-title">Esperando fotos…</div>
    <div class="empty-sub">Sube una foto para arrancar</div>
    <div class="empty-hashtag"><?= $hashtag ?></div>
</div>

<script>
(function () {
'use strict';

/* ── Config ─────────────────────────────────────────────────── */
const SLUG         = <?= json_encode($event->slug) ?>;
const EVENT_ID     = <?= (int) $event->id ?>;
const DISPLAY_MS   = 7000;   // ms per slide
const FADE_MS      = 500;
const POLL_MS      = 3000;
const MAX_BARS     = 20;
const REACTIONS    = ['❤️','🔥','😍','🎉','👏','✨','💃'];

/* ── State ──────────────────────────────────────────────────── */
let photos       = [];          // full pool (max MAX_BARS)
let currentIdx   = 0;           // index in photos[] being shown
let slideTimer   = null;        // setTimeout handle for next advance
let slideStart   = 0;           // Date.now() when current slide started
let latestTs     = 0;           // max ts seen (for polling)
let isTransiting = false;

/* ── DOM refs ──────────────────────────────────────────────── */
const stage       = document.getElementById('stage');
const layerA      = document.getElementById('layerA');
const layerB      = document.getElementById('layerB');
const track       = document.getElementById('progress-track');
const avatar      = document.getElementById('avatar');
const uploaderEl  = document.getElementById('uploader-name');
const evTime      = document.getElementById('ev-time');
const counter     = document.getElementById('photo-counter');
const notifPill   = document.getElementById('notif-pill');
const reactionBox = document.getElementById('reaction-container');
const emptyState  = document.getElementById('empty-state');

let activeLayer   = layerA;
let inactiveLayer = layerB;

/* ── Helpers ─────────────────────────────────────────────────── */
function initials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return parts[0].slice(0, 2).toUpperCase();
}

function relativeTime(ts) {
    const diff = Math.floor((Date.now() / 1000) - ts);
    if (diff < 30)  return 'hace un momento';
    if (diff < 90)  return 'hace 1 min';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    return `hace ${Math.floor(diff / 3600)} h`;
}

function padStart2(n) { return String(n).padStart(2, '0'); }

/* ── Progress bars ───────────────────────────────────────────── */
function rebuildBars() {
    track.innerHTML = '';
    photos.forEach((_, i) => {
        const slot = document.createElement('div');
        slot.className = 'bar-slot';
        const fill = document.createElement('div');
        fill.className = 'bar-fill';
        slot.appendChild(fill);
        track.appendChild(slot);
    });
    syncBars();
}

function syncBars() {
    const slots = track.querySelectorAll('.bar-slot');
    slots.forEach((slot, i) => {
        slot.classList.remove('done', 'active');
        if (i < currentIdx)  slot.classList.add('done');
        if (i === currentIdx) slot.classList.add('active');
        // Reset animation so it restarts cleanly
        if (i === currentIdx) {
            const fill = slot.querySelector('.bar-fill');
            fill.style.animation = 'none';
            fill.getBoundingClientRect(); // reflow
            fill.style.animation = '';
        }
    });
}

/* ── Counter ─────────────────────────────────────────────────── */
function updateCounter() {
    counter.textContent = photos.length
        ? `${currentIdx + 1} / ${photos.length}`
        : '0 / 0';
}

/* ── Show photo ──────────────────────────────────────────────── */
function showPhoto(idx, animate) {
    if (!photos.length) {
        showEmpty();
        return;
    }
    hideEmpty();

    if (idx < 0) idx = 0;
    if (idx >= photos.length) idx = photos.length - 1;
    currentIdx = idx;

    const photo = photos[idx];

    // Crossfade: preload on inactive layer, then swap
    inactiveLayer.style.backgroundImage = `url('${photo.thumb}')`;

    const doSwap = () => {
        inactiveLayer.classList.add('visible');
        activeLayer.classList.remove('visible');
        // swap refs
        const tmp   = activeLayer;
        activeLayer   = inactiveLayer;
        inactiveLayer = tmp;
        isTransiting  = false;
    };

    if (animate) {
        isTransiting = true;
        // Start fade
        inactiveLayer.style.transition = `opacity ${FADE_MS}ms ease`;
        activeLayer.style.transition   = `opacity ${FADE_MS}ms ease`;
        requestAnimationFrame(() => requestAnimationFrame(doSwap));
        setTimeout(() => {
            inactiveLayer.style.transition = '';
            activeLayer.style.transition   = '';
        }, FADE_MS + 50);
    } else {
        // instant (first load)
        inactiveLayer.style.transition = 'none';
        activeLayer.style.transition   = 'none';
        doSwap();
    }

    // Update UI
    avatar.textContent     = initials(photo.uploader);
    uploaderEl.textContent = photo.uploader || 'Anónimo';
    evTime.textContent     = relativeTime(photo.ts);

    updateCounter();
    syncBars();

    // Schedule next
    clearTimeout(slideTimer);
    slideStart = Date.now();
    slideTimer = setTimeout(advanceSlide, DISPLAY_MS);
}

/* ── Advance / loop ──────────────────────────────────────────── */
function advanceSlide() {
    if (!photos.length) return;
    const nextIdx = (currentIdx + 1) % photos.length;
    showPhoto(nextIdx, true);
}

/* ── Empty state ─────────────────────────────────────────────── */
function showEmpty() {
    emptyState.style.display = 'flex';
    track.innerHTML = '';
    counter.textContent = '0 / 0';
}
function hideEmpty() {
    emptyState.style.display = 'none';
}

/* ── Emoji reactions ─────────────────────────────────────────── */
function fireReactions() {
    const count = 5 + Math.floor(Math.random() * 3); // 5-7
    for (let i = 0; i < count; i++) {
        setTimeout(() => {
            const el  = document.createElement('div');
            el.className = 'reaction';
            el.textContent = REACTIONS[Math.floor(Math.random() * REACTIONS.length)];
            el.style.left  = (8 + Math.random() * 84) + '%';
            el.style.animationDelay = '0s';
            reactionBox.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }, i * 150);
    }
}

/* ── New-photo notification ──────────────────────────────────── */
let notifHideTimer = null;
function showNotif(name) {
    clearTimeout(notifHideTimer);
    notifPill.textContent = `📸 ${name} compartió una foto`;
    notifPill.classList.add('show');
    notifHideTimer = setTimeout(() => notifPill.classList.remove('show'), 4000);
}

/* ── Seed initial photos ─────────────────────────────────────── */
function seedPhotos(raw) {
    photos = raw.slice(0, MAX_BARS);
    photos.forEach(p => { if (p.ts > latestTs) latestTs = p.ts; });
    if (photos.length) {
        rebuildBars();
        showPhoto(0, false);
    } else {
        showEmpty();
    }
}

/* ── Polling ─────────────────────────────────────────────────── */
function poll() {
    const url = `/e/${SLUG}/photos/since?since=${latestTs}`;
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data || !Array.isArray(data.photos) || !data.photos.length) return;

            const incoming = data.photos;
            let added = 0;

            incoming.forEach(p => {
                // dedupe by id
                const exists = photos.some(x => x.id === p.id);
                if (!exists) {
                    // enforce max pool
                    if (photos.length >= MAX_BARS) photos.shift();
                    photos.push({ id: p.id, thumb: p.thumb, uploader: p.uploader, ts: p.ts });
                    if (p.ts > latestTs) latestTs = p.ts;
                    added++;
                }
            });

            if (added > 0) {
                rebuildBars();
                // Show reactions + notif for the latest addition
                const newest = incoming[incoming.length - 1];
                fireReactions();
                showNotif(newest.uploader || 'Alguien');
                // If we were showing empty, start now
                if (photos.length === added) {
                    showPhoto(0, false);
                }
            }
        })
        .catch(() => {/* network glitch — ignore */});
}

/* ── Fullscreen on click ─────────────────────────────────────── */
document.addEventListener('click', () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    }
});

/* ── Relative time refresh (every 30s) ──────────────────────── */
setInterval(() => {
    if (photos.length && photos[currentIdx]) {
        evTime.textContent = relativeTime(photos[currentIdx].ts);
    }
}, 30000);

/* ── Boot ────────────────────────────────────────────────────── */
const INITIAL = <?= $photosJson ?>;
seedPhotos(INITIAL);
setInterval(poll, POLL_MS);
// First poll fires after 3s so we don't duplicate seeds
setTimeout(() => {
    if (INITIAL.length === 0) poll(); // if empty, poll immediately once
}, 500);

})();
</script>
</body>
</html>
