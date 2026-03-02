
<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

if (isLoggedIn()) redirect('/account/logout.php'); // або /dashboard/ пізніше
?><!doctype html>
<html lang="uk">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Кабінет — <?= APP_NAME ?></title>
<style>
  body{font-family:system-ui;background:#fafafa;margin:0}
  .box{max-width:420px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
  a{display:block;text-align:center;padding:12px;border-radius:10px;text-decoration:none;font-weight:700}
  .primary{background:#111827;color:#fff;margin-top:10px}
  .ghost{border:1px solid #e5e7eb;color:#111827;margin-top:10px}
</style>
</head>
<body>
  <div class="box">
    <h2 style="margin:0 0 6px 0;">Кабінет користувача</h2>
    <div style="color:#6b7280;font-size:13px;">Оберіть дію:</div>

    <a class="primary" href="/account/login.php">Увійти</a>
    <a class="ghost" href="/account/register.php">Зареєструватись</a>

    <div style="margin-top:12px;font-size:12px;color:#6b7280;">
      Повернутись на <a href="/">головну</a>
    </div>
  </div>
</body>
</html>