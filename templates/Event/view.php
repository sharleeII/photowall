<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 */
$this->assign('title', $event->title . ' · Subir foto');
?>
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <div class="inline-block w-3 h-3 rounded-full mb-3" style="background: <?= h($event->theme_color) ?>"></div>
            <h1 class="text-3xl font-bold"><?= h($event->title) ?></h1>
            <p class="text-slate-400 mt-2">Sube tus fotos y aparecen en la pantalla</p>
        </div>

        <?php if (!$event->is_open): ?>
            <div class="bg-amber-900/30 border border-amber-700 rounded-xl p-4 text-center text-amber-200">
                El evento esta cerrado. Ya no se aceptan fotos.
            </div>
        <?php else: ?>

            <div id="upload-area" class="space-y-4">
                <label id="upload-label" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-2xl cursor-pointer hover:border-violet-500 transition border-slate-700 bg-slate-900/50">
                    <div class="text-center px-4">
                        <div class="text-4xl mb-2">📷</div>
                        <p class="text-sm text-slate-300">Toca aqui para tomar o elegir fotos</p>
                        <p class="text-xs text-slate-500 mt-1">JPEG, PNG, WEBP · max 15 MB</p>
                    </div>
                    <input id="photo-input" type="file" accept="image/jpeg,image/png,image/webp,image/*" multiple class="hidden">
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-medium text-sm cursor-pointer"
                           style="background: <?= h($event->theme_color) ?>20; border: 1.5px solid <?= h($event->theme_color) ?>">
                        <span>📸</span> Tomar foto
                        <input type="file" accept="image/*" capture="environment" class="hidden camera-input">
                    </label>
                    <label class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-medium text-sm cursor-pointer bg-slate-800 border border-slate-700">
                        <span>🖼️</span> Galeria
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/*" multiple class="hidden gallery-input">
                    </label>
                </div>

                <input type="text" id="uploader-name" placeholder="Tu nombre (opcional)"
                       maxlength="80"
                       class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 text-sm"
                       style="--tw-ring-color: <?= h($event->theme_color) ?>">

                <div id="preview-grid" class="grid grid-cols-3 gap-2 hidden"></div>

                <button id="submit-btn"
                        class="w-full px-4 py-4 rounded-xl font-semibold text-lg text-white disabled:opacity-40 disabled:cursor-not-allowed hidden"
                        style="background: <?= h($event->theme_color) ?>">
                    Subir fotos
                </button>

                <div id="progress-bar" class="w-full bg-slate-800 rounded-full h-2 hidden">
                    <div id="progress-fill" class="h-2 rounded-full transition-all" style="background: <?= h($event->theme_color) ?>; width: 0%"></div>
                </div>
            </div>

            <div id="success-screen" class="hidden text-center py-8 space-y-4">
                <div class="text-6xl">🎉</div>
                <h2 class="text-2xl font-bold">¡Foto subida!</h2>
                <p class="text-slate-400" id="success-msg">
                    <?php if ($event->moderation_enabled): ?>
                        Se revisara en un momento y aparecera en pantalla.
                    <?php else: ?>
                        Pronto la veras en la pantalla.
                    <?php endif; ?>
                </p>
                <button onclick="resetUpload()" class="mt-4 px-6 py-3 rounded-xl font-medium text-white"
                        style="background: <?= h($event->theme_color) ?>">
                    Subir otra foto
                </button>
            </div>

            <div id="error-banner" class="hidden mt-4 px-4 py-3 bg-rose-900/50 border border-rose-700 rounded-xl text-rose-200 text-sm"></div>

        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const UPLOAD_URL = '/e/<?= h($event->slug) ?>/upload';
    let pendingFiles = [];

    const photoInput   = document.getElementById('photo-input');
    const previewGrid  = document.getElementById('preview-grid');
    const submitBtn    = document.getElementById('submit-btn');
    const progressBar  = document.getElementById('progress-bar');
    const progressFill = document.getElementById('progress-fill');
    const successScreen = document.getElementById('success-screen');
    const uploadArea   = document.getElementById('upload-area');
    const errorBanner  = document.getElementById('error-banner');
    const nameInput    = document.getElementById('uploader-name');

    // Try to restore saved name.
    try { nameInput.value = localStorage.getItem('pw_name') || ''; } catch(e) {}

    function addFiles(files) {
        Array.from(files).forEach(f => {
            if (f.size > 15 * 1024 * 1024) {
                showError(`${f.name}: demasiado grande (max 15 MB)`);
                return;
            }
            pendingFiles.push(f);
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full aspect-square object-cover rounded-lg';
                const wrapper = document.createElement('div');
                wrapper.className = 'relative';
                const badge = document.createElement('div');
                badge.className = 'absolute top-1 right-1 w-5 h-5 bg-black/60 rounded-full flex items-center justify-center text-xs cursor-pointer';
                badge.textContent = '✕';
                const idx = pendingFiles.length - 1;
                badge.onclick = () => { pendingFiles.splice(idx, 1); wrapper.remove(); updateSubmitBtn(); };
                wrapper.append(img, badge);
                previewGrid.appendChild(wrapper);
            };
            reader.readAsDataURL(f);
        });
        updateSubmitBtn();
    }

    function updateSubmitBtn() {
        if (pendingFiles.length > 0) {
            submitBtn.classList.remove('hidden');
            previewGrid.classList.remove('hidden');
            submitBtn.textContent = pendingFiles.length === 1 ? 'Subir foto' : `Subir ${pendingFiles.length} fotos`;
        } else {
            submitBtn.classList.add('hidden');
            previewGrid.classList.add('hidden');
        }
    }

    function showError(msg) {
        errorBanner.textContent = msg;
        errorBanner.classList.remove('hidden');
        setTimeout(() => errorBanner.classList.add('hidden'), 7000);
    }

    photoInput.addEventListener('change', e => addFiles(e.target.files));
    document.querySelectorAll('.camera-input, .gallery-input').forEach(inp => {
        inp.addEventListener('change', e => addFiles(e.target.files));
    });

    submitBtn.addEventListener('click', async () => {
        if (!pendingFiles.length) return;
        const name = nameInput.value.trim();
        try { localStorage.setItem('pw_name', name); } catch(e) {}

        submitBtn.disabled = true;
        progressBar.classList.remove('hidden');

        let uploaded = 0;
        const errors = [];

        for (const file of pendingFiles) {
            const fd = new FormData();
            fd.append('photo', file);
            if (name) fd.append('name', name);

            try {
                const res = await fetch(UPLOAD_URL, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (data.ok) {
                    uploaded++;
                } else {
                    errors.push(data.error || data.errors?.[0] || 'Error desconocido');
                }
            } catch (e) {
                errors.push('Error de red. Verifica tu conexion.');
            }

            progressFill.style.width = `${Math.round(((pendingFiles.indexOf(file) + 1) / pendingFiles.length) * 100)}%`;
        }

        submitBtn.disabled = false;
        progressBar.classList.add('hidden');

        if (uploaded > 0) {
            const sMsg = document.getElementById('success-msg');
            if (sMsg && uploaded > 1) sMsg.textContent = `${uploaded} fotos subidas. Pronto las veras en pantalla.`;
            uploadArea.classList.add('hidden');
            successScreen.classList.remove('hidden');
        }
        if (errors.length) showError(errors.join(' / '));
    });

    window.resetUpload = function () {
        pendingFiles = [];
        previewGrid.innerHTML = '';
        previewGrid.classList.add('hidden');
        submitBtn.classList.add('hidden');
        photoInput.value = '';
        progressFill.style.width = '0%';
        uploadArea.classList.remove('hidden');
        successScreen.classList.add('hidden');
        errorBanner.classList.add('hidden');
    };
})();
</script>
