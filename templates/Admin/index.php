<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Event> $events
 * @var array<int, array{total:int, pending:int}> $counts
 */
$this->assign('title', 'Eventos · Photowall');
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Eventos</h1>
        <p class="text-slate-500 text-sm">Cada evento tiene su propio QR, slideshow y galería.</p>
    </div>
    <a href="/admin/events/new" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-violet-600 text-white font-medium hover:bg-violet-700">
        + Nuevo evento
    </a>
</div>

<?php if (empty($events)): ?>
    <div class="card text-center">
        <p class="text-slate-600">Todavía no hay eventos. Crea el primero.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($events as $event): ?>
            <a href="/admin/events/<?= $event->id ?>" class="card hover:shadow-md transition block">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h2 class="font-semibold text-lg"><?= h($event->title) ?></h2>
                        <p class="text-xs text-slate-500 font-mono"><?= h($event->slug) ?></p>
                    </div>
                    <span class="inline-block w-4 h-4 rounded" style="background: <?= h($event->theme_color) ?>"></span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded <?= $event->is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>">
                        <?= $event->is_open ? 'Abierto' : 'Cerrado' ?>
                    </span>
                    <?php if ($event->moderation_enabled): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-100 text-amber-700">
                            Moderado
                        </span>
                    <?php endif; ?>
                </div>
                <div class="mt-3 flex gap-4 text-sm text-slate-600">
                    <span><strong class="text-slate-900"><?= $counts[$event->id]['total'] ?? 0 ?></strong> fotos</span>
                    <?php if (($counts[$event->id]['pending'] ?? 0) > 0): ?>
                        <span class="text-amber-700"><strong><?= $counts[$event->id]['pending'] ?></strong> por moderar</span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
