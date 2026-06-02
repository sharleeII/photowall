<?php
/**
 * Event/wall_feed.php
 * Instagram Feed–style photo wall — dark mode, 3 columns, auto-scroll.
 *
 * CakePHP 5 vars:
 *   $event  — ->id, ->title, ->slug, ->theme_color
 *   $photos — Photo[] ->filename_thumb, ->uploader_name, ->created->getTimestamp()
 */
$this->disableAutoLayout();

$eventId    = h($event->id);
$eventTitle = h($event->title);
$eventSlug  = h($event->slug);
$accent     = h($event->theme_color ?: '#e1306c');

// Hashtag from title: remove spaces, lowercase
$hashtag = '#' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $event->title));

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
<title><?= $eventTitle ?> — Feed</title>
<style>
/* ── Base ─────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:     #000000;
  --card:   #111111;
  --border: #262626;
  --text:   #f5f5f5;
  --muted:  #737373;
  --accent: <?= $accent ?>;
}

html, body {
  width: 100%; height: 100%;
  overflow: hidden;
  background: var(--bg);
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
  cursor: default;
}

/* ── Topbar ───────────────────────────────────────────────── */
#topbar {
  position: fixed; top: 0; left: 0; right: 0; height: 56px;
  z-index: 20;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px;
  background: rgba(0,0,0,.88);
  border-bottom: 1px solid var(--border);
  backdrop-filter: blur(14px);
}

#live-badge {
  display: flex; align-items: center; gap: 7px;
  font-size: 12px; font-weight: 700;
  letter-spacing: .10em; text-transform: uppercase;
  color: var(--text);
}
#live-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #e53935;
  box-shadow: 0 0 6px #e53935, 0 0 12px #e5393566;
  animation: blink 1.4s ease-in-out infinite;
}
@keyframes blink {
  0%,100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.5); opacity: .6; }
}

#event-title {
  font-size: clamp(16px, 2.2vw, 30px);
  font-weight: 700; color: var(--text);
  flex: 1; text-align: center; padding: 0 16px;
}

#photo-counter {
  font-size: 14px; font-weight: 600; color: var(--muted);
  background: var(--card); border: 1px solid var(--border);
  padding: 5px 12px; border-radius: 16px; white-space: nowrap;
}

/* ── Feed wrapper ─────────────────────────────────────────── */
#feed {
  position: fixed; top: 56px; left: 0; right: 0; bottom: 0;
  overflow-y: scroll; overflow-x: hidden;
  scrollbar-width: none;
  overflow-anchor: none;   /* prevent browser scroll-anchor fighting our scrollTop resets */
  transition: opacity .35s ease;
}
#feed::-webkit-scrollbar { display: none; }

#feed-inner {
  padding: 10px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
  gap: 10px;
  align-items: start;
}

/* Gradient fades top + bottom */
#feed-top-fade {
  position: fixed; top: 56px; left: 0; right: 0; height: 36px;
  background: linear-gradient(to bottom, #000 0%, transparent 100%);
  z-index: 15; pointer-events: none;
}
#feed-bot-fade {
  position: fixed; bottom: 0; left: 0; right: 0; height: 70px;
  background: linear-gradient(to top, #000 0%, transparent 100%);
  z-index: 15; pointer-events: none;
}

