<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/categories.php';

$pageTitle = APP_NAME . ' — категорії';

// ✅ щоб не ловити "Cannot redeclare h()"
if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function normalizePhotoPath(?string $path): ?string {
  if ($path === null) return null;
  $path = trim($path);
  if ($path === '') return null;
  $path = str_replace('\\', '/', $path);
  if (preg_match('~^(https?://|data:)~i', $path)) return $path;
  if ($path[0] !== '/') $path = '/' . ltrim($path, '/');
  return $path;
}

$pdo = db();

/**
 * GET:
 * - id : поточна категорія (0/відсутнє = корінь)
 */
$catId = (int)($_GET['id'] ?? 0);
$current = $catId > 0 ? categoryById($catId) : null;
if ($catId > 0 && !$current) {
  http_response_code(404);
  echo "Not found";
  exit;
}

/**
 * 1) Які категорії показувати зверху:
 * - на корені: кореневі (parent_id IS NULL)
 * - всередині: підкатегорії поточної
 */
$children = categoriesChildren($catId > 0 ? $catId : null);

/**
 * 2) Товари:
 * - на корені: показуємо ВСІ активні (щоб “все як на головній”)
 * - в категорії: показуємо товари з цієї категорії + всіх підкатегорій (будь-яка глибина)
 *
 * Fallback:
 *   A) listings.category_id (int)
 *   B) listings.category (varchar)
 */
$items = [];
$subtreeIds = [];

