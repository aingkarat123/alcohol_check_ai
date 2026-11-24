<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

try {
    $pdo = open_sql_server($config['db']);
    $row = $pdo->query("SELECT DB_NAME() AS DatabaseName, @@SERVERNAME AS ServerName")->fetch();
    echo 'Connected to ' . $row['ServerName'] . ' / ' . $row['DatabaseName'] . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Connection failed: ' . $exception->getMessage() . PHP_EOL);
    exit(2);
}
