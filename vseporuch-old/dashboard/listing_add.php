<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';
require_once __DIR__ . '/../inc/categories.php';

if (!isLoggedIn()) redirect('/account/');
$uid = currentUserId();

$error = null;

$title = '';
$desc = '';
$cat = '';
$price = '';
$currency = 'UAH';
$lat = '';
$lng = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $desc  = trim((string)($_POST['description'] ?? ''));
  $cat   = trim((string)($_POST['category'] ?? ''));
  $price = (string)($_POST['price'] ?? '');
  $currency = trim((string)($_POST['currency'] ?? 'UAH'));
  $lat   = trim((string)($_POST['lat'] ?? ''));
  $lng   = trim((string)($_POST['lng'] ?? ''));

  $priceVal = null;
  if ($price !== '') {
    $priceVal = (float)str_replace(',', '.', $price);
  }

  // Валідація lat/lng: спочатку перевіряємо що НЕ порожні, тільки потім кастимо у float
  if ($title === '') {
    $error = 'Вкажи назву товару';
  } elseif ($lat === '' || $lng === '') {
    $error = 'Не вдалося визначити координати. Дозволь геолокацію у браузері і натисни “Визначити мою локацію”.';
  } else {
    $latVal = (float)str_replace(',', '.', $lat);
    $lngVal = (float)str_replace(',', '.', $lng);

    // 0,0 — майже завжди помилка/дефолт, тому відсікаємо
    if ($latVal == 0.0 && $lngVal == 0.0) {
      $error = 'Координати 0,0 виглядають як помилка. Дозволь геолокацію і визнач локацію ще раз.';
    } elseif ($latVal < -90 || $latVal > 90 || $lngVal < -180 || $lngVal > 180) {
      $error = 'Некоректні координати';
    } else {
      $pdo = db();

      $stmt = $pdo->prepare("
        INSERT INTO listings (user_id, title, description, category, price, currency, lat, lng, is_active, moderation_status, status, moderation_reason, moderated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 'pending', NULL, NULL)
      ");
      $stmt->execute([
        $uid,
        $title,
        $desc !== '' ? $desc : null,
        $cat !== '' ? $cat : null,
        $priceVal,
        $currency !== '' ? $currency : 'UAH',
        $latVal,
        $lngVal
      ]);

      $listingId = (int)$pdo->lastInsertId();

      // Фото (до 5)
      $photos = saveUploadedImages($_FILES['photos'] ?? [], __DIR__ . '/../uploads', '/uploads', 5);
      if ($photos) {
        $ins = $pdo->prepare("INSERT INTO listing_photos (listing_id, path, sort_order) VALUES (?, ?, ?)");
        $order = 1;
        foreach ($photos as $p) {
          $ins->execute([$listingId, $p, $order++]);
        }
      }

      redirect('/dashboard/?saved=listing_added');
    }
  }
}
?>
<?php
$pageKey   = 'dashboard';
$pageTitle = APP_NAME . ' — Додати оголошення';

