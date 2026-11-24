<?php
declare(strict_types=1);

function app_header(string $title, string $active = 'check'): void
{
    $demo = app_config('demo_mode');
    $settings = demo_settings();
    $user = current_user();
    $styleVersion = filemtime(dirname(__DIR__) . '/public/assets/css/app.css');
    ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0B2D63">
  <title><?= e($title) ?> · TCS ALCOHOL CHECK</title>
  <link rel="stylesheet" href="public/assets/css/app.css?v=<?= e($styleVersion) ?>">
</head>
<body data-demo="<?= $demo ? 'true' : 'false' ?>" data-csrf="<?= e(csrf_token()) ?>">
<header class="app-header">
  <div class="brand">
    <img src="public/assets/img/logo_tcs.png" alt="TCS" class="brand-logo">
    <div class="brand-copy">
      <strong>TCS ALCOHOL CHECK</strong>
      <span>ระบบตรวจวัดแอลกอฮอล์</span>
    </div>
  </div>
  <div class="header-meta">
    <span class="connection-dot" id="connectionDot"></span>
    <span id="connectionText">ออนไลน์</span>
    <span class="meta-divider"></span>
    <span><?= e($settings['checkpoint']) ?></span>
    <span class="meta-divider"></span>
    <time id="currentClock"><?= e(date('d/m/Y H:i')) ?></time>
  </div>
</header>
<?php if ($demo): ?>
<div class="demo-banner"><strong>DEMO MODE</strong><span>ข้อมูลทั้งหมดเป็นข้อมูลทดสอบ ไม่ใช่ผลตรวจจริง</span></div>
<?php endif; ?>
<main class="page-shell" id="mainContent">
  <div class="page-title-row">
    <div><p class="eyebrow"><?= e($user['display_name']) ?></p><h1><?= e($title) ?></h1></div>
  </div>
<?php
}

function app_footer(string $active = 'check'): void
{
    $items = [
        'check' => ['href' => 'index.php', 'label' => 'ตรวจแอลกอฮอล์', 'icon' => 'scan'],
        'history' => ['href' => 'history.php', 'label' => 'ประวัติการตรวจ', 'icon' => 'history'],
        'settings' => ['href' => 'settings.php', 'label' => 'ตั้งค่า', 'icon' => 'settings'],
    ];
    ?>
</main>
<nav class="bottom-nav" aria-label="เมนูหลัก">
<?php foreach ($items as $key => $item): ?>
  <a href="<?= e($item['href']) ?>" class="<?= $key === $active ? 'active' : '' ?>">
    <svg aria-hidden="true"><use href="public/assets/icons.svg#<?= e($item['icon']) ?>"></use></svg>
    <span><?= e($item['label']) ?></span>
  </a>
<?php endforeach; ?>
</nav>
<div class="toast-region" id="toastRegion" aria-live="polite"></div>
<script src="public/assets/js/common.js"></script>
</body>
</html>
<?php
}
