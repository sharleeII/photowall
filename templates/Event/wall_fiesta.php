<?php
/**
 * Event/wall_fiesta.php
 * Fullscreen Polaroid Party photo-wall — Fiesta / Quinceañera style
 *
 * CakePHP 5 vars:
 *   $event  — ->id, ->title, ->slug, ->theme_color
 *   $photos — Photo[] ->filename_thumb, ->uploader_name, ->created->getTimestamp()
 */
$this->disableAutoLayout();

$eventId    = h($event->id);
$eventTitle = h($event->title);
$eventSlug  = h($event->slug);
$accent     = h($event->theme_color ?: '#ff69b4');

// Build initial photo data for JS bootstrap
$initialPhotos = [];
$latestTs = 0;
foreach ($photos as $photo) {
    $ts = $photo->created->getTimestamp();
    if ($ts > $latestTs) $latestTs = $ts;
    $initialPhotos[] = [
        'thumb'    => '/files/' . $event->id . '/thumb/' . $photo->filename_thumb,
        'uploader' => h($photo->uploader_name),
        'ts'       => $ts,
    ];
}
$initialJson = json_encode($initialPhotos, JSON_HEX_TAG | JSON_HEX_APOS);
$latestTsJs  = (int) $latestTs;
$totalCount  = count($photos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $eventTitle ?> — Fiesta Polaroid 🎉</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

<style>
/* ── Reset & Base ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  width: 100%; height: 100%;
  overflow: hidden;
  background: #0a0a0a;
  font-family: system-ui, sans-serif;
  cursor: default;
}

/* ── Background: dark with radial spotlight + subtle noise ── */
#bg {
  position: fixed; inset: 0; z-index: 0;
  background:
    radial-gradient(ellipse 70% 60% at 50% 42%,
      #1e1220 0%,
      #0e0a14 40%,
      #05040a 100%);
}

/* SVG noise texture overlay */
#bg::after {
  content: '';
  position: absolute; inset: 0;
  opacity: 0.045;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size: 300px 300px;
  pointer-events: none;
}

/* Subtle coloured bokeh blobs */
#bg::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(circle 380px at 15%  75%, <?= $accent ?>22 0%, transparent 70%),
    radial-gradient(circle 280px at 85%  20%, #ffd70018 0%, transparent 70%),
    radial-gradient(circle 220px at 50%  90%, #00bcd418 0%, transparent 70%),
    radial-gradient(circle 160px at 70%  55%, #ff69b415 0%, transparent 70%);
  pointer-events: none;
  animation: bokehdrift 18s ease-in-out infinite alternate;
}

@keyframes bokehdrift {
  0%   { opacity: 0.8; transform: scale(1)   translateX(0px); }
  50%  { opacity: 1;   transform: scale(1.05) translateX(15px); }
  100% { opacity: 0.8; transform: scale(0.97) translateX(-10px); }
}

/* ── Confetti canvas lives behind polaroids ───────────────── */
#confetti-canvas {
  position: fixed; inset: 0; z-index: 1;
  pointer-events: none;
}

/* ── Photo stage ──────────────────────────────────────────── */
#stage {
  position: fixed; inset: 0; z-index: 2;
}

/* ── Polaroid card ────────────────────────────────────────── */
.polaroid {
  position: absolute;
  width: clamp(140px, 20vw, 220px);
  background: #fff;
  padding: 10px 10px 44px;
  border-radius: 2px;
  box-shadow:
    0 2px  4px  rgba(0,0,0,.40),
    0 8px  20px rgba(0,0,0,.55),
    0 18px 40px rgba(0,0,0,.30),
    inset 0 0 0 1px rgba(0,0,0,.08);
  transform: translate(-50%, -50%) rotate(0deg);
  transition:
    top     0.9s cubic-bezier(0.34, 1.56, 0.64, 1),
    left    0.05s ease,
    opacity 0.4s ease,
    transform 0.9s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.3s ease,
    scale   0.3s ease;
  will-change: top, opacity, transform;
  z-index: 3;
  cursor: default;
}

.polaroid img {
  width: 100%;
  height: 18vw;
  min-height: 100px;
  max-height: 200px;
  object-fit: cover;
  display: block;
  border-radius: 1px;
  /* slight vignette on the photo itself */
  box-shadow: inset 0 0 12px rgba(0,0,0,.18);
}

.polaroid .caption {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Caveat', cursive;
  font-size: clamp(13px, 1.4vw, 17px);
  font-weight: 600;
  color: #2a2a2a;
  letter-spacing: 0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 8px;
}

