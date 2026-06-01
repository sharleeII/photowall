<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Event $event */
/** @var \App\Model\Entity\Photo[] $photos */

$this->disableAutoLayout();

$accentColor = h($event->theme_color ?? '#e91e63');
$eventTitle  = h($event->title);
$eventSlug   = h($event->slug);
$eventId     = (int)$event->id;

// Sort photos newest-first; JS will add oldest-first so newest lands at top
$photoList = [];
foreach ($photos as $p) {
    $photoList[] = [
        'thumb'    => h($p->filename_thumb),
        'uploader' => h($p->uploader_name ?? ''),
        'ts'       => (int)$p->created->getTimestamp(),
    ];
}
usort($photoList, fn($a, $b) => $b['ts'] - $a['ts']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $eventTitle ?> · Mosaico</title>
<style>
/* ── Reset ───────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Root vars ───────────────────────────────────────────────── */
:root {
  --accent: <?= $accentColor ?>;
  --bg: #0d0d0d;
  --card-bg: #1a1a1a;
  --gap: 10px;
  --top-bar-h: 60px;
}

/* ── Body ─────────────────────────────────────────────────────── */
html, body {
  width: 100%; height: 100%;
  background: var(--bg);
  color: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
  cursor: none;
}

/* ── Noise texture overlay ───────────────────────────────────── */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  opacity: 0.025;
  pointer-events: none;
  z-index: 0;
}

/* ── Top bar ──────────────────────────────────────────────────── */
#top-bar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--top-bar-h);
  background: linear-gradient(to bottom, rgba(0,0,0,0.85) 0%, transparent 100%);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  z-index: 100;
  pointer-events: none;
}
.bar-live {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #fff;
}
.live-dot {
  width: 9px; height: 9px;
  border-radius: 50%;
  background: #e53935;
  animation: pulse-dot 1.4s ease-in-out infinite;
  flex-shrink: 0;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.45; transform: scale(0.75); }
}
.bar-title {
  font-size: 17px;
  font-weight: 700;
  color: #fff;
  text-align: center;
  max-width: 50%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bar-count {
  font-size: 13px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  white-space: nowrap;
}

/* ── Grid container ──────────────────────────────────────────── */
#grid {
  display: flex;
  gap: var(--gap);
  padding: calc(var(--top-bar-h) + 10px) var(--gap) var(--gap);
  width: 100%;
  height: 100vh;
  overflow: hidden;
  align-items: flex-start;
  position: relative;
  z-index: 1;
}
.col {
  display: flex;
  flex-direction: column;
  gap: var(--gap);
  flex: 1;
  min-width: 0;
}

/* ── Photo card ───────────────────────────────────────────────── */
.card {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  background: var(--card-bg);
  flex-shrink: 0;
  animation: card-enter 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.card img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  aspect-ratio: auto;
}
.card-overlay {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 100%);
  padding: 24px 10px 8px;
}
.card-name {
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── Newest highlight ─────────────────────────────────────────── */
.card.newest {
  box-shadow: 0 0 0 3px var(--accent), 0 12px 40px rgba(0,0,0,0.6);
}
.card.newest .badge-new {
  display: flex;
}
.badge-new {
  display: none;
  position: absolute;
  top: 8px; right: 8px;
  background: var(--accent);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  padding: 3px 7px;
  border-radius: 4px;
  text-transform: uppercase;
  line-height: 1.2;
  pointer-events: none;
  align-items: center;
}

/* ── Animations ───────────────────────────────────────────────── */
@keyframes card-enter {
  from { opacity: 0; transform: translateY(-24px) scale(0.92); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes card-exit {
  from { opacity: 1; transform: scale(1);    max-height: 400px; margin: 0; }
  to   { opacity: 0; transform: scale(0.88); max-height: 0;     margin: -10px 0 0; }
}

/* ── White flash overlay ──────────────────────────────────────── */
#flash {
  position: fixed;
  inset: 0;
  background: rgba(255,255,255,0.12);
  pointer-events: none;
  z-index: 200;
  opacity: 0;
  transition: opacity 0.05s ease;
}

/* ── Toast ────────────────────────────────────────────────────── */
#toast {
  position: fixed;
  top: 70px;
  left: 50%;
  transform: translateX(-50%) translateY(-20px);
  background: var(--accent);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  padding: 9px 22px;
  border-radius: 100px;
  white-space: nowrap;
  z-index: 300;
  opacity: 0;
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  pointer-events: none;
  max-width: 90vw;
  overflow: hidden;
  text-overflow: ellipsis;
}
#toast.show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

/* ── Fullscreen overlay ───────────────────────────────────────── */
#fullscreen-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.92);
  z-index: 500;
  align-items: center;
  justify-content: center;
  cursor: none;
}
#fullscreen-overlay.open {
  display: flex;
}
#fullscreen-overlay img {
  max-width: 95vw;
  max-height: 95vh;
  border-radius: 12px;
  object-fit: contain;
}

