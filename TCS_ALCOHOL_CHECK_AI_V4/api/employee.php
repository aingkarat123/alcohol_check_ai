<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_roles(['safety_officer', 'safety_manager', 'hr', 'administrator']);
$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '' || !preg_match('/^[A-Za-z0-9_-]{1,30}$/', $code)) {
    json_response(['success' => false, 'message' => 'กรุณาระบุรหัสพนักงาน'], 422);
}

if (!app_config('demo_mode')) {
    json_response(['success' => false, 'message' => 'ยังไม่ได้กำหนด Employee Master สำหรับ Production'], 503);
}

$employee = demo_employees()[$code] ?? null;
if ($employee === null) {
    json_response(['success' => false, 'message' => 'ไม่พบรหัสพนักงานนี้'], 404);
}
json_response(['success' => true, 'employee' => $employee, 'source' => 'demo_employee_master']);
