<?php
/**
 * Event/wall_bento.php
 * "Vibes" — Neon Bento Grid wall for Gen-Z parties.
 *
 * 12-cell irregular bento (6×4 grid), glassmorphism cards,
 * pulsing neon borders, vibe tags, glitch-flash on new photos.
 */
$this->disableAutoLayout();

$eventId    = h($event->id);
$eventTitle = h($event->title);
$eventSlug  = h($event->slug);
$accent     = h($event->theme_color ?: '#ff0080');

$initialPhotos = [];
$latestTs = 0;
foreach ($photos as $photo) {
    $ts = $photo->created->getTimestamp();
    if ($ts > $latestTs) $latestTs = $ts;
    $initialPhotos[] = [
        'thumb'    => h($photo->filename_thumb),
        'uploader' => h($photo->uploader_name),
        'ts'       => $ts,
    ];
}
$initialJson = json_encode($initialPhotos, JSON_HEX_TAG | JSON_HEX_APOS);
$latestTsJs  = (int)$latestTs;
$totalCount  = count($photos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $eventTitle ?> ✨ Vibes</title>
<style>
/* ── Reset ────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  width: 100%; height: 100%;
  overflow: hidden;
  background: #06000e;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
  cursor: default;
}

/* ── Background aurora ────────────────────────────────────── */
#bg {
  position: fixed; inset: 0; z-index: 0;
  background:
    radial-gradient(ellipse 80% 60% at 15% 20%, <?= $accent ?>22 0%, transparent 55%),
    radial-gradient(ellipse 60% 50% at 85% 80%, #00f0ff18 0%, transparent 55%),
    radial-gradient(ellipse 50% 40% at 50% 50%, #7b00ff14 0%, transparent 60%),
    #06000e;
  animation: aurora-drift 16s ease-in-out infinite alternate;
}
@keyframes aurora-drift {
  0%   { filter: hue-rotate(0deg); }
  50%  { filter: hue-rotate(30deg); }
  100% { filter: hue-rotate(-20deg); }
}

/* Scan lines overlay */
#bg::after {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 2px,
    rgba(0,0,0,.12) 2px,
    rgba(0,0,0,.12) 4px
  );
  pointer-events: none;
  opacity: .35;
}

/* ── Top bar ──────────────────────────────────────────────── */
#topbar {
  position: fixed; top: 0; left: 0; right: 0; height: 52px;
  z-index: 30;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 22px;
  background: rgba(6,0,14,.80);
  border-bottom: 1px solid rgba(255,255,255,.07);
  backdrop-filter: blur(14px);
}

#live-badge {
  display: flex; align-items: center; gap: 7px;
  font-size: 11px; font-weight: 800;
  letter-spacing: .16em; text-transform: uppercase;
  color: #fff;
}
#live-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #ff0050;
  box-shadow: 0 0 6px #ff0050, 0 0 16px #ff005099;
  animation: blink 1.3s ease-in-out infinite;
}
@keyframes blink {
  0%,100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.6); opacity: .5; }
}

#event-title {
  font-size: clamp(15px, 2vw, 26px);
  font-weight: 900;
  letter-spacing: .04em;
  color: #fff;
  text-shadow: 0 0 30px <?= $accent ?>, 0 0 60px <?= $accent ?>66;
  flex: 1; text-align: center; padding: 0 16px;
}

#photo-counter {
  font-size: 13px; font-weight: 700;
  color: rgba(255,255,255,.55);
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  padding: 4px 12px; border-radius: 14px;
  backdrop-filter: blur(4px);
  white-space: nowrap;
}

/* ── Bento grid ───────────────────────────────────────────── */
#bento {
  position: fixed;
  top: 52px; left: 0; right: 0; bottom: 0;
  z-index: 2;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  grid-template-rows: repeat(4, 1fr);
  gap: 7px;
  padding: 7px;
}

