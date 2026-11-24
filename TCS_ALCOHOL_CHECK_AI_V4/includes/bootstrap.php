<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['timezone']);

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: camera=(self), microphone=()");
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; frame-ancestors 'self'");
}

session_name($config['session_name']);
session_set_cookie_params([
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Lax',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/demo_store.php';

if (!isset($_SESSION['ac_user'])) {
    $_SESSION['ac_user'] = [
        'id' => 1,
        'employee_code' => 'SAFE001',
        'display_name' => 'เจ้าหน้าที่ Safety (Demo)',
        'role' => 'safety_manager',
    ];
}

if (!isset($_SESSION['ac_csrf'])) {
    $_SESSION['ac_csrf'] = bin2hex(random_bytes(24));
}

function app_config(?string $key = null): mixed
{
    global $config;
    return $key === null ? $config : ($config[$key] ?? null);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return (string) $_SESSION['ac_csrf'];
}

function require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        json_response(['success' => false, 'message' => 'CSRF token ไม่ถูกต้อง'], 419);
    }
}

function current_user(): array
{
    return $_SESSION['ac_user'];
}

function require_roles(array $roles): void
{
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึง');
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        json_response(['success' => false, 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง'], 400);
    }
    return $data;
}

function new_test_number(): string
{
    $next = (int) ($_SESSION['ac_test_sequence'] ?? 0) + 1;
    $_SESSION['ac_test_sequence'] = $next;
    return 'AC-' . date('Ymd') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function validate_image_data_url(string $dataUrl): array
{
    if (!preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $matches)) {
        throw new InvalidArgumentException('รูปภาพต้องเป็น JPEG, PNG หรือ WebP');
    }
    $binary = base64_decode($matches[2], true);
    if ($binary === false || strlen($binary) === 0 || strlen($binary) > app_config('max_image_bytes')) {
        throw new InvalidArgumentException('รูปภาพไม่ถูกต้องหรือมีขนาดเกิน 4 MB');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->buffer($binary);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new InvalidArgumentException('เนื้อหาไฟล์ไม่ใช่รูปภาพที่อนุญาต');
    }
    return ['mime' => $mime, 'bytes' => strlen($binary)];
}

function result_status(float $value, string $unit): array
{
    $settings = demo_settings();
    if ($unit !== $settings['unit'] || $settings['threshold'] === null) {
        return ['code' => 'REVIEW', 'label' => 'ต้องตรวจสอบ', 'threshold' => null];
    }
    $threshold = (float) $settings['threshold'];
    return $value < $threshold
        ? ['code' => 'PASS', 'label' => 'ผ่านเกณฑ์', 'threshold' => $threshold]
        : ['code' => 'FAIL', 'label' => 'เกินเกณฑ์', 'threshold' => $threshold];
}
