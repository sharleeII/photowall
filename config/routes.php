<?php
/**
 * Routes for Photowall.
 *
 * Public guest URLs live under /e/{slug} — they are stateless, no CSRF, no session needed.
 * Admin URLs live under /admin/* and are gated by AdminAuthMiddleware.
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        // Landing — redirect to /admin (which forces login if needed).
        $builder->redirect('/', '/admin');

        // Admin (auth gate handled by AdminAuthMiddleware).
        $builder->connect('/admin', ['controller' => 'Admin', 'action' => 'index']);
        $builder->connect('/admin/login', ['controller' => 'Admin', 'action' => 'login']);
        $builder->connect('/admin/logout', ['controller' => 'Admin', 'action' => 'logout']);
        $builder->connect('/admin/events/new', ['controller' => 'Admin', 'action' => 'eventNew']);
        $builder->connect('/admin/events/{id}', ['controller' => 'Admin', 'action' => 'eventShow'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/edit', ['controller' => 'Admin', 'action' => 'eventEdit'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/toggle-open', ['controller' => 'Admin', 'action' => 'eventToggleOpen'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/moderate', ['controller' => 'Admin', 'action' => 'eventModerate'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/qr', ['controller' => 'Admin', 'action' => 'eventQr'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/zip', ['controller' => 'Admin', 'action' => 'eventZip'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/photos/{id}/approve', ['controller' => 'Admin', 'action' => 'photoApprove'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/photos/{id}/reject', ['controller' => 'Admin', 'action' => 'photoReject'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/remove-frame', ['controller' => 'Admin', 'action' => 'eventRemoveFrame'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);
        $builder->connect('/admin/events/{id}/pending', ['controller' => 'Admin', 'action' => 'eventPendingJson'])
            ->setPatterns(['id' => '\d+'])
            ->setPass(['id']);

        // Guest / public per-event endpoints.
        $builder->connect('/e/{slug}', ['controller' => 'Event', 'action' => 'view'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/upload', ['controller' => 'Upload', 'action' => 'store'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/wall/feed', ['controller' => 'Event', 'action' => 'wallFeed'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/wall/stories', ['controller' => 'Event', 'action' => 'wallStories'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/wall/fiesta', ['controller' => 'Event', 'action' => 'wallFiesta'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/wall', ['controller' => 'Event', 'action' => 'wall'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/photos/since', ['controller' => 'Event', 'action' => 'photosJson'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
        $builder->connect('/e/{slug}/galeria', ['controller' => 'Event', 'action' => 'gallery'])
            ->setPatterns(['slug' => '[a-z0-9\-]+'])
            ->setPass(['slug']);
    });
};