/* Glitch flash on new photo */
#bento.glitch {
  animation: glitch-flash .55s cubic-bezier(.4,0,.2,1);
}
@keyframes glitch-flash {
  0%,100% { filter: none; }
  12%     { filter: brightness(1.9) saturate(0.2); }
  24%     { filter: hue-rotate(140deg) saturate(4) brightness(1.4); }
  36%     { filter: hue-rotate(-80deg) saturate(3) contrast(1.6); }
  50%     { filter: brightness(1.2) saturate(2); }
}

/* ── Bento cell positions (6 cols × 4 rows) ──────────────── */
.bc-0  { grid-column: 1/3; grid-row: 1/3; } /* 2×2 big     */
.bc-1  { grid-column: 3/5; grid-row: 1/2; } /* 2×1 wide    */
.bc-2  { grid-column: 5/7; grid-row: 1/3; } /* 2×2 big     */
.bc-3  { grid-column: 3/4; grid-row: 2/3; } /* 1×1 small   */
.bc-4  { grid-column: 4/5; grid-row: 2/3; } /* 1×1 small   */
.bc-5  { grid-column: 1/2; grid-row: 3/5; } /* 1×2 tall    */
.bc-6  { grid-column: 2/4; grid-row: 3/4; } /* 2×1 wide    */
.bc-7  { grid-column: 4/6; grid-row: 3/4; } /* 2×1 wide    */
.bc-8  { grid-column: 6/7; grid-row: 3/5; } /* 1×2 tall    */
.bc-9  { grid-column: 2/3; grid-row: 4/5; } /* 1×1 small   */
.bc-10 { grid-column: 3/5; grid-row: 4/5; } /* 2×1 wide    */
.bc-11 { grid-column: 5/6; grid-row: 4/5; } /* 1×1 small   */

/* ── Cell base ────────────────────────────────────────────── */
.cell {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  background: rgba(255,255,255,.03);
  /* neon border via CSS variable --neon set per-element */
  outline: 1.5px solid var(--neon, rgba(255,255,255,.15));
  box-shadow:
    0 0 12px var(--neon, transparent),
    inset 0 0 20px rgba(0,0,0,.4);
  animation: neon-pulse 3s ease-in-out infinite;
  transition: transform .3s cubic-bezier(.34,1.56,.64,1), outline .3s;
}
@keyframes neon-pulse {
  0%,100% { box-shadow: 0 0 8px var(--neon, transparent), inset 0 0 20px rgba(0,0,0,.4); outline-color: var(--neon, rgba(255,255,255,.15)); }
  50%      { box-shadow: 0 0 28px var(--neon, transparent), 0 0 60px color-mix(in srgb, var(--neon, transparent) 40%, transparent), inset 0 0 20px rgba(0,0,0,.4); }
}

/* Empty placeholder */
.cell-empty {
  display: flex; align-items: center; justify-content: center;
}
.cell-empty-icon {
  font-size: 2vw;
  opacity: .15;
  animation: spin-slow 8s linear infinite;
}
@keyframes spin-slow { to { transform: rotate(360deg); } }

/* Photo fill */
.cell img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}

/* Bottom name strip */
.cell-name {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 20px 10px 8px;
  background: linear-gradient(to top, rgba(0,0,0,.88) 0%, transparent 100%);
  font-size: clamp(10px, 1.1vw, 14px);
  font-weight: 700;
  color: #fff;
  letter-spacing: .03em;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  opacity: 0;
  transition: opacity .4s ease;
}
.cell:hover .cell-name,
.cell.newest .cell-name { opacity: 1; }

/* Vibe tag */
.cell-vibe {
  position: absolute; top: 8px; right: 8px;
  font-size: clamp(8px, .85vw, 11px);
  font-weight: 800;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: #fff;
  background: var(--neon, rgba(255,255,255,.2));
  padding: 3px 8px;
  border-radius: 6px;
  backdrop-filter: blur(4px);
  text-shadow: 0 1px 3px rgba(0,0,0,.6);
  opacity: 0;
  transition: opacity .4s ease;
}
.cell.newest .cell-vibe,
.cell:hover .cell-vibe { opacity: 1; }