/* ── Post card ────────────────────────────────────────────── */
.post {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 4px;
  overflow: hidden;
}
.post.post-new {
  border-color: var(--accent);
  animation: post-slide-in .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes post-slide-in {
  from { opacity: 0; transform: translateY(-14px) scale(.97); }
  to   { opacity: 1; transform: none; }
}

/* Header */
.post-header {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
}

/* Avatar circle with Instagram gradient ring when name present */
.post-avatar {
  position: relative;
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: #fff;
  flex-shrink: 0; z-index: 0;
}
.post-avatar::before {
  content: '';
  position: absolute; inset: -2px; border-radius: 50%;
  background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
  z-index: -1;
}
.post-avatar .av-inner {
  width: 100%; height: 100%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: #fff;
  border: 2px solid var(--card);
}

.post-meta { flex: 1; min-width: 0; }
.post-username {
  font-size: 13px; font-weight: 600; color: var(--text);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.post-time { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* Photo */
.post-photo {
  position: relative;
  aspect-ratio: 1; overflow: hidden;
  background: #000;
}
.post-photo img {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
  transition: transform .3s ease;
}
.post-new .post-photo img { transform: scale(1.02); }
.post-photo .new-badge {
  position: absolute; top: 10px; left: 10px;
  background: var(--accent); color: #fff;
  font-size: 10px; font-weight: 700; letter-spacing: .07em;
  padding: 3px 8px; border-radius: 3px; text-transform: uppercase;
}

/* Actions bar */
.post-actions {
  display: flex; align-items: center; gap: 14px;
  padding: 10px 14px 5px;
}
.post-actions svg {
  width: 22px; height: 22px;
  stroke: var(--text); fill: none; stroke-width: 1.8;
  stroke-linecap: round; stroke-linejoin: round;
}

/* Caption */
.post-caption {
  padding: 3px 14px 13px;
  font-size: 13px; line-height: 1.5; color: var(--text);
}
.post-caption .uname { font-weight: 600; margin-right: 4px; }
.post-caption .tag   { color: var(--muted); }

/* ── Toast ────────────────────────────────────────────────── */
#toast-container {
  position: fixed; top: 68px; left: 50%; transform: translateX(-50%);
  z-index: 30; pointer-events: none;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.toast {
  background: var(--accent); color: #fff;
  padding: 10px 24px; border-radius: 26px;
  font-size: 14px; font-weight: 700;
  box-shadow: 0 4px 20px rgba(0,0,0,.6), 0 0 30px <?= $accent ?>66;
  transform: translateY(-70px) scale(.9); opacity: 0;
  transition: transform .45s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
  white-space: nowrap;
}
.toast.show  { transform: none; opacity: 1; }
.toast.hide  { transform: translateY(-50px) scale(.9); opacity: 0; transition: transform .3s ease, opacity .3s ease; }

/* ── Empty state ──────────────────────────────────────────── */
#empty-state {
  position: fixed; inset: 56px 0 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 14px; pointer-events: none;
}
#empty-icon  { font-size: 72px; opacity: .2; }
#empty-text  { font-size: 20px; color: var(--muted); }
#empty-sub   { font-size: 12px; color: var(--border); letter-spacing: .07em; text-transform: uppercase; }

/* ── Fullscreen button ────────────────────────────────────── */
#fs-btn {
  position: fixed; bottom: 16px; right: 18px; z-index: 40;
  background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.20);
  color: #fff; font-size: 13px; font-weight: 600;
  padding: 7px 15px; border-radius: 20px;
  cursor: pointer; letter-spacing: .04em;
  backdrop-filter: blur(6px); transition: background .2s;
}
#fs-btn:hover { background: rgba(255,255,255,.20); }
</style>
</head>
<body>

<!-- Topbar -->
<div id="topbar">
  <div id="live-badge"><div id="live-dot"></div>LIVE</div>
  <div id="event-title"><?= $eventTitle ?></div>
  <div id="photo-counter">📸 <span id="count-num"><?= $totalCount ?></span> fotos</div>
</div>

<!-- Gradient fades -->
<div id="feed-top-fade"></div>
<div id="feed-bot-fade"></div>

<!-- Feed -->
<div id="feed"><div id="feed-inner"></div></div>

<!-- Toast -->
<div id="toast-container"></div>

