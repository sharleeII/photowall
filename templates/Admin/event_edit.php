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
        <form method="post" class="space-y-5">
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
