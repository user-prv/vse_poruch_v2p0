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
// (не зберігаємо пароль у змінну довше ніж треба)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');

  // Валідація
  if ($email === '' || $pass === '' || $pass2 === '') {
    $error = 'Заповни email і обидва поля пароля';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Некоректний email';
  } elseif (mb_strlen($pass) < 6) {
    $error = 'Пароль має бути мінімум 6 символів';
  } elseif ($pass !== $pass2) {
    $error = 'Паролі не співпадають';
  } else {
    $pdo = db();

    // Перевірка чи існує email (щоб не ловити виняток)
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([$email]);
    if ($check->fetch()) {
      $error = 'Такий email вже зареєстрований';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
      $stmt->execute([$email, $hash]);

      $id = (int)$pdo->lastInsertId();

      // Логінимо одразу після реєстрації
      $_SESSION['user'] = [
        'id' => $id,
        'email' => $email,
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
  <title>Реєстрація — <?= htmlspecialchars(APP_NAME) ?></title>
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
    <h2 style="margin:0 0 6px 0;">Реєстрація</h2>
    <div class="muted">Створи акаунт, щоб додавати та керувати оголошеннями.</div>

    <?php if ($error): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <input name="email" type="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required>

      <input name="password" type="password" placeholder="Пароль (мін. 6 символів)" required>
      <input name="password2" type="password" placeholder="Повтори пароль" required>

      <button type="submit">Створити акаунт</button>
    </form>

    <div class="muted" style="margin-top:12px;">
      Вже є акаунт? <a href="/account/login.php">Увійти</a><br>
      <a href="/account/">← Назад</a>
    </div>
  </div>
</body>
</html>