/* Newest cell spotlight */
.cell.newest {
  outline-width: 2.5px;
  transform: scale(1.015);
  z-index: 5;
  animation: neon-pulse 3s ease-in-out infinite, cell-pop .5s cubic-bezier(.34,1.56,.64,1);
}
@keyframes cell-pop {
  from { transform: scale(0.92); opacity: .6; }
  to   { transform: scale(1.015); opacity: 1; }
}

/* Cell enter animation */
.cell.cell-enter {
  animation: cell-enter .45s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes cell-enter {
  from { opacity: 0; transform: scale(.88); filter: blur(4px); }
  to   { opacity: 1; transform: none;       filter: none; }
}

/* ── Floating emoji particles ─────────────────────────────── */
.particle {
  position: fixed;
  pointer-events: none;
  z-index: 50;
  font-size: clamp(18px, 2.5vw, 32px);
  animation: particle-fly 1.4s cubic-bezier(.2,.8,.4,1) forwards;
}
@keyframes particle-fly {
  0%   { opacity: 1; transform: translate(0, 0) scale(1) rotate(0deg); }
  100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(1.6) rotate(var(--dr)); }
}

/* ── Toast ────────────────────────────────────────────────── */
#toast-container {
  position: fixed; top: 62px; left: 50%; transform: translateX(-50%);
  z-index: 40; pointer-events: none;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.toast {
  background: <?= $accent ?>; color: #fff;
  padding: 10px 26px; border-radius: 30px;
  font-size: clamp(13px, 1.5vw, 18px); font-weight: 800;
  letter-spacing: .02em;
  box-shadow: 0 4px 24px rgba(0,0,0,.6), 0 0 40px <?= $accent ?>99;
  transform: translateY(-80px) scale(.88); opacity: 0;
  transition: transform .45s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
  white-space: nowrap;
}
.toast.show  { transform: none; opacity: 1; }
.toast.hide  { transform: translateY(-60px) scale(.9); opacity: 0; transition: .3s ease; }

/* ── Empty state ──────────────────────────────────────────── */
#empty-state {
  position: fixed; inset: 52px 0 0; z-index: 10;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 16px; pointer-events: none;
}
#empty-glow {
  font-size: clamp(48px, 8vw, 80px);
  animation: empty-spin 6s linear infinite;
  filter: drop-shadow(0 0 30px <?= $accent ?>);
}
@keyframes empty-spin {
  0%,100% { transform: rotate(-8deg) scale(1); }
  50%      { transform: rotate(8deg) scale(1.05); }
}
#empty-text {
  font-size: clamp(16px, 2.2vw, 26px); font-weight: 800;
  color: rgba(255,255,255,.4); letter-spacing: .04em;
}
#empty-sub {
  font-size: 12px; color: rgba(255,255,255,.2);
  letter-spacing: .1em; text-transform: uppercase;
}

/* ── Fullscreen btn ───────────────────────────────────────── */
#fs-btn {
  position: fixed; bottom: 16px; right: 18px; z-index: 40;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.7); font-size: 13px; font-weight: 600;
  padding: 7px 14px; border-radius: 20px; cursor: pointer;
  backdrop-filter: blur(6px); letter-spacing: .04em;
  transition: background .2s, color .2s;
}
#fs-btn:hover { background: rgba(255,255,255,.16); color: #fff; }
</style>
</head>
<body>

<div id="bg"></div>

<!-- Topbar -->
<div id="topbar">
  <div id="live-badge"><div id="live-dot"></div>LIVE</div>
  <div id="event-title"><?= $eventTitle ?></div>
  <div id="photo-counter">✨ <span id="count-num"><?= $totalCount ?></span></div>
</div>

<!-- Bento grid (cells injected by JS) -->
<div id="bento"></div>

<!-- Toast -->
<div id="toast-container"></div>

<!-- Empty state -->
<div id="empty-state">
  <div id="empty-glow">✨</div>
  <div id="empty-text">Esperando las primeras fotos…</div>
  <div id="empty-sub">Sube tu foto desde la app</div>
</div>

<!-- Fullscreen -->
<button id="fs-btn">⛶ Pantalla completa</button>

