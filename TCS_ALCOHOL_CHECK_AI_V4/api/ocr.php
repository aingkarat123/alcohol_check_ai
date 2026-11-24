<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_roles(['safety_officer', 'safety_manager', 'administrator']);
require_csrf();
if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    json_response(['success' => false, 'message' => 'ไม่พบภาพสำหรับ OCR'], 422);
}
if ($_FILES['image']['size'] <= 0 || $_FILES['image']['size'] > app_config('max_image_bytes')) {
    json_response(['success' => false, 'message' => 'ภาพ OCR มีขนาดไม่ถูกต้อง'], 422);
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($_FILES['image']['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    json_response(['success' => false, 'message' => 'ชนิดไฟล์ภาพไม่ถูกต้อง'], 422);
}
if (!function_exists('curl_init')) {
    json_response(['success' => false, 'message' => 'PHP cURL extension ยังไม่พร้อม'], 503);
}

$settings = demo_settings();
$url = app_config('ocr_service_url') . '/api/ocr/alcohol';
$request = [
    'image' => new CURLFile($_FILES['image']['tmp_name'], $mime, 'capture.' . explode('/', $mime)[1]),
    'device_profile' => $settings['device_profile'],
    'test_session_id' => session_id(),
];
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $request,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_HTTPHEADER => ['X-Internal-Service: tcs-alcohol-check'],
]);
$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
if ($body === false || $status < 200 || $status >= 300) {
    json_response([
        'success' => false,
        'message' => 'OCR Service ไม่พร้อม กรุณาเริ่ม Python Service แล้วลองใหม่',
        'service_error' => $error ?: ('HTTP ' . $status),
    ], 503);
}
$decoded = json_decode($body, true);
if (!is_array($decoded)) {
    json_response(['success' => false, 'message' => 'OCR Service ส่งข้อมูลไม่ถูกต้อง'], 502);
}
$decoded['source'] = 'python_ocr';
demo_audit('ocr_processed', [
    'success' => (bool) ($decoded['success'] ?? false),
    'confidence' => $decoded['confidence'] ?? null,
]);
json_response($decoded);
