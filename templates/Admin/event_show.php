<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var string $publicUrl
 * @var string $wallUrl
 * @var string $galleryUrl
 * @var array{approved:int,pending:int,rejected:int} $stats
 * @var array<\App\Model\Entity\Photo> $latest
 */
$this->assign('title', $event->title . ' · Admin');
?>
<div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <span class="inline-block w-4 h-4 rounded" style="background: <?= h($event->theme_color) ?>"></span>
            <?= h($event->title) ?>
        </h1>
        <p class="text-sm text-slate-500 mt-1 font-mono"><?= h($event->slug) ?></p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="/admin/events/<?= $event->id ?>/edit" class="btn btn-secondary inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-200 text-slate-800 hover:bg-slate-300 font-medium">Editar</a>
        <form method="post" action="/admin/events/<?= $event->id ?>/toggle-open" class="inline">
            <button class="inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium <?= $event->is_open ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' ?>">
                <?= $event->is_open ? 'Cerrar uploads' : 'Reabrir uploads' ?>
            </button>
        </form>
        <a href="/admin/events/<?= $event->id ?>/zip" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-200 text-slate-800 hover:bg-slate-300 font-medium">Descargar ZIP</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="card text-center">
        <p class="text-3xl font-bold text-emerald-600"><?= $stats['approved'] ?></p>
        <p class="text-sm text-slate-500 mt-1">Aprobadas</p>
    </div>
    <div class="card text-center">
        <p class="text-3xl font-bold <?= $stats['pending'] > 0 ? 'text-amber-600' : 'text-slate-400' ?>"><?= $stats['pending'] ?></p>
        <p class="text-sm text-slate-500 mt-1">
            <?php if ($stats['pending'] > 0): ?>
                <a href="/admin/events/<?= $event->id ?>/moderate" class="text-amber-700 underline">Moderar</a>
            <?php else: ?>
                Pendientes
            <?php endif; ?>
        </p>
    </div>
    <div class="card text-center">
        <p class="text-3xl font-bold text-slate-400"><?= $stats['rejected'] ?></p>
        <p class="text-sm text-slate-500 mt-1">Rechazadas</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card">
        <h2 class="font-semibold mb-3">QR para invitados</h2>
        <p class="text-sm text-slate-600 mb-3">Imprime o proyecta este código a la entrada. Apunta a la URL pública del evento.</p>
        <div class="bg-white rounded border border-slate-200 inline-block p-2">
            <img src="/admin/events/<?= $event->id ?>/qr" alt="QR" class="w-64 h-64">
        </div>
        <div class="mt-3 flex gap-2">
            <a href="/admin/events/<?= $event->id ?>/qr" download="qr_<?= h($event->slug) ?>.png" class="text-sm text-violet-700 underline">Descargar PNG</a>
        </div>
    </div>

    <div class="card">
        <h2 class="font-semibold mb-3">URLs</h2>
        <div class="space-y-4 text-sm">
            <div>
                <p class="text-slate-500 mb-1">Subida (invitado):</p>
                <p class="font-mono text-xs break-all bg-slate-50 p-2 rounded border"><?= h($publicUrl) ?></p>
            </div>

            <div>
                <p class="text-slate-500 mb-2 font-medium">Proyector — elige el modo:</p>
                <div class="space-y-2">
                    <?php
                    $wallBase = rtrim($wallUrl, '/');
                    $walls = [
                        ['label' => '🎬 Cinema', 'desc' => 'Swiper 3D + bokeh', 'url' => $wallBase],
                        ['label' => '📱 Stories', 'desc' => 'Estilo Instagram', 'url' => $wallBase . '/stories'],
                        ['label' => '🎉 Fiesta', 'desc' => 'Polaroids + confetti', 'url' => $wallBase . '/fiesta'],
                    ];
                    foreach ($walls as $w): ?>
                    <div class="flex items-center gap-3 bg-slate-50 border rounded p-2">
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold"><?= $w['label'] ?></span>
                            <span class="text-slate-400 ml-1"><?= $w['desc'] ?></span>
                            <p class="font-mono text-xs text-slate-400 truncate"><?= h($w['url']) ?></p>
                        </div>
                        <a href="<?= h($w['url']) ?>" target="_blank"
                           class="flex-shrink-0 px-3 py-1.5 rounded-lg text-white text-xs font-semibold"
                           style="background:<?= h($event->theme_color) ?>">
                            Abrir ↗
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <p class="text-slate-500 mb-1">Galería pública:</p>
                <p class="font-mono text-xs break-all bg-slate-50 p-2 rounded border"><?= h($galleryUrl) ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($latest)): ?>
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">Últimas fotos aprobadas</h2>
            <a href="<?= h($galleryUrl) ?>" target="_blank" class="text-sm text-violet-700 underline">Ver galería completa ↗</a>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2">
            <?php foreach ($latest as $photo): ?>
                <a href="/files/<?= $event->id ?>/orig/<?= h($photo->filename_original) ?>" target="_blank">
                    <img src="/files/<?= $event->id ?>/thumb/<?= h($photo->filename_thumb) ?>"
                         alt="" loading="lazy"
                         class="w-full aspect-square object-cover rounded hover:opacity-80 transition">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
