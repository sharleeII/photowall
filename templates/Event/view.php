<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Event $event
 * @var \App\Model\Entity\EventFrame[] $frames
 */
$this->assign('title', $event->title . ' · Subir foto');
$hasFrames = !empty($frames);
$eventId   = $event->id;
$accent    = h($event->theme_color);

// Build frames JSON for JS
$framesJson = json_encode(array_map(fn($f) => [
    'id'    => $f->id,
    'url'   => '/files/frames/' . $eventId . '/' . h($f->filename),
    'label' => h($f->label ?: ''),
], $frames), JSON_HEX_TAG);
?>
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <div class="inline-block w-3 h-3 rounded-full mb-3" style="background: <?= $accent ?>"></div>
            <h1 class="text-3xl font-bold"><?= h($event->title) ?></h1>
            <p class="text-slate-400 mt-2">Sube tus fotos y aparecen en la pantalla</p>
        </div>

        <?php if (!$event->is_open): ?>
            <div class="bg-amber-900/30 border border-amber-700 rounded-xl p-4 text-center text-amber-200">
                El evento esta cerrado. Ya no se aceptan fotos.
            </div>
        <?php else: ?>

            <div id="upload-area" class="space-y-4">

                <?php if ($hasFrames): ?>
                <!-- Frame picker -->
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Elige un marco</p>
                    <div class="flex gap-2 overflow-x-auto pb-1 snap-x snap-mandatory" id="frame-picker">

                        <!-- No frame option -->
                        <button type="button"
                                class="frame-option snap-start flex-shrink-0 w-16 h-16 rounded-xl border-2 transition-all
                                       flex items-center justify-center text-2xl
                                       border-slate-600 bg-slate-800 selected"
                                data-frame-id="0" data-frame-url="">
                            <span class="text-slate-400 text-xl">✕</span>
                        </button>

                        <?php foreach ($frames as $frame): ?>
                        <button type="button"
                                class="frame-option snap-start flex-shrink-0 w-16 h-16 rounded-xl border-2 transition-all overflow-hidden relative"
                                style="background: repeating-conic-gradient(#374151 0% 25%,#1f2937 0% 50%) 0 0/8px 8px"
                                data-frame-id="<?= $frame->id ?>"
                                data-frame-url="/files/frames/<?= $eventId ?>/<?= h($frame->filename) ?>">
                            <img src="/files/frames/<?= $eventId ?>/<?= h($frame->filename) ?>"
                                 alt="<?= h($frame->label ?: 'Marco') ?>"
                                 class="absolute inset-0 w-full h-full object-contain pointer-events-none">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if (array_filter($frames, fn($f) => $f->label)): ?>
                    <p id="frame-label" class="text-xs text-slate-400 mt-1 text-center h-4">Sin marco</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                           style="background: <?= $accent ?>20; border: 1.5px solid <?= $accent ?>">
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
                       style="--tw-ring-color: <?= $accent ?>">

                <div id="preview-grid" class="grid grid-cols-3 gap-2 hidden"></div>

                <button id="submit-btn"
                        class="w-full px-4 py-4 rounded-xl font-semibold text-lg text-white disabled:opacity-40 disabled:cursor-not-allowed hidden"
                        style="background: <?= $accent ?>">
                    Subir fotos
                </button>

                <div id="progress-bar" class="w-full bg-slate-800 rounded-full h-2 hidden">
                    <div id="progress-fill" class="h-2 rounded-full transition-all" style="background: <?= $accent ?>; width: 0%"></div>
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
                        style="background: <?= $accent ?>">
                    Subir otra foto
                </button>
            </div>

            <div id="error-banner" class="hidden mt-4 px-4 py-3 bg-rose-900/50 border border-rose-700 rounded-xl text-rose-200 text-sm"></div>

        <?php endif; ?>
    </div>
</div>

<style>
.frame-option.selected {
    border-color: <?= $accent ?>;
    box-shadow: 0 0 0 2px <?= $accent ?>66;
}
</style>

