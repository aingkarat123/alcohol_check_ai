<?php
declare(strict_types=1);

function demo_employees(): array
{
    return [
        '1001' => ['code' => '1001', 'name' => 'สมชาย ใจดี', 'position' => 'Welder', 'department' => 'Construction'],
        '1002' => ['code' => '1002', 'name' => 'วิชัย มั่นคง', 'position' => 'Rigger', 'department' => 'Construction'],
        '1003' => ['code' => '1003', 'name' => 'อนันต์ ปลอดภัย', 'position' => 'Safety Officer', 'department' => 'Safety'],
        '1004' => ['code' => '1004', 'name' => 'สุภาวดี พูนผล', 'position' => 'Site Administrator', 'department' => 'Administration'],
        '1005' => ['code' => '1005', 'name' => 'ณัฐพล ทำดี', 'position' => 'Driver', 'department' => 'Logistics'],
        '001001' => ['code' => '001001', 'name' => 'ทดสอบ รหัสศูนย์นำหน้า', 'position' => 'Technician', 'department' => 'Maintenance'],
    ];
}

function demo_settings(): array
{
    if (!isset($_SESSION['ac_settings'])) {
        $_SESSION['ac_settings'] = [
            'checkpoint' => 'ประตูหลัก',
            'device_id' => 'DEMO-LCD-01',
            'device_profile' => 'generic_lcd',
            'unit' => 'mg/L',
            'threshold' => 0.05,
            'stable_frames' => 3,
            'confidence_threshold' => 0.80,
        ];
    }
    return $_SESSION['ac_settings'];
}

function demo_records(): array
{
    return array_values($_SESSION['ac_records'] ?? []);
}

function demo_add_record(array $record): void
{
    $_SESSION['ac_records'][$record['test_number']] = $record;
}

function demo_find_record(string $testNumber): ?array
{
    return $_SESSION['ac_records'][$testNumber] ?? null;
}

function demo_audit(string $action, array $details = []): void
{
    $_SESSION['ac_audit'][] = [
        'time' => date(DATE_ATOM),
        'user' => $_SESSION['ac_user']['display_name'] ?? 'unknown',
        'action' => $action,
        'details' => $details,
    ];
}
