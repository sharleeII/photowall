<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 */
$this->assign('title', 'Editar · ' . $event->title);
?>
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/admin/events/<?= $event->id ?>" class="text-sm text-slate-400 hover:text-slate-600 transition inline-flex items-center gap-1">
            ← <?= h($event->title) ?>
        </a>
        <h1 class="text-xl font-bold text-slate-900 mt-2">Editar evento</h1>
        <p class="text-slate-400 text-sm mt-0.5">
            Slug: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded"><?= h($event->slug) ?></code>
            — no se puede cambiar, rompería los QR impresos.
        </p>
    </div>

    <div class="card">
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <div class="field">
                <label class="field-label">Título del evento</label>
                <input type="text" name="title" value="<?= h($event->title) ?>" required
                       class="field-input">
            </div>

            <div class="field">
                <label class="field-label">Color del tema</label>
                <div class="flex items-center gap-3 mt-0.5">
                    <input type="color" name="theme_color" value="<?= h($event->theme_color) ?>"
                           class="h-10 w-16 rounded-lg border-2 border-slate-200 cursor-pointer p-0.5 bg-white">
                    <span class="text-sm text-slate-500">Aparece en el slideshow, QR y fotos nuevas.</span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Photo frame -->
            <div class="field">
                <label class="field-label">Marco fotográfico</label>

                <?php if ($event->frame_filename): ?>
                    <!-- Current frame preview -->
                    <div class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-4">
                        <div class="relative w-24 h-24 rounded-lg overflow-hidden bg-slate-200 flex-shrink-0"
                             style="background: repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 0 0 / 12px 12px;">
                            <img src="/files/frames/<?= $event->id ?>/frame.png?v=<?= time() ?>"
                                 alt="Marco actual" class="w-full h-full object-contain">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700">Marco activo</p>
                            <p class="text-xs text-slate-400 mt-0.5">Se superpone sobre cada foto nueva subida.</p>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                <input type="checkbox" name="remove_frame" value="1"
                                       class="w-4 h-4 text-red-500 rounded border-slate-300">
                                <span class="text-xs text-red-500 font-medium">Eliminar marco</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <input type="file" name="frame" accept="image/png"
                       class="block w-full text-sm text-slate-500
                              file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700
                              hover:file:bg-violet-100 cursor-pointer">
                <span class="field-hint">
                    PNG con fondo transparente, al menos 800 × 800 px.
                    El marco se bake sobre el thumb de cada foto nueva.
                    <?= $event->frame_filename ? 'Sube uno nuevo para reemplazar el actual.' : '' ?>
                </span>
            </div>

            <div class="divider"></div>

            <label class="flex items-start gap-3 cursor-pointer group">
                <div class="mt-0.5">
                    <input type="checkbox" name="moderation_enabled" value="1"
                           <?= $event->moderation_enabled ? 'checked' : '' ?>
                           class="w-4 h-4 text-violet-600 rounded border-slate-300 focus:ring-violet-500 cursor-pointer">
                </div>
                <span>
                    <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">Moderación activada</span>
                    <span class="block text-xs text-slate-400 mt-0.5 leading-relaxed">
                        Si está activa, las fotos pasan por cola de aprobación antes de aparecer.
                    </span>
                </span>
            </label>

            <div class="divider"></div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="/admin/events/<?= $event->id ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
