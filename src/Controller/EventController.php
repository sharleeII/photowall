<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Guest-facing event controller.
 *
 * Routes (no auth, no CSRF):
 *   GET  /e/{slug}          → upload form
 *   GET  /e/{slug}/wall     → live slideshow (fullscreen)
 *   GET  /e/{slug}/photos.json?since={timestamp} → new approved photos
 *   GET  /e/{slug}/galeria  → read-only photo gallery
 *
 * @property \App\Model\Table\EventsTable $Events
 * @property \App\Model\Table\PhotosTable $Photos
 */
class EventController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Events = $this->fetchTable('Events');
        $this->Photos = $this->fetchTable('Photos');
        $this->viewBuilder()->setLayout('public');
    }

    /** Upload form shown to guests after scanning QR. */
    public function view(string $slug): void
    {
        $event = $this->getEventBySlug($slug);
        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event'));
    }

    /** Full-screen slideshow for the projector. */
    public function wall(string $slug): void
    {
        $event = $this->getEventBySlug($slug);

        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toList();

        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    /** Instagram Stories-style wall. */
    public function wallStories(string $slug): void
    {
        $event = $this->getEventBySlug($slug);
        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toList();
        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    /** Instagram Feed wall. */
    public function wallFeed(string $slug): void
    {
        $event = $this->getEventBySlug($slug);
        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toList();
        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    /** Neon bento / Gen-Z vibes wall. */
    public function wallBento(string $slug): void
    {
        $event = $this->getEventBySlug($slug);
        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toList();
        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    /** Polaroid party wall. */
    public function wallFiesta(string $slug): void
    {
        $event = $this->getEventBySlug($slug);
        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->limit(50)
            ->all()
            ->toList();
        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    /**
     * JSON endpoint polled by wall.js every 3 s.
     * Returns photos approved *after* the given timestamp.
     *
     * GET /e/{slug}/photos.json?since={unix_timestamp}
     */
    public function photosJson(string $slug): Response
    {
        $event = $this->getEventBySlug($slug);

        $since = $this->request->getQuery('since');
        $sinceTs = $since !== null ? (int)$since : 0;

        $query = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved']);

        if ($sinceTs > 0) {
            // Convert unix timestamp to ISO datetime string for SQLite comparison.
            $sinceDate = (new \DateTime('@' . $sinceTs))->format('Y-m-d H:i:s');
            $query->where(['Photos.created >' => $sinceDate]);
        }

        $photos = $query
            ->orderBy(['created' => 'ASC'])
            ->limit(30)
            ->all()
            ->toList();

        $items = array_map(fn ($p) => [
            'id'       => $p->id,
            'thumb'    => '/files/' . $event->id . '/thumb/' . $p->filename_thumb,
            'orig'     => '/files/' . $event->id . '/orig/' . $p->filename_original,
            'uploader' => $p->uploader_name,
            'ts'       => $p->created->getTimestamp(),
        ], $photos);

        return $this->response
            ->withType('application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withStringBody(json_encode([
                'photos' => $items,
                'event_open' => (bool)$event->is_open,
                'server_ts' => time(),
            ], JSON_UNESCAPED_SLASHES));
    }

    /** Read-only gallery for sharing after the event. */
    public function gallery(string $slug): void
    {
        $event = $this->getEventBySlug($slug);

        $photos = $this->Photos->find()
            ->where(['event_id' => $event->id, 'status' => 'approved'])
            ->orderBy(['created' => 'DESC'])
            ->all()
            ->toList();

        $this->set('themeColor', $event->theme_color);
        $this->set(compact('event', 'photos'));
    }

    private function getEventBySlug(string $slug): \App\Model\Entity\Event
    {
        $event = $this->Events->find()->where(['slug' => $slug])->first();
        if (!$event) {
            throw new NotFoundException('Evento no encontrado.');
        }

        return $event;
    }
}
