<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_roles(['safety_officer', 'safety_manager', 'hr', 'administrator']);

$records = array_reverse(demo_records());
$query = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));
$records = array_values(array_filter($records, static function (array $record) use ($query, $status, $date): bool {
    $haystack = mb_strtolower($record['employee']['code'] . ' ' . $record['employee']['name'] . ' ' . $record['employee']['position']);
    $queryMatch = $query === '' || str_contains($haystack, mb_strtolower($query));
    $statusMatch = $status === '' || $record['status']['code'] === $status;
    $dateMatch = $date === '' || substr($record['tested_at'], 0, 10) === $date;
    return $queryMatch && $statusMatch && $dateMatch;
}));
app_header('ประวัติการตรวจ', 'history');
?>
<section class="card filter-card">
  <form method="get" class="filter-grid">
    <label>ค้นหา<input name="q" value="<?= e($query) ?>" placeholder="รหัส ชื่อ หรือตำแหน่ง"></label>
    <label>วันที่<input type="date" name="date" value="<?= e($date) ?>"></label>
    <label>สถานะ<select name="status">
      <option value="">ทุกสถานะ</option>
      <?php foreach (['PASS' => 'ผ่านเกณฑ์', 'FAIL' => 'เกินเกณฑ์', 'RETEST' => 'รอตรวจซ้ำ', 'REVIEW' => 'ต้องตรวจสอบ'] as $key => $label): ?>
      <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select></label>
    <button class="btn btn-primary" type="submit">ค้นหา</button>
  </form>
</section>
<div class="section-heading"><h2>รายการตรวจ</h2><span><?= count($records) ?> รายการ</span></div>
<?php if ($records === []): ?>
<section class="empty-state card"><svg><use href="public/assets/icons.svg#history"></use></svg><h2>ยังไม่มีรายการตรวจ</h2><p>เมื่อบันทึกผลจากหน้าตรวจ รายการจะปรากฏที่นี่</p><a class="btn btn-primary" href="index.php">เริ่มตรวจแอลกอฮอล์</a></section>
<?php else: ?>
<div class="history-cards">
<?php foreach ($records as $record): ?>
  <article class="history-card card">
    <img src="<?= e($record['employee_photo']) ?>" alt="ภาพ <?= e($record['employee']['name']) ?>">
    <div class="history-main">
      <div class="history-title"><div><strong><?= e($record['employee']['name']) ?></strong><span><?= e($record['employee']['code']) ?> · <?= e($record['employee']['position']) ?></span></div><span class="status-badge <?= strtolower($record['status']['code']) ?>"><?= e($record['status']['code']) ?></span></div>
      <div class="history-reading"><?= number_format((float) $record['confirmed_value'], 2) ?> <span><?= e($record['unit']) ?></span></div>
      <div class="history-meta"><span><?= e(date('d/m/Y H:i', strtotime($record['tested_at']))) ?></span><span><?= e($record['checkpoint']) ?></span><span><?= e($record['tested_by']) ?></span></div>
      <a class="text-link" href="test-detail.php?id=<?= urlencode($record['test_number']) ?>">ดูรายละเอียดและหลักฐาน →</a>
    </div>
  </article>
<?php endforeach; ?>
</div>
<div class="desktop-table card">
  <table><thead><tr><th>พนักงาน</th><th>ตำแหน่ง</th><th>ผลตรวจ</th><th>สถานะ</th><th>วันเวลา</th><th></th></tr></thead><tbody>
  <?php foreach ($records as $record): ?>
  <tr><td><strong><?= e($record['employee']['name']) ?></strong><small><?= e($record['employee']['code']) ?></small></td><td><?= e($record['employee']['position']) ?></td><td><strong><?= number_format((float) $record['confirmed_value'], 2) ?></strong> <?= e($record['unit']) ?></td><td><span class="status-badge <?= strtolower($record['status']['code']) ?>"><?= e($record['status']['code']) ?></span></td><td><?= e(date('d/m/Y H:i', strtotime($record['tested_at']))) ?></td><td><a href="test-detail.php?id=<?= urlencode($record['test_number']) ?>">รายละเอียด</a></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<?php app_footer('history'); ?>
