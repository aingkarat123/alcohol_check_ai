<?php
declare(strict_types=1);

return [
    'app_name' => 'TCS ALCOHOL CHECK',
    'timezone' => 'Asia/Bangkok',
    'demo_mode' => filter_var(getenv('TCS_AC_DEMO') ?: 'true', FILTER_VALIDATE_BOOL),
    'base_url' => rtrim((string) (getenv('TCS_AC_BASE_URL') ?: ''), '/'),
    'ocr_service_url' => rtrim((string) (getenv('TCS_AC_OCR_URL') ?: 'http://127.0.0.1:8010'), '/'),
    'max_image_bytes' => 4 * 1024 * 1024,
    'session_name' => 'TCS_AC_SESSION',
    'db' => [
        'server' => (string) (getenv('TCS_AC_DB_SERVER') ?: 'tcp:192.168.1.151,1433'),
        'database' => (string) (getenv('TCS_AC_DB_NAME') ?: 'Training'),
        'auth' => strtolower((string) (getenv('TCS_AC_DB_AUTH') ?: 'windows')),
        'username' => (string) (getenv('TCS_AC_DB_USER') ?: ''),
        'password' => (string) (getenv('TCS_AC_DB_PASS') ?: ''),
        'encrypt' => filter_var(getenv('TCS_AC_DB_ENCRYPT') ?: 'true', FILTER_VALIDATE_BOOL),
        'trust_certificate' => filter_var(getenv('TCS_AC_DB_TRUST_CERT') ?: 'false', FILTER_VALIDATE_BOOL),
    ],
];
