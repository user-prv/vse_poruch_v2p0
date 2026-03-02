<?php
declare(strict_types=1);

/**
 * API: /api/listings.php
 * Повертає JSON зі списком оголошень, відсортованих від найближчого до найдальшого.
 *
 * GET:
 * - q   (string)  пошук по назві/опису/категорії
 * - lat (float)   широта точки пошуку
 * - lng (float)   довгота точки пошуку
 *
 * Відповідь:
 * {
 *   ok: true,
 *   items: [{ id, title, description, category, price, currency, lat, lng, distanceKm, photo_urls: [] }]
 * }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/db.php';

function out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
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

try {
  $q   = trim((string)($_GET['q'] ?? ''));
  $lat = trim((string)($_GET['lat'] ?? ''));
  $lng = trim((string)($_GET['lng'] ?? ''));

  if ($lat === '' || $lng === '') {
    out(['ok' => false, 'error' => 'lat/lng required'], 400);
  }

  $latVal = (float)str_replace(',', '.', $lat);
  $lngVal = (float)str_replace(',', '.', $lng);

  if ($latVal < -90 || $latVal > 90 || $lngVal < -180 || $lngVal > 180) {
    out(['ok' => false, 'error' => 'invalid lat/lng'], 400);
  }

  $pdo = db();

  // ВАЖЛИВО: JOIN users щоб не показувати оголошення заблокованих користувачів
  $sql = "
    SELECT
      l.id,
      l.title,
      l.description,
      l.category,
      l.price,
      l.currency,
      l.lat,
      l.lng,

      (6371 * 2 * ASIN(SQRT(
        POWER(SIN((RADIANS(l.lat) - RADIANS(:ulat)) / 2), 2) +
        COS(RADIANS(:ulat)) * COS(RADIANS(l.lat)) *
        POWER(SIN((RADIANS(l.lng) - RADIANS(:ulng)) / 2), 2)
      ))) AS distanceKm,

      (
        SELECT p.path
        FROM listing_photos p
        WHERE p.listing_id = l.id
        ORDER BY p.sort_order ASC, p.id ASC
        LIMIT 1
      ) AS photo1

    FROM listings l
    JOIN users u ON u.id = l.user_id
    WHERE l.is_active = 1
      AND ( (l.moderation_status IS NULL OR LOWER(TRIM(l.moderation_status)) = 'active') AND (l.status IS NULL OR LOWER(TRIM(l.status)) = 'active') )
      AND (u.status IS NULL OR u.status <> 'blocked')
      AND (u.blocked_at IS NULL)
  ";

  $params = [
    ':ulat' => $latVal,
    ':ulng' => $lngVal,
  ];

  if ($q !== '') {
    $sql .= " AND (l.title LIKE :q OR l.description LIKE :q OR l.category LIKE :q) ";
    $params[':q'] = '%' . $q . '%';
  }

  // СОРТУВАННЯ: ближче → вище (і без радіусу)
  $sql .= " ORDER BY distanceKm ASC, l.id DESC LIMIT 200 ";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $items = [];
  foreach ($rows as $r) {
    $photo1 = normalizePhotoPath($r['photo1'] !== null ? (string)$r['photo1'] : null);

    $items[] = [
      'id' => (int)$r['id'],
      'title' => (string)$r['title'],
      'description' => $r['description'] !== null ? (string)$r['description'] : '',
      'category' => $r['category'] !== null ? (string)$r['category'] : '',
      'price' => $r['price'] !== null ? (float)$r['price'] : null,
      'currency' => $r['currency'] !== null ? (string)$r['currency'] : 'UAH',
      'lat' => (float)$r['lat'],
      'lng' => (float)$r['lng'],
      'distanceKm' => isset($r['distanceKm']) ? (float)$r['distanceKm'] : null,

      // КРИТИЧНО: фронт зараз чекає photo_urls[]
      'photo_urls' => $photo1 ? [$photo1] : [],
    ];
  }

  out(['ok' => true, 'items' => $items]);

} catch (Throwable $e) {
  out([
    'ok' => false,
    'error' => 'server_error',
    'message' => $e->getMessage(),
  ], 500);
}