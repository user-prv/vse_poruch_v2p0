<?php
declare(strict_types=1);

if (!isset($pageTitle)) $pageTitle = APP_NAME;

$pageKey = $pageKey ?? null;

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$script  = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!function_exists('str_starts_with')) {
  function str_starts_with(string $haystack, string $needle): bool {
    return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
  }
}

if (!$pageKey) {
  if (str_starts_with($uriPath, '/admin')) $pageKey = 'admin';
  elseif (str_starts_with($uriPath, '/dashboard')) $pageKey = 'dashboard';
  elseif ($script === 'item.php') $pageKey = 'item';
  elseif ($script === 'categories.php') $pageKey = 'categories';
  elseif ($script === 'rules.php') $pageKey = 'info';
  else $pageKey = 'home';
}

$bodyClass = $bodyClass ?? '';

function cssHref(string $file): string {
  return "/assets/css/{$file}.css?v=2";
}

$isLogged = function_exists('isLoggedIn') ? (bool)isLoggedIn() : false;
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <!-- Base -->
  <link rel="stylesheet" href="<?= cssHref('base') ?>">

  <!-- Page CSS -->
  <?php if ($pageKey === 'home'): ?>
    <link rel="stylesheet" href="<?= cssHref('home') ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <?php elseif ($pageKey === 'item'): ?>
    <link rel="stylesheet" href="<?= cssHref('item') ?>">
  <?php elseif ($pageKey === 'dashboard'): ?>
    <link rel="stylesheet" href="<?= cssHref('dashboard') ?>">
  <?php elseif ($pageKey === 'admin'): ?>
    <link rel="stylesheet" href="<?= cssHref('admin') ?>">
  <?php elseif ($pageKey === 'categories'): ?>
    <link rel="stylesheet" href="<?= cssHref('home') ?>">
  <?php endif; ?>
</head>

<body class="<?= htmlspecialchars((string)$bodyClass, ENT_QUOTES, 'UTF-8') ?>" data-page="<?= htmlspecialchars((string)$pageKey, ENT_QUOTES, 'UTF-8') ?>">

<script>
  window.APP = {
    pageKey: <?= json_encode($pageKey) ?>,
    apiListingsUrl: "/api/listings.php",
    isLoggedIn: <?= $isLogged ? 'true' : 'false' ?>
  };
</script>

<nav class="topnav">
  <div class="topnav__inner">
    <a class="topnav__brand" href="/"><?= htmlspecialchars(APP_NAME) ?></a>
    <div class="topnav__links">
      <a href="/" class="<?= $pageKey==='home'?'is-active':'' ?>">Головна</a>
      <a href="/categories.php" class="<?= $pageKey==='categories'?'is-active':'' ?>">Категорії</a>
      <a href="/rules.php" class="<?= $pageKey==='info'?'is-active':'' ?>">Інфо</a>
      <a href="<?= $isLogged ? '/dashboard/' : '/account/' ?>" class="<?= $pageKey==='dashboard'?'is-active':'' ?>">
        Кабінет
      </a>
    </div>
  </div>
</nav>

<?php if ($pageKey === 'home'): ?>
  <div class="wrap"><div class="grid">
<?php else: ?>
  <div class="wrap">
<?php endif; ?>