/* ── Empty state ──────────────────────────────────────────────── */
#empty-state {
  display: none;
  position: fixed;
  inset: 0;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 24px;
  z-index: 50;
  pointer-events: none;
}
#empty-state.visible {
  display: flex;
}
.empty-text {
  font-size: 22px;
  font-weight: 700;
  color: rgba(255,255,255,0.35);
  letter-spacing: 0.03em;
  position: relative;
  z-index: 1;
}
/* CSS grid lines decoration */
.empty-grid-lines {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.empty-grid-lines::before,
.empty-grid-lines::after {
  content: '';
  position: absolute;
  inset: 0;
}
.empty-grid-lines::before {
  background-image:
    linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 80px 80px;
}
.empty-grid-lines::after {
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
</head>
<body>

<!-- Top bar -->
<div id="top-bar">
  <div class="bar-live">
    <span class="live-dot"></span>
    <span>LIVE</span>
  </div>
  <div class="bar-title"><?= $eventTitle ?></div>
  <div class="bar-count" id="photo-count">📸 0 fotos</div>
</div>

<!-- Masonry grid: 4 columns -->
<div id="grid">
  <div class="col" id="col-0"></div>
  <div class="col" id="col-1"></div>
  <div class="col" id="col-2"></div>
  <div class="col" id="col-3"></div>
</div>

<!-- White flash -->
<div id="flash"></div>

<!-- Toast -->
<div id="toast"></div>

<!-- Fullscreen overlay -->
<div id="fullscreen-overlay">
  <img id="fullscreen-img" src="" alt="">
</div>

<!-- Empty state -->
<div id="empty-state">
  <div class="empty-grid-lines"></div>
  <div class="empty-text">Las fotos aparecerán aquí</div>
</div>

<script>
(function () {
  'use strict';

  /* ── Config ──────────────────────────────────────────────── */
  const EVENT_ID   = <?= $eventId ?>;
  const EVENT_SLUG = <?= json_encode($event->slug) ?>;
  const POLL_MS    = 3000;
  const MAX_CARDS  = 24;

  /* ── Column state ────────────────────────────────────────── */
  const cols = [
    document.getElementById('col-0'),
    document.getElementById('col-1'),
    document.getElementById('col-2'),
    document.getElementById('col-3'),
  ];
  const colCounts = [0, 0, 0, 0];
  let totalCards = 0;

  /* ── UI refs ─────────────────────────────────────────────── */
  const countEl   = document.getElementById('photo-count');
  const flashEl   = document.getElementById('flash');
  const toastEl   = document.getElementById('toast');
  const fsOverlay = document.getElementById('fullscreen-overlay');
  const fsImg     = document.getElementById('fullscreen-img');
  const emptyEl   = document.getElementById('empty-state');

  /* ── Polling state ────────────────────────────────────────── */
  let lastTs     = 0;
  let toastTimer = null;

  /* ── Column helpers ───────────────────────────────────────── */
  function shortestCol() {
    return colCounts.indexOf(Math.min(...colCounts));
  }
  function tallestCol() {
    return colCounts.indexOf(Math.max(...colCounts));
  }

  /* ── Misc helpers ─────────────────────────────────────────── */
  function updateCount() {
    countEl.textContent = '📸 ' + totalCards + ' foto' + (totalCards !== 1 ? 's' : '');
  }
  function showEmpty() { emptyEl.classList.add('visible'); }
  function hideEmpty() { emptyEl.classList.remove('visible'); }

  /* ── Build card DOM ───────────────────────────────────────── */
  function buildCard(photo, isNew) {
    const thumbSrc = '/files/' + EVENT_ID + '/thumb/' + photo.thumb;

    const card = document.createElement('div');
    card.className = 'card' + (isNew ? ' newest' : '');
    card.dataset.ts = photo.ts;

    // "NUEVA" badge (visible only when .newest class is active)
    const badge = document.createElement('div');
    badge.className = 'badge-new';
    badge.textContent = 'NUEVA';
    card.appendChild(badge);

    // Photo image
    const img = document.createElement('img');
    img.src     = thumbSrc;
    img.alt     = photo.uploader || 'Foto';
    img.loading = 'lazy';
    img.decoding = 'async';
    card.appendChild(img);

    // Name overlay (only when uploader name is present)
    if (photo.uploader) {
      const overlay = document.createElement('div');
      overlay.className = 'card-overlay';
      const name = document.createElement('div');
      name.className   = 'card-name';
      name.textContent = photo.uploader;
      overlay.appendChild(name);
      card.appendChild(overlay);
    }

    // Fullscreen on click
    card.addEventListener('click', function () {
      openFullscreen(thumbSrc);
    });

    // Strip .newest after 8 s
    if (isNew) {
      setTimeout(function () {
        card.classList.remove('newest');
      }, 8000);
    }

    return card;
  }

  /* ── Add card to grid ─────────────────────────────────────── */
  function addCard(photo, isNew) {
    const ci   = shortestCol();
    const card = buildCard(photo, isNew);
    cols[ci].prepend(card);   // new cards go to TOP of the column
    colCounts[ci]++;
    totalCards++;
    hideEmpty();
    updateCount();

    if (totalCards > MAX_CARDS) {
      removeOldest();
    }
  }

  /* ── Remove bottom card from tallest column ───────────────── */
  function removeOldest() {
    const ci   = tallestCol();
    const last = cols[ci].lastElementChild;
    if (!last) return;
    last.style.animation = 'card-exit 0.4s ease forwards';
    setTimeout(function () {
      if (last.parentNode) {
        last.parentNode.removeChild(last);
        colCounts[ci]--;
        totalCards--;
        updateCount();
        if (totalCards === 0) showEmpty();
      }
    }, 400);
  }

  /* ── White flash ──────────────────────────────────────────── */
  function triggerFlash() {
    flashEl.style.opacity = '1';
    setTimeout(function () { flashEl.style.opacity = '0'; }, 200);
  }

  /* ── Toast ────────────────────────────────────────────────── */
  function showToast(message) {
    if (toastTimer) {
      clearTimeout(toastTimer);
      toastEl.classList.remove('show');
    }
    toastEl.textContent = message;
    void toastEl.offsetWidth; // force reflow so transition fires
    toastEl.classList.add('show');
    toastTimer = setTimeout(function () {
      toastEl.classList.remove('show');
      toastTimer = null;
    }, 3500);
  }

  /* ── Fullscreen ───────────────────────────────────────────── */
  function openFullscreen(src) {
    fsImg.src = src;
    fsOverlay.classList.add('open');
  }
  fsOverlay.addEventListener('click', function () {
    fsOverlay.classList.remove('open');
    fsImg.src = '';
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      fsOverlay.classList.remove('open');
      fsImg.src = '';
    }
  });

  /* ── Bootstrap initial photos ─────────────────────────────── */
  // PHP delivered newest-first. We reverse so oldest is added first,
  // meaning the newest photo ends up at the top of a column.
  const initialPhotos = <?= json_encode($photoList, JSON_UNESCAPED_UNICODE) ?>;

  if (initialPhotos.length === 0) {
    showEmpty();
  } else {
    initialPhotos.slice().reverse().forEach(function (p) {
      addCard(p, false);
      if (p.ts > lastTs) lastTs = p.ts;
    });
  }

  /* ── Polling ──────────────────────────────────────────────── */
  function poll() {
    fetch('/e/' + EVENT_SLUG + '/photos/since?since=' + lastTs, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
      cache: 'no-store',
    })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (data) {
        const newPhotos = (data.photos || []);
        if (newPhotos.length === 0) return;

        // Sort ascending so the very newest ends up at top after prepend
        newPhotos.sort(function (a, b) { return a.ts - b.ts; });

        newPhotos.forEach(function (p) {
          addCard(p, true);
          if (p.ts > lastTs) lastTs = p.ts;
        });

        // Single flash per poll cycle
        triggerFlash();

        // Toast for the newest photo in this batch
        const newest = newPhotos[newPhotos.length - 1];
        const msg = newest.uploader
          ? '📸 ' + newest.uploader + ' subió una foto'
          : '📸 Nueva foto';
        showToast(msg);
      })
      .catch(function () { /* silent — keep polling */ });
  }

  setInterval(poll, POLL_MS);

})();
</script>
</body>
</html>