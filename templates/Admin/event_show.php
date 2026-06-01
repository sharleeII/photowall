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

<!-- Header -->
<div class="flex items-start justify-between mb-7 gap-4 flex-wrap">
    <div>
        <a href="/admin" class="text-sm text-slate-400 hover:text-slate-600 transition inline-flex items-center gap-1 mb-2">
            ← Eventos
        </a>
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
            <span class="inline-block w-4 h-4 rounded-md shadow-sm border border-black/10"
                  style="background: <?= h($event->theme_color) ?>"></span>
            <?= h($event->title) ?>
            <?php if ($event->is_open): ?>
                <span class="badge badge-green text-xs">Abierto</span>
            <?php else: ?>
                <span class="badge badge-slate text-xs">Cerrado</span>
            <?php endif; ?>
        </h1>
        <p class="text-slate-400 text-sm mt-1 font-mono"><?= h($event->slug) ?></p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="/admin/events/<?= $event->id ?>/edit" class="btn btn-secondary">Editar</a>
        <form method="post" action="/admin/events/<?= $event->id ?>/toggle-open" class="inline">
            <button class="btn <?= $event->is_open ? 'btn-warning' : 'btn-success' ?>">
                <?= $event->is_open ? 'Cerrar uploads' : 'Reabrir uploads' ?>
            </button>
        </form>
        <a href="/admin/events/<?= $event->id ?>/zip" class="btn btn-secondary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar ZIP
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card card-sm text-center">
        <p class="text-3xl font-bold text-emerald-600 tabular-nums"><?= $stats['approved'] ?></p>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mt-1">Aprobadas</p>
    </div>
    <div class="card card-sm text-center <?= $stats['pending'] > 0 ? 'ring-2 ring-amber-300' : '' ?>">
        <p class="text-3xl font-bold tabular-nums <?= $stats['pending'] > 0 ? 'text-amber-500' : 'text-slate-300' ?>">
            <?= $stats['pending'] ?>
        </p>
        <p class="text-xs font-semibold uppercase tracking-wide mt-1">
            <?php if ($stats['pending'] > 0): ?>
                <a href="/admin/events/<?= $event->id ?>/moderate"
                   class="text-amber-600 hover:text-amber-700 underline underline-offset-2">
                    Moderar →
                </a>
            <?php else: ?>
                <span class="text-slate-400">Pendientes</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="card card-sm text-center">
        <p class="text-3xl font-bold text-slate-300 tabular-nums"><?= $stats['rejected'] ?></p>
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mt-1">Rechazadas</p>
    </div>
</div>

<!-- Photo frame -->
<?php if ($event->frame_filename): ?>
<div class="card mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="card-title mb-0">Marco fotográfico</h2>
        <a href="/admin/events/<?= $event->id ?>/edit" class="text-sm text-violet-600 hover:text-violet-800 font-medium">
            Cambiar →
        </a>
    </div>
    <div class="flex items-center gap-4">
        <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 border border-slate-200"
             style="background: repeating-conic-gradient(#e2e8f0 0% 25%, #fff 0% 50%) 0 0 / 10px 10px;">
            <img src="/files/frames/<?= $event->id ?>/frame.png?v=<?= time() ?>"
                 alt="Marco" class="w-full h-full object-contain">
        </div>
        <div>
            <p class="text-sm font-semibold text-slate-700">Marco activo ✓</p>
            <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                Cada foto nueva subida por los invitados saldrá con este marco aplicado.
            </p>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card mb-6 border-dashed">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-500">Sin marco fotográfico</p>
            <p class="text-xs text-slate-400 mt-0.5">Las fotos se suben sin overlay. Agrega uno desde Editar.</p>
        </div>
        <a href="/admin/events/<?= $event->id ?>/edit" class="btn btn-secondary text-xs">
            Agregar marco
        </a>
    </div>
</div>
<?php endif; ?>

<!-- QR + URLs -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <!-- QR -->
    <div class="card">
        <h2 class="card-title">QR para invitados</h2>
        <p class="text-sm text-slate-400 mb-4 leading-relaxed">
            Imprime o proyecta este código a la entrada. Apunta a la URL pública del evento.
        </p>
        <div class="bg-white rounded-lg border border-slate-100 shadow-sm inline-block p-3">
            <img src="/admin/events/<?= $event->id ?>/qr" alt="QR" class="w-56 h-56">
        </div>
        <div class="mt-3">
            <a href="/admin/events/<?= $event->id ?>/qr"
               download="qr_<?= h($event->slug) ?>.png"
               class="text-sm text-violet-600 hover:text-violet-800 font-medium inline-flex items-center gap-1">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar PNG
            </a>
        </div>
    </div>

    <!-- URLs -->
    <div class="card">
        <h2 class="card-title">URLs</h2>
        <div class="space-y-4 text-sm">

            <!-- Upload -->
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Subida para invitados</p>
                <p class="font-mono text-xs break-all bg-slate-50 border border-slate-200 px-3 py-2 rounded-lg text-slate-600 select-all">
                    <?= h($publicUrl) ?>
                </p>
            </div>

            <div class="divider"></div>

            <!-- Walls -->
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Proyector — elige el modo</p>
                <div class="space-y-1.5">
                    <?php
                    $wallBase = rtrim($wallUrl, '/');
                    $walls = [
                        ['label' => '🎬 Cinema',  'desc' => 'Carrusel 3D + bokeh',   'url' => $wallBase],
                        ['label' => '⬛ Mosaico', 'desc' => 'Grid + spotlight',       'url' => $wallBase . '/stories'],
                        ['label' => '🎉 Fiesta',  'desc' => 'Polaroids + confetti',   'url' => $wallBase . '/fiesta'],
                        ['label' => '📲 Feed',    'desc' => 'Estilo Instagram',        'url' => $wallBase . '/feed'],
                    ];
                    foreach ($walls as $w): ?>
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 hover:border-slate-300 transition">
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-slate-800 text-[13px]"><?= $w['label'] ?></span>
                            <span class="text-slate-400 text-xs ml-1.5"><?= $w['desc'] ?></span>
                        </div>
                        <a href="<?= h($w['url']) ?>" target="_blank"
                           class="flex-shrink-0 px-2.5 py-1 rounded-md text-white text-xs font-semibold shadow-sm"
                           style="background:<?= h($event->theme_color) ?>">
                            Abrir ↗
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Gallery -->
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Galería pública</p>
                <div class="flex items-center gap-2">
                    <p class="font-mono text-xs break-all bg-slate-50 border border-slate-200 px-3 py-2 rounded-lg text-slate-600 flex-1 select-all">
                        <?= h($galleryUrl) ?>
                    </p>
                    <a href="<?= h($galleryUrl) ?>" target="_blank"
                       class="flex-shrink-0 btn btn-secondary text-xs px-2.5 py-1.5">
                        Ver ↗
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Latest photos -->
<?php if (!empty($latest)): ?>
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title mb-0">Últimas fotos aprobadas</h2>
            <a href="<?= h($galleryUrl) ?>" target="_blank"
               class="text-sm text-violet-600 hover:text-violet-800 font-medium">
                Ver galería completa ↗
            </a>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-8 gap-2">
            <?php foreach ($latest as $photo): ?>
                <a href="/files/<?= $event->id ?>/orig/<?= h($photo->filename_original) ?>" target="_blank"
                   class="block">
                    <img src="/files/<?= $event->id ?>/thumb/<?= h($photo->filename_thumb) ?>"
                         alt="" loading="lazy"
                         class="w-full aspect-square object-cover rounded-lg border border-slate-100 hover:opacity-80 hover:scale-105 transition">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
