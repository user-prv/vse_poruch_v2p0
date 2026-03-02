<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';
require_once __DIR__ . '/../inc/categories.php';

if (!isLoggedIn()) redirect('/account/');
$uid = currentUserId();

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Перевіряємо доступ до оголошення
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$id, $uid]);
$it = $stmt->fetch();
if (!$it) redirect('/dashboard/');

// Поточні фото
$ph = $pdo->prepare("SELECT id, path, sort_order FROM listing_photos WHERE listing_id=? ORDER BY sort_order ASC, id ASC");
$ph->execute([$id]);
$photos = $ph->fetchAll();

$error = null;
$success = isset($_GET['saved']) && $_GET['saved'] === '1' ? 'Зміни збережено ✅ Оголошення очікує верифікації адміном.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim((string)($_POST['title'] ?? ''));
  $desc  = trim((string)($_POST['description'] ?? ''));
  $cat   = trim((string)($_POST['category'] ?? ''));
  $price = (string)($_POST['price'] ?? '');
  $currency = trim((string)($_POST['currency'] ?? 'UAH'));
  $lat   = trim((string)($_POST['lat'] ?? ''));
  $lng   = trim((string)($_POST['lng'] ?? ''));
  $active = isset($_POST['is_active']) ? 1 : 0;

  $priceVal = null;
  if ($price !== '') $priceVal = (float)str_replace(',', '.', $price);

  $latVal = (float)str_replace(',', '.', $lat);
  $lngVal = (float)str_replace(',', '.', $lng);

  if ($title === '') {
    $error = 'Вкажи назву товару';
  } elseif ($lat === '' || $lng === '' || $latVal == 0.0 || $lngVal == 0.0) {
    $error = 'Вкажи координати (lat/lng)';
  } elseif ($latVal < -90 || $latVal > 90 || $lngVal < -180 || $lngVal > 180) {
    $error = 'Некоректні координати';
  } else {
    // Update listing
    $u = $pdo->prepare("
      UPDATE listings
      SET title=?, description=?, category=?, price=?, currency=?, lat=?, lng=?, is_active=?,
          moderation_status='pending', status='pending', moderation_reason=NULL, moderated_at=NULL
      WHERE id=? AND user_id=?
    ");
    $u->execute([
      $title,
      $desc !== '' ? $desc : null,
      $cat !== '' ? $cat : null,
      $priceVal,
      $currency !== '' ? $currency : 'UAH',
      $latVal,
      $lngVal,
      $active,
      $id,
      $uid
    ]);

    // Скільки фото вже є
    $cntStmt = $pdo->prepare("SELECT COUNT(*) c FROM listing_photos WHERE listing_id=?");
    $cntStmt->execute([$id]);
    $existing = (int)($cntStmt->fetch()['c'] ?? 0);

    $left = max(0, 5 - $existing);
    if ($left > 0) {
      $newPhotos = saveUploadedImages($_FILES['photos'] ?? [], __DIR__ . '/../uploads', '/uploads', $left);
      if ($newPhotos) {
        $maxStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0) m FROM listing_photos WHERE listing_id=?");
        $maxStmt->execute([$id]);
        $order = (int)($maxStmt->fetch()['m'] ?? 0) + 1;

        $ins = $pdo->prepare("INSERT INTO listing_photos (listing_id, path, sort_order) VALUES (?, ?, ?)");
        foreach ($newPhotos as $p) {
          $ins->execute([$id, $p, $order++]);
        }
      }
    }

    redirect('/dashboard/listing_edit.php?id=' . $id . '&saved=1');
  }
}

// Оновлюємо дані для відображення після можливого POST з помилкою
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$id, $uid]);
$it = $stmt->fetch();

$ph = $pdo->prepare("SELECT id, path, sort_order FROM listing_photos WHERE listing_id=? ORDER BY sort_order ASC, id ASC");
$ph->execute([$id]);
$photos = $ph->fetchAll();
$photoCount = count($photos);
$leftToAdd = max(0, 5 - $photoCount);
?>
<?php
$pageKey   = 'dashboard';
$pageTitle = APP_NAME . ' — Редагувати оголошення';

