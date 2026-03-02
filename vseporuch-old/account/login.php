<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

if (isLoggedIn()) {
  redirect('/dashboard/');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $error = 'Заповни email і пароль';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Некоректний email';
  } else {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
      $error = 'Невірний email або пароль';
    } else {
      $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'email' => (string)$user['email'],
        'role' => 'user'
      ];

      redirect('/dashboard/');
    }
  }
}
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Вхід — <?= htmlspecialchars(APP_NAME) ?></title>
  <style>
    body{font-family:system-ui;background:#fafafa;margin:0}
    .box{max-width:420px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
    input{width:100%;height:40px;border:1px solid #e5e7eb;border-radius:10px;padding:0 10px;margin:8px 0;box-sizing:border-box}
    button{width:100%;height:40px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;cursor:pointer}
    button:hover{background:#0b1220}
    .muted{color:#6b7280;font-size:13px}
    a{color:#2563eb;text-decoration:none}
    .err{color:#b91c1c;margin:10px 0}
  </style>
</head>
<body>
  <div class="box">
    <h2 style="margin:0 0 6px 0;">Вхід</h2>
    <div class="muted">Увійди, щоб керувати оголошеннями.</div>

    <?php if ($error): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <input name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required>
      <input name="password" type="password" placeholder="Пароль" required>
      <button type="submit">Увійти</button>
    
      <div style="margin-top:10px;">
        <a href="/request_reset.php" style="color:#2563eb;text-decoration:none;font-weight:800;">Відновити пароль</a>
      </div>
    </form>

    <div class="muted" style="margin-top:12px;">
      Нема акаунту? <a href="/account/register.php">Зареєструватись</a><br>
      <a href="/account/">← Назад</a>
    </div>
  </div>
</body>
</html>