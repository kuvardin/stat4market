<?php

declare(strict_types=1);

$settings = require __DIR__ . '/settings.php';
if (file_exists(__DIR__ . '/settings.local.php')) {
    $settings = array_merge($settings, require __DIR__ . '/settings.local.php');
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => $settings['db.adapter'],
            'host' => $settings['db.host'],
            'name' => $settings['db.base'],
            'user' => $settings['db.user'],
            'pass' => $settings['db.pass'],
            'port' => $settings['db.port'],
            'charset' => $settings['db.charset'],
        ],
    ],
    'version_order' => 'creation'
];
