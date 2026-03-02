<?php
declare(strict_types=1);

/**
 * Видалення одного фото оголошення
 * POST: photo_id, listing_id
 * Захист: тільки власник оголошення може видаляти фото
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

if (!isLoggedIn()) redirect('/account/');
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('/dashboard/');
}

$photoId   = (int)($_POST['photo_id'] ?? 0);
$listingId = (int)($_POST['listing_id'] ?? 0);

if ($photoId <= 0 || $listingId <= 0) {
  redirect('/dashboard/');
}

$pdo = db();

/**
 * 1) Перевіряємо, що:
 * - фото існує
 * - воно належить оголошенню listing_id
 * - це оголошення належить поточному користувачу
 */
$q = $pdo->prepare("
  SELECT p.path
  FROM listing_photos p
  JOIN listings l ON l.id = p.listing_id
  WHERE p.id = ? AND p.listing_id = ? AND l.user_id = ?
  LIMIT 1
");
$q->execute([$photoId, $listingId, $uid]);
$row = $q->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  // або фото не існує, або не твоє
  redirect('/dashboard/listing_edit.php?id=' . $listingId);
}

$path = (string)$row['path'];

/**
 * 2) Видаляємо запис з БД
 */
$del = $pdo->prepare("DELETE FROM listing_photos WHERE id = ? AND listing_id = ?");
$del->execute([$photoId, $listingId]);

/**
 * 3) (Опційно) видаляємо файл з диска
 * Видаляємо тільки якщо файл у /uploads щоб не дати видалити щось стороннє.
 */
if ($path !== '' && str_starts_with($path, '/uploads/')) {
  $abs = realpath(__DIR__ . '/..' . $path);
  $uploadsDir = realpath(__DIR__ . '/../uploads');

  // Захист: файл має бути всередині папки uploads
  if ($abs && $uploadsDir && str_starts_with($abs, $uploadsDir) && is_file($abs)) {
    @unlink($abs);
  }
}

/**
 * 4) Повертаємося на редагування оголошення
 */
redirect('/dashboard/listing_edit.php?id=' . $listingId);