function fetchItemsAll(PDO $pdo): array {
  $sql = "
    SELECT
      l.id, l.title, l.price, l.currency, l.lat, l.lng, l.created_at,
      COALESCE(c.name, l.category) AS category_name,
      (
        SELECT p.path FROM listing_photos p
        WHERE p.listing_id = l.id
        ORDER BY p.sort_order ASC, p.id ASC
        LIMIT 1
      ) AS photo
    FROM listings l
    LEFT JOIN categories c ON c.id = l.category_id
    WHERE l.is_active = 1
      AND ( (l.moderation_status IS NULL OR LOWER(TRIM(l.moderation_status)) = 'active') AND (l.status IS NULL OR LOWER(TRIM(l.status)) = 'active') )
    ORDER BY l.created_at DESC
    LIMIT 200
  ";
  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function fetchItemsByCategoryIds(PDO $pdo, array $ids): array {
  if (!$ids) return [];
  $in = implode(',', array_fill(0, count($ids), '?'));
  $sql = "
    SELECT
      l.id, l.title, l.price, l.currency, l.lat, l.lng, l.created_at,
      c.name AS category_name,
      (
        SELECT p.path FROM listing_photos p
        WHERE p.listing_id = l.id
        ORDER BY p.sort_order ASC, p.id ASC
        LIMIT 1
      ) AS photo
    FROM listings l
    LEFT JOIN categories c ON c.id = l.category_id
    WHERE l.is_active = 1
      AND ( (l.moderation_status IS NULL OR LOWER(TRIM(l.moderation_status)) = 'active') AND (l.status IS NULL OR LOWER(TRIM(l.status)) = 'active') )
      AND l.category_id IN ($in)
    ORDER BY l.created_at DESC
    LIMIT 200
  ";
  $st = $pdo->prepare($sql);
  $st->execute($ids);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

function fetchItemsByCategoryNames(PDO $pdo, array $names): array {
  if (!$names) return [];
  $in = implode(',', array_fill(0, count($names), '?'));
  $sql = "
    SELECT
      l.id, l.title, l.price, l.currency, l.lat, l.lng, l.created_at,
      l.category AS category_name,
      (
        SELECT p.path FROM listing_photos p
        WHERE p.listing_id = l.id
        ORDER BY p.sort_order ASC, p.id ASC
        LIMIT 1
      ) AS photo
    FROM listings l
    WHERE l.is_active = 1
      AND ( (l.moderation_status IS NULL OR LOWER(TRIM(l.moderation_status)) = 'active') AND (l.status IS NULL OR LOWER(TRIM(l.status)) = 'active') )
      AND l.category IN ($in)
    ORDER BY l.created_at DESC
    LIMIT 200
  ";
  $st = $pdo->prepare($sql);
  $st->execute($names);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

try {
  if ($catId > 0) {
    $subtreeIds = categorySubtreeIds($catId);

    // 1) Нова схема
    $items = fetchItemsByCategoryIds($pdo, $subtreeIds);

    // 2) Fallback на стару схему
    if (!$items) {
      $all = categoriesAll(true);
      $byId = [];
      foreach ($all as $r) $byId[(int)$r['id']] = $r;

      $wantNames = [];
      foreach ($subtreeIds as $cid) {
        if (isset($byId[$cid])) $wantNames[] = (string)$byId[$cid]['name'];
      }

      $items = fetchItemsByCategoryNames($pdo, $wantNames);
    }
  } else {
    // Корінь — як на головній
    $items = fetchItemsAll($pdo);
  }
} catch (Throwable $e) {
  $items = fetchItemsAll($pdo);
}

/** Breadcrumbs */
$crumbs = $catId > 0 ? categoryBreadcrumbs($catId) : [];

include __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <div class="grid">

    <!-- ЛІВА КОЛОНКА: категорії + список товарів -->
    <div class="card">
      <div class="pad">
        <div class="topbar">
          <div>
            <div class="brand"><?= h(APP_NAME) ?></div>
            <div class="muted">Категорії та товари</div>
          </div>

          <!-- ✅ ЗНЯЛИ userbox повністю:
               тут НЕМАЄ "Мій кабінет", "Вийти", "Кабінет" -->
        </div>

        <!-- Breadcrumbs -->
        <div class="mt10 muted">
          <a class="link-muted" href="/categories.php">Всі категорії</a>
          <?php foreach ($crumbs as $c): ?>
            <span class="muted"> / </span>
            <a class="link-muted" href="/categories.php?id=<?= (int)$c['id'] ?>"><?= h($c['name']) ?></a>
          <?php endforeach; ?>
        </div>

        <!-- Категорії / підкатегорії -->
        <div class="mt12">
          <div class="results">Категорії</div>
          <div class="row mt10" style="gap:10px;">
            <?php if (!$children): ?>
              <span class="muted">Немає підкатегорій</span>
            <?php else: ?>
              <?php foreach ($children as $c): ?>
                <?php $icon = $c['icon_path'] ?? null; ?>
                <a class="btn" href="/categories.php?id=<?= (int)$c['id'] ?>" style="background:#fff;color:var(--dark);border:1px solid var(--border);">
                  <?php if ($icon): ?>
                    <img src="<?= h((string)$icon) ?>" alt="" style="width:20px;height:20px;border-radius:6px;object-fit:cover;margin-right:8px;">
                  <?php endif; ?>
                  <?= h((string)$c['name']) ?>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="hint">
          Натисни “На мапі” у картці — і побачиш <strong>всі товари</strong> цієї вибірки на карті (мобільний: відкривається карта).
        </div>
      </div>

      <div class="pad border-top">
        <div class="row" style="justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
          <div class="results">
            Товари: <span id="count"><?= (int)count($items) ?></span>
            <span id="status" class="muted status"></span>
          </div>

          <label class="muted" style="display:flex;align-items:center;gap:8px;font-size:12px;">
            Сортування:
            <select id="sortSelect" style="padding:7px 10px;border-radius:10px;border:1px solid var(--border);background:#fff;">
              <option value="newest">Спочатку нові</option>
              <option value="nearest">Показати найближчі</option>
              <option value="price_asc">За ціною (зростання)</option>
              <option value="price_desc">За ціною (спадання)</option>
            </select>
          </label>
        </div>
      </div>

      <!-- Список товарів (картки як на головній) -->
      <div id="list" class="list">
        <?php if (!$items): ?>
          <div class="pad muted">Немає товарів у цій категорії.</div>
        <?php else: ?>
          <?php foreach ($items as $it): ?>
            <?php
              $photo = normalizePhotoPath($it['photo'] ?? null);
              $price = $it['price'] ?? null;
              $cur = $it['currency'] ?? 'UAH';
              $catName = $it['category_name'] ?? '';
            ?>
            <div class="item" data-id="<?= (int)$it['id'] ?>" data-lat="<?= isset($it['lat']) ? h((string)$it['lat']) : '' ?>" data-lng="<?= isset($it['lng']) ? h((string)$it['lng']) : '' ?>" data-price="<?= $price !== null ? h((string)$price) : '' ?>">
              <div style="display:flex;gap:10px;">
                <div style="width:78px;">
                  <?php if ($photo): ?>
                    <img src="<?= h((string)$photo) ?>" alt=""
                      style="width:72px;height:56px;object-fit:cover;border-radius:10px;border:1px solid var(--border);display:block;">
                  <?php endif; ?>
                </div>

                <div style="flex:1;display:flex;justify-content:space-between;gap:10px;">
                  <div>
                    <div class="title"><?= h((string)$it['title']) ?></div>
                    <div class="meta"><?= h((string)$catName) ?> • <span data-distance-km>— км</span></div>

                    <div class="row mt10" style="gap:10px;">
                      <a class="details-btn" href="/item.php?id=<?= (int)$it['id'] ?>" onclick="event.stopPropagation();">Детальніше →</a>
                      <a class="details-btn" href="#" data-show-map="1" data-focus="<?= (int)$it['id'] ?>" onclick="event.preventDefault();event.stopPropagation();">На мапі</a>
                    </div>
                  </div>

                  <div style="text-align:right;min-width:90px;">
                    <div class="price">
                      <?= $price !== null ? h((string)rtrim(rtrim((string)$price,'0'),'.')) . ' ' . h((string)$cur) : '—' ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ПРАВА КОЛОНКА: карта -->
    <div class="card">
      <div class="pad">
        <div class="results">Карта</div>
        <div class="muted">Показує всі товари зі списку ліворуч.</div>
      </div>
      <div class="border-top">
        <div id="map" style="height: calc(100vh - 150px); min-height: 360px;"></div>
      </div>
    </div>

  </div><!-- /.grid -->
</div><!-- /.wrap -->

<!-- Leaflet (на цій сторінці теж потрібен) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {
  "use strict";

  const ITEMS = <?= json_encode(array_map(function($r){
    return [
      'id' => (int)$r['id'],
      'title' => (string)$r['title'],
      'category' => (string)($r['category_name'] ?? ''),
      'price' => $r['price'] !== null ? (float)$r['price'] : null,
      'currency' => (string)($r['currency'] ?? 'UAH'),
      'lat' => isset($r['lat']) ? (float)$r['lat'] : null,
      'lng' => isset($r['lng']) ? (float)$r['lng'] : null,
      'photo' => normalizePhotoPath($r['photo'] ?? null),
    ];
  }, $items), JSON_UNESCAPED_UNICODE) ?>;

  const statusEl = document.getElementById('status');
  const sortSelectEl = document.getElementById('sortSelect');
  const listEl = document.getElementById('list');
  function setStatus(msg){ if (statusEl) statusEl.textContent = msg ? ('— ' + msg) : ''; }

  const listRows = Array.from(document.querySelectorAll('#list .item'));
  listRows.forEach((row, idx) => row.dataset.initialOrder = String(idx));

  function haversineKm(lat1, lng1, lat2, lng2) {
    const toRad = (n) => n * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function updateDistanceInList(userLat, userLng) {
    listRows.forEach((row) => {
      const lat = Number(row.dataset.lat);
      const lng = Number(row.dataset.lng);
      const distanceEl = row.querySelector('[data-distance-km]');
      if (!distanceEl || !Number.isFinite(lat) || !Number.isFinite(lng)) {
        if (distanceEl) distanceEl.textContent = '— км';
        row.dataset.distanceKm = '';
        return;
      }

      const distanceKm = haversineKm(userLat, userLng, lat, lng);
      row.dataset.distanceKm = String(distanceKm);
      distanceEl.textContent = distanceKm.toFixed(1) + ' км';
    });
  }

  function applySort(mode) {
    if (!listEl) return;

    const rows = [...listRows];
    rows.sort((a, b) => {
      if (mode === 'nearest') {
        const ad = Number(a.dataset.distanceKm);
        const bd = Number(b.dataset.distanceKm);
        const av = Number.isFinite(ad) ? ad : Number.POSITIVE_INFINITY;
        const bv = Number.isFinite(bd) ? bd : Number.POSITIVE_INFINITY;
        if (av !== bv) return av - bv;
      }

      if (mode === 'price_asc' || mode === 'price_desc') {
        const ap = Number(a.dataset.price);
        const bp = Number(b.dataset.price);
        const aValid = Number.isFinite(ap);
        const bValid = Number.isFinite(bp);
        if (aValid && bValid && ap !== bp) {
          return mode === 'price_asc' ? ap - bp : bp - ap;
        }
        if (aValid !== bValid) return aValid ? -1 : 1;
      }

      return Number(a.dataset.initialOrder) - Number(b.dataset.initialOrder);
    });

    rows.forEach((row) => listEl.appendChild(row));
  }

  if (sortSelectEl) {
    sortSelectEl.addEventListener('change', () => applySort(sortSelectEl.value));
  }

  if (typeof L === 'undefined') return;

  let center = {lat: 50.4501, lng: 30.5234};

  const map = L.map('map').setView([center.lat, center.lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
  }).addTo(map);

  const markersById = new Map();

  function esc(s){
    return String(s).replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
  }

  const bounds = [];
  ITEMS.forEach(it => {
    if (typeof it.lat !== 'number' || typeof it.lng !== 'number') return;

    const marker = L.marker([it.lat, it.lng]).addTo(map);
    const img = it.photo ? `<div style="margin-top:8px;"><img src="${esc(it.photo)}" style="max-width:180px;max-height:110px;object-fit:contain;border-radius:10px;border:1px solid #e5e7eb;display:block;"></div>` : '';

    marker.bindPopup(`
      <div style="font-weight:900;font-size:13px;">${esc(it.title)}</div>
      <div style="font-size:12px;color:#6b7280;margin-top:4px;">${esc(it.category||'')}</div>
      <div style="margin-top:6px;font-weight:900;">${it.price!=null ? esc(it.price) + ' ' + esc(it.currency||'UAH') : 'Ціна не вказана'}</div>
      ${img}
      <div style="margin-top:8px;">
        <a href="/item.php?id=${it.id}" style="display:inline-block;font-size:12px;font-weight:900;color:#2563eb;text-decoration:none;">Детальніше →</a>
      </div>
    `);

    markersById.set(String(it.id), marker);
    bounds.push([it.lat, it.lng]);
  });

  if (bounds.length) {
    map.fitBounds(bounds, {padding: [20,20]});
  }

  if (navigator.geolocation) {
    setStatus('визначаю геолокацію...');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setStatus('');
        const userLat = pos.coords.latitude;
        const userLng = pos.coords.longitude;
        map.setView([userLat, userLng], 13);
        updateDistanceInList(userLat, userLng);
        if (sortSelectEl && sortSelectEl.value === 'nearest') {
          applySort('nearest');
        }
      },
      () => setStatus(''),
      { enableHighAccuracy: true, timeout: 7000, maximumAge: 60000 }
    );
  }

  const backBtn = document.createElement('button');
  backBtn.className = 'back-to-list';
  backBtn.type = 'button';
  backBtn.textContent = '← До списку';
  backBtn.addEventListener('click', () => {
    document.body.classList.remove('map-mode');
  });
  document.body.appendChild(backBtn);

  document.querySelectorAll('[data-show-map="1"]').forEach((a) => {
    a.addEventListener('click', () => {
      document.body.classList.add('map-mode');

      const id = a.getAttribute('data-focus');
      const marker = markersById.get(String(id));
      if (marker) {
        const p = marker.getLatLng();
        map.setView([p.lat, p.lng], 15);
        marker.openPopup();
      } else if (bounds.length) {
        map.fitBounds(bounds, {padding: [20,20]});
      }

      setTimeout(() => map.invalidateSize(), 150);
    });
  });

  document.querySelectorAll('#list .item').forEach((row) => {
    row.addEventListener('click', () => {
      const id = row.getAttribute('data-id');
      const marker = markersById.get(String(id));
      if (!marker) return;

      document.body.classList.add('map-mode');
      const p = marker.getLatLng();
      map.setView([p.lat, p.lng], 15);
      marker.openPopup();
      setTimeout(() => map.invalidateSize(), 150);
    });
  });

})();
</script>

<?php include __DIR__ . '/inc/footer.php'; ?>
