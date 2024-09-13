<?php

declare(strict_types=1);

return [
    // База данных
    'db.adapter' => 'pgsql',
    'db.host' => 'postgres',
    'db.port' => '5432',
    'db.user' => $_ENV['POSTGRES_USER'],
    'db.pass' => $_ENV['POSTGRES_PASSWORD'],
    'db.base' => $_ENV['POSTGRES_DB'],
    'db.charset' => 'utf8mb4',
    'db.logs_dir' => '',

    // Сайт
    'site.host' => 'https://localhost:4443',
    'site.domain' => 'localhost', // Только домен (example.com)
    'site.name' => 'Stat4Market',

    'language.default' => 'ru',
    'items_limit.default' => 30,
    'items_limit_max.default' => 30,
    'timezone.default' => 'Asia/Almaty',

    'time_online' => 300, // Время, в течение которого пользователь считается онлайн, в секундах

    // Куки
    'cookies.names.session_id' => 'session_id',
    'cookies.names.lang_code' => 'language',
    'cookies.expires' => time() + 31536000,
    'cookies.path' => '/',
    'cookies.domain' => '.localhost', // Домен куков

    // JWT-токены
    'jwt.key' => '',
    'jwt.ttl.access_token' => 2 * 3600,
    'jwt.ttl.refresh_token' => 30 * 24 * 3600,
];