(function () {
  'use strict';

  var workflow = document.querySelector('.workflow');
  if (!workflow) return;
  var unit = workflow.dataset.unit;
  var threshold = workflow.dataset.threshold === '' ? null : Number(workflow.dataset.threshold);
  var state = {
    employee: null,
    employeePhoto: null,
    devicePhoto: null,
    originalOcrValue: null,
    confirmedValue: null,
    confidence: null,
    ocrSource: null,
    editReason: null,
    cameraStream: null,
    openingCamera: false,
    torchSupported: false,
    ocrController: null,
    requestSequence: 0,
    lookupController: null,
    saving: false
  };

  var el = {};
  [
    'employeeStep', 'employeeCode', 'employeeSpinner', 'employeeError', 'employeeSummary', 'employeeName', 'changeEmployee',
    'employeeCodeDisplay', 'employeePosition', 'cameraStep', 'confirmStep',
    'cameraVideo', 'cameraCanvas', 'cameraPanel', 'cameraTitle', 'cameraHint', 'faceGuide', 'scanFrame',
    'startCamera', 'captureEmployee', 'retakeEmployee', 'employeePreview', 'employeeProof',
    'devicePreview', 'scanStatus', 'captureDevice', 'rescanDevice', 'toggleTorch', 'rescanFromResult', 'retakeFromResult',
    'ocrError', 'demoTools', 'demoOcrValue', 'useDemoOcr', 'resultDeviceImage', 'ocrSourcePill',
    'readingValue', 'readingUnit', 'readingConfidence', 'readingTime', 'resultStatus',
    'editReading', 'resultHeading', 'saveTest', 'editDialog', 'editForm',
    'correctedValue', 'editReason', 'saveNotice', 'savedTestNumber'
  ].forEach(function (id) { el[id] = document.getElementById(id); });

  function show(node, visible) {
    if (!node) return;
    node.hidden = !visible;
  }

  function setAlert(node, message) {
    if (!node) return;
    node.textContent = message || '';
    show(node, Boolean(message));
  }

  function stopCamera() {
    if (state.cameraStream) state.cameraStream.getTracks().forEach(function (track) { track.stop(); });
    state.cameraStream = null;
    state.torchSupported = false;
    el.cameraVideo.srcObject = null;
    el.toggleTorch.classList.remove('active');
    el.toggleTorch.setAttribute('aria-pressed', 'false');
  }

  function renderCamera() {
    var hasEmployeePhoto = Boolean(state.employeePhoto);
    var hasDevicePhoto = Boolean(state.devicePhoto);
    var live = Boolean(state.cameraStream) && !state.openingCamera;
    var reading = Boolean(state.ocrController);
    show(el.cameraVideo, live);
    show(el.cameraPanel, live || hasDevicePhoto);
    show(el.devicePreview, hasDevicePhoto);
    show(el.faceGuide, live && !hasEmployeePhoto);
    show(el.scanFrame, live && hasEmployeePhoto);
    show(el.startCamera, !live && !hasDevicePhoto);
    el.startCamera.disabled = !state.employee || state.openingCamera;
    show(el.captureEmployee, live && !hasEmployeePhoto);
    show(el.captureDevice, live && hasEmployeePhoto);
    show(el.toggleTorch, live && hasEmployeePhoto && state.torchSupported);
    show(el.employeeProof, hasEmployeePhoto);
    el.retakeEmployee.disabled = reading || state.openingCamera;
    show(el.rescanDevice, hasDevicePhoto && !reading);
    show(el.demoTools, hasEmployeePhoto && !hasDevicePhoto);
    show(el.scanStatus, reading);
    var hint = 'ถ่ายภาพขณะพนักงานกำลังเป่าเครื่องตรวจ';
    if (hasEmployeePhoto) hint = 'เล็งตัวเลขบนเครื่องให้อยู่ในกรอบ';
    if (hasDevicePhoto) hint = reading ? 'กำลังอ่านค่าจากภาพ' : 'จัดหน้าจอเครื่องให้อยู่ในกรอบแล้วลองอีกครั้ง';
    el.cameraTitle.textContent = hasEmployeePhoto ? 'สแกนหน้าจอเครื่องตรวจ' : 'ถ่ายภาพพนักงาน';
    if (el.cameraHint.textContent !== hint) el.cameraHint.textContent = hint;
  }

  function renderStep() {
    var identified = Boolean(state.employee);
    var hasResult = identified && state.confirmedValue !== null;
    show(el.employeeStep, !identified);
    show(el.employeeSummary, identified);
    show(el.cameraStep, identified && !hasResult);
    show(el.confirmStep, hasResult);
  }

  function clearScan() {
    if (state.ocrController) state.ocrController.abort();
    state.ocrController = null;
    state.devicePhoto = null;
    state.originalOcrValue = null;
    state.confirmedValue = null;
    state.confidence = null;
    state.ocrSource = null;
    state.editReason = null;
    el.devicePreview.removeAttribute('src');
    el.resultDeviceImage.removeAttribute('src');
    el.saveTest.disabled = true;
    if (el.demoTools) el.demoTools.open = false;
    el.readingValue.textContent = '—';
    el.readingUnit.textContent = unit;
    el.readingConfidence.textContent = '—';
    el.readingTime.textContent = '—';
    el.resultStatus.className = 'status-badge review';
    el.resultStatus.textContent = 'รอการยืนยัน';
    setAlert(el.ocrError, '');
    renderStep();
  }

  function clearEmployee() {
    state.employee = null;
    state.employeePhoto = null;
    state.openingCamera = false;
    el.employeePreview.removeAttribute('src');
    stopCamera();
    clearScan();
    renderCamera();
  }

  async function lookupEmployee(code, sequence) {
    if (state.lookupController) state.lookupController.abort();
    state.lookupController = new AbortController();
    show(el.employeeSpinner, true);
    setAlert(el.employeeError, '');
    try {
      var response = await fetch('api/employee.php?code=' + encodeURIComponent(code), {
        signal: state.lookupController.signal,
        headers: {'Accept': 'application/json'}
      });
      var result = await response.json();
      if (sequence !== state.requestSequence || el.employeeCode.value.trim() !== code) return;
      if (!response.ok || !result.success) throw new Error(result.message || 'ไม่พบพนักงาน');
      state.employee = result.employee;
      el.employeeName.textContent = result.employee.name;
      el.employeeCodeDisplay.textContent = result.employee.code;
      el.employeePosition.textContent = result.employee.position;
      renderStep();
      renderCamera();
      el.startCamera.focus();
    } catch (error) {
      if (error.name === 'AbortError') return;
      if (sequence !== state.requestSequence) return;
      clearEmployee();
      setAlert(el.employeeError, error.message);
    } finally {
      if (sequence === state.requestSequence) show(el.employeeSpinner, false);
    }
  }

  var lookupTimer;
  el.employeeCode.addEventListener('input', function () {
    if (state.saving) return;
    show(el.saveNotice, false);
    window.clearTimeout(lookupTimer);
    state.requestSequence += 1;
    clearEmployee();
    show(el.employeeSpinner, false);
    setAlert(el.employeeError, '');
    var code = el.employeeCode.value.trim();
    if (!code) return;
    var sequence = state.requestSequence;
    lookupTimer = window.setTimeout(function () { lookupEmployee(code, sequence); }, 380);
  });
  el.employeeCode.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' || state.saving) return;
    event.preventDefault();
    window.clearTimeout(lookupTimer);
    state.requestSequence += 1;
    clearEmployee();
    show(el.employeeSpinner, false);
    setAlert(el.employeeError, '');
    var code = el.employeeCode.value.trim();
    if (code) lookupEmployee(code, state.requestSequence);
  });

  async function openCamera() {
    if (!state.employee || state.openingCamera || state.cameraStream) return;
    var sequence = state.requestSequence;
    state.openingCamera = true;
    renderCamera();
    setAlert(el.ocrError, '');
    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('กรุณาเปิดผ่าน HTTPS หรือ localhost เพื่อใช้กล้อง');
      }
      var stream = await navigator.mediaDevices.getUserMedia({
        video: {facingMode: {ideal: 'environment'}, width: {ideal: 1280}, height: {ideal: 720}},
        audio: false
      });
      if (sequence !== state.requestSequence) {
        stream.getTracks().forEach(function (track) { track.stop(); });
        return;
      }
      var track = stream.getVideoTracks()[0];
      state.cameraStream = stream;
      el.cameraVideo.srcObject = stream;
      show(el.cameraPanel, true);
      show(el.cameraVideo, true);
      await el.cameraVideo.play();
      if (sequence !== state.requestSequence) return;
      state.torchSupported = Boolean(track.getCapabilities && track.getCapabilities().torch);
    } catch (error) {
      if (sequence !== state.requestSequence) return;
      stopCamera();
      setAlert(el.ocrError, error.name === 'NotAllowedError'
        ? 'กรุณาอนุญาตให้เบราว์เซอร์ใช้กล้อง แล้วลองใหม่'
        : error.message);
    } finally {
      if (sequence === state.requestSequence) {
        state.openingCamera = false;
        renderCamera();
        if (state.cameraStream) (state.employeePhoto ? el.captureDevice : el.captureEmployee).focus({preventScroll: true});
      }
    }
  }
  el.startCamera.addEventListener('click', openCamera);

  function capture(frame) {
    var video = el.cameraVideo;
    var canvas = el.cameraCanvas;
    var width = video.videoWidth;
    var height = video.videoHeight;
    if (!width || !height || video.readyState < 2) {
      throw new Error('กล้องยังเตรียมภาพอยู่ กรุณาลองถ่ายอีกครั้ง');
    }
    var x = 0, y = 0, cropWidth = width, cropHeight = height;
    if (frame) {
      // Convert the on-screen frame to sensor pixels using the centered cover crop.
      var preview = video.getBoundingClientRect();
      var bounds = frame.getBoundingClientRect();
      var scale = Math.max(preview.width / width, preview.height / height);
      var imageLeft = preview.left + (preview.width - width * scale) / 2;
      var imageTop = preview.top + (preview.height - height * scale) / 2;
      x = Math.max(0, (bounds.left - imageLeft) / scale);
      y = Math.max(0, (bounds.top - imageTop) / scale);
      cropWidth = Math.min(width - x, bounds.width / scale);
      cropHeight = Math.min(height - y, bounds.height / scale);
    }
    var resize = Math.min(1, (frame ? 1200 : 960) / Math.max(cropWidth, cropHeight));
    canvas.width = Math.max(1, Math.round(cropWidth * resize));
    canvas.height = Math.max(1, Math.round(cropHeight * resize));
    canvas.getContext('2d').drawImage(video, x, y, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.88);
  }

  el.captureEmployee.addEventListener('click', function () {
    if (!state.employee || !state.cameraStream || state.employeePhoto) return;
    try {
      state.employeePhoto = capture();
      el.employeePreview.src = state.employeePhoto;
      setAlert(el.ocrError, '');
      renderCamera();
      el.captureDevice.focus({preventScroll: true});
    } catch (error) {
      setAlert(el.ocrError, error.message);
    }
  });

  function retakeEmployee() {
    if (state.saving || !state.employeePhoto || state.ocrController) return;
    state.employeePhoto = null;
    el.employeePreview.removeAttribute('src');
    clearScan();
    renderCamera();
    if (state.cameraStream) {
      if (el.toggleTorch.getAttribute('aria-pressed') === 'true') setTorch(false);
      el.captureEmployee.focus({preventScroll: true});
    } else {
      openCamera();
    }
  }
  el.retakeEmployee.addEventListener('click', retakeEmployee);
  el.retakeFromResult.addEventListener('click', retakeEmployee);

  async function setTorch(enabled) {
    var stream = state.cameraStream;
    var track = stream && stream.getVideoTracks()[0];
    if (!track) return;
    try {
      await track.applyConstraints({advanced: [{torch: enabled}]});
      if (state.cameraStream !== stream) return;
      el.toggleTorch.classList.toggle('active', enabled);
      el.toggleTorch.setAttribute('aria-pressed', String(enabled));
    } catch (error) {
      if (state.cameraStream === stream) window.showToast('เปลี่ยนสถานะไฟแฟลชไม่สำเร็จ', 'danger');
    }
  }
  el.toggleTorch.addEventListener('click', function () {
    setTorch(el.toggleTorch.getAttribute('aria-pressed') !== 'true');
  });

  function canvasBlob(canvas) {
    return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.9); });
  }

  function formatReading(value) {
    return value.toFixed(Math.round(value * 1000) % 10 === 0 ? 2 : 3);
  }

  function applyOcrResult(result) {
    if (!result.success || !/^\d+(?:\.\d{1,3})?$/.test(String(result.detected_value || ''))) {
      throw new Error(result.error_message || result.message || 'อ่านตัวเลขไม่ชัดเจน กรุณาสแกนใหม่');
    }
    state.originalOcrValue = Number(result.detected_value);
    state.confirmedValue = state.originalOcrValue;
    state.confidence = Number(result.confidence || 0);
    state.ocrSource = result.source || 'python_ocr';
    state.editReason = null;
    el.resultDeviceImage.src = state.devicePhoto;
    el.readingValue.textContent = formatReading(state.confirmedValue);
    el.readingUnit.textContent = result.detected_unit || unit;
    el.readingConfidence.textContent = Math.round(state.confidence * 100) + '%';
    el.readingTime.textContent = new Intl.DateTimeFormat('th-TH', {timeStyle: 'medium'}).format(new Date());
    var simulated = state.ocrSource === 'demo_simulation';
    el.ocrSourcePill.textContent = simulated ? 'ผลจำลอง DEMO' : 'Python OCR';
    el.ocrSourcePill.classList.toggle('demo', simulated);
    updateStatus();
    el.saveTest.disabled = false;
    renderStep();
    window.scrollTo({top: 0});
    el.resultHeading.focus({preventScroll: true});
    if (result.warning) window.showToast(result.warning, 'warning');
  }

  async function readDevicePhoto(photo, send) {
    var controller = new AbortController();
    state.ocrController = controller;
    state.devicePhoto = photo;
    el.devicePreview.src = photo;
    stopCamera();
    setAlert(el.ocrError, '');
    renderCamera();
    try {
      var response = await send(controller.signal);
      var result = await response.json();
      if (controller !== state.ocrController) return;
      if (!response.ok) throw new Error(result.message || 'OCR Service ไม่พร้อม');
      applyOcrResult(result);
    } catch (error) {
      if (controller === state.ocrController) setAlert(el.ocrError, error.message);
    } finally {
      if (controller === state.ocrController) {
        state.ocrController = null;
        renderCamera();
      }
    }
  }

  el.captureDevice.addEventListener('click', function () {
    if (!state.employeePhoto || !state.cameraStream || state.devicePhoto) return;
    try {
      var photo = capture(el.scanFrame);
      readDevicePhoto(photo, async function (signal) {
        var blob = await canvasBlob(el.cameraCanvas);
        if (!blob) throw new Error('เตรียมภาพสำหรับ OCR ไม่สำเร็จ กรุณาสแกนใหม่');
        if (signal.aborted) throw new Error('ยกเลิกการสแกน');
        var form = new FormData();
        form.append('image', blob, 'device-screen.jpg');
        return fetch('api/ocr.php', {
          method: 'POST',
          headers: {'X-CSRF-Token': window.tcsCsrf},
          body: form,
          signal: signal
        });
      });
    } catch (error) {
      setAlert(el.ocrError, error.message);
    }
  });

  function rescanDevice() {
    if (!state.employeePhoto || state.ocrController || state.saving) return;
    clearScan();
    renderCamera();
    openCamera();
  }
  el.rescanDevice.addEventListener('click', rescanDevice);
  el.rescanFromResult.addEventListener('click', rescanDevice);

  function makeDemoImage(value) {
    var canvas = el.cameraCanvas;
    canvas.width = 1200;
    canvas.height = 460;
    var context = canvas.getContext('2d');
    context.fillStyle = '#111827';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.strokeStyle = '#475569';
    context.lineWidth = 18;
    context.strokeRect(35, 35, canvas.width - 70, canvas.height - 70);
    context.font = 'bold 230px monospace';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillStyle = '#9BE15D';
    context.shadowColor = '#9BE15D';
    context.shadowBlur = 24;
    context.fillText(value, canvas.width / 2, canvas.height / 2);
    context.shadowBlur = 0;
    context.font = '44px Arial';
    context.fillText(unit, canvas.width - 150, canvas.height - 70);
    return canvas.toDataURL('image/jpeg', 0.9);
  }

  if (el.useDemoOcr) {
    el.useDemoOcr.addEventListener('click', function () {
      if (!state.employeePhoto || state.devicePhoto || state.openingCamera) return;
      var value = el.demoOcrValue.value;
      readDevicePhoto(makeDemoImage(value), function (signal) {
        return fetch('api/demo-ocr.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.tcsCsrf},
          body: JSON.stringify({value: value}),
          signal: signal
        });
      });
    });
  }

  function updateStatus() {
    el.resultStatus.className = 'status-badge review';
    if (threshold === null || !Number.isFinite(state.confirmedValue)) {
      el.resultStatus.textContent = 'REVIEW · ยังไม่ได้ตั้งเกณฑ์';
      return;
    }
    if (state.confirmedValue < threshold) {
      el.resultStatus.className = 'status-badge pass';
      el.resultStatus.textContent = 'PASS · ผ่านเกณฑ์';
    } else {
      el.resultStatus.className = 'status-badge fail';
      el.resultStatus.textContent = 'FAIL · เกินเกณฑ์';
    }
  }

  el.editReading.addEventListener('click', function () {
    if (state.saving || state.confirmedValue === null) return;
    el.correctedValue.value = formatReading(state.confirmedValue);
    el.editReason.value = state.editReason || '';
    el.editDialog.showModal();
  });

  el.editForm.addEventListener('submit', function (event) {
    if (event.submitter && event.submitter.value === 'cancel') return;
    event.preventDefault();
    var corrected = Number(el.correctedValue.value);
    var reason = el.editReason.value.trim();
    if (!Number.isFinite(corrected) || corrected < 0 || !reason) {
      window.showToast('กรุณาระบุค่าและเหตุผลให้ครบ', 'danger');
      return;
    }
    state.confirmedValue = corrected;
    state.editReason = reason;
    el.readingValue.textContent = formatReading(corrected);
    updateStatus();
    el.editDialog.close();
    window.showToast('บันทึกค่าแก้ไขและเหตุผลแล้ว', 'success');
  });

  el.saveTest.addEventListener('click', async function () {
    if (state.saving || el.saveTest.disabled || !state.employee || !state.employeePhoto || !state.devicePhoto || state.confirmedValue === null) return;
    state.saving = true;
    el.changeEmployee.disabled = true;
    el.confirmStep.inert = true;
    el.saveTest.disabled = true;
    el.saveTest.classList.add('loading');
    var payload = {
      employee_code: state.employee.code,
      employee_photo: state.employeePhoto,
      device_photo: state.devicePhoto,
      original_ocr_value: state.originalOcrValue,
      confirmed_value: state.confirmedValue,
      unit: unit,
      ocr_confidence: state.confidence,
      ocr_source: state.ocrSource,
      edit_reason: state.editReason,
      device_id: workflow.dataset.device,
      checkpoint: workflow.dataset.checkpoint,
      confirmed: true
    };
    try {
      var response = await fetch('api/save-test.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.tcsCsrf},
        body: JSON.stringify(payload)
      });
      var result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'บันทึกไม่สำเร็จ');
      resetAll();
      el.savedTestNumber.textContent = result.test_number;
      show(el.saveNotice, true);
    } catch (error) {
      window.showToast(error.message, 'danger');
      el.saveTest.disabled = false;
    } finally {
      state.saving = false;
      el.changeEmployee.disabled = false;
      el.confirmStep.inert = false;
      el.saveTest.classList.remove('loading');
    }
  });

  function resetAll() {
    window.clearTimeout(lookupTimer);
    state.requestSequence += 1;
    if (state.lookupController) state.lookupController.abort();
    el.employeeCode.value = '';
    show(el.saveNotice, false);
    show(el.employeeSpinner, false);
    setAlert(el.employeeError, '');
    clearEmployee();
    window.scrollTo({top: 0, behavior: 'smooth'});
    el.employeeCode.focus({preventScroll: true});
  }
  el.changeEmployee.addEventListener('click', function () {
    if (!state.saving) resetAll();
  });
  window.addEventListener('pagehide', function () {
    if (state.openingCamera) {
      state.requestSequence += 1;
      state.openingCamera = false;
    }
    stopCamera();
    renderCamera();
  });
})();
