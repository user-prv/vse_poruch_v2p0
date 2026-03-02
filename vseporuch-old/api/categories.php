<?php
declare(strict_types=1);

/**
 * API: /api/categories.php
 * Повертає JSON з деревом категорій для сторінки "Категорії" або форм.
 *
 * Response:
 * { ok: true, items: [ {id, parent_id, name, icon_path, children: [...] } ] }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/categories.php';

function out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $rows = categoriesAll(true);
  $tree = categoriesTree($rows);
  out(['ok' => true, 'items' => $tree]);
} catch (Throwable $e) {
  out(['ok' => false, 'error' => 'server_error', 'message' => $e->getMessage()], 500);
}
