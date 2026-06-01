<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tiny admin auth — single password from config, validated against a signed cookie.
 *
 * - Protects every path that starts with /admin EXCEPT /admin/login and /admin/logout.
 * - Cookie value is HMAC-SHA256("admin", Security.salt). Compared with hash_equals.
 * - On miss/invalid, redirects to /admin/login.
 */
class AdminAuthMiddleware implements MiddlewareInterface
{
    public const COOKIE_NAME = 'pw_admin';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $isAdmin = str_starts_with($path, '/admin');
        $isPublicAdminRoute = in_array($path, ['/admin/login', '/admin/logout'], true);

        if (!$isAdmin || $isPublicAdminRoute) {
            return $handler->handle($request);
        }

        $cookies = $request->getCookieParams();
        $token = $cookies[self::COOKIE_NAME] ?? '';
        $expected = self::expectedToken();

        if (!is_string($token) || !hash_equals($expected, $token)) {
            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', '/admin/login?next=' . urlencode($path));
        }

        return $handler->handle($request);
    }

    /**
     * Compute the expected cookie token for the current admin password.
     */
    public static function expectedToken(): string
    {
        $salt = (string)Configure::read('Security.salt');

        return hash_hmac('sha256', 'admin', $salt);
    }
}
