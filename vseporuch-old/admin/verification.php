<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../inc/db.php';

$pdo = db();

$q = trim((string)($_GET['q'] ?? ''));

$sql = "
SELECT l.id, l.user_id, u.email, l.title, l.category, l.price, l.currency, l.created_at
FROM listings l
JOIN users u ON u.id = l.user_id
WHERE (LOWER(TRIM(COALESCE(l.moderation_status, ''))) = 'pending' OR LOWER(TRIM(COALESCE(l.status, ''))) = 'pending')
";
$params = [];

if ($q !== '') {
  $sql .= " AND (l.title LIKE ? OR l.category LIKE ? OR u.email LIKE ?)";
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
}

$sql .= " ORDER BY l.created_at ASC, l.id ASC LIMIT 300";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Адмінка — Верифікація</title>
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
    .empty{padding:16px;color:#6b7280}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="pad">
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <div>
          <div style="font-weight:900;font-size:18px;">Верифікація</div>
          <div class="muted">Показані тільки товари, що очікують верифікації.</div>
        </div>
        <div class="row">
          <a class="btn" href="/admin/">Назад</a>
        </div>
      </div>

      <form class="row" method="get">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="назва/категорія/email">
        <button type="submit">Пошук</button>
      </form>

      <?php if (!$rows): ?>
        <div class="empty">Немає товарів, що потребують верифікації.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Користувач</th><th>Назва</th><th>Ціна</th><th>Створено</th><th>Дія</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars((string)$r['email']) ?> <span class="muted">#<?= (int)$r['user_id'] ?></span></td>
                <td>
                  <?= htmlspecialchars((string)$r['title']) ?>
                  <div class="muted"><?= htmlspecialchars((string)($r['category'] ?? '')) ?></div>
                </td>
                <td><?= htmlspecialchars((string)$r['price']) ?> <?= htmlspecialchars((string)$r['currency']) ?></td>
                <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
                <td>
                  <form method="post" action="/admin/verification_action.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" name="action" value="verify">Верифікувати</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
