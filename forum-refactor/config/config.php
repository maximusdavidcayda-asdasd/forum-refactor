<?php
declare(strict_types=1);

return [
    'db' => [
        'dsn'  => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=nested_forum_lab;charset=utf8mb4',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'display' => [
        'max_indent_depth' => 8,
        'max_reply_depth'  => 10,
        'max_comment_len'  => 2000,
    ],
];