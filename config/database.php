<?php
return [
    'default' => getenv('DB_CONNECTION') ?: 'mysql',
    'auto_create' => getenv('DB_AUTO_CREATE') === false ? true : filter_var(getenv('DB_AUTO_CREATE'), FILTER_VALIDATE_BOOL),
    'auto_migrate' => getenv('DB_AUTO_MIGRATE') === false ? true : filter_var(getenv('DB_AUTO_MIGRATE'), FILTER_VALIDATE_BOOL),
    'mysql' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'autopublisherx',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'sqlite' => [
        'path' => getenv('DB_SQLITE_PATH') ?: BASE_PATH . '/database/sqlite.db',
    ],
];
