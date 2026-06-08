<?php

declare(strict_types=1);

/**
 * Настройки подключения к MySQL (XAMPP по умолчанию).
 * При необходимости измените пароль или имя БД.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'STYLE',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
