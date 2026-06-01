<?php
/**
 * @var \App\View\AppView $this
 * @var string $next
 * @var string|null $error
 */
$this->assign('title', 'Iniciar sesión · Photowall');
?>
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2 text-violet-400 font-semibold">
                <span class="inline-block w-2 h-2 rounded-full bg-violet-500"></span>
                Photowall
            </div>
            <h1 class="text-2xl font-bold mt-2">Acceso administrador</h1>
            <p class="text-slate-400 text-sm mt-1">Contraseña única para crear eventos y moderar.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-rose-900/40 border border-rose-700 text-rose-200 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4 bg-slate-900 p-6 rounded-xl border border-slate-800">
            <input type="hidden" name="next" value="<?= h($next) ?>">
            <label class="block">
                <span class="text-sm text-slate-300">Contraseña</span>
                <input type="password" name="password" autofocus required autocomplete="current-password"
                       class="mt-1 w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </label>
            <button type="submit"
                    class="w-full px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-medium">
                Entrar
            </button>
        </form>
    </div>
</div>
