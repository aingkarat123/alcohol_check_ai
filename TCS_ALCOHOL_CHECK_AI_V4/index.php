<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
require_roles(['safety_officer', 'safety_manager', 'administrator']);
$settings = demo_settings();
app_header('ตรวจแอลกอฮอล์', 'check');
?>
<section class="workflow" data-unit="<?= e($settings['unit']) ?>" data-device="<?= e($settings['device_id']) ?>" data-checkpoint="<?= e($settings['checkpoint']) ?>" data-threshold="<?= e((string) $settings['threshold']) ?>">
  <section class="card step-card" id="employeeStep">
    <div class="save-notice" id="saveNotice" role="status" hidden>
      <strong>บันทึกผลตรวจแล้ว</strong>
      <span>เลขที่รายการ <span id="savedTestNumber"></span></span>
    </div>
    <div class="step-heading">
      <h2><label for="employeeCode">รหัสพนักงาน</label></h2>
    </div>
    <div class="input-with-icon">
      <svg aria-hidden="true"><use href="public/assets/icons.svg#badge"></use></svg>
      <input id="employeeCode" type="text" inputmode="numeric" enterkeyhint="go" autocomplete="off" maxlength="30" placeholder="กรอกรหัสพนักงาน" autofocus>
      <span class="input-spinner" id="employeeSpinner" hidden></span>
    </div>
    <?php if (app_config('demo_mode')): ?>
    <p class="field-hint">รหัสทดลอง: 1001–1005 หรือ 001001</p>
    <?php endif; ?>
    <div class="inline-alert danger" id="employeeError" role="alert" hidden></div>
  </section>

  <div class="card employee-summary" id="employeeSummary" hidden>
    <div class="employee-main">
      <strong id="employeeName"></strong>
      <span><span id="employeeCodeDisplay"></span> · <span id="employeePosition"></span></span>
    </div>
    <button type="button" class="btn btn-secondary" id="changeEmployee">เปลี่ยนพนักงาน</button>
  </div>

  <section class="card step-card" id="cameraStep" hidden>
    <div class="step-heading">
      <h2 id="cameraTitle">ถ่ายภาพพนักงาน</h2>
      <p id="cameraHint" aria-live="polite">ถ่ายภาพขณะพนักงานกำลังเป่าเครื่องตรวจ</p>
    </div>
    <div class="camera-panel" id="cameraPanel" hidden>
      <video id="cameraVideo" playsinline muted hidden></video>
      <canvas id="cameraCanvas" hidden></canvas>
      <img id="devicePreview" alt="ภาพหน้าจอเครื่องตรวจ" hidden>
      <div class="face-guide" id="faceGuide" aria-hidden="true" hidden></div>
      <div class="scan-frame" id="scanFrame" aria-hidden="true" hidden><span class="scan-line"></span></div>
      <div class="scan-status" id="scanStatus" role="status" hidden><span class="pulse"></span>กำลังอ่านค่า…</div>
    </div>
    <div class="button-row camera-actions">
      <button type="button" class="btn btn-primary btn-large" id="startCamera" disabled>
        <svg aria-hidden="true"><use href="public/assets/icons.svg#camera"></use></svg><span>เปิดกล้อง</span>
      </button>
      <button type="button" class="btn btn-primary btn-large" id="captureEmployee" hidden>ถ่ายภาพพนักงาน</button>
      <button type="button" class="btn btn-primary btn-large" id="captureDevice" hidden>สแกนหน้าจอเครื่อง</button>
      <button type="button" class="btn btn-icon" id="toggleTorch" aria-label="ไฟแฟลช" aria-pressed="false" hidden>
        <svg><use href="public/assets/icons.svg#flashlight"></use></svg>
      </button>
      <button type="button" class="btn btn-primary btn-large" id="rescanDevice" hidden>สแกนใหม่</button>
    </div>
    <div class="capture-proof" id="employeeProof" hidden>
      <img id="employeePreview" alt="ภาพพนักงานที่ถ่าย">
      <span>ถ่ายภาพพนักงานแล้ว</span>
      <button type="button" class="btn btn-secondary" id="retakeEmployee">ถ่ายใหม่</button>
    </div>
    <div class="inline-alert danger" id="ocrError" role="alert" hidden></div>
    <?php if (app_config('demo_mode')): ?>
    <details class="demo-picker" id="demoTools" hidden>
      <summary>ทดลองด้วยผลจำลอง</summary>
      <div class="demo-tools">
        <select id="demoOcrValue" aria-label="เลือกค่าจำลอง">
        <option value="0.00">0.00 mg/L</option>
        <option value="0.01">0.01 mg/L</option>
        <option value="0.05">0.05 mg/L</option>
        <option value="0.08">0.08 mg/L</option>
        <option value="0.10">0.10 mg/L</option>
        </select>
        <button type="button" class="btn btn-warning" id="useDemoOcr">ใช้ผลจำลอง</button>
      </div>
    </details>
    <?php endif; ?>
  </section>

  <section class="card step-card" id="confirmStep" hidden>
    <div class="step-heading">
      <h2 id="resultHeading" tabindex="-1">ตรวจสอบผล</h2>
      <p>เทียบค่าด้านล่างกับภาพหน้าจอเครื่องก่อนยืนยัน</p>
    </div>
    <div class="result-layout">
      <div class="evidence-thumb"><img id="resultDeviceImage" alt="หลักฐานหน้าจอเครื่อง"></div>
      <div class="reading-block">
        <span class="source-pill" id="ocrSourcePill">OCR จริง</span>
        <div class="reading-value"><strong id="readingValue">—</strong><span id="readingUnit"><?= e($settings['unit']) ?></span></div>
        <div class="reading-meta">
          <span>Confidence <strong id="readingConfidence">—</strong></span>
          <span>เวลา <strong id="readingTime">—</strong></span>
        </div>
        <div class="status-badge review" id="resultStatus">รอการยืนยัน</div>
      </div>
    </div>
    <div class="confirm-actions">
      <button type="button" class="btn btn-success btn-large" id="saveTest" disabled>
        <svg><use href="public/assets/icons.svg#save"></use></svg><span>ยืนยันและบันทึกผล</span>
      </button>
      <div class="confirm-options">
        <button type="button" class="btn btn-secondary" id="editReading">แก้ไขค่า</button>
        <button type="button" class="btn btn-secondary" id="rescanFromResult">สแกนใหม่</button>
        <button type="button" class="btn btn-secondary" id="retakeFromResult">ถ่ายพนักงานใหม่</button>
      </div>
    </div>
  </section>
</section>

<dialog id="editDialog" class="modal">
  <form method="dialog" id="editForm">
    <div class="modal-heading"><h2>แก้ไขค่า OCR</h2><button value="cancel" class="icon-close" aria-label="ปิด" formnovalidate>×</button></div>
    <p>ระบบจะเก็บค่า OCR เดิมและบันทึกประวัติการแก้ไข</p>
    <label>ค่าที่ถูกต้อง
      <div class="value-input"><input id="correctedValue" type="number" min="0" max="9.999" step="0.001" required><span><?= e($settings['unit']) ?></span></div>
    </label>
    <label>เหตุผลที่แก้ไข
      <textarea id="editReason" rows="3" maxlength="250" required placeholder="เช่น จุดทศนิยมอ่านไม่ถูกต้อง"></textarea>
    </label>
    <div class="button-row"><button value="cancel" class="btn btn-secondary" formnovalidate>ยกเลิก</button><button value="default" class="btn btn-primary" id="applyEdit">บันทึกการแก้ไข</button></div>
  </form>
</dialog>

<script src="public/assets/js/check.js?v=<?= e(filemtime(__DIR__ . '/public/assets/js/check.js')) ?>"></script>
<?php app_footer('check'); ?>
