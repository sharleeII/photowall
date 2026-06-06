<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageService;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Upload controller — receives photos from guests.
 *
 * POST /e/{slug}/upload  (no auth, no CSRF — public endpoint)
 *
 * Flow:
 *   1. Validate MIME + size.
 *   2. Rate-limit by IP (simple file-based cache).
 *   3. Move to webroot/files/{event_id}/orig/{uuid}.jpg
 *   4. Generate thumb → webroot/files/{event_id}/thumb/{uuid}.jpg
 *   5. Insert DB row (status = 'pending' or 'approved' depending on event setting).
 *   6. Return JSON or redirect with success message.
 *
 * @property \App\Model\Table\EventsTable $Events
 * @property \App\Model\Table\PhotosTable $Photos
 */
class UploadController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Events      = $this->fetchTable('Events');
        $this->Photos      = $this->fetchTable('Photos');
        $this->EventFrames = $this->fetchTable('EventFrames');
    }

    public function store(string $slug): Response
    {
        $this->request->allowMethod(['post']);

        $event = $this->Events->find()->where(['slug' => $slug])->first();
        if (!$event) {
            throw new NotFoundException('Evento no encontrado.');
        }

        if (!$event->is_open) {
            return $this->jsonError('El evento ya esta cerrado, no se aceptan mas fotos.', 403);
        }

        // Rate limit — max 20 uploads per IP per minute.
        $ip = $this->request->clientIp() ?? '0.0.0.0';
        if ($this->isRateLimited($ip)) {
            return $this->jsonError('Demasiadas fotos seguidas. Espera un momento.', 429);
        }

        $uploadedFiles = $this->request->getUploadedFiles();
        // Support single file (name="photo") or multiple (name="photos[]").
        $files = [];
        if (isset($uploadedFiles['photo'])) {
            $files = [$uploadedFiles['photo']];
        } elseif (isset($uploadedFiles['photos'])) {
            $files = is_array($uploadedFiles['photos']) ? $uploadedFiles['photos'] : [$uploadedFiles['photos']];
        }

        if (empty($files)) {
            return $this->jsonError('No se recibio ninguna foto.', 422);
        }

        $uploaderName = trim((string)($this->request->getData('name') ?? ''));
        $uploaderName = $uploaderName !== '' ? mb_substr($uploaderName, 0, 80) : null;
        $status = $event->moderation_enabled ? 'pending' : 'approved';
        $uploadsDir = (string)Configure::read('Photowall.uploads_dir');

        // Resolve selected frame (0 or absent = no frame).
        $frameId  = (int)($this->request->getData('frame_id') ?? 0);
        $framePath = null;
        if ($frameId > 0) {
            $frame = $this->EventFrames->find()
                ->where(['id' => $frameId, 'event_id' => $event->id])
                ->first();
            if ($frame) {
                $candidate = $uploadsDir . 'frames' . DIRECTORY_SEPARATOR
                    . $event->id . DIRECTORY_SEPARATOR . $frame->filename;
                if (is_file($candidate)) {
                    $framePath = $candidate;
                }
            }
        }

        $saved = 0;
        $errors = [];

        foreach (array_slice($files, 0, 10) as $uploaded) { // hard cap 10 per request
            if (!($uploaded instanceof \Psr\Http\Message\UploadedFileInterface)) {
                continue;
            }
            if ($uploaded->getError() !== UPLOAD_ERR_OK) {
                $errors[] = 'Error en la subida del archivo.';
                continue;
            }

            $tmpPath = $uploaded->getStream()->getMetadata('uri');
            $clientMime = $uploaded->getClientMediaType() ?? '';

            $validation = ImageService::validate($tmpPath, $clientMime);
            if (!$validation['ok']) {
                $errors[] = $validation['error'];
                continue;
            }

            $uuid = $this->makeUuid();
            $origFilename = $uuid . '.jpg';
            $thumbFilename = $uuid . '_t.jpg';

            $origDir = $uploadsDir . $event->id . DIRECTORY_SEPARATOR . 'orig' . DIRECTORY_SEPARATOR;
            $thumbDir = $uploadsDir . $event->id . DIRECTORY_SEPARATOR . 'thumb' . DIRECTORY_SEPARATOR;

            $framedFilename = $uuid . '_framed.jpg';

            try {
                ImageService::saveOriginal($tmpPath, $origDir . $origFilename);
                ImageService::generateThumb($origDir . $origFilename, $thumbDir . $thumbFilename);

                // Apply guest-selected frame (if any).
                if ($framePath !== null) {
                    // Thumb (for the live walls) — framed at thumb resolution.
                    ImageService::applyFrame($thumbDir . $thumbFilename, $framePath);
                    // Full-res framed copy for gallery view + ZIP download.
                    ImageService::generateThumb($origDir . $origFilename, $thumbDir . $framedFilename, 1600);
                    ImageService::applyFrame($thumbDir . $framedFilename, $framePath);
                }
            } catch (\Throwable $e) {
                @unlink($origDir . $origFilename);
                @unlink($thumbDir . $thumbFilename);
                @unlink($thumbDir . $framedFilename);
                $errors[] = 'No se pudo procesar la imagen. Intenta con otra foto.';
                continue;
            }

            $photo = $this->Photos->newEntity([
                'event_id'          => $event->id,
                'filename_original' => $origFilename,
                'filename_thumb'    => $thumbFilename,
                'uploader_name'     => $uploaderName,
                'uploader_ip'       => $ip,
                'status'            => $status,
                'created'           => new DateTime(),
            ]);

            if ($this->Photos->save($photo)) {
                $saved++;
            } else {
                @unlink($origDir . $origFilename);
                @unlink($thumbDir . $thumbFilename);
                $errors[] = 'No se pudo guardar la foto en la base de datos.';
            }
        }

        $isAjax = $this->request->is('ajax') ||
                  str_contains($this->request->getHeaderLine('Accept'), 'application/json');

        if ($isAjax) {
            $statusCode = $saved > 0 ? 200 : 422;

            return $this->response
                ->withStatus($statusCode)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'ok'     => $saved > 0,
                    'saved'  => $saved,
                    'errors' => $errors,
                    'status' => $status,
                ], JSON_UNESCAPED_UNICODE));
        }

        // Non-AJAX fallback (shouldn't happen with JS upload, but just in case).
        if ($saved > 0) {
            $this->Flash->success($saved === 1
                ? '¡Foto subida! Pronto la veras en pantalla.'
                : "¡{$saved} fotos subidas! Pronto las veras en pantalla.");
        }
        foreach ($errors as $err) {
            $this->Flash->error($err);
        }

        return $this->redirect('/e/' . $slug);
    }

    private function jsonError(string $message, int $code = 400): Response
    {
        return $this->response
            ->withStatus($code)
            ->withType('application/json')
            ->withStringBody(json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE));
    }

    /** Simple file-based rate limiter — no Redis needed. */
    private function isRateLimited(string $ip): bool
    {
        $limit = (int)Configure::read('Photowall.rate_limit_per_min');
        $key = TMP . 'rl_' . md5($ip) . '.json';
        $window = 60;

        $data = ['ts' => time(), 'count' => 0];
        if (is_file($key)) {
            $raw = @file_get_contents($key);
            if ($raw) {
                $loaded = json_decode($raw, true);
                if (is_array($loaded) && (time() - $loaded['ts']) < $window) {
                    $data = $loaded;
                }
            }
        }

        $data['count']++;
        @file_put_contents($key, json_encode($data), LOCK_EX);

        return $data['count'] > $limit;
    }

    private function makeUuid(): string
    {
        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
