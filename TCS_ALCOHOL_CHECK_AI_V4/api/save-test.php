<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_roles(['safety_officer', 'safety_manager', 'administrator']);
require_csrf();
$data = request_json();

$required = ['employee_code', 'employee_photo', 'device_photo', 'confirmed_value', 'unit', 'device_id', 'checkpoint', 'confirmed'];
foreach ($required as $field) {
    if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
        json_response(['success' => false, 'message' => 'ข้อมูลไม่ครบ: ' . $field], 422);
    }
}
if ($data['confirmed'] !== true) {
    json_response(['success' => false, 'message' => 'เจ้าหน้าที่ต้องยืนยันค่าก่อนบันทึก'], 422);
}

$employee = demo_employees()[(string) $data['employee_code']] ?? null;
if ($employee === null) {
    json_response(['success' => false, 'message' => 'ไม่พบข้อมูลพนักงาน ห้ามบันทึกผลตรวจ'], 422);
}

if (!is_numeric($data['confirmed_value'])) {
    json_response(['success' => false, 'message' => 'ค่าผลตรวจต้องเป็นตัวเลข'], 422);
}
$confirmedValue = round((float) $data['confirmed_value'], 3);
if ($confirmedValue < 0 || $confirmedValue > 9.999) {
    json_response(['success' => false, 'message' => 'ค่าผลตรวจอยู่นอกช่วงที่ระบบรองรับ'], 422);
}

try {
    validate_image_data_url((string) $data['employee_photo']);
    validate_image_data_url((string) $data['device_photo']);
} catch (InvalidArgumentException $exception) {
    json_response(['success' => false, 'message' => $exception->getMessage()], 422);
}

$settings = demo_settings();
if ((string) $data['device_id'] !== $settings['device_id'] || (string) $data['checkpoint'] !== $settings['checkpoint']) {
    json_response(['success' => false, 'message' => 'ข้อมูลเครื่องตรวจหรือจุดตรวจไม่ตรงกับ Session ปัจจุบัน'], 422);
}

$status = result_status($confirmedValue, (string) $data['unit']);
$original = isset($data['original_ocr_value']) && is_numeric($data['original_ocr_value'])
    ? round((float) $data['original_ocr_value'], 3)
    : null;
$edited = $original !== null && abs($original - $confirmedValue) > 0.0001;
if ($edited && trim((string) ($data['edit_reason'] ?? '')) === '') {
    json_response(['success' => false, 'message' => 'กรุณาระบุเหตุผลที่แก้ไขค่า OCR'], 422);
}

$testNumber = new_test_number();
$record = [
    'test_number' => $testNumber,
    'employee' => $employee,
    'employee_photo' => (string) $data['employee_photo'],
    'device_photo' => (string) $data['device_photo'],
    'original_ocr_value' => $original,
    'confirmed_value' => $confirmedValue,
    'unit' => (string) $data['unit'],
    'ocr_confidence' => isset($data['ocr_confidence']) ? round((float) $data['ocr_confidence'], 3) : null,
    'ocr_source' => (string) ($data['ocr_source'] ?? 'unknown'),
    'edit_reason' => $edited ? trim((string) $data['edit_reason']) : null,
    'status' => $status,
    'device_id' => $settings['device_id'],
    'checkpoint' => $settings['checkpoint'],
    'tested_by' => current_user()['display_name'],
    'tested_at' => date(DATE_ATOM),
    'previous_test_id' => $data['previous_test_id'] ?? null,
];

demo_add_record($record);
demo_audit('test_record_created', [
    'test_number' => $testNumber,
    'employee_code' => $employee['code'],
    'status' => $status['code'],
    'ocr_edited' => $edited,
]);
json_response([
    'success' => true,
    'message' => 'บันทึกผลตรวจเรียบร้อย',
    'test_number' => $testNumber,
    'status' => $status,
    'server_time' => date(DATE_ATOM),
]);
