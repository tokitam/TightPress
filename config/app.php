<?php

$appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';

return [
    'name'        => $_ENV['APP_NAME']     ?? 'TightPress',
    'url'         => $appUrl,
    'theme'       => $_ENV['APP_THEME']    ?? 'twentytwentythree',
    'themes_dir'  => PUBLIC_DIR . '/themes',
    'uploads_dir' => PUBLIC_DIR . '/uploads',
    'uploads_url' => $appUrl . '/uploads',
    'debug'       => ($_ENV['APP_DEBUG']   ?? 'false') === 'true',
    'timezone'    => $_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo',
];
