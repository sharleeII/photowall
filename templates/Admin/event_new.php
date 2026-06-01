<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 */
$this->assign('title', 'Nuevo evento · Photowall');
?>
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/admin" class="text-sm text-slate-400 hover:text-slate-600 transition inline-flex items-center gap-1">
            ← Eventos
        </a>
        <h1 class="text-xl font-bold text-slate-900 mt-2">Crear evento</h1>
        <p class="text-slate-400 text-sm mt-0.5">El slug y el QR se generan automáticamente desde el título.</p>
    </div>

    <div class="card">
        <form method="post" class="space-y-5">
            <div class="field">
                <label class="field-label">Título del evento</label>
                <input type="text" name="title" required placeholder="Ej: Quinceañera de Valentina"
                       class="field-input">
                <span class="field-hint">Se usará como título en el proyector y la galería.</span>
            </div>

            <div class="field">
                <label class="field-label">Color del tema</label>
                <div class="flex items-center gap-3 mt-0.5">
                    <input type="color" name="theme_color" value="#7c3aed"
                           class="h-10 w-16 rounded-lg border-2 border-slate-200 cursor-pointer p-0.5 bg-white">
                    <span class="text-sm text-slate-500">Aparece en el slideshow, QR y fotos nuevas.</span>
                </div>
            </div>

            <div class="divider"></div>

            <label class="flex items-start gap-3 cursor-pointer group">
                <div class="mt-0.5">
                    <input type="checkbox" name="moderation_enabled" value="1"
                           class="w-4 h-4 text-violet-600 rounded border-slate-300 focus:ring-violet-500 cursor-pointer">
                </div>
                <span>
                    <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">Activar moderación</span>
                    <span class="block text-xs text-slate-400 mt-0.5 leading-relaxed">
                        Las fotos entran en cola de aprobación antes de aparecer en pantalla.
                        Útil si quieres controlar el contenido.
                    </span>
                </span>
            </label>

            <div class="divider"></div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Crear evento</button>
                <a href="/admin" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <p class="text-xs text-slate-400 mt-4 text-center">
        Puedes agregar marcos fotográficos después de crear el evento.
    </p>
</div>
