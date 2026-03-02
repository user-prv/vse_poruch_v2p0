<?php
declare(strict_types=1);

/**
 * dashboard/index.php — Кабінет користувача
 * Важливо: використовує спільні inc/header.php та inc/footer.php,
 * щоб меню працювало на всіх сторінках.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

if (!isLoggedIn()) redirect('/account/');

$pageKey   = 'dashboard';
$pageTitle = APP_NAME . ' — Кабінет';

$uid = currentUserId();
$pdo = db();

$savedMessage = null;
if (isset($_GET['saved']) && $_GET['saved'] === 'listing_added') {
  $savedMessage = 'Оголошення успішно збережено ✅';
}

/** Профіль */
$u = $pdo->prepare("SELECT email, nickname, phone, about, avatar_path FROM users WHERE id=? LIMIT 1");
$u->execute([$uid]);
$user = $u->fetch(PDO::FETCH_ASSOC) ?: [];

$displayNick = !empty($user['nickname']) ? (string)$user['nickname'] : 'Користувач';

/** Оголошення + перше фото */
$stmt = $pdo->prepare("
  SELECT
    l.id, l.title, l.category, l.price, l.currency, l.is_active,
    CASE
      WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='pending' OR LOWER(TRIM(COALESCE(l.status, '')))='pending' THEN 'pending'
      WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='blocked' OR LOWER(TRIM(COALESCE(l.status, '')))='blocked' THEN 'blocked'
      WHEN LOWER(TRIM(COALESCE(l.moderation_status, '')))='deleted' OR LOWER(TRIM(COALESCE(l.status, '')))='deleted' THEN 'deleted'
      ELSE 'active'
    END AS moderation_status, l.created_at,
    (
      SELECT p.path
      FROM listing_photos p
      WHERE p.listing_id = l.id
      ORDER BY p.sort_order ASC, p.id ASC
      LIMIT 1
    ) AS photo
  FROM listings l
  WHERE l.user_id = ?
  ORDER BY l.created_at DESC
");
$stmt->execute([$uid]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function formatPrice($price, $currency): string {
  if ($price === null || $price === '') return '—';
  $cur = $currency ?: 'UAH';
  $p = (string)$price;
  if (str_contains($p, '.')) $p = rtrim(rtrim($p, '0'), '.');
  return $p . ' ' . $cur;
}

include __DIR__ . '/../inc/header.php';
?>


  <div class="card">
    <?php if ($savedMessage): ?>
      <div class="pad" style="border-bottom:1px solid var(--border);color:#166534;font-weight:800;">
        <?= h($savedMessage) ?>
      </div>
    <?php endif; ?>

    <div class="pad" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
      <div>
        <div style="font-weight:900;font-size:16px;">Мій кабінет</div>
        <div class="muted"><?= h((string)(currentUserEmail() ?? ($user['email'] ?? ''))) ?></div>
      </div>

      <div class="row" style="justify-content:flex-end;">
        <a class="btn primary" href="/dashboard/listing_add.php">+ Додати</a>
      </div>
    </div>

    <!-- Профіль -->
    <div class="pad border-top" style="background:#fbfbfb;">
      <div class="row" style="align-items:flex-start;gap:12px;">
        <div>
          <?php if (!empty($user['avatar_path'])): ?>
            <img src="<?= h((string)$user['avatar_path']) ?>" alt="avatar"
                 style="width:90px;height:90px;border-radius:16px;border:1px solid var(--border);object-fit:cover;background:#f3f4f6;">
          <?php else: ?>
            <div style="width:90px;height:90px;border-radius:16px;border:1px solid var(--border);background:#f3f4f6;"></div>
          <?php endif; ?>
        </div>

        <div style="flex:1;min-width:260px;">
          <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
            <div>
              <div style="font-weight:900;font-size:16px;"><?= h($displayNick) ?></div>
              <div class="muted">Профіль продавця</div>
            </div>
            <div>
              <a class="btn primary" href="/dashboard/profile.php">Редагувати профіль</a>
            </div>
          </div>

          <div class="row mt10">
            <div class="card" style="padding:10px;border-radius:12px;min-width:220px;">
              <div class="muted">Email</div>
              <div style="font-weight:800;"><?= h((string)($user['email'] ?? '—')) ?></div>
            </div>

            <div class="card" style="padding:10px;border-radius:12px;min-width:220px;">
              <div class="muted">Телефон</div>
              <div style="font-weight:800;"><?= h((string)($user['phone'] ?? '—')) ?></div>
            </div>
          </div>

          <div class="mt10" style="white-space:pre-wrap;">
            <?php if (!empty($user['about'])): ?>
              <?= h((string)$user['about']) ?>
            <?php else: ?>
              <span class="muted">Про себе: не заповнено</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Оголошення -->
    <div class="pad border-top">
      <div style="font-weight:900;">Мої оголошення</div>
      <div class="muted">Редагування доступне з мобільного — кнопки є в кожній картці.</div>
    </div>

    <?php if (!$listings): ?>
      <div class="pad border-top muted">У тебе ще немає оголошень. Натисни «Додати», щоб створити перше.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($listings as $it): ?>
          <?php
            $id = (int)$it['id'];
            $photo = $it['photo'] ?? null;
          ?>
          <div class="item">
            <div style="display:flex;gap:12px;align-items:flex-start;">
              <div style="width:92px;flex:0 0 auto;">
                <?php if (!empty($photo)): ?>
                  <img src="<?= h((string)$photo) ?>" alt="photo"
                       style="width:92px;height:72px;object-fit:cover;border-radius:10px;border:1px solid var(--border);background:#f3f4f6;display:block;">
                <?php else: ?>
                  <div class="muted" style="width:92px;height:72px;border-radius:10px;border:1px solid var(--border);background:#f3f4f6;display:flex;align-items:center;justify-content:center;">—</div>
                <?php endif; ?>
              </div>

              <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                  <div style="min-width:0;">
                    <div class="title"><?= h((string)$it['title']) ?></div>
                    <div class="meta"><?= h((string)($it['category'] ?? '—')) ?></div>
                    <div class="muted mt10">
                      ID: <?= $id ?> · <?= (int)$it['is_active']===1 ? 'Активне' : 'Вимкнено' ?> ·
                      <?php
                        $m = (string)($it['moderation_status'] ?? 'active');
                        if ($m === 'pending') echo 'Очікує верифікації';
                        elseif ($m === 'active') echo 'Верифіковане';
                        elseif ($m === 'blocked') echo 'Заблоковане';
                        elseif ($m === 'deleted') echo 'Видалене';
                        else echo 'Статус: ' . h($m);
                      ?>
                    </div>
                  </div>
                  <div style="text-align:right;min-width:120px;">
                    <div class="price"><?= h(formatPrice($it['price'] ?? null, (string)($it['currency'] ?? 'UAH'))) ?></div>
                  </div>
                </div>

                <div class="row mt10" style="justify-content:flex-end;">
                  <a class="btn primary" href="/dashboard/listing_edit.php?id=<?= $id ?>">Редагувати</a>
                  <form method="post" action="/dashboard/listing_delete.php" onsubmit="return confirm('Видалити оголошення?');">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn" type="submit">Видалити</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
