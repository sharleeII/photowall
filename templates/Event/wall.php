<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var array<\App\Model\Entity\Photo> $photos
 */
$this->assign('title', $event->title . ' · Live Wall');

$initialPhotos = array_map(fn ($p) => [
    'id'       => $p->id,
    'thumb'    => '/files/' . $event->id . '/thumb/' . $p->filename_thumb,
    'orig'     => '/files/' . $event->id . '/orig/' . $p->filename_original,
    'uploader' => $p->uploader_name,
    'ts'       => $p->created->getTimestamp(),
], $photos);
?>
<div id="wall" class="fixed inset-0 bg-black overflow-hidden">
    <!-- Photo container — slides animate here -->
    <div id="slide-container" class="absolute inset-0"></div>

    <!-- Event title overlay (fades out after 10s) -->
    <div id="event-title" class="absolute top-0 left-0 right-0 flex items-center justify-center pointer-events-none"
         style="padding-top: env(safe-area-inset-top, 24px);">
        <div class="px-5 py-2 rounded-full mt-4 text-white text-sm font-semibold backdrop-blur-sm"
             style="background: <?= h($event->theme_color) ?>cc">
            <?= h($event->title) ?>
        </div>
    </div>

    <!-- "New photo" badge -->
    <div id="new-badge" class="fixed bottom-6 left-1/2 -translate-x-1/2 hidden pointer-events-none">
        <div class="px-4 py-2 rounded-full text-white text-sm font-semibold shadow-xl backdrop-blur"
             style="background: <?= h($event->theme_color) ?>">
            <span id="new-badge-name"></span>
        </div>
    </div>

    <!-- Upload nudge (shown when no photos yet) -->
    <div id="empty-nudge" class="absolute inset-0 flex items-center justify-center text-slate-600 text-lg <?= empty($photos) ? '' : 'hidden' ?>">
        Esperando las primeras fotos...
    </div>
</div>

<style>
body { margin: 0; overflow: hidden; }
.slide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 1.2s ease;
}
.slide.active { opacity: 1; }
.slide img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
}
.slide.ken-burns img {
    animation: kenBurns 8s ease-in-out forwards;
}
@keyframes kenBurns {
    from { transform: scale(1.0); }
    to   { transform: scale(1.08); }
}
.uploader-tag {
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    color: #fff;
    font-size: 14px;
    padding: 4px 14px;
    border-radius: 999px;
    white-space: nowrap;
    max-width: 80vw;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
(function () {
    const SLUG = '<?= h($event->slug) ?>';
    const INTERVAL_MS = 6000;   // time per slide
    const POLL_MS = 3000;       // poll for new photos
    const ACCENT = '<?= h($event->theme_color) ?>';

    let pool = <?= json_encode($initialPhotos, JSON_UNESCAPED_SLASHES) ?>;
    let latestTs = pool.length ? Math.max(...pool.map(p => p.ts)) : 0;
    let currentIdx = 0;
    let slideTimer = null;
    let slideEl = null;
    const container = document.getElementById('slide-container');
    const badge = document.getElementById('new-badge');
    const badgeName = document.getElementById('new-badge-name');
    const nudge = document.getElementById('empty-nudge');

    // Fade out event title after 10s.
    setTimeout(() => {
        const title = document.getElementById('event-title');
        if (title) { title.style.transition = 'opacity 1.5s'; title.style.opacity = '0'; }
    }, 10000);

    function showSlide(photo, isNew = false) {
        const prev = slideEl;
        if (prev) {
            prev.classList.remove('active');
            setTimeout(() => prev.remove(), 1400);
        }
        nudge.classList.add('hidden');

        const el = document.createElement('div');
        el.className = 'slide' + (isNew ? ' ken-burns' : '');

        const img = document.createElement('img');
        img.src = photo.thumb;
        img.loading = 'eager';
        el.appendChild(img);

        if (photo.uploader) {
            const tag = document.createElement('div');
            tag.className = 'uploader-tag';
            tag.textContent = photo.uploader;
            el.appendChild(tag);
        }

        container.appendChild(el);
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('active')));
        slideEl = el;

        if (isNew) {
            showNewBadge(photo.uploader);
        }
    }

    function showNewBadge(name) {
        badgeName.textContent = name ? `Nueva foto de ${name}` : 'Nueva foto';
        badge.classList.remove('hidden');
        setTimeout(() => badge.classList.add('hidden'), 5000);
    }

    function nextSlide() {
        if (!pool.length) {
            nudge.classList.remove('hidden');
            return;
        }
        showSlide(pool[currentIdx % pool.length]);
        currentIdx++;
    }

    function startRotation() {
        if (slideTimer) clearInterval(slideTimer);
        if (pool.length) nextSlide();
        slideTimer = setInterval(nextSlide, INTERVAL_MS);
    }

    // Poll for new photos.
    async function pollNew() {
        try {
            const url = `/e/${SLUG}/photos/since?since=${latestTs}`;
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) return;
            const data = await res.json();

            if (data.photos && data.photos.length) {
                data.photos.forEach(p => {
                    if (!pool.find(x => x.id === p.id)) {
                        pool.push(p);
                        if (p.ts > latestTs) latestTs = p.ts;
                    }
                });
                // Show newest immediately, interrupting current.
                const newest = data.photos[data.photos.length - 1];
                showSlide(newest, true);
                clearInterval(slideTimer);
                slideTimer = setInterval(nextSlide, INTERVAL_MS);
            }
        } catch (e) { /* network blip, ignore */ }
    }

    startRotation();
    setInterval(pollNew, POLL_MS);
})();
</script>