/* Newest highlight */
.polaroid.newest {
  z-index: 10 !important;
  scale: 1.08;
  box-shadow:
    0 4px  8px  rgba(0,0,0,.45),
    0 14px 35px rgba(0,0,0,.60),
    0 28px 55px rgba(0,0,0,.35),
    0  0   0  3px <?= $accent ?>,
    inset 0 0 0 1px rgba(0,0,0,.08);
}

/* Fade-out when evicted */
.polaroid.evicting {
  opacity: 0 !important;
  scale: 0.88;
  transition: opacity 0.5s ease, scale 0.5s ease;
}

/* ── Spotlight overlay ────────────────────────────────────── */
#sp-dim {
  position: fixed; inset: 0;
  z-index: 25;
  background: rgba(0,0,0,0);
  pointer-events: none;
  transition: background 0.5s ease;
}
#sp-dim.on {
  background: rgba(0,0,0,.82);
}

/* Spotlight card — renders as a big sharp polaroid */
#sp-card {
  position: fixed;
  display: none;
  z-index: 30;
  background: #fff;
  border-radius: 2px;
  overflow: hidden;
  box-shadow:
    0 4px 12px rgba(0,0,0,.50),
    0 12px 30px rgba(0,0,0,.40);
}
#sp-card.open {
  box-shadow:
    0 12px 50px rgba(0,0,0,.75),
    0 30px 80px rgba(0,0,0,.55),
    0 0 0 4px <?= $accent ?>,
    0 0 70px <?= $accent ?>88;
}
#sp-img {
  position: absolute;
  top: 10px; left: 10px; right: 10px; bottom: 52px;
  object-fit: cover;
  display: block;
  border-radius: 1px;
  width: calc(100% - 20px);
  height: calc(100% - 62px);
}
#sp-caption {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Caveat', cursive;
  font-size: clamp(20px, 3vw, 38px);
  font-weight: 700;
  color: #2a2a2a;
  padding: 0 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.02em;
}

/* Uploader name — appears below the spotlit polaroid */
#sp-name {
  position: fixed;
  left: 50%;
  top: 0; /* overridden by JS */
  z-index: 31;
  transform: translateX(-50%) translateY(18px);
  font-family: 'Caveat', cursive;
  font-size: clamp(28px, 4.5vw, 62px);
  font-weight: 700;
  color: #fff;
  text-shadow:
    0 0 30px <?= $accent ?>cc,
    0 0 60px <?= $accent ?>88,
    0 2px 6px rgba(0,0,0,.9);
  opacity: 0;
  pointer-events: none;
  white-space: nowrap;
  transition: opacity 0.4s ease, transform 0.4s ease;
}
#sp-name.visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

/* ── Top overlay ──────────────────────────────────────────── */
#overlay {
  position: fixed; top: 0; left: 0; right: 0;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 24px 12px;
  background: linear-gradient(to bottom, rgba(0,0,0,.72) 0%, transparent 100%);
  pointer-events: none;
}

/* Live dot */
#live-badge {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #fff;
  text-transform: uppercase;
}

#live-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: #e53935;
  box-shadow: 0 0 6px #e53935, 0 0 14px #e5393588;
  animation: pulse-dot 1.4s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { transform: scale(1);   opacity: 1; }
  50%       { transform: scale(1.5); opacity: 0.6; }
}

/* Event title */
#event-title {
  font-size: clamp(18px, 2.6vw, 38px);
  font-weight: 900;
  letter-spacing: 0.04em;
  color: #fff;
  text-shadow:
    0 0 20px <?= $accent ?>cc,
    0 0 40px <?= $accent ?>66,
    0 2px 4px rgba(0,0,0,.8);
  text-align: center;
  flex: 1;
  padding: 0 20px;
}

/* Photo counter */
#photo-counter {
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  backdrop-filter: blur(6px);
  padding: 5px 14px;
  border-radius: 20px;
  white-space: nowrap;
}

/* ── Toast notification ───────────────────────────────────── */
#toast-container {
  position: fixed;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 35;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  pointer-events: none;
}

