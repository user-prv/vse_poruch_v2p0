<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';

require_once __DIR__ . '/../inc/db.php';
$pdo = db();

$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT id, email, status, blocked_reason, blocked_at, is_admin FROM users WHERE 1=1";
$params = [];

if ($q !== '') {
  $sql .= " AND (email LIKE ? OR id = ?)";
  $params[] = '%' . $q . '%';
  $params[] = ctype_digit($q) ? (int)$q : -1;
}

$sql .= " ORDER BY id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Адмінка — Користувачі</title>
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
          <div style="font-weight:900;font-size:18px;">Користувачі</div>
          <div class="muted">Блокування юзера блокує і його оголошення на мапі.</div>
        </div>
        <div class="row">
          <a class="btn" href="/admin/">Назад</a>
        </div>
      </div>

      <form class="row" method="get">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="email або id">
        <button type="submit">Пошук</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>ID</th><th>Email</th><th>Статус</th><th>Причина</th><th>Дії</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars((string)$u['email']) ?> <?php if ((int)$u['is_admin']===1) echo '<span class="muted">(admin)</span>'; ?></td>
              <td>
                <?php if (($u['status'] ?? 'active') === 'blocked'): ?>
                  <span class="bad">blocked</span>
                <?php else: ?>
                  <span class="good">active</span>
                <?php endif; ?>
              </td>
              <td class="muted"><?= htmlspecialchars((string)($u['blocked_reason'] ?? '')) ?></td>
              <td>
                <?php if ((int)$u['is_admin']===1): ?>
                  <span class="muted">admin</span>
                <?php else: ?>
                  <?php if (($u['status'] ?? 'active') === 'blocked'): ?>
                    <form method="post" action="/admin/users_action.php" style="display:inline;">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="action" value="unblock">
                      <button type="submit">Розблокувати</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/admin/users_action.php" style="display:inline;">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="action" value="block">
                      <input type="hidden" name="reason" value="Порушення правил платформи">
                      <button type="submit">Заблокувати</button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>

                <a class="btn primary" href="/admin/listings.php?user_id=<?= (int)$u['id'] ?>" style="margin-left:8px;">Оголошення</a>
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