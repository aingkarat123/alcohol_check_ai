<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_roles(['safety_officer', 'safety_manager', 'administrator']);
$settings = demo_settings();
$canEdit = in_array(current_user()['role'], ['safety_manager', 'administrator'], true);
app_header('ตั้งค่า', 'settings');
?>
<div class="settings-layout">
  <section class="card settings-card">
    <div class="section-heading"><div><h2>จุดตรวจและเครื่องตรวจ</h2><p>มีผลกับรายการตรวจใหม่เท่านั้น</p></div></div>
    <form id="settingsForm">
      <label>จุดตรวจ<input name="checkpoint" value="<?= e($settings['checkpoint']) ?>" <?= $canEdit ? '' : 'disabled' ?> required></label>
      <label>รหัสเครื่องตรวจ<input name="device_id" value="<?= e($settings['device_id']) ?>" <?= $canEdit ? '' : 'disabled' ?> required></label>
      <label>Device Profile<select name="device_profile" disabled><option value="generic_lcd">Generic LCD (เตรียมปรับเทียบ)</option></select></label>
      <div class="form-grid-2">
        <label>หน่วยวัด<select name="unit" <?= $canEdit ? '' : 'disabled' ?>><option <?= $settings['unit'] === 'mg/L' ? 'selected' : '' ?>>mg/L</option><option <?= $settings['unit'] === 'g/L' ? 'selected' : '' ?>>g/L</option><option <?= $settings['unit'] === '%BAC' ? 'selected' : '' ?>>%BAC</option></select></label>
        <label>Threshold<input type="number" name="threshold" step="0.001" min="0" value="<?= e((string) $settings['threshold']) ?>" <?= $canEdit ? '' : 'disabled' ?>></label>
      </div>
      <div class="inline-alert warning">ค่าเกณฑ์นี้เป็นข้อมูล Demo เท่านั้น ต้องได้รับการอนุมัติตามนโยบายบริษัทและหน่วยวัดจริงก่อน Production</div>
      <div class="form-grid-2">
        <label>เฟรมที่ต้องตรงกัน<input type="number" name="stable_frames" min="2" max="10" value="<?= e((string) $settings['stable_frames']) ?>" <?= $canEdit ? '' : 'disabled' ?>></label>
        <label>Confidence ขั้นต่ำ<input type="number" name="confidence_threshold" min="0.5" max="1" step="0.01" value="<?= e((string) $settings['confidence_threshold']) ?>" <?= $canEdit ? '' : 'disabled' ?>></label>
      </div>
      <?php if ($canEdit): ?><button class="btn btn-primary btn-large" type="submit">บันทึกการตั้งค่า</button><?php endif; ?>
    </form>
  </section>
  <aside>
    <section class="card quick-link-card"><h2>สำหรับผู้ดูแลระบบ</h2><p>ดู KPI รายชื่อที่ต้องตรวจ และส่งออกรายงาน</p><a class="btn btn-secondary" href="dashboard.php">เปิด Dashboard</a></section>
    <section class="card system-card"><h2>สถานะระบบ</h2><dl><div><dt>โหมด</dt><dd>DEMO</dd></div><div><dt>Employee Master</dt><dd>ข้อมูลจำลอง</dd></div><div><dt>OCR Service</dt><dd id="ocrServiceState">ตรวจเมื่อใช้งาน</dd></div><div><dt>Timezone</dt><dd>Asia/Bangkok</dd></div></dl></section>
  </aside>
</div>
<script src="public/assets/js/settings.js"></script>
<?php app_footer('settings'); ?>
