<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_roles(['safety_officer', 'safety_manager', 'hr', 'administrator']);
$record = demo_find_record((string) ($_GET['id'] ?? ''));
if ($record === null) {
    http_response_code(404);
    exit('ไม่พบรายการตรวจ');
}
app_header('รายละเอียดผลตรวจ', 'history');
?>
<a class="back-link" href="history.php">← กลับไปประวัติ</a>
<section class="detail-hero card">
  <div><p class="eyebrow"><?= e($record['test_number']) ?></p><h2><?= e($record['employee']['name']) ?></h2><span><?= e($record['employee']['code']) ?> · <?= e($record['employee']['position']) ?></span></div>
  <div class="detail-result"><strong><?= number_format((float) $record['confirmed_value'], 2) ?></strong><span><?= e($record['unit']) ?></span><em class="status-badge <?= strtolower($record['status']['code']) ?>"><?= e($record['status']['code']) ?></em></div>
</section>
<section class="evidence-grid">
  <article class="card"><h2>ภาพพนักงานขณะตรวจ</h2><img src="<?= e($record['employee_photo']) ?>" alt="ภาพพนักงานขณะตรวจ"></article>
  <article class="card"><h2>ภาพหน้าจอเครื่องตรวจ</h2><img src="<?= e($record['device_photo']) ?>" alt="ภาพหน้าจอเครื่องตรวจ"></article>
</section>
<section class="card audit-card">
  <h2>ข้อมูลการตรวจสอบ</h2>
  <dl class="detail-list">
    <div><dt>ค่า OCR เดิม</dt><dd><?= $record['original_ocr_value'] === null ? 'อ่านไม่สำเร็จ' : number_format((float) $record['original_ocr_value'], 2) . ' ' . e($record['unit']) ?></dd></div>
    <div><dt>ค่าที่ยืนยัน</dt><dd><?= number_format((float) $record['confirmed_value'], 2) ?> <?= e($record['unit']) ?></dd></div>
    <div><dt>แหล่งผล OCR</dt><dd><?= e($record['ocr_source']) ?></dd></div>
    <div><dt>Confidence</dt><dd><?= $record['ocr_confidence'] === null ? '—' : number_format((float) $record['ocr_confidence'] * 100, 1) . '%' ?></dd></div>
    <div><dt>เหตุผลที่แก้ไข</dt><dd><?= e($record['edit_reason'] ?? 'ไม่มีการแก้ไข') ?></dd></div>
    <div><dt>เกณฑ์ที่ใช้</dt><dd><?= $record['status']['threshold'] === null ? 'ยังไม่ได้กำหนด' : e((string) $record['status']['threshold']) . ' ' . e($record['unit']) ?></dd></div>
    <div><dt>เครื่องตรวจ</dt><dd><?= e($record['device_id']) ?></dd></div>
    <div><dt>จุดตรวจ</dt><dd><?= e($record['checkpoint']) ?></dd></div>
    <div><dt>ผู้ตรวจ</dt><dd><?= e($record['tested_by']) ?></dd></div>
    <div><dt>วันเวลา Server</dt><dd><?= e(date('d/m/Y H:i:s', strtotime($record['tested_at']))) ?></dd></div>
  </dl>
</section>
<?php app_footer('history'); ?>
