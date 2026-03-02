<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo "Not found";
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    l.id, l.user_id, l.title, l.description, l.category, l.price, l.currency,
    l.is_active, l.moderation_status, l.status, l.created_at,
    u.email AS user_email, u.nickname, u.phone, u.about, u.avatar_path,
    u.status AS user_status, u.blocked_at AS user_blocked_at
  FROM listings l
  JOIN users u ON u.id = l.user_id
  WHERE l.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  http_response_code(404);
  echo "Not found";
  exit;
}

$userBlocked = false;
if (!empty($item['user_status']) && $item['user_status'] === 'blocked') $userBlocked = true;
if (!empty($item['user_blocked_at'])) $userBlocked = true;

$m1 = mb_strtolower(trim((string)($item['moderation_status'] ?? '')));
$m2 = mb_strtolower(trim((string)($item['status'] ?? '')));
$effectiveModeration = 'active';
if ($m1 === 'pending' || $m2 === 'pending') $effectiveModeration = 'pending';
elseif ($m1 === 'blocked' || $m2 === 'blocked') $effectiveModeration = 'blocked';
elseif ($m1 === 'deleted' || $m2 === 'deleted') $effectiveModeration = 'deleted';

$listingInactive = ((int)$item['is_active'] !== 1) || ($effectiveModeration !== 'active');

$blockedMsg = null;
if ($userBlocked) $blockedMsg = "Профіль продавця заблоковано адміністрацією.";
elseif ($listingInactive) $blockedMsg = "Оголошення вимкнено або заблоковано.";

$p = $pdo->prepare("
  SELECT id, path
  FROM listing_photos
  WHERE listing_id = ?
  ORDER BY sort_order ASC, id ASC
");
$p->execute([$id]);
$photos = $p->fetchAll(PDO::FETCH_ASSOC);

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function formatPrice($price, $currency): string {
  if ($price === null || $price === '') return '—';
  $cur = $currency ?: 'UAH';
  $p = (string)$price;
  if (str_contains($p, '.')) $p = rtrim(rtrim($p, '0'), '.');
  return $p . ' ' . $cur;
}

$displayNick = !empty($item['nickname']) ? (string)$item['nickname'] : 'Користувач';

/* Масив урлів фото для JS */
$photoUrls = array_values(array_filter(array_map(fn($r) => $r['path'] ?? null, $photos)));
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= h($item['title']) ?> — <?= h(APP_NAME) ?></title>

  <link rel="stylesheet" href="/assets/css/base.css?v=4">
  <link rel="stylesheet" href="/assets/css/item.css?v=4">
</head>
<body>

<div class="wrap">

  <!-- TOPBAR -->
  <div class="row item-topbar">
    <div class="row">
      <a class="btn" href="/?view=list">← До списку</a>
      <a class="btn" href="/">На карту</a>

      <?php if (!isLoggedIn()): ?>
        <a class="btn primary" href="/account/">Увійти</a>
      <?php else: ?>
        <a class="btn primary" href="/dashboard/">Мій кабінет</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($blockedMsg): ?>
    <div class="card mt12">
      <div class="pad">
        <div class="muted"><strong><?= h($blockedMsg) ?></strong></div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 2 КОЛОНКИ СТОРІНКИ: ТОВАР | ПРОДАВЕЦЬ -->
  <div class="grid mt12 item-grid">

    <!-- ТОВАР (основний блок) -->
    <div class="card item-card">
      <div class="pad">

        <div class="muted">Оголошення</div>
        <div class="item-title"><?= h($item['title']) ?></div>

        <!-- РЯД: ГАЛЕРЕЯ ЗЛІВА + ІНФО СПРАВА -->
        <div class="item-product-row mt10">

          <!-- ГАЛЕРЕЯ -->
          <div class="item-gallery"
               data-photos='<?= h(json_encode($photoUrls, JSON_UNESCAPED_UNICODE)) ?>'>

            <?php if (!$photoUrls): ?>
              <div class="muted">Фото не додано</div>
            <?php else: ?>
              <div class="item-stage">
                <button class="item-nav item-nav--prev" type="button" aria-label="prev">‹</button>

                <img class="item-main-photo" id="itemMainPhoto"
                     src="<?= h($photoUrls[0]) ?>" alt="photo">

                <button class="item-nav item-nav--next" type="button" aria-label="next">›</button>
              </div>

              <div class="item-counter muted">
                <span id="itemCounter">1</span> / <?= (int)count($photoUrls) ?>
              </div>

              <?php if (count($photoUrls) > 1): ?>
                <div class="item-thumbs" id="itemThumbs">
                  <?php foreach ($photoUrls as $i => $src): ?>
                    <button type="button"
                            class="item-thumb-btn<?= $i === 0 ? ' is-active' : '' ?>"
                            data-idx="<?= (int)$i ?>"
                            aria-label="photo <?= (int)($i+1) ?>">
                      <img class="item-thumb" src="<?= h($src) ?>" alt="thumb">
                    </button>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <!-- ІНФО (ціна/категорія/дата) -->
          <div class="item-info">
            <div class="item-price"><?= h(formatPrice($item['price'], $item['currency'])) ?></div>

            <div class="muted mt10">Категорія: <?= h($item['category'] ?: '—') ?></div>
            <div class="muted mt10">Додано: <?= h($item['created_at'] ?: '') ?></div>
          </div>

        </div><!-- /item-product-row -->

        <!-- ОПИС (НА ВСЮ ШИРИНУ НИЖЧЕ) -->
        <div class="mt12 item-desc">
          <?php if (!empty($item['description'])): ?>
            <div class="item-desc-text"><?= h($item['description']) ?></div>
          <?php else: ?>
            <div class="muted">Опис: не вказано</div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- ПРОДАВЕЦЬ (права колонка) -->
    <div class="card item-seller">
      <div class="pad">
        <div class="muted">Продавець</div>

        <div class="row mt12 item-seller-head">
          <?php if (!empty($item['avatar_path'])): ?>
            <img class="seller-avatar" src="<?= h($item['avatar_path']) ?>" alt="avatar">
          <?php else: ?>
            <div class="seller-avatar seller-avatar--empty"></div>
          <?php endif; ?>

          <div class="seller-meta">
            <div class="seller-name"><?= h($displayNick) ?></div>
            <div class="muted">ID: <?= (int)$item['user_id'] ?></div>
          </div>
        </div>

        <div class="mt12">
          <div class="muted">Телефон</div>
          <div class="seller-value"><?= h($item['phone'] ?: '—') ?></div>
        </div>

        <div class="mt12">
          <div class="muted">Email</div>
          <div class="seller-value"><?= h($item['user_email'] ?: '—') ?></div>
        </div>

        <div class="mt12">
          <div class="muted">Про себе</div>
          <?php if (!empty($item['about'])): ?>
            <div class="seller-about"><?= h($item['about']) ?></div>
          <?php else: ?>
            <div class="muted">Не заповнено</div>
          <?php endif; ?>
        </div>

        <div class="mt12">
          <a class="btn primary" href="/user.php?id=<?= (int)$item['user_id'] ?>">Профіль продавця</a>
        </div>
      </div>
    </div>

  </div><!-- /item-grid -->
</div><!-- /wrap -->



<script src="/assets/js/pages/item.js?v=3" defer></script>

</body>
</html>