<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var array<\App\Model\Entity\Photo> $pending
 */
$this->assign('title', 'Moderar · ' . $event->title);
?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-bold">Moderar fotos</h1>
        <p class="text-slate-500 text-sm">Evento: <a href="/admin/events/<?= $event->id ?>" class="underline"><?= h($event->title) ?></a></p>
    </div>
    <span id="pending-count" class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-sm font-medium">
        <?= count($pending) ?> pendientes
    </span>
</div>

<?php if (empty($pending)): ?>
    <div class="card text-center text-slate-500" id="empty-state">
        No hay fotos pendientes. Esta pagina se actualiza cada 4 segundos.
    </div>
<?php endif; ?>

<div id="moderate-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    <?php foreach ($pending as $photo): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-2 space-y-2" data-photo-id="<?= $photo->id ?>">
        <a href="/files/<?= $event->id ?>/orig/<?= h($photo->filename_original) ?>" target="_blank" class="block">
            <img src="/files/<?= $event->id ?>/thumb/<?= h($photo->filename_thumb) ?>"
                 class="w-full aspect-square object-cover rounded" loading="lazy">
        </a>
        <?php if ($photo->uploader_name): ?>
            <p class="text-xs text-slate-500 truncate px-1"><?= h($photo->uploader_name) ?></p>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="moderatePhoto(<?= $photo->id ?>, 'approve', this)"
                    class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                Aprobar
            </button>
            <button onclick="moderatePhoto(<?= $photo->id ?>, 'reject', this)"
                    class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700">
                Rechazar
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    const eventId = <?= (int)$event->id ?>;
    const grid = document.getElementById('moderate-grid');
    const counter = document.getElementById('pending-count');
    const seen = new Set(
        Array.from(grid.querySelectorAll('[data-photo-id]')).map(el => Number(el.dataset.photoId))
    );

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function cardHtml(p) {
        const name = p.uploader ? `<p class="text-xs text-slate-500 truncate px-1">${esc(p.uploader)}</p>` : '';
        return `<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-2 space-y-2" data-photo-id="${p.id}">
            <a href="${p.orig}" target="_blank" class="block">
                <img src="${p.thumb}" class="w-full aspect-square object-cover rounded" loading="lazy">
            </a>
            ${name}
            <div class="grid grid-cols-2 gap-2">
                <button onclick="moderatePhoto(${p.id},'approve',this)"
                    class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar</button>
                <button onclick="moderatePhoto(${p.id},'reject',this)"
                    class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700">Rechazar</button>
            </div>
        </div>`;
    }

    async function refresh() {
        try {
            const res = await fetch(`/admin/events/${eventId}/pending`, { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            const ids = new Set(data.photos.map(p => p.id));

            data.photos.forEach(p => {
                if (!seen.has(p.id)) { seen.add(p.id); grid.insertAdjacentHTML('afterbegin', cardHtml(p)); }
            });

            grid.querySelectorAll('[data-photo-id]').forEach(el => {
                const id = Number(el.dataset.photoId);
                if (!ids.has(id)) { seen.delete(id); el.remove(); }
            });

            const left = grid.querySelectorAll('[data-photo-id]').length;
            counter.textContent = `${left} pendientes`;
        } catch (e) { /* red caida, ignorar */ }
    }

    window.moderatePhoto = async function (id, action, btn) {
        const card = btn.closest('[data-photo-id]');
        card.style.opacity = '0.4';
        card.querySelectorAll('button').forEach(b => b.disabled = true);
        try {
            const res = await fetch(`/admin/photos/${id}/${action}`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (res.ok) {
                seen.delete(id); card.remove();
                counter.textContent = `${grid.querySelectorAll('[data-photo-id]').length} pendientes`;
            } else {
                card.style.opacity = '1';
                card.querySelectorAll('button').forEach(b => b.disabled = false);
                alert('No se pudo procesar.');
            }
        } catch (e) {
            card.style.opacity = '1';
            card.querySelectorAll('button').forEach(b => b.disabled = false);
        }
    };

    setInterval(refresh, 4000);
})();
</script>