include __DIR__ . '/../inc/header.php';
?>
<style>
.wrap{max-width:820px;margin:30px auto;padding:16px}
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
    .ok{color:#166534;margin:10px 0}
    a{color:#2563eb;text-decoration:none}
    .grid{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}
    .ph{border:1px solid #e5e7eb;border-radius:12px;padding:8px}
    .ph img{width:140px;height:105px;object-fit:cover;border-radius:10px;display:block}
    .ph form{margin-top:8px}
    .danger{background:#b91c1c}
    .danger:hover{background:#991b1b}
</style>

  <div class="card">
    <h2 style="margin:0 0 6px 0;">Редагувати оголошення</h2>
    <div class="small">Фото: <?= $photoCount ?>/5. Можна додати ще: <?= $leftToAdd ?>.</div>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($photos): ?>
      <div class="small" style="margin-top:10px;">Поточні фото (можна видаляти):</div>
      <div class="grid">
        <?php foreach ($photos as $p): ?>
          <div class="ph">
            <img src="<?= htmlspecialchars($p['path']) ?>" alt="photo">
            <form method="post" action="/dashboard/photo_delete.php" onsubmit="return confirm('Видалити фото?');">
              <input type="hidden" name="photo_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="listing_id" value="<?= (int)$id ?>">
              <button type="submit" class="btn danger" style="height:32px;">Видалити</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input name="title" value="<?= htmlspecialchars((string)$it['title']) ?>" required>
      <textarea name="description"><?= htmlspecialchars((string)($it['description'] ?? '')) ?></textarea>
      <?php
        // Категорії з БД (користувач обирає, а не вводить вручну)
        $cats = categoriesForSelect(true);
        $currentCat = (string)($it['category'] ?? '');
      ?>
      <select name="category">
        <option value="">— Обери категорію —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars((string)$c['raw_name']) ?>" <?= ($currentCat === (string)$c['raw_name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>


      <div class="row">
        <input name="price" inputmode="decimal" value="<?= htmlspecialchars((string)($it['price'] ?? '')) ?>" placeholder="Ціна">
        <input name="currency" value="<?= htmlspecialchars((string)($it['currency'] ?? 'UAH')) ?>" placeholder="Валюта">
      </div>

      <div class="row">
        <input id="lat" name="lat" value="<?= htmlspecialchars((string)$it['lat']) ?>" required>
        <input id="lng" name="lng" value="<?= htmlspecialchars((string)$it['lng']) ?>" required>
      </div>

      <button class="btn2" type="button" id="btnGeo">Визначити мою локацію</button>
      <div class="small" id="geoStatus" style="margin-top:6px;"></div>

      <label class="small" style="display:block;margin-top:10px;">
        <input type="checkbox" name="is_active" <?= ((int)$it['is_active'] === 1) ? 'checked' : '' ?>> Активне
      </label>

      <div style="margin-top:12px;">
        <?php if ($leftToAdd > 0): ?>
          <input type="file" name="photos[]" accept="image/*" multiple>
          <div class="small">Додай нові фото (сервер збереже максимум <?= $leftToAdd ?>, щоб загалом було не більше 5).</div>
        <?php else: ?>
          <div class="small">Досягнуто ліміт 5 фото. Видали одне, щоб додати інше.</div>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button class="btn" type="submit">Зберегти</button>
        <a href="/dashboard/">Назад</a>
      </div>
    </form>
  </div>
</div>

<script>
  const btn = document.getElementById('btnGeo');
  const st  = document.getElementById('geoStatus');
  btn.addEventListener('click', () => {
    st.textContent = '';
    if (!navigator.geolocation) { st.textContent = 'Геолокація не підтримується.'; return; }

    st.textContent = 'Визначаю локацію...';
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        document.getElementById('lat').value = pos.coords.latitude.toFixed(6);
        document.getElementById('lng').value = pos.coords.longitude.toFixed(6);
        st.textContent = 'Готово ✅';
      },
      () => { st.textContent = 'Не вдалося отримати локацію (відмова/помилка).'; },
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
    );
  });
</script>
<?php include __DIR__ . '/../inc/footer.php'; ?>
