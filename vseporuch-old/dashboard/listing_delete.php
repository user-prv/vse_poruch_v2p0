
<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireLogin();
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/dashboard/');
$id = (int)($_POST['id'] ?? 0);

$pdo = db();
$stmt = $pdo->prepare("DELETE FROM listings WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);

redirect('/dashboard/');