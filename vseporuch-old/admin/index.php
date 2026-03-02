<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Адмінка — <?= htmlspecialchars(APP_NAME) ?></title>
  <style>
    body{margin:0;font-family:system-ui;background:#fafafa}
    .wrap{max-width:900px;margin:30px auto;padding:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    .pad{padding:12px}
    .row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    a.btn{height:38px;border-radius:10px;background:#111827;color:#fff;text-decoration:none;font-weight:900;display:inline-flex;align-items:center;padding:0 12px}
    a.btn.primary{background:#2563eb}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="pad">
      <div style="font-weight:900;font-size:18px;">Адмінка</div>
      <div class="row">
        <a class="btn primary" href="/admin/users.php">Користувачі</a>
        <a class="btn primary" href="/admin/listings.php">Оголошення</a>
        <a class="btn primary" href="/admin/verification.php">Верифікація</a>
        <a class="btn primary" href="/admin/categories.php">Категорії</a>
        <a class="btn" href="/">На карту</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>