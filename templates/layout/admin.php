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

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px;
               padding: 0.5rem 1.1rem; border-radius: 8px; font-weight: 500; font-size: 0.875rem;
               transition: background 0.15s, box-shadow 0.15s, transform 0.1s; border: none; cursor: pointer; }
        .btn:active { transform: scale(0.98); }
        .btn-primary   { background: #7c3aed; color: #fff; }
        .btn-primary:hover { background: #6d28d9; box-shadow: 0 2px 8px rgba(124,58,237,.35); }
        .btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-danger    { background: #e11d48; color: #fff; }
        .btn-danger:hover { background: #be123c; box-shadow: 0 2px 8px rgba(225,29,72,.3); }
        .btn-warning   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .btn-warning:hover { background: #fde68a; }
        .btn-success   { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .btn-success:hover { background: #a7f3d0; }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }
        .card-sm { padding: 1rem 1.25rem; }
        .card-title { font-size: 0.9375rem; font-weight: 600; color: #1e293b; margin-bottom: 0.875rem; }

        /* ── Form fields ── */
        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label, .field-label { font-size: 0.8125rem; font-weight: 600; color: #475569; letter-spacing: 0.01em; }
        .field-input {
            width: 100%; padding: 0.5rem 0.75rem;
            border: 1.5px solid #cbd5e1; border-radius: 8px;
            font-size: 0.9375rem; color: #0f172a; background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.12); }
        .field-hint { font-size: 0.75rem; color: #94a3b8; }

        /* ── Badges ── */
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 99px;
                 font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; }
        .badge-green  { background: #d1fae5; color: #065f46; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-slate  { background: #f1f5f9; color: #475569; }
        .badge-red    { background: #ffe4e6; color: #9f1239; }
        .badge-violet { background: #ede9fe; color: #5b21b6; }

        /* ── Divider ── */
        .divider { height: 1px; background: #f1f5f9; margin: 1.25rem 0; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <nav class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200
                px-4 sm:px-6 py-0 flex items-center justify-between h-14
                shadow-[0_1px_0_0_#e2e8f0,0_1px_8px_-2px_rgba(0,0,0,.06)]">
        <a href="/admin" class="flex items-center gap-2.5 font-bold text-violet-700 text-[15px] tracking-tight">
            <span class="inline-flex w-6 h-6 rounded-lg bg-violet-600 items-center justify-center">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <rect x="1" y="1" width="4" height="4" rx="1" fill="white"/>
                    <rect x="7" y="1" width="4" height="4" rx="1" fill="white" opacity=".7"/>
                    <rect x="1" y="7" width="4" height="4" rx="1" fill="white" opacity=".7"/>
                    <rect x="7" y="7" width="4" height="4" rx="1" fill="white" opacity=".5"/>
                </svg>
            </span>
            Photowall
        </a>
        <div class="flex items-center gap-1 text-sm">
            <a href="/admin" class="px-3 py-1.5 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition font-medium">Eventos</a>
            <a href="/admin/events/new" class="px-3 py-1.5 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition font-medium">+ Nuevo</a>
            <span class="w-px h-4 bg-slate-200 mx-1"></span>
            <a href="/admin/logout" class="px-3 py-1.5 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition font-medium">Salir</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
</body>
</html>
