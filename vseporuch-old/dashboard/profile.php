<?php
declare(strict_types=1);

/**
 * Сторінка профілю користувача:
 * - аватар (фото)
 * - нікнейм
 * - телефон
 * - "про себе"
 * - email (тільки показуємо, редагування можна додати пізніше)
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

/* 1) Захист: тільки для залогінених */
if (!isLoggedIn()) redirect('/account/');

$uid = currentUserId();
$pdo = db();

/* 2) Отримуємо поточні дані користувача */
$stmt = $pdo->prepare("SELECT id, email, nickname, phone, about, avatar_path FROM users WHERE id=? LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) redirect('/dashboard/');

$error = null;
$success = isset($_GET['saved']) && $_GET['saved'] === '1' ? 'Зміни збережено ✅' : null;

/* 3) Обробка форми збереження */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nickname = trim((string)($_POST['nickname'] ?? ''));
  $phone    = trim((string)($_POST['phone'] ?? ''));
  $about    = trim((string)($_POST['about'] ?? ''));

  // Невелика валідація
  if ($nickname !== '' && mb_strlen($nickname) > 60) {
    $error = 'Нікнейм занадто довгий (макс 60 символів)';
  } elseif ($phone !== '' && mb_strlen($phone) > 30) {
    $error = 'Телефон занадто довгий (макс 30 символів)';
  } else {
    // 3.1) Якщо завантажили аватар — зберігаємо
    $newAvatar = saveUploadedAvatar($_FILES['avatar'] ?? [], __DIR__ . '/../uploads', '/uploads');
    $avatarToSave = $user['avatar_path'];
    if ($newAvatar) $avatarToSave = $newAvatar;

    // 3.2) Оновлюємо дані користувача
    $u = $pdo->prepare("
      UPDATE users
      SET nickname=?, phone=?, about=?, avatar_path=?
      WHERE id=?
    ");
    $u->execute([
      $nickname !== '' ? $nickname : null,
      $phone !== '' ? $phone : null,
      $about !== '' ? $about : null,
      $avatarToSave,
      $uid
    ]);

    redirect('/dashboard/profile.php?saved=1');
  }
}

/* 4) Дефолтний нік (якщо не заданий) */
$displayNick = $user['nickname'] ?: 'Користувач';
?>
<?php
$pageKey   = 'dashboard';
$pageTitle = APP_NAME . ' — Профіль';

include __DIR__ . '/../inc/header.php';
?>
<style>
.wrap{max-width:800px;margin:30px auto;padding:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
    .row{display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap}
    .ava{width:120px;height:120px;border-radius:18px;border:1px solid #e5e7eb;object-fit:cover;background:#f3f4f6}
    input,textarea{width:100%;border:1px solid #e5e7eb;border-radius:10px;box-sizing:border-box;margin:8px 0}
    input{height:40px;padding:0 10px}
    textarea{height:120px;padding:10px}
    .btn{height:40px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;padding:0 12px;cursor:pointer}
    .btn:hover{background:#0b1220}
    .muted{color:#6b7280;font-size:12px}
    .err{color:#b91c1c;margin:10px 0}
    .ok{color:#166534;margin:10px 0}
    a{color:#2563eb;text-decoration:none}
    .col{flex:1;min-width:260px}
</style>

  <div class="card">
    <h2 style="margin:0 0 6px 0;">Профіль</h2>
    <div class="muted">Ці дані будуть показані над списком твоїх товарів у кабінеті.</div>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="row">
        <div>
          <!-- Поточний аватар -->
          <?php if (!empty($user['avatar_path'])): ?>
            <img class="ava" src="<?= htmlspecialchars((string)$user['avatar_path']) ?>" alt="avatar">
          <?php else: ?>
            <img class="ava" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='54%25' text-anchor='middle' font-size='14' fill='%236b7280' font-family='Arial'%3ENo avatar%3C/text%3E%3C/svg%3E" alt="no avatar">
          <?php endif; ?>

          <div style="margin-top:8px;">
            <input type="file" name="avatar" accept="image/*">
            <div class="muted">Аватар: JPG/PNG/WebP, до 5MB</div>
          </div>
        </div>

        <div class="col">
          <div class="muted">Email (поки без редагування)</div>
          <input value="<?= htmlspecialchars((string)$user['email']) ?>" disabled>

          <div class="muted">Нікнейм</div>
          <input name="nickname" placeholder="Наприклад: Roman_kyiv"
                 value="<?= htmlspecialchars((string)($user['nickname'] ?? '')) ?>">

          <div class="muted">Телефон</div>
          <input name="phone" placeholder="+380..."
                 value="<?= htmlspecialchars((string)($user['phone'] ?? '')) ?>">

          <div class="muted">Про себе</div>
          <textarea name="about" placeholder="Коротко: що продаєш, як краще звʼязатись..."><?= htmlspecialchars((string)($user['about'] ?? '')) ?></textarea>
        </div>
      </div>

      <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button class="btn" type="submit">Зберегти</button>
        <a href="/dashboard/">← Назад у кабінет</a>
      </div>
    </form>
  </div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
