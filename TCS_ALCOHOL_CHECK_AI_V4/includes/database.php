<?php
declare(strict_types=1);

function open_sql_server(array $settings): PDO
{
    if (!in_array('sqlsrv', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('PHP extension pdo_sqlsrv is required.');
    }

    $auth = $settings['auth'];
    if (!in_array($auth, ['windows', 'sql'], true)) {
        throw new InvalidArgumentException('TCS_AC_DB_AUTH must be windows or sql.');
    }
    if ($auth === 'sql' && ($settings['username'] === '' || $settings['password'] === '')) {
        throw new RuntimeException('Set TCS_AC_DB_USER and TCS_AC_DB_PASS for SQL Authentication.');
    }

    $dsn = implode(';', [
        'sqlsrv:Server=' . $settings['server'],
        'Database=' . $settings['database'],
        'Encrypt=' . ($settings['encrypt'] ? 'yes' : 'no'),
        'TrustServerCertificate=' . ($settings['trust_certificate'] ? 'yes' : 'no'),
        'LoginTimeout=5',
    ]);

    return new PDO(
        $dsn,
        $auth === 'sql' ? $settings['username'] : null,
        $auth === 'sql' ? $settings['password'] : null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}