<script>
(function () {
  'use strict';

  /* ── Config ─────────────────────────────────────────────── */
  const ACCENT     = '<?= $accent ?>';
  const EVENT_ID   = '<?= $eventId ?>';
  const EVENT_SLUG = '<?= $eventSlug ?>';
  const POLL_MS    = 3000;
  const MAX_CELLS  = 12;

  /* ── Neon color palette ──────────────────────────────────── */
  const NEONS = [
    '#ff0080', // hot pink
    '#00f0ff', // cyan
    '#bf00ff', // purple
    '#ff6200', // orange
    '#00ff88', // neon green
    '#ffe600', // yellow
    '#ff3366', // red-pink
    '#0080ff', // electric blue
    '#ff00cc', // magenta
    '#00ffcc', // mint
    '#ff4400', // red-orange
    '#8800ff', // deep violet
  ];

  /* ── Vibe tags ───────────────────────────────────────────── */
  const VIBES = [
    'REINA 👑', 'SLAY ✨', 'ICON 💅', 'LIT 🔥',
    'NO CAP 💯', 'BESTIE 💗', 'VIBE ✌️', 'GLOW UP ⭐',
    'LEGEND 🏆', 'QUEEN', 'ICONIC', 'CUTE AF 🥺',
  ];

  /* ── Emoji burst pool ────────────────────────────────────── */
  const BURST_EMOJIS = ['✨','💅','🔥','👑','💗','⭐','🎉','💫','🥂','💃'];

  /* ── Bento zone order (index maps to CSS class bc-N) ─────── */
  const ZONE_COUNT = MAX_CELLS;

  /* ── State ───────────────────────────────────────────────── */
  let sinceTs    = <?= $latestTsJs ?>;
  let totalCount = <?= $totalCount ?>;
  let zoneAge    = new Array(ZONE_COUNT).fill(0);
  let zoneEl     = new Array(ZONE_COUNT).fill(null);
  let ageCounter = 0;
  let newestEl   = null;

  /* ── DOM ─────────────────────────────────────────────────── */
  const bento    = document.getElementById('bento');
  const countNum = document.getElementById('count-num');
  const toastEl  = document.getElementById('toast-container');
  const emptyEl  = document.getElementById('empty-state');
  const fsBtn    = document.getElementById('fs-btn');

  /* ── Init grid cells (empty placeholders) ────────────────── */
  function initGrid() {
    for (let i = 0; i < ZONE_COUNT; i++) {
      const ph = document.createElement('div');
      ph.className = `cell bc-${i} cell-empty`;
      ph.style.setProperty('--neon', NEONS[i % NEONS.length]);
      ph.innerHTML = `<span class="cell-empty-icon">✦</span>`;
      bento.appendChild(ph);
    }
  }

  /* ── Pick zone to evict (oldest) ─────────────────────────── */
  function pickZone() {
    let minAge = Infinity, idx = 0;
    for (let i = 0; i < ZONE_COUNT; i++) {
      if (zoneAge[i] < minAge) { minAge = zoneAge[i]; idx = i; }
    }
    return idx;
  }

  /* ── Random vibe for this photo ─────────────────────────── */
  function pickVibe(ts) {
    return VIBES[ts % VIBES.length];
  }

  /* ── Add photo to bento ──────────────────────────────────── */
  function addPhoto(photo, animate) {
    emptyEl.style.display = 'none';

    ageCounter++;
    const zone   = pickZone();
    const neon   = NEONS[zone % NEONS.length];
    const vibe   = pickVibe(photo.ts);
    const imgSrc = `/files/${EVENT_ID}/thumb/${photo.thumb}`;
    const uname  = (photo.uploader || '').substring(0, 28);

    // Build new cell
    const cell = document.createElement('div');
    cell.className = `cell bc-${zone}${animate ? ' cell-enter' : ''}`;
    cell.style.setProperty('--neon', neon);

    cell.innerHTML = `
      <img src="${imgSrc}" alt="${uname}" loading="lazy">
      <div class="cell-vibe">${vibe}</div>
      <div class="cell-name">${uname || 'Invitado'}</div>`;

    // Remove previous occupant: tracked cell on second+ visit, or placeholder on first visit
    const prev = zoneEl[zone] ?? bento.querySelector(`.bc-${zone}`);
    if (prev?.parentNode) prev.parentNode.removeChild(prev);

    bento.appendChild(cell);
    zoneAge[zone] = ageCounter;
    zoneEl[zone]  = cell;

    // Mark newest
    if (newestEl) newestEl.classList.remove('newest');
    cell.classList.add('newest');
    newestEl = cell;
    setTimeout(() => { if (newestEl === cell) { cell.classList.remove('newest'); newestEl = null; } }, 8000);

    return cell;
  }

  /* ── Glitch the whole grid ───────────────────────────────── */
  function triggerGlitch() {
    bento.classList.remove('glitch');
    void bento.offsetWidth; // reflow
    bento.classList.add('glitch');
    setTimeout(() => bento.classList.remove('glitch'), 600);
  }

  /* ── Burst emoji particles from cell ────────────────────── */
  function burstParticles(cellEl) {
    const rect = cellEl.getBoundingClientRect();
    const cx   = rect.left + rect.width  / 2;
    const cy   = rect.top  + rect.height / 2;
    const count = 8;
    for (let i = 0; i < count; i++) {
      const p = document.createElement('div');
      p.className = 'particle';
      p.textContent = BURST_EMOJIS[Math.floor(Math.random() * BURST_EMOJIS.length)];
      const angle = (i / count) * 2 * Math.PI + (Math.random() * .5);
      const dist  = 80 + Math.random() * 100;
      p.style.cssText = `
        left: ${cx}px; top: ${cy}px;
        --dx: ${Math.cos(angle) * dist}px;
        --dy: ${Math.sin(angle) * dist - 40}px;
        --dr: ${(Math.random() - .5) * 120}deg;
      `;
      document.body.appendChild(p);
      setTimeout(() => p.remove(), 1500);
    }
  }

  /* ── Toast ───────────────────────────────────────────────── */
  function showToast(name) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = `✨ ¡${(name || 'alguien').substring(0, 22)} acaba de subir una foto!`;
    toastEl.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => {
      t.classList.add('hide'); t.classList.remove('show');
      setTimeout(() => t.remove(), 350);
    }, 4500);
  }

  /* ── Counter ─────────────────────────────────────────────── */
  function updateCounter(n) { totalCount = n; countNum.textContent = n; }

  /* ── Bootstrap ───────────────────────────────────────────── */
  function bootstrap() {
    initGrid();
    const initial = <?= $initialJson ?>;
    if (!initial.length) { emptyEl.style.display = ''; return; }
    emptyEl.style.display = 'none';
    // Place newest photos (up to MAX_CELLS), oldest first to end on newest
    const toPlace = initial.slice(0, MAX_CELLS).reverse();
    toPlace.forEach(p => addPhoto(p, false));
  }

  /* ── Polling ─────────────────────────────────────────────── */
  async function poll() {
    try {
      const res = await fetch(`/e/${EVENT_SLUG}/photos/since?since=${sinceTs}`, { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.photos?.length) return;

      data.photos.forEach(p => {
        const cell = addPhoto({ thumb: p.thumb, uploader: p.uploader, ts: p.ts }, true);
        showToast(p.uploader);
        triggerGlitch();
        setTimeout(() => burstParticles(cell), 200);
        if (p.ts > sinceTs) sinceTs = p.ts;
      });
      updateCounter(totalCount + data.photos.length);
    } catch (_) {}
  }

  /* ── Fullscreen ──────────────────────────────────────────── */
  function toggleFs() {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen().catch(() => {});
    else document.exitFullscreen().catch(() => {});
  }
  fsBtn.addEventListener('click', e => { e.stopPropagation(); toggleFs(); });
  document.addEventListener('click', toggleFs);

  /* ── Init ────────────────────────────────────────────────── */
  bootstrap();
  setInterval(poll, POLL_MS);

})();
</script>
</body>
</html>
