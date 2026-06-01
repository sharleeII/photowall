<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $this->fetch('title') ?: 'Photowall · Admin' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition; }
        .btn-primary { @apply bg-violet-600 text-white hover:bg-violet-700; }
        .btn-secondary { @apply bg-slate-200 text-slate-800 hover:bg-slate-300; }
        .btn-danger { @apply bg-rose-600 text-white hover:bg-rose-700; }
        .card { @apply bg-white rounded-xl shadow-sm border border-slate-200 p-5; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <nav class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3 flex items-center justify-between">
        <a href="/admin" class="flex items-center gap-2 font-semibold text-violet-700">
            <span class="inline-block w-2 h-2 rounded-full bg-violet-600"></span>
            Photowall
        </a>
        <div class="flex items-center gap-3 text-sm">
            <a href="/admin" class="text-slate-600 hover:text-slate-900">Eventos</a>
            <a href="/admin/events/new" class="text-slate-600 hover:text-slate-900">Nuevo</a>
            <a href="/admin/logout" class="text-slate-500 hover:text-rose-600">Salir</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</body>
</html>
