<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/db.php';

$pdo = db();

$token = trim((string)($_GET['token'] ?? ''));
$err = null;
$ok = null;

if ($token === '') {
  $err = 'Немає токена';
} else {
  $st = $pdo->prepare("
    SELECT id, user_id
    FROM password_resets
    WHERE token = ? AND expires_at > NOW() AND used_at IS NULL
    LIMIT 1
  ");
  $st->execute([$token]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $err = 'Токен недійсний або протермінований';
  }
}

if (!$err && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $p1 = (string)($_POST['password'] ?? '');
  $p2 = (string)($_POST['password2'] ?? '');

  if (mb_strlen($p1) < 6) {
    $err = 'Пароль має бути мінімум 6 символів';
  } elseif ($p1 !== $p2) {
    $err = 'Паролі не співпадають';
  } else {
    $hash = password_hash($p1, PASSWORD_DEFAULT);

    // У вашому проєкті поле називається password_hash
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
        ->execute([$hash, (int)$row['user_id']]);

    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")
        ->execute([(int)$row['id']]);

    $ok = 'Пароль оновлено ✅ Тепер можна увійти.';
  }
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Новий пароль — <?= h(APP_NAME) ?></title>
  <style>
    body{font-family:system-ui;background:#fafafa;margin:0}
    .box{max-width:520px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
    input{width:100%;height:40px;border:1px solid #e5e7eb;border-radius:10px;padding:0 10px;box-sizing:border-box;margin-top:10px}
    .btn{height:40px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;padding:0 12px;cursor:pointer;margin-top:12px}
    .btn:hover{background:#0b1220}
    .muted{color:#6b7280;font-size:12px}
    .err{color:#b91c1c;margin-top:10px}
    .ok{background:#dcfce7;color:#166534;border-radius:10px;padding:10px;font-size:12px;margin-top:10px}
    .hint{background:#f3f4f6;border-radius:10px;padding:10px;font-size:12px;color:#374151;margin-top:10px}
    a{color:#2563eb;text-decoration:none}
    a:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="box">
    <h2 style="margin:0 0 6px 0;">Новий пароль</h2>
    <div class="muted">Встанови новий пароль для акаунта.</div>

    <?php if ($err): ?>
      <div class="err"><?= h($err) ?></div>
      <div class="hint"><a href="/request_reset.php">← Спробувати ще раз</a></div>
    <?php elseif ($ok): ?>
      <div class="ok"><?= h($ok) ?></div>
      <div class="hint"><a href="/account/login.php">→ Перейти до входу</a></div>
    <?php else: ?>
      <form method="post">
        <input type="password" name="password" placeholder="Новий пароль (мін. 6 символів)" required>
        <input type="password" name="password2" placeholder="Повтори пароль" required>
        <button class="btn" type="submit">Зберегти</button>
      </form>
      <div style="margin-top:12px;">
        <a href="/account/login.php">← Назад</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
