<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_roles(['safety_officer', 'safety_manager', 'administrator']);
require_csrf();
if (!app_config('demo_mode')) {
    json_response(['success' => false, 'message' => 'ฟังก์ชันนี้ใช้ได้เฉพาะ Demo Mode'], 403);
}
$data = request_json();
$allowed = ['0.00', '0.01', '0.05', '0.08', '0.10'];
$value = (string) ($data['value'] ?? '');
if (!in_array($value, $allowed, true)) {
    json_response(['success' => false, 'message' => 'ค่าจำลองไม่ถูกต้อง'], 422);
}
demo_audit('demo_ocr_simulated', ['value' => $value]);
json_response([
    'success' => true,
    'detected_value' => $value,
    'detected_unit' => demo_settings()['unit'],
    'confidence' => 1.0,
    'processing_time_ms' => 0,
    'needs_review' => true,
    'source' => 'demo_simulation',
    'warning' => 'ผลนี้เป็นค่าจำลอง ไม่ได้อ่านจากภาพจริง',
]);
