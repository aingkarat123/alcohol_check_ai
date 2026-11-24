const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../public/assets/js/check.js'), 'utf8');
const image = (id) => 'data:image/jpeg;base64,' + id;

function startFlow(readOcr) {
  const elements = new Map();
  const stats = { opens: 0, stops: 0, draws: [], saved: null };
  const get = (id) => {
    if (!elements.has(id)) {
      elements.set(id, {
        events: {},
        dataset: {},
        classList: { add() {}, remove() {}, toggle() {} },
        addEventListener(event, callback) { this.events[event] = callback; },
        removeAttribute(name) { delete this[name]; },
        setAttribute(name, value) { this[name] = value; },
        getAttribute(name) { return this[name]; },
        focus() {},
        scrollIntoView() {},
        showModal() { this.open = true; }
      });
    }
    return elements.get(id);
  };
  Object.assign(get('cameraVideo'), {
    videoWidth: 1280, videoHeight: 720, readyState: 2,
    play: async () => {},
    getBoundingClientRect: () => ({ left: 0, top: 0, width: 320, height: 300 })
  });
  get('scanFrame').getBoundingClientRect = () => ({ left: 32, top: 75, width: 256, height: 150 });
  Object.assign(get('cameraCanvas'), {
    getContext: () => ({
      drawImage: (_video, ...args) => stats.draws.push(args)
    }),
    toDataURL: () => image(stats.draws.length),
    toBlob: (callback) => callback(new Blob(['screen'], { type: 'image/jpeg' }))
  });
  const respond = (data) => ({ ok: true, json: async () => data });
  vm.runInNewContext(script, {
    AbortController,
    FormData,
    Intl,
    navigator: {
      mediaDevices: {
        async getUserMedia(options) {
          assert.equal(options.video.facingMode.ideal, 'environment');
          stats.opens++;
          const track = { stop() { stats.stops++; } };
          return { getTracks: () => [track], getVideoTracks: () => [track] };
        }
      }
    },
    document: {
      querySelector: () => ({ dataset: { unit: 'mg/L', threshold: '0.05', device: 'TEST', checkpoint: 'Gate' } }),
      querySelectorAll: () => [],
      getElementById: get
    },
    window: { showToast() {}, addEventListener() {}, scrollTo() {}, setTimeout, clearTimeout },
    async fetch(url, request) {
      if (url.startsWith('api/employee.php')) {
        const code = new URL(url, 'http://localhost').searchParams.get('code');
        return respond({ success: true, employee: { name: 'Demo', code, position: 'Tester' } });
      }
      if (url === 'api/ocr.php') return respond(await readOcr(request));
      assert.equal(url, 'api/save-test.php');
      stats.saved = JSON.parse(request.body);
      return respond({ success: true, test_number: 'TEST-1' });
    }
  });
  const click = (id) => get(id).events.click();
  async function choose(code) {
    get('employeeCode').value = code;
    get('employeeCode').events.keydown({ key: 'Enter', preventDefault() {} });
    await settle();
  }
  return { get, stats, click, choose };
}

const settle = () => new Promise((resolve) => setImmediate(resolve));

test('one camera opening captures two different images and saves both after confirmation', async () => {
  const app = startFlow(async (request) => {
    assert.equal(request.body.get('image').name, 'device-screen.jpg');
    return { success: true, detected_value: '0.085', detected_unit: 'mg/L', confidence: 0.95 };
  });
  await app.choose('1001');
  assert.equal(app.get('employeeStep').hidden, true);
  assert.equal(app.get('cameraStep').hidden, false);
  assert.equal(app.get('cameraPanel').hidden, true);
  await app.click('startCamera');
  const liveStream = app.get('cameraVideo').srcObject;
  assert.equal(app.get('faceGuide').hidden, false);
  app.click('captureEmployee');
  assert.equal(app.get('cameraVideo').srcObject, liveStream);
  assert.equal(app.stats.opens, 1);
  assert.equal(app.stats.stops, 0);
  assert.equal(app.get('faceGuide').hidden, true);
  assert.equal(app.get('scanFrame').hidden, false);
  await app.click('saveTest');
  assert.equal(app.stats.saved, null);
  app.click('captureDevice');
  await settle();
  assert.equal(app.stats.stops, 1);
  assert.equal(app.get('readingValue').textContent, '0.085');
  assert.equal(app.get('cameraStep').hidden, true);
  assert.equal(app.get('confirmStep').hidden, false);
  assert.ok(app.stats.draws[1][2] < app.stats.draws[0][2]);
  assert.equal(app.stats.saved, null);
  await app.click('saveTest');
  assert.equal(app.stats.saved.employee_photo, image(1));
  assert.equal(app.stats.saved.device_photo, image(2));
  assert.equal(app.stats.saved.confirmed, true);
  assert.equal(app.stats.saved.confirmed_value, 0.085);
  assert.equal(app.get('employeeStep').hidden, false);
  assert.equal(app.get('employeeCode').value, '');
  assert.equal(app.get('saveNotice').hidden, false);
  assert.equal(app.get('savedTestNumber').textContent, 'TEST-1');
});

test('failed OCR can be retried and a late response cannot affect the next employee', async () => {
  let finishOcr;
  const app = startFlow(async () => new Promise((resolve) => { finishOcr = resolve; }));
  await app.choose('1001');
  await app.click('startCamera');
  app.click('captureEmployee');
  app.click('captureDevice');
  await settle();
  finishOcr({ success: false, error_message: 'อ่านค่าไม่ได้' });
  await settle();
  assert.equal(app.get('confirmStep').hidden, true);
  assert.equal(app.get('ocrError').textContent, 'อ่านค่าไม่ได้');
  assert.equal(app.get('rescanDevice').hidden, false);
  app.click('rescanDevice');
  await settle();
  assert.equal(app.get('employeePreview').src, image(1));
  assert.equal(app.get('captureDevice').hidden, false);
  app.click('captureDevice');
  await settle();
  await app.choose('1002');
  finishOcr({ success: true, detected_value: '0.08' });
  await settle();
  assert.equal(app.get('employeePreview').src, undefined);
  assert.equal(app.get('devicePreview').src, undefined);
  assert.equal(app.get('confirmStep').hidden, true);
  assert.equal(app.get('readingValue').textContent, '—');
});
