(function () {
  'use strict';
  var form = document.getElementById('settingsForm');
  if (!form || !form.querySelector('button[type="submit"]')) return;
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    var button = form.querySelector('button[type="submit"]');
    var data = Object.fromEntries(new FormData(form).entries());
    data.stable_frames = Number(data.stable_frames);
    data.confidence_threshold = Number(data.confidence_threshold);
    data.threshold = data.threshold === '' ? null : Number(data.threshold);
    button.disabled = true;
    try {
      var response = await fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.tcsCsrf},
        body: JSON.stringify(data)
      });
      var result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'บันทึกไม่สำเร็จ');
      window.showToast(result.message, 'success');
      window.setTimeout(function () { window.location.reload(); }, 600);
    } catch (error) {
      window.showToast(error.message, 'danger');
      button.disabled = false;
    }
  });
})();
