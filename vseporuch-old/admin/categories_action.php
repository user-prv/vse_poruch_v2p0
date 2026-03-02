<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/upload.php';

$pdo = db();

function redirectBack(): void {
  header('Location: /admin/categories.php');
  exit;
}

$action = (string)($_POST['action'] ?? '');

function parseParentId($raw) {
  $raw = trim((string)$raw);
  if ($raw === '' || $raw === '0') return null;
  if (!ctype_digit($raw)) return null;
  return (int)$raw;
}

/* =========================================================
   CREATE
   ========================================================= */
if ($action === 'create') {

  $name = trim((string)($_POST['name'] ?? ''));
  $parentId = parseParentId($_POST['parent_id'] ?? '');

  if ($name === '') redirectBack();

  $iconPath = null;
  if (!empty($_FILES['icon']) && !empty($_FILES['icon']['name'])) {
    try {
      $iconPath = saveUploadedImageSingle(
        $_FILES['icon'],
        __DIR__ . '/../uploads/category_icons',
        '/uploads/category_icons',
        2_000_000,
        100
      );
    } catch (Throwable $e) {
      $iconPath = null;
    }
  }

  $st = $pdo->prepare("INSERT INTO categories (parent_id, name, icon_path) VALUES (?, ?, ?)");
  $st->execute([$parentId, $name, $iconPath]);

  redirectBack();
}

/* =========================================================
   UPDATE (редагування)
   ========================================================= */
if ($action === 'update') {

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) redirectBack();

  $name = trim((string)($_POST['name'] ?? ''));
  $parentId = parseParentId($_POST['parent_id'] ?? '');

  if ($name === '') redirectBack();

  // Поточна категорія
  $cur = $pdo->prepare("SELECT id, parent_id, icon_path FROM categories WHERE id=? LIMIT 1");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row) redirectBack();

  // Забороняємо робити parent_id = self
  if ($parentId !== null && $parentId === $id) {
    $parentId = null;
  }

  // Нова іконка (опційно). Якщо не завантажили — лишаємо стару.
  $iconPath = (string)($row['icon_path'] ?? '');
  if (!empty($_FILES['icon']) && !empty($_FILES['icon']['name'])) {
    try {
      $newIcon = saveUploadedImageSingle(
        $_FILES['icon'],
        __DIR__ . '/../uploads/category_icons',
        '/uploads/category_icons',
        2_000_000,
        100
      );
      if ($newIcon) $iconPath = $newIcon;
    } catch (Throwable $e) {
      // якщо не вийшло — лишаємо стару
    }
  }

  $st = $pdo->prepare("UPDATE categories SET parent_id=?, name=?, icon_path=? WHERE id=? LIMIT 1");
  $st->execute([$parentId, $name, $iconPath ?: null, $id]);

  redirectBack();
}

/* =========================================================
   DELETE
   ========================================================= */
if ($action === 'delete') {

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) redirectBack();

  // Не видаляємо, якщо є підкатегорії
  $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
  $chk->execute([$id]);
  $cnt = (int)$chk->fetchColumn();

  if ($cnt > 0) redirectBack();

  $st = $pdo->prepare("DELETE FROM categories WHERE id = ? LIMIT 1");
  $st->execute([$id]);

  redirectBack();
}

redirectBack();