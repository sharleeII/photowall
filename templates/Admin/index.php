<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Event> $events
 * @var array<int, array{total:int, pending:int}> $counts
 */
$this->assign('title', 'Eventos · Photowall');
?>
<div class="flex items-center justify-between mb-7">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Eventos</h1>
        <p class="text-slate-400 text-sm mt-0.5">Cada evento tiene su propio QR, slideshow y galería.</p>
    </div>
    <a href="/admin/events/new" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        Nuevo evento
    </a>
</div>

<?php if (empty($events)): ?>
    <div class="card text-center py-16">
        <div class="text-4xl mb-3">📸</div>
        <p class="text-slate-500 font-medium">Todavía no hay eventos.</p>
        <p class="text-slate-400 text-sm mt-1">Crea el primero con el botón de arriba.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($events as $event): ?>
            <?php
                $total   = $counts[$event->id]['total']   ?? 0;
                $pending = $counts[$event->id]['pending'] ?? 0;
            ?>
            <a href="/admin/events/<?= $event->id ?>"
               class="card block hover:shadow-md transition-shadow group relative overflow-hidden"
               style="border-left: 4px solid <?= h($event->theme_color) ?>">

                <!-- color accent top strip -->
                <div class="absolute top-0 left-0 right-0 h-0.5 opacity-30"
                     style="background: <?= h($event->theme_color) ?>"></div>

                <div class="flex items-start justify-between mb-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900 text-[15px] truncate group-hover:text-violet-700 transition-colors">
                            <?= h($event->title) ?>
                        </h2>
                        <p class="text-xs text-slate-400 font-mono mt-0.5"><?= h($event->slug) ?></p>
                    </div>
                    <span class="w-5 h-5 rounded-md flex-shrink-0 ml-3 mt-0.5 border border-black/10 shadow-sm"
                          style="background: <?= h($event->theme_color) ?>"></span>
                </div>

                <div class="divider my-3"></div>

                <div class="flex items-center gap-2 flex-wrap mb-3">
                    <span class="badge <?= $event->is_open ? 'badge-green' : 'badge-slate' ?>">
                        <?= $event->is_open ? '● Abierto' : '○ Cerrado' ?>
                    </span>
                    <?php if ($event->moderation_enabled): ?>
                        <span class="badge badge-amber">Moderado</span>
                    <?php endif; ?>
                    <?php if ($pending > 0): ?>
                        <span class="badge badge-red"><?= $pending ?> pendientes</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-1 text-sm text-slate-500">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span><strong class="text-slate-800 font-semibold"><?= $total ?></strong> fotos</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
