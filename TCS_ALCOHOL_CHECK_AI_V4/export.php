<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_roles(['safety_manager', 'hr', 'administrator']);
$records = demo_records();
$format = (string) ($_GET['format'] ?? 'excel');
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="TCS_Alcohol_Check_' . date('Ymd_His') . '.xls"');
    echo "\xEF\xBB\xBF";
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>TCS Alcohol Check Report</title>
<style>body{font-family:Arial,sans-serif;color:#172033;padding:24px}h1{color:#0B2D63}table{border-collapse:collapse;width:100%}th,td{border:1px solid #cbd5e1;padding:8px;text-align:left}th{background:#eaf0f9}@media print{button{display:none}}</style></head><body>
<h1>TCS ALCOHOL CHECK</h1><p>รายงาน ณ <?= e(date('d/m/Y H:i')) ?> · DEMO MODE</p>
<?php if ($format === 'print'): ?><button onclick="window.print()">พิมพ์ / บันทึก PDF</button><?php endif; ?>
<table><thead><tr><th>เลขที่</th><th>รหัส</th><th>ชื่อ</th><th>ตำแหน่ง</th><th>ผลตรวจ</th><th>หน่วย</th><th>สถานะ</th><th>วันเวลา</th><th>ผู้ตรวจ</th></tr></thead><tbody>
<?php foreach ($records as $record): ?><tr><td><?= e($record['test_number']) ?></td><td><?= e($record['employee']['code']) ?></td><td><?= e($record['employee']['name']) ?></td><td><?= e($record['employee']['position']) ?></td><td><?= e((string) $record['confirmed_value']) ?></td><td><?= e($record['unit']) ?></td><td><?= e($record['status']['code']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($record['tested_at']))) ?></td><td><?= e($record['tested_by']) ?></td></tr><?php endforeach; ?>
</tbody></table></body></html>
