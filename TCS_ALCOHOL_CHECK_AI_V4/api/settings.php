<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_roles(['safety_manager', 'administrator']);
require_csrf();
$data = request_json();
$checkpoint = trim((string) ($data['checkpoint'] ?? ''));
$deviceId = trim((string) ($data['device_id'] ?? ''));
$unit = trim((string) ($data['unit'] ?? ''));
$threshold = $data['threshold'] ?? null;
$stableFrames = (int) ($data['stable_frames'] ?? 3);
$confidence = (float) ($data['confidence_threshold'] ?? 0.8);

if ($checkpoint === '' || $deviceId === '' || !in_array($unit, ['mg/L', 'g/L', '%BAC'], true)) {
    json_response(['success' => false, 'message' => 'ข้อมูลตั้งค่าไม่ครบ'], 422);
}
if ($threshold !== null && (!is_numeric($threshold) || (float) $threshold < 0)) {
    json_response(['success' => false, 'message' => 'Threshold ไม่ถูกต้อง'], 422);
}
if ($stableFrames < 2 || $stableFrames > 10 || $confidence < 0.5 || $confidence > 1) {
    json_response(['success' => false, 'message' => 'ค่า Auto Capture ไม่อยู่ในช่วงที่รองรับ'], 422);
}
$_SESSION['ac_settings'] = array_merge(demo_settings(), [
    'checkpoint' => $checkpoint,
    'device_id' => $deviceId,
    'unit' => $unit,
    'threshold' => $threshold === null || $threshold === '' ? null : round((float) $threshold, 3),
    'stable_frames' => $stableFrames,
    'confidence_threshold' => $confidence,
]);
demo_audit('settings_updated', ['device_id' => $deviceId, 'checkpoint' => $checkpoint]);
json_response(['success' => true, 'message' => 'บันทึกการตั้งค่าแล้ว', 'settings' => $_SESSION['ac_settings']]);
