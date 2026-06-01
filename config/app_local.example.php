<?php
/**
 * PRODUCTION CONFIG EXAMPLE
 * Copy to config/app_local.php and fill in real values.
 */
use function Cake\Core\env;

return [
    'debug' => false,

    'Security' => [
        // Generate: php -r "echo bin2hex(random_bytes(32));"
        'salt' => env('SECURITY_SALT', 'CHANGE_ME'),
    ],

    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Sqlite',
            'persistent' => false,
            'database' => CONFIG . 'photowall.sqlite',
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Sqlite',
            'persistent' => false,
            'database' => ':memory:',
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
        ],
    ],

    'Photowall' => [
        'admin_password' => env('PHOTOWALL_ADMIN_PASSWORD', 'CHANGE_ME'),
        'uploads_dir' => WWW_ROOT . 'files' . DIRECTORY_SEPARATOR,
        'max_upload_mb' => 15,
        'rate_limit_per_min' => 20,
        'thumb_max_edge_px' => 1080,
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    ],
];
