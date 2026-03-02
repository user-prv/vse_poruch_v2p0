<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/db.php';

$pdo = db();

$err = null;
$msg = null;
$link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));

  if ($email === '') {
    $err = 'Вкажи email';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Некоректний email';
  } else {
    // ВАЖЛИВО: не палимо, чи існує email (анти-перебір)
    $msg = 'Якщо такий email існує — посилання для відновлення сформовано.';

    $st = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    if ($u) {
      $token = bin2hex(random_bytes(32)); // 64 символи hex
      $exp = date('Y-m-d H:i:s', time() + 3600); // 1 година

      $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
          ->execute([(int)$u['id'], $token, $exp]);

      // MVP: показуємо лінк тут. Далі замінимо на відправку email.
      $link = '/reset_password.php?token=' . $token;
    }
  }
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Відновлення паролю — <?= h(APP_NAME) ?></title>
  <style>
    body{font-family:system-ui;background:#fafafa;margin:0}
    .box{max-width:520px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
    input{width:100%;height:40px;border:1px solid #e5e7eb;border-radius:10px;padding:0 10px;box-sizing:border-box;margin-top:10px}
    .btn{height:40px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;padding:0 12px;cursor:pointer;margin-top:12px}
    .btn:hover{background:#0b1220}
    .muted{color:#6b7280;font-size:12px}
    .err{color:#b91c1c;margin-top:10px}
    .hint{background:#f3f4f6;border-radius:10px;padding:10px;font-size:12px;color:#374151;margin-top:10px;word-break:break-all}
    a{color:#2563eb;text-decoration:none}
    a:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="box">
    <h2 style="margin:0 0 6px 0;">Відновлення паролю</h2>
    <div class="muted">Введи email — згенеруємо посилання для скидання паролю (MVP без пошти).</div>

    <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="hint"><?= h($msg) ?></div><?php endif; ?>

    <form method="post">
      <input type="email" name="email" placeholder="Email" required value="<?= h($_POST['email'] ?? '') ?>">
      <button class="btn" type="submit">Згенерувати посилання</button>
    </form>

    <?php if ($link): ?>
      <div class="hint">
        <div><strong>Посилання:</strong></div>
        <div style="margin-top:6px;"><a href="<?= h($link) ?>"><?= h($link) ?></a></div>
      </div>
    <?php endif; ?>

    <div style="margin-top:12px;">
      <a href="/account/login.php">← Повернутись до входу</a>
    </div>
  </div>
</body>
</html>
