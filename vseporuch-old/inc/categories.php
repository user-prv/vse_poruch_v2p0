<?php
declare(strict_types=1);

/**
 * inc/categories.php
 * Допоміжні функції для роботи з категоріями.
 * Працює з таблицею `categories`:
 * - id (int)
 * - parent_id (int|null)
 * - name (varchar)
 * - icon_path (varchar|null)  // шлях до іконки/фото категорії
 * - is_active (tinyint, optional)
 * - sort_order (int, optional)
 */

require_once __DIR__ . '/db.php';

/** Повертає всі активні категорії (або всі, якщо $onlyActive=false) */
function categoriesAll(bool $onlyActive = true): array {
  $pdo = db();

  // Підтримуємо проєкт навіть якщо нема is_active/sort_order:
  // 1) пробуємо SELECT з цими колонками
  // 2) якщо впало — робимо простіший SELECT
  try {
    $sql = "SELECT id, parent_id, name, icon_path, is_active, sort_order
            FROM categories
            " . ($onlyActive ? "WHERE (is_active IS NULL OR is_active = 1)" : "") . "
            ORDER BY COALESCE(sort_order, 999999) ASC, name ASC";
    return $pdo->query($sql)->fetchAll();
  } catch (Throwable $e) {
    $sql = "SELECT id, parent_id, name, icon_path
            FROM categories
            ORDER BY name ASC";
    return $pdo->query($sql)->fetchAll();
  }
}

/** Будує дерево категорій (parent -> children) */
function categoriesTree(array $rows): array {
  $byId = [];
  foreach ($rows as $r) {
    $id = (int)$r['id'];
    $byId[$id] = $r;
    $byId[$id]['children'] = [];
  }

  $tree = [];
  foreach ($byId as $id => &$node) {
    $pid = isset($node['parent_id']) ? (int)$node['parent_id'] : 0;
    if (!empty($pid) && isset($byId[$pid])) {
      $byId[$pid]['children'][] = &$node;
    } else {
      $tree[] = &$node;
    }
  }
  unset($node);

  return $tree;
}

/** Повертає плоский список для <select> з відступами */
function categoriesForSelect(bool $onlyActive = true): array {
  $rows = categoriesAll($onlyActive);
  $tree = categoriesTree($rows);

  $out = [];
  $walk = function(array $nodes, int $depth) use (&$walk, &$out) {
    foreach ($nodes as $n) {
      $prefix = str_repeat('— ', $depth);
      $out[] = [
        'id' => (int)$n['id'],
        'name' => $prefix . (string)$n['name'],
        'raw_name' => (string)$n['name'],
        'parent_id' => isset($n['parent_id']) ? (int)$n['parent_id'] : null,
        'icon_path' => $n['icon_path'] ?? null,
      ];
      if (!empty($n['children'])) $walk($n['children'], $depth + 1);
    }
  };

  $walk($tree, 0);
  return $out;
}

/* =========================================================
   ДОДАНО: навігація та вибірка піддерева (для /categories.php)
   ========================================================= */

/** Одна категорія за id */
function categoryById(int $id): ?array {
  if ($id <= 0) return null;
  $pdo = db();
  $st = $pdo->prepare("SELECT id, parent_id, name, icon_path FROM categories WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ? $row : null;
}

/** Діти категорії (1 рівень). Якщо $parentId = null → кореневі категорії */
function categoriesChildren(?int $parentId): array {
  $pdo = db();
  if ($parentId === null) {
    $st = $pdo->query("SELECT id, parent_id, name, icon_path FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
    return $st->fetchAll();
  }
  $st = $pdo->prepare("SELECT id, parent_id, name, icon_path FROM categories WHERE parent_id=? ORDER BY name ASC");
  $st->execute([$parentId]);
  return $st->fetchAll();
}

/** Хлібні крихти: від поточної категорії до кореня */
function categoryBreadcrumbs(int $id): array {
  $pdo = db();
  $crumbs = [];
  $cur = $id;

  // захист від циклів
  $guard = 0;
  while ($cur > 0 && $guard++ < 50) {
    $st = $pdo->prepare("SELECT id, parent_id, name FROM categories WHERE id=? LIMIT 1");
    $st->execute([$cur]);
    $row = $st->fetch();
    if (!$row) break;

    $crumbs[] = [
      'id' => (int)$row['id'],
      'name' => (string)$row['name'],
      'parent_id' => $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
    ];
    $cur = $row['parent_id'] !== null ? (int)$row['parent_id'] : 0;
  }

  return array_reverse($crumbs);
}

/** Всі id підкатегорій будь-якої глибини + сама категорія (MySQL 8+) */
function categorySubtreeIds(int $rootId): array {
  if ($rootId <= 0) return [];
  $pdo = db();

  $sql = "
    WITH RECURSIVE tree AS (
      SELECT id FROM categories WHERE id = ?
      UNION ALL
      SELECT c.id
      FROM categories c
      JOIN tree t ON c.parent_id = t.id
    )
    SELECT id FROM tree
  ";

  $st = $pdo->prepare($sql);
  $st->execute([$rootId]);

  return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}
