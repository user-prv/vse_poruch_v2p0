<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

/**
 * Адмін-гард:
 * - тільки для залогінених
 * - тільки для users.is_admin = 1
 */

if (!isLoggedIn()) {
  redirect('/account/');
  exit;
}

$pdo = db();
$uid = currentUserId();

$stmt = $pdo->prepare("SELECT id, is_admin FROM users WHERE id=? LIMIT 1");
$stmt->execute([$uid]);
$me = $stmt->fetch();

if (!$me || (int)$me['is_admin'] !== 1) {
  http_response_code(403);
  echo "Доступ заборонено";
  exit;
}