<script>
(function () {
    const UPLOAD_URL = '/e/<?= h($event->slug) ?>/upload';
    const ACCENT     = '<?= $accent ?>';
    const HAS_FRAMES = <?= $hasFrames ? 'true' : 'false' ?>;

    let pendingFiles     = [];
    let selectedFrameId  = 0;
    let selectedFrameUrl = '';
    let currentOrient    = null; // orientation of the chosen photo(s): portrait|landscape|square

    const photoInput    = document.getElementById('photo-input');
    const previewGrid   = document.getElementById('preview-grid');
    const submitBtn     = document.getElementById('submit-btn');
    const progressBar   = document.getElementById('progress-bar');
    const progressFill  = document.getElementById('progress-fill');
    const successScreen = document.getElementById('success-screen');
    const uploadArea    = document.getElementById('upload-area');
    const errorBanner   = document.getElementById('error-banner');
    const nameInput     = document.getElementById('uploader-name');
    const frameLabelEl  = document.getElementById('frame-label');

    // Restore saved name.
    try { nameInput.value = localStorage.getItem('pw_name') || ''; } catch(e) {}

    // ── Frame picker ─────────────────────────────────────────────
    function orientationOf(w, h) {
        if (!w || !h) return 'square';
        const r = w / h;
        if (r > 1.1) return 'landscape';
        if (r < 0.9) return 'portrait';
        return 'square';
    }

    // Classify each frame by its PNG aspect ratio (once its image loads).
    function classifyFrames() {
        document.querySelectorAll('.frame-option[data-frame-id]').forEach(btn => {
            if (btn.dataset.frameId === '0') return;
            const img = btn.querySelector('img');
            if (!img) return;
            const set = () => { btn.dataset.orientation = orientationOf(img.naturalWidth, img.naturalHeight); };
            if (img.complete && img.naturalWidth) { set(); }
            else { img.addEventListener('load', set); }
        });
    }

    function selectNoFrame() {
        selectedFrameId = 0;
        selectedFrameUrl = '';
        document.querySelectorAll('.frame-option').forEach(b => b.classList.remove('selected'));
        const noFrame = document.querySelector('.frame-option[data-frame-id="0"]');
        if (noFrame) noFrame.classList.add('selected');
        if (frameLabelEl) frameLabelEl.textContent = 'Sin marco';
        updateAllOverlays();
    }

    // Show only frames compatible with the photo's orientation.
    // 'square' frames fit any photo; pass null to show all.
    function filterFramesByPhoto(orient) {
        let visible = 0;
        document.querySelectorAll('.frame-option[data-frame-id]').forEach(btn => {
            if (btn.dataset.frameId === '0') return; // "no frame" always shown
            const fo = btn.dataset.orientation || 'square';
            const compatible = !orient || fo === 'square' || fo === orient;
            btn.style.display = compatible ? '' : 'none';
            if (compatible) visible++;
            if (!compatible && parseInt(btn.dataset.frameId, 10) === selectedFrameId) {
                selectNoFrame();
            }
        });
        if (frameLabelEl && orient && selectedFrameId === 0) {
            frameLabelEl.textContent = visible > 0
                ? (orient === 'portrait' ? 'Marcos para foto vertical' : orient === 'landscape' ? 'Marcos para foto horizontal' : 'Sin marco')
                : 'No hay marcos para esta orientación';
        }
    }

    // Read a file's display orientation (browsers honour EXIF on <img>).
    function detectPhotoOrientation(file) {
        return new Promise(resolve => {
            const img = new Image();
            img.onload = () => { resolve(orientationOf(img.naturalWidth, img.naturalHeight)); URL.revokeObjectURL(img.src); };
            img.onerror = () => { resolve(null); URL.revokeObjectURL(img.src); };
            img.src = URL.createObjectURL(file);
        });
    }

    if (HAS_FRAMES) {
        classifyFrames();
        document.querySelectorAll('.frame-option').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.frame-option').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedFrameId  = parseInt(btn.dataset.frameId, 10);
                selectedFrameUrl = btn.dataset.frameUrl || '';
                if (frameLabelEl) {
                    frameLabelEl.textContent = selectedFrameId === 0
                        ? 'Sin marco'
                        : (btn.querySelector('img')?.alt || 'Marco');
                }
                // Update overlays on existing previews.
                updateAllOverlays();
            });
        });
    }

    function updateAllOverlays() {
        document.querySelectorAll('.preview-frame-overlay').forEach(el => el.remove());
        if (!selectedFrameUrl) return;
        document.querySelectorAll('#preview-grid .preview-wrapper').forEach(wrapper => {
            addOverlay(wrapper);
        });
    }

    function addOverlay(wrapper) {
        if (!selectedFrameUrl) return;
        const ov = document.createElement('img');
        ov.src = selectedFrameUrl;
        ov.className = 'preview-frame-overlay absolute inset-0 w-full h-full object-cover pointer-events-none';
        wrapper.appendChild(ov);
    }

    // ── File handling ─────────────────────────────────────────────
    function addFiles(files) {
        const wasEmpty = pendingFiles.length === 0;
        Array.from(files).forEach(f => {
            if (f.size > 15 * 1024 * 1024) {
                showError(`${f.name}: demasiado grande (max 15 MB)`);
                return;
            }
            pendingFiles.push(f);
            const reader = new FileReader();
            reader.onload = e => {
                const wrapper = document.createElement('div');
                wrapper.className = 'preview-wrapper relative';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full aspect-square object-cover rounded-lg';

                const badge = document.createElement('div');
                badge.className = 'absolute top-1 right-1 w-5 h-5 bg-black/60 rounded-full flex items-center justify-center text-xs cursor-pointer z-10';
                badge.textContent = '✕';
                const fileRef = f;
                badge.onclick = () => {
                    const i = pendingFiles.indexOf(fileRef);
                    if (i !== -1) pendingFiles.splice(i, 1);
                    wrapper.remove();
                    updateSubmitBtn();
                    if (pendingFiles.length === 0 && HAS_FRAMES) {
                        currentOrient = null;
                        filterFramesByPhoto(null);
                    }
                };

                wrapper.append(img, badge);
                if (selectedFrameUrl) addOverlay(wrapper);
                previewGrid.appendChild(wrapper);
            };
            reader.readAsDataURL(f);
        });
        updateSubmitBtn();

        // On the first photo, detect its orientation and filter the frame list.
        if (wasEmpty && pendingFiles.length > 0 && HAS_FRAMES) {
            detectPhotoOrientation(pendingFiles[0]).then(o => {
                currentOrient = o;
                filterFramesByPhoto(o);
            });
        }
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

    // ── Upload ────────────────────────────────────────────────────
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
            if (selectedFrameId > 0) fd.append('frame_id', selectedFrameId);

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
        document.querySelectorAll('.camera-input, .gallery-input').forEach(inp => { inp.value = ''; });
        progressFill.style.width = '0%';
        uploadArea.classList.remove('hidden');
        successScreen.classList.add('hidden');
        errorBanner.classList.add('hidden');
        if (HAS_FRAMES) {
            currentOrient = null;
            selectNoFrame();
            filterFramesByPhoto(null);
        }
    };
})();
</script>
