<?php
declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AdminAuthMiddleware;
use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Admin controller — single-password gate, event CRUD, moderation, ZIP export.
 *
 * @property \App\Model\Table\EventsTable $Events
 * @property \App\Model\Table\PhotosTable $Photos
 */
class AdminController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Events      = $this->fetchTable('Events');
        $this->Photos      = $this->fetchTable('Photos');
        $this->EventFrames = $this->fetchTable('EventFrames');
        $this->viewBuilder()->setLayout('admin');
    }

    // ---------- Auth ----------

    public function login(): ?Response
    {
        $next = (string)($this->request->getQuery('next') ?? '/admin');
        $error = null;

        if ($this->request->is('post')) {
            $password = (string)$this->request->getData('password');
            $expected = (string)Configure::read('Photowall.admin_password');

            if (hash_equals($expected, $password) && $expected !== '') {
                $token = AdminAuthMiddleware::expectedToken();
                $cookie = Cookie::create(
                    AdminAuthMiddleware::COOKIE_NAME,
                    $token,
                    [
                        'path' => '/',
                        'expires' => new DateTime('+30 days'),
                        'httponly' => true,
                        'secure' => $this->request->is('https'),
                        'samesite' => 'Lax',
                    ]
                );

                return $this->response
                    ->withCookie($cookie)
                    ->withStatus(302)
                    ->withHeader('Location', $next);
            }

            $error = 'Contrasena incorrecta.';
        }

        $this->set(compact('next', 'error'));
        $this->viewBuilder()->setLayout('public'); // login sin sidebar

        return null;
    }

    public function logout(): Response
    {
        $cookie = Cookie::create(
            AdminAuthMiddleware::COOKIE_NAME,
            '',
            ['path' => '/', 'expires' => new DateTime('-1 day')]
        );

        return $this->response
            ->withCookie($cookie)
            ->withStatus(302)
            ->withHeader('Location', '/admin/login');
    }

    // ---------- Events ----------

    public function index(): void
    {
        $events = $this->Events->find()
            ->orderBy(['Events.created' => 'DESC'])
            ->all()
            ->toList();

        $counts = [];
        foreach ($events as $ev) {
            $counts[$ev->id] = [
                'total' => $this->Photos->find()->where(['event_id' => $ev->id])->count(),
                'pending' => $this->Photos->find()
                    ->where(['event_id' => $ev->id, 'status' => 'pending'])
                    ->count(),
            ];
        }

        $this->set(compact('events', 'counts'));
    }

    public function eventNew(): ?Response
    {
        $event = $this->Events->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['slug'] = $this->uniqueSlug((string)($data['title'] ?? ''));
            $data['theme_color'] = $data['theme_color'] ?? '#7c3aed';
            $data['moderation_enabled'] = !empty($data['moderation_enabled']);
            $data['is_open'] = true;

            $event = $this->Events->newEntity($data);

            if ($this->Events->save($event)) {
                $this->Flash->success('Evento creado.');

                return $this->redirect(['action' => 'eventShow', $event->id]);
            }

            $this->Flash->error('No se pudo crear el evento.');
        }

        $this->set(compact('event'));

        return null;
    }

    public function eventShow(int $id): void
    {
        $event = $this->getEvent($id);

        $baseUrl = $this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getAuthority();
        $publicUrl = $baseUrl . '/e/' . $event->slug;
        $wallUrl = $publicUrl . '/wall';
        $galleryUrl = $publicUrl . '/galeria';

        $stats = [
            'approved' => $this->Photos->find()->where(['event_id' => $event->id, 'status' => 'approved'])->count(),
            'pending' => $this->Photos->find()->where(['event_id' => $event->id, 'status' => 'pending'])->count(),
            'rejected' => $this->Photos->find()->where(['event_id' => $event->id, 'status' => 'rejected'])->count(),
        ];

        $latest = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(24)
            ->all()
            ->toList();

        $frames = $this->EventFrames->find()
            ->where(['event_id' => $event->id])
            ->orderBy(['sort_order' => 'ASC', 'created' => 'ASC'])
            ->all()
            ->toList();

        $this->set(compact('event', 'publicUrl', 'wallUrl', 'galleryUrl', 'stats', 'latest', 'frames'));
    }

    /** Upload a new frame PNG for an event. POST /admin/events/{id}/frames/upload */
    public function eventFrameUpload(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $event = $this->getEvent($id);

        $file = $this->request->getUploadedFile('frame');
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $this->Flash->error('No se recibió ningún archivo.');

            return $this->redirect(['action' => 'eventShow', $id]);
        }

        $tmpPath = $file->getStream()->getMetadata('uri');
        $mime = mime_content_type($tmpPath) ?: '';
        if ($mime !== 'image/png') {
            $this->Flash->error('El marco debe ser un PNG con transparencia (.png).');

            return $this->redirect(['action' => 'eventShow', $id]);
        }

        $uploadsDir = (string)Configure::read('Photowall.uploads_dir');
        $frameDir = $uploadsDir . 'frames' . DIRECTORY_SEPARATOR . $event->id . DIRECTORY_SEPARATOR;
        if (!is_dir($frameDir)) {
            mkdir($frameDir, 0775, true);
        }

        $filename = $this->makeFrameUuid() . '.png';
        $file->moveTo($frameDir . $filename);

        $label = trim((string)($this->request->getData('label') ?? ''));

        $frame = $this->EventFrames->newEntity([
            'event_id'   => $event->id,
            'filename'   => $filename,
            'label'      => $label !== '' ? mb_substr($label, 0, 100) : null,
            'sort_order' => 0,
            'created'    => new \Cake\I18n\DateTime(),
        ]);
        $this->EventFrames->save($frame);

        $this->Flash->success('Marco agregado.');

        return $this->redirect(['action' => 'eventShow', $id]);
    }

    /** Delete a single frame. POST /admin/frames/{frameId}/delete */
    public function frameDelete(int $frameId): Response
    {
        $this->request->allowMethod(['post']);

        try {
            $frame = $this->EventFrames->get($frameId);
        } catch (\Exception $e) {
            $this->Flash->error('Marco no encontrado.');

            return $this->redirect('/admin');
        }

        $uploadsDir = (string)Configure::read('Photowall.uploads_dir');
        $filePath = $uploadsDir . 'frames' . DIRECTORY_SEPARATOR
            . $frame->event_id . DIRECTORY_SEPARATOR . $frame->filename;
        @unlink($filePath);

        $eventId = $frame->event_id;
        $this->EventFrames->delete($frame);
        $this->Flash->success('Marco eliminado.');

        return $this->redirect(['action' => 'eventShow', $eventId]);
    }

    public function eventEdit(int $id): ?Response
    {
        $event = $this->getEvent($id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $data['moderation_enabled'] = !empty($data['moderation_enabled']);
            unset($data['slug']); // slug stays the same — changing it breaks printed QRs

            $event = $this->Events->patchEntity($event, $data);

            if ($this->Events->save($event)) {
                $this->Flash->success('Evento actualizado.');

                return $this->redirect(['action' => 'eventShow', $event->id]);
            }
            $this->Flash->error('No se pudo guardar.');
        }

        $this->set(compact('event'));

        return null;
    }

    public function eventToggleOpen(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $event = $this->getEvent($id);
        $event->is_open = !$event->is_open;
        $this->Events->save($event);
        $this->Flash->success($event->is_open ? 'Evento abierto.' : 'Evento cerrado.');

        return $this->redirect(['action' => 'eventShow', $event->id]);
    }

    public function eventQr(int $id): Response
    {
        $event = $this->getEvent($id);

        $baseUrl = $this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getAuthority();
        $url = $baseUrl . '/e/' . $event->slug;

        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 800,
            margin: 24,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $this->response
            ->withType('image/png')
            ->withStringBody($result->getString());
    }

    // ---------- Moderation ----------

    public function eventModerate(int $id): void
    {
        $event = $this->getEvent($id);

        $pending = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'pending'])
            ->orderBy(['created' => 'ASC'])
            ->all()
            ->toList();

        $this->set(compact('event', 'pending'));
    }

    public function eventPendingJson(int $id): Response
    {
        $event = $this->getEvent($id);

        $pending = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'pending'])
            ->orderBy(['created' => 'ASC'])
            ->all()
            ->toList();

        $items = array_map(fn ($p) => [
            'id' => $p->id,
            'thumb' => '/files/' . $event->id . '/thumb/' . $p->filename_thumb,
            'orig' => '/files/' . $event->id . '/orig/' . $p->filename_original,
            'uploader' => $p->uploader_name,
            'created' => $p->created->format(DATE_ATOM),
        ], $pending);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['photos' => $items], JSON_UNESCAPED_SLASHES));
    }

    public function photoApprove(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $photo = $this->Photos->get($id);
        $photo->status = 'approved';
        $this->Photos->save($photo);

        if ($this->request->is('ajax') || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            return $this->response->withType('application/json')->withStringBody('{"ok":true}');
        }

        return $this->redirect(['action' => 'eventModerate', $photo->event_id]);
    }

    public function photoReject(int $id): Response
    {
        $this->request->allowMethod(['post']);
        $photo = $this->Photos->get($id);

        $uploadsDir = (string)Configure::read('Photowall.uploads_dir');
        $orig = $uploadsDir . $photo->event_id . DIRECTORY_SEPARATOR . 'orig' . DIRECTORY_SEPARATOR . $photo->filename_original;
        $thumb = $uploadsDir . $photo->event_id . DIRECTORY_SEPARATOR . 'thumb' . DIRECTORY_SEPARATOR . $photo->filename_thumb;
        @unlink($orig);
        @unlink($thumb);

        $this->Photos->delete($photo);

        if ($this->request->is('ajax') || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            return $this->response->withType('application/json')->withStringBody('{"ok":true}');
        }

        return $this->redirect(['action' => 'eventModerate', $photo->event_id]);
    }

    // ---------- ZIP export ----------

    public function eventZip(int $id): Response
    {
        $event = $this->getEvent($id);

        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'ASC'])
            ->all()
            ->toList();

        $uploadsDir = (string)Configure::read('Photowall.uploads_dir');
        // Fixed name per event — one file max, no accumulation over time.
        $tmpZip = TMP . 'event_' . $event->id . '.zip';

        $zip = new \ZipArchive();
        $zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($photos as $i => $p) {
            $orig = $uploadsDir . $event->id . DIRECTORY_SEPARATOR . 'orig' . DIRECTORY_SEPARATOR . $p->filename_original;
            if (is_file($orig)) {
                $zip->addFile($orig, sprintf('%04d_%s', $i + 1, $p->filename_original));
            }
        }
        $zip->close();

        $filename = $event->slug . '_fotos_' . date('Ymd_His') . '.zip';

        // Schedule deletion after the response is sent.
        register_shutdown_function('unlink', $tmpZip);

        return $this->response
            ->withType('application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withFile($tmpZip);
    }

    // ---------- Helpers ----------

    private function makeFrameUuid(): string
    {
        return sprintf('%08x%04x%04x', mt_rand(0, 0xffffffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    private function getEvent(int $id): \App\Model\Entity\Event
    {
        try {
            return $this->Events->get($id);
        } catch (\Exception $e) {
            throw new NotFoundException('Evento no encontrado.');
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = $this->slugify($title);
        if ($base === '') {
            $base = 'evento';
        }
        $slug = $base;
        $i = 2;
        while ($this->Events->exists(['slug' => $slug])) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function slugify(string $text): string
    {
        $transliterated = function_exists('transliterator_transliterate')
            ? transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text)
            : null;
        $text = $transliterated ?: mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return mb_substr($text, 0, 60);
    }
}
