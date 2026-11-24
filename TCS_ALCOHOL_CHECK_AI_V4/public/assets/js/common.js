(function () {
  'use strict';

  var clock = document.getElementById('currentClock');
  function updateClock() {
    if (!clock) return;
    clock.textContent = new Intl.DateTimeFormat('th-TH', {
      dateStyle: 'short',
      timeStyle: 'short',
      timeZone: 'Asia/Bangkok'
    }).format(new Date());
  }
  updateClock();
  window.setInterval(updateClock, 30000);

  var dot = document.getElementById('connectionDot');
  var text = document.getElementById('connectionText');
  function updateConnection() {
    var online = navigator.onLine;
    if (dot) dot.classList.toggle('offline', !online);
    if (text) text.textContent = online ? 'ออนไลน์' : 'ออฟไลน์';
  }
  window.addEventListener('online', updateConnection);
  window.addEventListener('offline', updateConnection);
  updateConnection();

  window.tcsCsrf = document.body.dataset.csrf || '';
  window.showToast = function (message, type) {
    var region = document.getElementById('toastRegion');
    if (!region) return;
    var toast = document.createElement('div');
    toast.className = 'toast ' + (type || 'info');
    toast.textContent = message;
    region.appendChild(toast);
    window.setTimeout(function () {
      toast.classList.add('leaving');
      window.setTimeout(function () { toast.remove(); }, 250);
    }, 3600);
  };
})();
