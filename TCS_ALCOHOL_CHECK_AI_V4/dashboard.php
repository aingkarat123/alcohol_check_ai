<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_roles(['safety_manager', 'hr', 'administrator']);
$records = demo_records();
$counts = ['PASS' => 0, 'FAIL' => 0, 'RETEST' => 0, 'REVIEW' => 0];
$testedCodes = [];
$ocrFailed = 0;
foreach ($records as $record) {
    $counts[$record['status']['code']] = ($counts[$record['status']['code']] ?? 0) + 1;
    $testedCodes[$record['employee']['code']] = true;
    if ($record['original_ocr_value'] === null) {
        $ocrFailed++;
    }
}
$rosterTotal = count(demo_employees());
$testedTotal = count($testedCodes);
app_header('Dashboard ผู้ดูแลระบบ', 'settings');
?>
<div class="dashboard-toolbar">
  <div><p>อ้างอิงรายชื่อพนักงานที่มีกำหนดตรวจใน Demo Roster</p></div>
  <div class="button-row"><a class="btn btn-secondary" href="export.php?format=excel">Export Excel</a><a class="btn btn-secondary" href="export.php?format=print" target="_blank">พิมพ์ / PDF</a></div>
</div>
<section class="kpi-grid">
  <article class="kpi-card card"><span>ต้องตรวจ</span><strong><?= $rosterTotal ?></strong><small>ตาม Test Roster</small></article>
  <article class="kpi-card card blue"><span>ตรวจแล้ว</span><strong><?= $testedTotal ?></strong><small><?= $rosterTotal ? round($testedTotal / $rosterTotal * 100) : 0 ?>%</small></article>
  <article class="kpi-card card"><span>ยังไม่ตรวจ</span><strong><?= max(0, $rosterTotal - $testedTotal) ?></strong><small>คงเหลือ</small></article>
  <article class="kpi-card card green"><span>ผ่านเกณฑ์</span><strong><?= $counts['PASS'] ?></strong><small>PASS</small></article>
  <article class="kpi-card card red"><span>เกินเกณฑ์</span><strong><?= $counts['FAIL'] ?></strong><small>FAIL</small></article>
  <article class="kpi-card card amber"><span>รอตรวจซ้ำ</span><strong><?= $counts['RETEST'] ?></strong><small>RETEST</small></article>
  <article class="kpi-card card orange"><span>ต้องตรวจสอบ</span><strong><?= $counts['REVIEW'] ?></strong><small>REVIEW</small></article>
  <article class="kpi-card card"><span>OCR ไม่สำเร็จ</span><strong><?= $ocrFailed ?></strong><small>แก้ไขด้วยผู้ตรวจ</small></article>
</section>
<section class="card roster-card">
  <div class="section-heading"><h2>สถานะ Test Roster</h2><span>Demo <?= $rosterTotal ?> คน</span></div>
  <div class="roster-list">
  <?php foreach (demo_employees() as $employee): ?>
    <div><span class="roster-state <?= isset($testedCodes[$employee['code']]) ? 'done' : '' ?>"><?= isset($testedCodes[$employee['code']]) ? '✓' : '•' ?></span><strong><?= e($employee['name']) ?></strong><span><?= e($employee['code']) ?></span><em><?= isset($testedCodes[$employee['code']]) ? 'ตรวจแล้ว' : 'ยังไม่ตรวจ' ?></em></div>
  <?php endforeach; ?>
  </div>
</section>
<?php app_footer('settings'); ?>
