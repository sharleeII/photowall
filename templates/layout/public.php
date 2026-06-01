<?php
/**
 * @var \App\View\AppView $this
 */
$themeColor = $this->get('themeColor') ?: '#7c3aed';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= h($themeColor) ?>">
    <title><?= $this->fetch('title') ?: 'Photowall' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --accent: <?= h($themeColor) ?>; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .accent-bg { background-color: var(--accent); }
        .accent-text { color: var(--accent); }
        .accent-border { border-color: var(--accent); }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</body>
</html>
