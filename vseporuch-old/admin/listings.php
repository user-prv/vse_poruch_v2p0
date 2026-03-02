<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../inc/db.php';

$pdo = db();

$q = trim((string)($_GET['q'] ?? ''));
$userId = (int)($_GET['user_id'] ?? 0);

$sql = "
SELECT l.id, l.user_id, u.email, l.title, l.category, l.price, l.currency,
       l.is_active,
       CASE
         WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='pending' OR LOWER(TRIM(COALESCE(l.status, '')))='pending' THEN 'pending'
         WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='blocked' OR LOWER(TRIM(COALESCE(l.status, '')))='blocked' THEN 'blocked'
         WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='deleted' OR LOWER(TRIM(COALESCE(l.status, '')))='deleted' THEN 'deleted'
         ELSE 'active'
       END AS moderation_status,
       l.moderation_reason, l.created_at
FROM listings l
JOIN users u ON u.id = l.user_id
WHERE 1=1
";
$params = [];

if ($userId > 0) { $sql .= " AND l.user_id=?"; $params[] = $userId; }
if ($q !== '')   { $sql .= " AND (l.title LIKE ? OR l.category LIKE ? OR u.email LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }

$sql .= " ORDER BY l.id DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Адмінка — Оголошення</title>
  <style>
    body{margin:0;font-family:system-ui;background:#fafafa}
    .wrap{max-width:1200px;margin:30px auto;padding:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    .pad{padding:12px}
    .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px}
    input{height:38px;border:1px solid #e5e7eb;border-radius:10px;padding:0 10px;min-width:260px}
    button,a.btn{height:38px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:900;padding:0 12px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
    a.btn.primary{background:#2563eb}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border-top:1px solid #e5e7eb;text-align:left;font-size:14px;vertical-align:top}
    th{background:#f9fafb;font-weight:900}
    .muted{color:#6b7280;font-size:12px}
    .bad{color:#991b1b;font-weight:900}
    .good{color:#166534;font-weight:900}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="pad">
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <div>
          <div style="font-weight:900;font-size:18px;">Оголошення</div>
          <div class="muted">Блок/розблок/видалення (moderation_status)</div>
        </div>
        <div class="row">
          <a class="btn" href="/admin/">Назад</a>
          <a class="btn primary" href="/admin/verification.php">Верифікація</a>
        </div>
      </div>

      <form class="row" method="get">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="назва/категорія/email">
        <?php if ($userId>0): ?><input type="hidden" name="user_id" value="<?= $userId ?>"><?php endif; ?>
        <button type="submit">Пошук</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>ID</th><th>Користувач</th><th>Назва</th><th>Ціна</th><th>Статус</th><th>Дії</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td><?= (int)$l['id'] ?></td>
              <td><?= htmlspecialchars((string)$l['email']) ?> <span class="muted">#<?= (int)$l['user_id'] ?></span></td>
              <td><?= htmlspecialchars((string)$l['title']) ?><div class="muted"><?= htmlspecialchars((string)($l['category'] ?? '')) ?></div></td>
              <td><?= htmlspecialchars((string)$l['price']) ?> <?= htmlspecialchars((string)$l['currency']) ?></td>
              <td>
                <?php if (($l['moderation_status'] ?? 'active') === 'blocked'): ?>
                  <span class="bad">blocked</span>
                  <div class="muted"><?= htmlspecialchars((string)($l['moderation_reason'] ?? '')) ?></div>
                <?php elseif (($l['moderation_status'] ?? 'active') === 'deleted'): ?>
                  <span class="muted">deleted</span>
                <?php elseif (($l['moderation_status'] ?? 'active') === 'pending'): ?>
                  <span class="muted">pending verification</span>
                <?php else: ?>
                  <span class="good">active</span>
                <?php endif; ?>
                <div class="muted">is_active: <?= (int)$l['is_active'] ?></div>
              </td>
              <td>
                <?php if (($l['moderation_status'] ?? 'active') === 'blocked'): ?>
                  <form method="post" action="/admin/listings_action.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                    <input type="hidden" name="action" value="unblock">
                    <button type="submit">Розблокувати</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="/admin/listings_action.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                    <input type="hidden" name="action" value="block">
                    <input type="hidden" name="reason" value="Порушення правил">
                    <button type="submit">Заблокувати</button>
                  </form>
                <?php endif; ?>

                <form method="post" action="/admin/listings_action.php" style="display:inline;margin-left:8px;" onsubmit="return confirm('Видалити оголошення?');">
                  <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit">Видалити</button>
                </form>

                <a class="btn primary" href="/item.php?id=<?= (int)$l['id'] ?>" target="_blank" style="margin-left:8px;">Відкрити</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>
</body>
</html>