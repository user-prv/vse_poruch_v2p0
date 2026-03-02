<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));

if ($id <= 0) { http_response_code(400); exit("Bad id"); }

if ($action === 'block') {
  $stmt = $pdo->prepare("UPDATE listings SET status='blocked', moderation_status='blocked', blocked_reason=:r, blocked_at=NOW(), moderated_at=NOW() WHERE id=:id");
  $stmt->execute([':r' => $reason ?: 'Blocked', ':id' => $id]);

} elseif ($action === 'unblock') {
  $stmt = $pdo->prepare("UPDATE listings SET status='active', moderation_status='active', blocked_reason=NULL, blocked_at=NULL, moderated_at=NOW() WHERE id=:id");
  $stmt->execute([':id' => $id]);

} elseif ($action === 'delete') {
  // soft delete
  $stmt = $pdo->prepare("UPDATE listings SET status='deleted', moderation_status='deleted', deleted_at=NOW(), moderated_at=NOW() WHERE id=:id");
  $stmt->execute([':id' => $id]);

} else {
  http_response_code(400);
  exit("Bad action");
}

header('Location: /admin/listings.php');
exit;