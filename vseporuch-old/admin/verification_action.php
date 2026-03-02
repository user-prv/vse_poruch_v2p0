<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
if ($id <= 0 || $action !== 'verify') {
  http_response_code(400);
  exit('Bad request');
}

$st = $pdo->prepare("UPDATE listings SET moderation_status='active', status='active', moderation_reason=NULL, moderated_at=NOW() WHERE id=:id");
$st->execute([':id' => $id]);

header('Location: /admin/verification.php');
exit;