.toast {
  background: <?= $accent ?>;
  color: #fff;
  padding: 12px 28px;
  border-radius: 40px;
  font-size: clamp(14px, 1.6vw, 20px);
  font-weight: 700;
  letter-spacing: 0.02em;
  box-shadow:
    0 4px 20px rgba(0,0,0,.5),
    0 0 30px <?= $accent ?>88;
  transform: translateY(-120px) scale(0.85);
  opacity: 0;
  transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
  white-space: nowrap;
  max-width: 90vw;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toast.show {
  transform: translateY(0) scale(1);
  opacity: 1;
}

.toast.hide {
  transform: translateY(-80px) scale(0.9);
  opacity: 0;
  transition: transform 0.3s ease, opacity 0.3s ease;
}

/* ── Empty state ──────────────────────────────────────────── */
#empty-state {
  position: fixed; inset: 0; z-index: 5;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20px;
  pointer-events: none;
}

#empty-polaroid {
  width: clamp(160px, 22vw, 240px);
  aspect-ratio: 4/5;
  border: 3px dashed rgba(255,255,255,.25);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: empty-breathe 2.4s ease-in-out infinite, empty-spin 8s linear infinite;
  position: relative;
}

@keyframes empty-breathe {
  0%, 100% { border-color: rgba(255,255,255,.15); box-shadow: 0 0 0px transparent; }
  50%       { border-color: <?= $accent ?>99;     box-shadow: 0 0 30px <?= $accent ?>44; }
}

@keyframes empty-spin {
  0%   { transform: rotate(-4deg); }
  25%  { transform: rotate(4deg); }
  50%  { transform: rotate(-2deg); }
  75%  { transform: rotate(3deg); }
  100% { transform: rotate(-4deg); }
}

#empty-polaroid .empty-icon {
  font-size: clamp(32px, 5vw, 56px);
  opacity: 0.5;
}

#empty-text {
  font-family: 'Caveat', cursive;
  font-size: clamp(18px, 2.4vw, 30px);
  color: rgba(255,255,255,.45);
  letter-spacing: 0.04em;
  text-align: center;
}

#empty-sub {
  font-size: 12px;
  color: rgba(255,255,255,.25);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ── Sparkle decorations ──────────────────────────────────── */
.sparkle {
  position: fixed;
  pointer-events: none;
  z-index: 1;
  font-size: clamp(10px, 1.5vw, 18px);
  opacity: 0;
  animation: sparkle-float linear infinite;
}

@keyframes sparkle-float {
  0%   { opacity: 0; transform: translateY(0)   rotate(0deg)   scale(0.5); }
  15%  { opacity: 0.7; }
  85%  { opacity: 0.6; }
  100% { opacity: 0; transform: translateY(-80px) rotate(360deg) scale(1.2); }
}

/* ── Fullscreen button ────────────────────────────────────── */
#fs-btn {
  position: fixed;
  bottom: 18px; right: 20px;
  z-index: 40;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.25);
  backdrop-filter: blur(6px);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 30px;
  cursor: pointer;
  letter-spacing: 0.05em;
  transition: background 0.2s;
}
#fs-btn:hover { background: rgba(255,255,255,.22); }
</style>
</head>

<body>

<!-- Background -->
<div id="bg"></div>

<!-- Confetti canvas -->
<canvas id="confetti-canvas"></canvas>

<!-- Floating sparkles (decorative) -->
<div class="sparkle" id="sp1"  style="left:5%;  bottom:10%; animation-duration:6s;  animation-delay:0s">✨</div>
<div class="sparkle" id="sp2"  style="left:22%; bottom:15%; animation-duration:8s;  animation-delay:1.2s">⭐</div>
<div class="sparkle" id="sp3"  style="left:45%; bottom:8%;  animation-duration:7s;  animation-delay:0.5s">✨</div>
<div class="sparkle" id="sp4"  style="left:68%; bottom:20%; animation-duration:9s;  animation-delay:2s">🌟</div>
<div class="sparkle" id="sp5"  style="left:85%; bottom:12%; animation-duration:6.5s;animation-delay:0.8s">✨</div>
<div class="sparkle" id="sp6"  style="left:12%; bottom:35%; animation-duration:11s; animation-delay:3s">⭐</div>
<div class="sparkle" id="sp7"  style="left:92%; bottom:40%; animation-duration:10s; animation-delay:1.7s">✨</div>

<!-- Photo stage -->
<div id="stage"></div>

<!-- Top overlay -->
<div id="overlay">
  <div id="live-badge">
    <div id="live-dot"></div>
    LIVE
  </div>
  <div id="event-title"><?= $eventTitle ?></div>
  <div id="photo-counter">📸 <span id="count-num"><?= $totalCount ?></span> fotos</div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- Empty state -->
