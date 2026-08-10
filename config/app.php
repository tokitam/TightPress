<?php

return [
    'name'     => $_ENV['APP_NAME']     ?? 'TightPress',
    'url'      => $_ENV['APP_URL']      ?? 'http://localhost:8080',
    'theme'    => $_ENV['APP_THEME']    ?? 'twentytwentythree',
    'debug'    => ($_ENV['APP_DEBUG']   ?? 'false') === 'true',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo',
];
