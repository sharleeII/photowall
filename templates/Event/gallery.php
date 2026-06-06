<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var array<\App\Model\Entity\Photo> $photos
 * @var array<array{thumb:string,download:string,uploader:?string}> $items
 */
$this->assign('title', $event->title . ' · Galería');
?>
<div class="min-h-screen">
    <div class="sticky top-0 bg-slate-950/90 backdrop-blur border-b border-slate-800 px-4 py-3 z-10">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <h1 class="font-semibold"><?= h($event->title) ?></h1>
            <span class="text-sm text-slate-400"><?= count($photos) ?> fotos</span>
        </div>
    </div>

    <?php if (empty($photos)): ?>
        <div class="flex items-center justify-center min-h-64 text-slate-500">
            Todavia no hay fotos aprobadas.
        </div>
    <?php else: ?>
        <div class="max-w-6xl mx-auto p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                <?php foreach ($items as $item): ?>
                    <a href="<?= h($item['download']) ?>"
                       target="_blank" class="block group">
                        <div class="aspect-square overflow-hidden rounded-lg bg-slate-900">
                            <img src="<?= h($item['thumb']) ?>"
                                 alt="<?= $item['uploader'] ? h($item['uploader']) : '' ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                        <?php if ($item['uploader']): ?>
                            <p class="text-xs text-slate-500 mt-1 truncate px-1"><?= h($item['uploader']) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
