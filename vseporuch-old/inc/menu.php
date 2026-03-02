<?php
declare(strict_types=1);

/**
 * inc/menu.php
 * Єдине мобільне меню (bottom nav) для ВСІХ сторінок.
 * $pageKey може бути заданий сторінкою. Якщо ні — визначаємо по URL.
 */

$pageKey = $pageKey ?? null;

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$script  = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!$pageKey) {
  if (str_starts_with($uriPath, '/admin')) $pageKey = 'admin';
  elseif (str_starts_with($uriPath, '/dashboard')) $pageKey = 'dashboard';
  elseif ($script === 'item.php' || str_starts_with($uriPath, '/item.php')) $pageKey = 'item';
  elseif ($script === 'rules.php') $pageKey = 'info';
  elseif ($script === 'categories.php') $pageKey = 'categories';
  else $pageKey = 'home';
}
?>
<nav class="mnav" aria-label="Навігація">
  <a class="mnav__item <?= $pageKey === 'home' ? 'is-active' : '' ?>" href="/">
    <span class="mnav__ico">🏠</span>
    <span class="mnav__txt">Головна</span>
  </a>

  <a class="mnav__item <?= $pageKey === 'categories' ? 'is-active' : '' ?>" href="/categories.php">
    <span class="mnav__ico">🔲</span>
    <span class="mnav__txt">Категорії</span>
  </a>

  <a class="mnav__item <?= $pageKey === 'info' ? 'is-active' : '' ?>" href="/rules.php">
    <span class="mnav__ico">ℹ️</span>
    <span class="mnav__txt">Інфо</span>
  </a>

  <a class="mnav__item <?= $pageKey === 'dashboard' ? 'is-active' : '' ?>" href="/dashboard/">
    <span class="mnav__ico">👤</span>
    <span class="mnav__txt">Кабінет</span>
  </a>
</nav>