include __DIR__ . '/../inc/header.php';
?>
<style>
.wrap{max-width:720px;margin:30px auto;padding:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
    input,textarea{width:100%;border:1px solid #e5e7eb;border-radius:10px;box-sizing:border-box;margin:8px 0}
    input{height:40px;padding:0 10px}
    textarea{height:110px;padding:10px}
    .row{display:flex;gap:10px}
    .btn{height:40px;border:0;border-radius:10px;background:#111827;color:#fff;font-weight:800;padding:0 12px;cursor:pointer}
    .btn:hover{background:#0b1220}
    .btn2{height:40px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;color:#111827;font-weight:800;padding:0 12px;cursor:pointer}
    .btn2:hover{background:#f9fafb}
    .small{font-size:12px;color:#6b7280}
    .err{color:#b91c1c;margin:10px 0}
    a{color:#2563eb;text-decoration:none}
    .ok{color:#166534;font-weight:800}
    .warn{color:#991b1b;font-weight:800}
    code{background:#f3f4f6;padding:2px 6px;border-radius:6px}
</style>

  <div class="card">
    <h2 style="margin:0 0 6px 0;">Додати оголошення</h2>
    <div class="small">Можна додати до 5 фото. Локацію визначаємо автоматично (одноразово) або кнопкою “повторити”.</div>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input name="title" placeholder="Назва (наприклад: Продам велосипед)" value="<?= htmlspecialchars($title) ?>" required>
      <textarea name="description" placeholder="Опис (стан, деталі, контакти)"><?= htmlspecialchars($desc) ?></textarea>
      <?php
        // Категорії з БД (користувач обирає, а не вводить вручну)
        $cats = categoriesForSelect(true);
      ?>
      <select name="category">
        <option value="">— Обери категорію —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars((string)$c['raw_name']) ?>" <?= (($cat ?? '') === (string)$c['raw_name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>


      <div class="row">
        <input name="price" placeholder="Ціна" inputmode="decimal" value="<?= htmlspecialchars($price) ?>">
        <input name="currency" placeholder="Валюта" value="<?= htmlspecialchars($currency ?: 'UAH') ?>">
      </div>

      <div class="row">
        <input id="lat" name="lat" placeholder="lat (визначається автоматично)" value="<?= htmlspecialchars($lat) ?>" required>
        <input id="lng" name="lng" placeholder="lng (визначається автоматично)" value="<?= htmlspecialchars($lng) ?>" required>
      </div>

      <button class="btn2" type="button" id="btnGeo">Визначити мою локацію</button>
      <div class="small" id="geoStatus" style="margin-top:6px;">Очікую визначення локації…</div>

      <div style="margin-top:12px;">
        <input type="file" name="photos[]" accept="image/*" multiple>
        <div class="small">До 5 фото (JPG/PNG/WebP), до 5MB кожне.</div>
      </div>

      <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button class="btn" type="submit">Зберегти</button>
        <a href="/dashboard/">Назад</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const btn = document.getElementById('btnGeo');
  const st  = document.getElementById('geoStatus');
  const latEl = document.getElementById('lat');
  const lngEl = document.getElementById('lng');

  function setStatus(html){
    st.innerHTML = html;
  }

  function explain(err){
    if (!err) return 'Невідома помилка';
    // 1=PERMISSION_DENIED, 2=POSITION_UNAVAILABLE, 3=TIMEOUT
    if (err.code === 1) return 'Відмовлено в доступі до геолокації. <span class="muted">Safari → Website Settings → Location = Allow.</span>';
    if (err.code === 2) return 'Локація недоступна. <span class="muted">Спробуй увімкнути Wi-Fi/GPS або вийти на відкрите місце.</span>';
    if (err.code === 3) return 'Час очікування вичерпано. <span class="muted">Натисни кнопку ще раз.</span>';
    return (err.message || 'Помилка геолокації');
  }

  function requestGeo(){
    if (!navigator.geolocation) {
      setStatus('<span class="warn">Геолокація не підтримується браузером.</span>');
      return;
    }

    setStatus('Визначаю локацію…');

    const options = {
      enableHighAccuracy: true,
      timeout: 20000,     // було 8000 — для iOS часто мало
      maximumAge: 0       // важливо: не брати старий кеш, щоб не було “ніби тестові”
    };

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        latEl.value = lat.toFixed(6);
        lngEl.value = lng.toFixed(6);

        const acc = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : null;
        setStatus('<span class="ok">✅ Локацію визначено:</span> ' +
          '<code>' + lat.toFixed(6) + '</code>, <code>' + lng.toFixed(6) + '</code>' +
          (acc ? ' <span class="muted">(±' + acc + ' м)</span>' : '')
        );
      },
      (err) => {
        setStatus('<span class="warn">❌ ' + explain(err) + '</span> <span class="muted">[код: ' + (err && err.code ? err.code : '?') + ']</span>');
      },
      options
    );
  }

  // Кнопка = повторити
  btn.addEventListener('click', requestGeo);

  // Авто-спроба при відкритті сторінки (одноразово)
  window.addEventListener('load', () => {
    setTimeout(requestGeo, 300);
  });
})();
</script>
<?php include __DIR__ . '/../inc/footer.php'; ?>
