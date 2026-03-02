<?php
declare(strict_types=1);

/**
 * Публічний профіль продавця:
 * - аватар, нік, email, телефон (якщо є), про себе
 * - список активних товарів цього продавця (з прев’ю фото)
 *
 * URL: /user.php?id=45
 */
 
$pageCss    = ['/assets/css/item.css'];
$pageJsPage = '';
$layout     = 'default';

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/db.php';

$pdo = db();

/* 1) ID користувача */
$uid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($uid <= 0) {
  http_response_code(404);
  echo "Користувача не знайдено";
  exit;
}

/* 2) Дані користувача */
$u = $pdo->prepare("SELECT id, email, nickname, phone, about, avatar_path FROM users WHERE id=? LIMIT 1");
$u->execute([$uid]);
$user = $u->fetch();
if (!$user) {
  http_response_code(404);
  echo "Користувача не знайдено";
  exit;
}

$name = $user['nickname'] ?: 'Користувач';

/* 3) Товари користувача (активні) + перше фото */
$stmt = $pdo->prepare("
  SELECT
    l.id, l.title, l.price, l.currency, l.category, l.created_at,
    (
      SELECT p.path
      FROM listing_photos p
      WHERE p.listing_id = l.id
      ORDER BY p.sort_order ASC, p.id ASC
      LIMIT 1
    ) AS photo
  FROM listings l
  WHERE l.user_id = ? AND l.is_active = 1
    AND ( (l.moderation_status IS NULL OR LOWER(TRIM(l.moderation_status)) = 'active') AND (l.status IS NULL OR LOWER(TRIM(l.status)) = 'active') )
  ORDER BY l.created_at DESC
  LIMIT 200
");
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

function formatPrice($price, $currency): string {
  if ($price === null || $price === '') return '—';
  return $price . ' ' . ($currency ?: 'UAH');
}
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string)$name) ?> — <?= htmlspecialchars(APP_NAME) ?></title>
  <style>
    body{margin:0;font-family:system-ui;background:#fafafa}
    .wrap{max-width:1100px;margin:24px auto;padding:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    .top{padding:14px;border-bottom:1px solid #e5e7eb}
    .muted{color:#6b7280;font-size:12px}
    .ava{width:90px;height:90px;border-radius:16px;border:1px solid #e5e7eb;object-fit:cover;background:#f3f4f6}
    .row{display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap}
    .name{font-weight:900;font-size:18px}
    .about{white-space:pre-wrap;margin-top:10px;color:#111827;font-size:14px}
    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:14px}
    @media (max-width: 900px){ .grid{grid-template-columns:1fr;} }
    .item{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
    .thumb{width:100%;height:170px;object-fit:cover;background:#f3f4f6}
    .pad{padding:10px}
    a{color:#2563eb;text-decoration:none}
    a:hover{text-decoration:underline}
    .price{font-weight:900;margin-top:6px}
  </style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <div class="top">
      <div class="muted"><a href="/" style="text-decoration:none;color:#2563eb;">← Назад на карту</a></div>

      <div class="row" style="margin-top:10px;">
        <?php if (!empty($user['avatar_path'])): ?>
          <img class="ava" src="<?= htmlspecialchars((string)$user['avatar_path']) ?>" alt="avatar">
        <?php else: ?>
          <img class="ava" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='90' height='90'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='54%25' text-anchor='middle' font-size='12' fill='%236b7280' font-family='Arial'%3EAvatar%3C/text%3E%3C/svg%3E" alt="avatar">
        <?php endif; ?>

        <div style="flex:1;min-width:250px;">
          <div class="name"><?= htmlspecialchars((string)$name) ?></div>
          <div class="muted"><?= htmlspecialchars((string)$user['email']) ?></div>

          <?php if (!empty($user['phone'])): ?>
            <div style="margin-top:6px;">Телефон: <strong><?= htmlspecialchars((string)$user['phone']) ?></strong></div>
          <?php endif; ?>

          <?php if (!empty($user['about'])): ?>
            <div class="about"><span class="muted">Про себе:</span><br><?= htmlspecialchars((string)$user['about']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="padding:14px;font-weight:900;">Активні товари продавця (<?= count($items) ?>)</div>

    <div class="grid">
      <?php foreach ($items as $it): ?>
        <a class="item" href="/item.php?id=<?= (int)$it['id'] ?>">
          <?php if (!empty($it['photo'])): ?>
            <img class="thumb" src="<?= htmlspecialchars((string)$it['photo']) ?>" alt="photo">
          <?php else: ?>
            <div class="thumb"></div>
          <?php endif; ?>
          <div class="pad">
            <div style="font-weight:900;"><?= htmlspecialchars((string)$it['title']) ?></div>
            <div class="muted"><?= htmlspecialchars((string)($it['category'] ?? '—')) ?></div>
            <div class="price"><?= htmlspecialchars(formatPrice($it['price'] ?? null, $it['currency'] ?? 'UAH')) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  </div>

</div>
</body>
</html>