<div id="empty-state">
  <div id="empty-polaroid">
    <span class="empty-icon">📷</span>
  </div>
  <div id="empty-text">Esperando las primeras fotos…</div>
  <div id="empty-sub">¡Sube tu foto desde la app!</div>
</div>

<!-- Spotlight: dim overlay + big polaroid card + name label -->
<div id="sp-dim"></div>
<div id="sp-card">
  <img id="sp-img" src="" alt="">
  <div id="sp-caption"></div>
</div>
<div id="sp-name"></div>

<!-- Fullscreen button -->
<button id="fs-btn">⛶ Pantalla completa</button>

<script>
(function () {
  'use strict';

  /* ── Config ─────────────────────────────────────────────── */
  const ACCENT        = '<?= $accent ?>';
  const EVENT_ID      = '<?= $eventId ?>';
  const EVENT_SLUG    = '<?= $eventSlug ?>';
  const POLL_INTERVAL = 3000;   // ms
  const MAX_ZONES     = 12;
  const CONFETTI_COLORS = [ACCENT, '#ffffff', '#ffd700', '#ff69b4', '#00bcd4', '#a855f7'];

  /* ── Spotlight config ───────────────────────────────────── */
  const SPOT_SHOW_MS  = 5000;   // hold at center (ms)
  const SPOT_TRANS_MS = 500;    // fly-in / fly-out (ms)
  const SPOT_PAUSE_MS = 1800;   // pause between spotlights (ms)

  /* ── Zone grid: 4 cols × 3 rows ─────────────────────────── */
  const COL_PCT = [12, 37, 63, 88];
  const ROW_PCT = [20, 50, 80];

  const ZONES = [];
  ROW_PCT.forEach(r => COL_PCT.forEach(c => ZONES.push({ x: c, y: r })));
  // ZONES[0..11]: each {x, y} in percentage of viewport

  /* ── State ───────────────────────────────────────────────── */
  let sinceTs      = <?= $latestTsJs ?>;
  let totalCount   = <?= $totalCount ?>;
  let zoneAge      = new Array(MAX_ZONES).fill(0); // lower = older
  let zoneEl       = new Array(MAX_ZONES).fill(null);
  let ageCounter   = 0;
  let newestEl     = null;

  /* ── Spotlight state ────────────────────────────────────── */
  let spotQueue = [];    // zone indices pending in current round
  let spotTimer = null;
  let spotFrom  = null;  // saved bounding rect for return animation

  /* ── DOM refs ────────────────────────────────────────────── */
  const stage      = document.getElementById('stage');
  const countNum   = document.getElementById('count-num');
  const toastCont  = document.getElementById('toast-container');
  const emptyState = document.getElementById('empty-state');
  const cfCanvas   = document.getElementById('confetti-canvas');

  /* ── Spotlight DOM refs ─────────────────────────────────── */
  const spDim     = document.getElementById('sp-dim');
  const spCard    = document.getElementById('sp-card');
  const spImg     = document.getElementById('sp-img');
  const spCaption = document.getElementById('sp-caption');
  const spName    = document.getElementById('sp-name');
  const fsBtn     = document.getElementById('fs-btn');

  /* ── confetti instance bound to our canvas ───────────────── */
  const myConfetti = confetti.create(cfCanvas, { resize: true, useWorker: true });

  /* ── Continuous gentle confetti drizzle ─────────────────── */
  function drizzle() {
    myConfetti({
      particleCount : 3,
      angle         : 90,
      spread        : 160,
      origin        : { x: Math.random(), y: -0.1 },
      colors        : CONFETTI_COLORS,
      startVelocity : 20,
      gravity       : 0.4,
      ticks         : 200,
      scalar        : 0.9,
      drift         : (Math.random() - 0.5) * 0.8,
    });
  }
  setInterval(drizzle, 400);

  /* ── Landing confetti burst ──────────────────────────────── */
  function burstAt(xPct, yPct) {
    myConfetti({
      particleCount : 60,
      spread        : 80,
      origin        : { x: xPct / 100, y: yPct / 100 },
      colors        : CONFETTI_COLORS,
      startVelocity : 25,
      gravity       : 0.55,
      ticks         : 220,
      scalar        : 1.1,
    });
  }

  /* ── Pick best zone (oldest / empty) ─────────────────────── */
  function pickZone() {
    // Find the zone with the smallest age value (oldest)
    let minAge = Infinity, idx = 0;
    for (let i = 0; i < MAX_ZONES; i++) {
      if (zoneAge[i] < minAge) { minAge = zoneAge[i]; idx = i; }
    }
    return idx;
  }

  /* ── Evict existing card in zone ─────────────────────────── */
  function evict(zoneIdx) {
    const el = zoneEl[zoneIdx];
    if (!el) return;
    el.classList.add('evicting');
    setTimeout(() => { if (el.parentNode) el.parentNode.removeChild(el); }, 600);
    zoneEl[zoneIdx] = null;
    if (newestEl === el) newestEl = null;
  }

  /* ── Build & place a polaroid ────────────────────────────── */
  function addPolaroid(photo, animate) {
    // Hide empty state on first photo
    emptyState.style.display = 'none';

    ageCounter++;
    const zone    = pickZone();
    const zData   = ZONES[zone];

    // Evict old card if present
    evict(zone);

    // Rotation: -14 to +14 deg, avoid near-zero
    let rot = (Math.random() * 28) - 14;
    if (Math.abs(rot) < 3) rot += rot >= 0 ? 3 : -3;

    // Landing rotation has a tiny extra wobble
    const landRot = rot + (Math.random() * 4 - 2);

    // Build element
    const card = document.createElement('div');
    card.className = 'polaroid';

    const safeUploader = photo.uploader
      ? photo.uploader.substring(0, 28)
      : 'Invitado';

    card.innerHTML = `
      <img src="${photo.thumb}" alt="${safeUploader}" loading="lazy">
      <div class="caption">${safeUploader}</div>
    `;

    // Start position: off-screen top, at target X
    card.style.left    = `${zData.x}%`;
    card.style.top     = '-20%';
    card.style.transform  = `translate(-50%, -50%) rotate(${rot}deg)`;
    card.style.opacity = animate ? '0' : '1';
    card.style.zIndex  = '3';

    stage.appendChild(card);

    if (animate) {
      // Trigger reflow then animate in
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          card.style.opacity = '1';
          card.style.top     = `${zData.y}%`;
          card.style.transform = `translate(-50%, -50%) rotate(${landRot}deg)`;

          // After landing, burst confetti
          setTimeout(() => burstAt(zData.x, zData.y), 600);
        });
      });
    } else {
      // Static placement (initial load)
      card.style.top       = `${zData.y}%`;
      card.style.transform = `translate(-50%, -50%) rotate(${landRot}deg)`;
    }

    // Mark newest
    if (newestEl) {
      newestEl.classList.remove('newest');
    }
    card.classList.add('newest');
    newestEl = card;

    // Update zone tracking
    zoneAge[zone] = ageCounter;
    zoneEl[zone]  = card;

    return card;
  }

  /* ── Toast ───────────────────────────────────────────────── */
  function showToast(uploaderName) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    const safe = uploaderName
      ? uploaderName.substring(0, 30)
      : 'alguien';
    toast.textContent = `🎉 ¡Nueva foto de ${safe}!`;
    toastCont.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add('show'));
    });

    setTimeout(() => {
      toast.classList.add('hide');
      toast.classList.remove('show');
      setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
    }, 5000);
  }

  /* ── Counter ─────────────────────────────────────────────── */
  function updateCounter(n) {
    totalCount = n;
    countNum.textContent = n;
  }

  /* ── Spotlight: queue helpers ────────────────────────────── */
  function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  function refillQueue() {
    const occupied = [];
    for (let i = 0; i < MAX_ZONES; i++) {
      if (zoneEl[i]) occupied.push(i);
    }
    spotQueue = shuffleArray(occupied);
  }

  /* ── Spotlight: target rect (centered, polaroid proportions) */
  function getTargetRect() {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    // Card height = 70% of viewport height; width = polaroid ratio ~0.80
    const h = vh * 0.70;
    const w = h * 0.80;
    return {
      left   : (vw - w) / 2,
      top    : (vh - h) / 2,
      width  : w,
      height : h,
    };
  }

  /* ── Spotlight: open ──────────────────────────────────────── */
  function doSpotlight() {
    // Refill queue when empty (start of new round)
    if (spotQueue.length === 0) refillQueue();
    if (spotQueue.length === 0) {
      spotTimer = setTimeout(doSpotlight, 2000);
      return;
    }

    // Pop next zone; skip any that were evicted while waiting
    let pol = null;
    while (spotQueue.length > 0) {
      const zoneIdx = spotQueue.pop();
      if (zoneEl[zoneIdx]) { pol = zoneEl[zoneIdx]; break; }
    }
    if (!pol) {
      spotTimer = setTimeout(doSpotlight, 500);
      return;
    }

    const polImg   = pol.querySelector('img');
    const polCapt  = pol.querySelector('.caption');
    const captText = polCapt ? polCapt.textContent.trim() : '';

    // Capture polaroid's current screen position
    spotFrom = pol.getBoundingClientRect();
    const to = getTargetRect();

    // Place sp-card over the polaroid (no transition yet)
    spCard.style.transition = 'none';
    spCard.style.left   = spotFrom.left   + 'px';
    spCard.style.top    = spotFrom.top    + 'px';
    spCard.style.width  = spotFrom.width  + 'px';
    spCard.style.height = spotFrom.height + 'px';

    spImg.src = polImg ? polImg.src : '';
    spCaption.textContent = captText;

    // Position name just below the target card
    spName.textContent  = captText;
    spName.style.top    = (to.top + to.height + 18) + 'px';
    spName.style.bottom = 'auto';

    spCard.style.display = 'block';
    spDim.classList.add('on');

    // Animate to center
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const tx = `left ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                   `top ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                   `width ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                   `height ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
                   `box-shadow ${SPOT_TRANS_MS}ms ease`;
        spCard.style.transition = tx;
        spCard.style.left   = to.left   + 'px';
        spCard.style.top    = to.top    + 'px';
        spCard.style.width  = to.width  + 'px';
        spCard.style.height = to.height + 'px';
        spCard.classList.add('open');

        // Show name after card arrives
        setTimeout(() => {
          if (captText && captText !== 'Invitado') {
            spName.classList.add('visible');
          }
        }, SPOT_TRANS_MS + 100);
      });
    });

    spotTimer = setTimeout(closeSpotlight, SPOT_SHOW_MS + SPOT_TRANS_MS);
  }

  /* ── Spotlight: close ─────────────────────────────────────── */
  function closeSpotlight() {
    if (!spotFrom) return;

    spName.classList.remove('visible');

    const tx = `left ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
               `top ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
               `width ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
               `height ${SPOT_TRANS_MS}ms cubic-bezier(.4,0,.2,1),` +
               `box-shadow ${SPOT_TRANS_MS}ms ease`;
    spCard.style.transition = tx;
    spCard.style.left   = spotFrom.left   + 'px';
    spCard.style.top    = spotFrom.top    + 'px';
    spCard.style.width  = spotFrom.width  + 'px';
    spCard.style.height = spotFrom.height + 'px';
    spCard.classList.remove('open');
    spDim.classList.remove('on');

    setTimeout(() => {
      spCard.style.display = 'none';
      spotFrom = null;
      spotTimer = setTimeout(doSpotlight, SPOT_PAUSE_MS);
    }, SPOT_TRANS_MS + 80);
  }

  /* ── Bootstrap initial photos ───────────────────────────── */
  function bootstrap() {
    const initial = <?= $initialJson ?>;
    if (initial.length === 0) {
      emptyState.style.display = '';
      return;
    }
    emptyState.style.display = 'none';

    // Place up to MAX_ZONES photos (most recent first for best zones)
    const toPlace = initial.slice(-MAX_ZONES);
    toPlace.forEach(p => addPolaroid(p, false));
  }

  /* ── Polling ─────────────────────────────────────────────── */
  async function poll() {
    try {
      const url = `/e/${EVENT_SLUG}/photos/since?since=${sinceTs}`;
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.photos || data.photos.length === 0) return;

      data.photos.forEach(p => {
        addPolaroid({
          thumb    : p.thumb,
          uploader : p.uploader,
          ts       : p.ts,
        }, true);
        showToast(p.uploader);
        if (p.ts > sinceTs) sinceTs = p.ts;
      });

      updateCounter(totalCount + data.photos.length);
    } catch (_) {
      // Network error — silently retry next tick
    }
  }

  /* ── Fullscreen ──────────────────────────────────────────── */
  function toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  }

  fsBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleFullscreen();
  });

  document.addEventListener('click', () => toggleFullscreen());

  /* ── Sparkle opacity animation ───────────────────────────── */
  document.querySelectorAll('.sparkle').forEach(el => {
    el.style.animationPlayState = 'running';
  });

  /* ── Init ────────────────────────────────────────────────── */
  bootstrap();
  setInterval(poll, POLL_INTERVAL);
  setTimeout(doSpotlight, 4000);   // start spotlight cycle after 4s

})();
</script>
</body>
</html>
