<?php
declare(strict_types=1);

/**
 * Повертає WEB-шляхи збережених фото (масив), максимум $maxCount.
 * Підтримує input name="photos[]" (multiple).
 */
function saveUploadedImages(array $files, string $uploadDirAbs, string $uploadDirWeb, int $maxCount = 5): array {
  $out = [];

  if (!isset($files['name']) || !is_array($files['name'])) return $out;

  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];

  if (!is_dir($uploadDirAbs)) {
    @mkdir($uploadDirAbs, 0755, true);
  }

  $count = count($files['name']);
  $limit = min($count, $maxCount);

  for ($i = 0; $i < $limit; $i++) {
    $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) continue;

    $tmp = $files['tmp_name'][$i] ?? '';
    if (!$tmp || !is_uploaded_file($tmp)) continue;

    // size limit 5MB
    $size = (int)($files['size'][$i] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) continue;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) continue;

    $ext = $allowed[$mime];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;

    $targetAbs = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $targetAbs)) continue;

    $out[] = rtrim($uploadDirWeb, '/') . '/' . $name;
  }

  return $out;
}

/**
 * Зберігає 1 аватар (один файл), повертає web-шлях або null.
 * Використовувати з input name="avatar"
 */
function saveUploadedAvatar(array $file, string $uploadDirAbs, string $uploadDirWeb): ?string {
  if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return null;

  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];

  $tmp = (string)($file['tmp_name'] ?? '');
  if ($tmp === '' || !is_uploaded_file($tmp)) return null;

  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > 5 * 1024 * 1024) return null;

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $tmp);
  finfo_close($finfo);

  if (!isset($allowed[$mime])) return null;

  if (!is_dir($uploadDirAbs)) @mkdir($uploadDirAbs, 0755, true);

  $ext = $allowed[$mime];
  $name = 'ava_' . bin2hex(random_bytes(16)) . '.' . $ext;

  $targetAbs = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $name;
  if (!move_uploaded_file($tmp, $targetAbs)) return null;

  return rtrim($uploadDirWeb, '/') . '/' . $name;
}
/**
 * Зберігає ОДИН файл (input name="icon" без multiple).
 * Повертає WEB-шлях або null.
 *
 * $maxBytes — ліміт розміру файлу.
 * $squareSize — якщо GD доступний, робить квадратний прев’ю-файл (типу 100x100) для іконок.
 */
function saveUploadedImageSingle(array $file, string $uploadDirAbs, string $uploadDirWeb, int $maxBytes = 2_000_000, ?int $squareSize = null): ?string {
  if (empty($file['name']) || empty($file['tmp_name'])) return null;
  if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) return null;

  $tmp = (string)$file['tmp_name'];
  if ($tmp === '' || !is_uploaded_file($tmp)) return null;

  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > $maxBytes) return null;

  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $tmp);
  finfo_close($finfo);

  if (!isset($allowed[$mime])) return null;

  if (!is_dir($uploadDirAbs)) {
    @mkdir($uploadDirAbs, 0755, true);
  }

  $ext = $allowed[$mime];
  $name = bin2hex(random_bytes(16)) . '.' . $ext;
  $dstAbs = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $name;
  $dstWeb = rtrim($uploadDirWeb, '/') . '/' . $name;

  // Якщо треба квадратну іконку і GD доступний — генеруємо її напряму в dstAbs
  if ($squareSize !== null && $squareSize > 0 && function_exists('imagecreatetruecolor')) {
    $ok = createSquareThumbFromUpload($tmp, $dstAbs, $mime, $ext, $squareSize);
    if ($ok) return $dstWeb;
    // якщо не вийшло — падаємо на звичайне move_uploaded_file
  }

  if (!@move_uploaded_file($tmp, $dstAbs)) return null;

  return $dstWeb;
}

/**
 * Створює квадратне зображення $size x $size з центру (contain+crop),
 * і записує у $dstAbs.
 */
function createSquareThumbFromUpload(string $srcTmp, string $dstAbs, string $mime, string $ext, int $size): bool {
  try {
    // load
    if ($mime === 'image/jpeg') $src = @imagecreatefromjpeg($srcTmp);
    elseif ($mime === 'image/png') $src = @imagecreatefrompng($srcTmp);
    elseif ($mime === 'image/webp') $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcTmp) : false;
    else $src = false;

    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w <= 0 || $h <= 0) { imagedestroy($src); return false; }

    // crop center square
    $side = min($w, $h);
    $sx = (int)(($w - $side) / 2);
    $sy = (int)(($h - $side) / 2);

    $dst = imagecreatetruecolor($size, $size);

    // preserve transparency for png/webp
    if ($mime === 'image/png' || $mime === 'image/webp') {
      imagealphablending($dst, false);
      imagesavealpha($dst, true);
      $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
      imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);

    $ok = false;
    if ($ext === 'jpg') $ok = imagejpeg($dst, $dstAbs, 85);
    elseif ($ext === 'png') $ok = imagepng($dst, $dstAbs, 6);
    elseif ($ext === 'webp' && function_exists('imagewebp')) $ok = imagewebp($dst, $dstAbs, 80);

    imagedestroy($dst);
    imagedestroy($src);

    return (bool)$ok;
  } catch (Throwable $e) {
    return false;
  }
}
