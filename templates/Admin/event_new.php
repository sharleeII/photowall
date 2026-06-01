<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 */
$this->assign('title', 'Nuevo evento · Photowall');
?>
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-1">Crear evento</h1>
    <p class="text-slate-500 text-sm mb-6">El slug y el QR se generan automáticamente desde el título.</p>

    <form method="post" class="card space-y-5">
        <?= $this->Form->control('title', [
            'label' => 'Título',
            'class' => 'mt-1 w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-violet-500',
            'templates' => ['inputContainer' => '<div class="block">{{content}}</div>'],
            'placeholder' => 'Ej: Cumpleaños de Juan',
            'required' => true,
        ]) ?>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Color del tema</span>
            <input type="color" name="theme_color" value="#7c3aed"
                   class="mt-1 h-10 w-20 rounded border border-slate-300">
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="moderation_enabled" value="1"
                   class="mt-1 w-4 h-4 text-violet-600 rounded focus:ring-violet-500">
            <span class="text-sm">
                <span class="font-medium">Activar moderación</span>
                <span class="block text-slate-500">Las fotos pasan por una cola antes de aparecer en la pantalla.</span>
            </span>
        </label>

        <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-violet-600 text-white font-medium hover:bg-violet-700">
                Crear evento
            </button>
            <a href="/admin" class="text-sm text-slate-500 hover:text-slate-700">Cancelar</a>
        </div>
    </form>
</div>
