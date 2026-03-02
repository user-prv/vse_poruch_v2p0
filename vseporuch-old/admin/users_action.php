<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../inc/db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));

if ($id <= 0) { http_response_code(400); exit('Bad id'); }

if ($action === 'block') {
  $pdo->prepare("UPDATE listings SET moderation_status='blocked', moderation_reason=?, moderated_at=NOW() WHERE id=?")
      ->execute([$reason ?: 'Blocked', $id]);

} elseif ($action === 'unblock') {
  $pdo->prepare("UPDATE listings SET moderation_status='active', moderation_reason=NULL, moderated_at=NULL WHERE id=?")
      ->execute([$id]);

} elseif ($action === 'delete') {
  $pdo->prepare("UPDATE listings SET moderation_status='deleted', moderated_at=NOW() WHERE id=?")
      ->execute([$id]);

} else {
  http_response_code(400);
  exit('Bad action');
}

header('Location: /admin/listings.php');
exit;