<!-- Empty state -->
<div id="empty-state">
  <div id="empty-icon">📸</div>
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
  const HASHTAG    = '<?= h($hashtag) ?>';
  const POLL_MS    = 3000;
  const MAX_POSTS  = 40;    // prune oldest beyond this
  const SCROLL_SPD = 0.55;  // px per rAF tick (~33px/s at 60fps)
  const NEW_PAUSE  = 5000;  // ms to pause scroll on new photo

  /* ── State ───────────────────────────────────────────────── */
  let sinceTs    = <?= $latestTsJs ?>;
  let totalCount = <?= $totalCount ?>;
  let posts      = [];   // DOM elements, newest first
  let scrollY    = 0;
  let paused     = false;
  let pauseTimer = null;

  /* ── DOM ─────────────────────────────────────────────────── */
  const feedEl    = document.getElementById('feed');
  const feedInner = document.getElementById('feed-inner');
  const countNum  = document.getElementById('count-num');
  const toastCont = document.getElementById('toast-container');
  const emptyEl   = document.getElementById('empty-state');
  const fsBtn     = document.getElementById('fs-btn');

  /* ── Avatar colors (hash name → gradient) ───────────────── */
  const AV_GRADS = [
    '#e1306c,#833ab4',
    '#f09433,#dc2743',
    '#0ea5e9,#6366f1',
    '#10b981,#0ea5e9',
    '#f59e0b,#ef4444',
    '#a855f7,#ec4899',
  ];
  function avatarGrad(name) {
    let h = 0;
    for (let i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) >>> 0;
    const pair = AV_GRADS[h % AV_GRADS.length].split(',');
    return `linear-gradient(135deg, ${pair[0]}, ${pair[1]})`;
  }

  /* ── Relative time ───────────────────────────────────────── */
  function timeAgo(ts) {
    const d = Math.floor(Date.now() / 1000 - ts);
    if (d < 60)    return 'hace un momento';
    if (d < 3600)  return `hace ${Math.floor(d / 60)} min`;
    return `hace ${Math.floor(d / 3600)}h`;
  }

  /* ── Build post DOM element ──────────────────────────────── */
  function createPost(photo, isNew) {
    const uname  = (photo.uploader || 'Invitado').substring(0, 30);
    const initLt = uname.charAt(0).toUpperCase();
    const imgSrc = `/files/${EVENT_ID}/thumb/${photo.thumb}`;
    const ago    = timeAgo(photo.ts);

    const el = document.createElement('div');
    el.className = 'post' + (isNew ? ' post-new' : '');

    el.innerHTML = `
      <div class="post-header">
        <div class="post-avatar">
          <div class="av-inner" style="background:${avatarGrad(uname)}">${initLt}</div>
        </div>
        <div class="post-meta">
          <div class="post-username">${uname}</div>
          <div class="post-time">${ago}</div>
        </div>
      </div>
      <div class="post-photo">
        <img src="${imgSrc}" alt="${uname}" loading="lazy">
        ${isNew ? '<div class="new-badge">Nueva</div>' : ''}
      </div>
      <div class="post-actions">
        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </div>
      <div class="post-caption">
        <span class="uname">${uname}</span><span class="tag">${HASHTAG}</span>
      </div>`;

    // Strip "new" styling after 8s
    if (isNew) {
      setTimeout(() => {
        el.classList.remove('post-new');
        el.style.borderColor = '';
        const badge = el.querySelector('.new-badge');
        if (badge) badge.remove();
      }, 8000);
    }

    return el;
  }

  /* ── Add post to feed ────────────────────────────────────── */
  function addPost(photo, isNew) {
    emptyEl.style.display = 'none';
    const el = createPost(photo, isNew);

    if (isNew) {
      feedInner.prepend(el);
      posts.unshift(el);

      // Prune oldest when over cap
      while (posts.length > MAX_POSTS) {
        const old = posts.pop();
        if (old.parentNode) old.parentNode.removeChild(old);
      }

      // Jump to top so new post is visible, then pause auto-scroll.
      // Use scrollTo(instant) — scrollTop assignment alone can be ignored
      // by browsers when scroll-anchor is repositioning content.
      scrollY = 0;
      feedEl.scrollTo({ top: 0, behavior: 'instant' });
      pauseScroll(NEW_PAUSE);
      showToast(photo.uploader);
    } else {
      feedInner.appendChild(el);
      posts.push(el);
    }
  }

  /* ── Pause ───────────────────────────────────────────────── */
  function pauseScroll(ms) {
    paused = true;
    clearTimeout(pauseTimer);
    pauseTimer = setTimeout(() => { paused = false; }, ms);
  }

  /* ── Toast ───────────────────────────────────────────────── */
  function showToast(uploaderName) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = `📸 ¡${(uploaderName || 'Alguien').substring(0, 24)} subió una foto!`;
    toastCont.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => {
      t.classList.add('hide'); t.classList.remove('show');
      setTimeout(() => t.remove(), 400);
    }, 4500);
  }

  /* ── Counter ─────────────────────────────────────────────── */
  function updateCounter(n) { totalCount = n; countNum.textContent = n; }

  /* ── Auto-scroll loop ────────────────────────────────────── */
  function scrollTick() {
    if (!paused) {
      const max = feedEl.scrollHeight - feedEl.clientHeight;
      if (max > 10) {
        if (scrollY < max) {
          scrollY += SCROLL_SPD;
          feedEl.scrollTop = scrollY;
        } else {
          // Reached bottom — fade, reset to top, fade back in
          feedEl.style.opacity = '0';
          paused = true;
          setTimeout(() => {
            scrollY = 0;
            feedEl.scrollTop = 0;
            feedEl.style.opacity = '1';
            paused = false;
          }, 380);
        }
      }
    }
    requestAnimationFrame(scrollTick);
  }

  /* ── Polling ─────────────────────────────────────────────── */
  async function poll() {
    try {
      const res = await fetch(`/e/${EVENT_SLUG}/photos/since?since=${sinceTs}`, { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.photos || !data.photos.length) return;
      data.photos.forEach(p => {
        addPost({ thumb: p.thumb, uploader: p.uploader, ts: p.ts }, true);
        if (p.ts > sinceTs) sinceTs = p.ts;
      });
      updateCounter(totalCount + data.photos.length);
    } catch (_) {}
  }

  /* ── Bootstrap ───────────────────────────────────────────── */
  function bootstrap() {
    const initial = <?= $initialJson ?>;
    // PHP returns newest-first; append in that order → newest at DOM top
    if (!initial.length) { emptyEl.style.display = ''; return; }
    emptyEl.style.display = 'none';
    initial.forEach(p => {
      const el = createPost(p, false);
      feedInner.appendChild(el);
      posts.push(el);
    });
  }

  /* ── Fullscreen ──────────────────────────────────────────── */
  function toggleFs() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else {
      document.exitFullscreen().catch(() => {});
    }
  }
  fsBtn.addEventListener('click', e => { e.stopPropagation(); toggleFs(); });
  document.addEventListener('click', toggleFs);

  /* ── Init ────────────────────────────────────────────────── */
  bootstrap();
  setInterval(poll, POLL_MS);
  setTimeout(scrollTick, 2500);   // start scrolling after 2.5s

})();
</script>
</body>
</html>
