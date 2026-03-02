<?php
declare(strict_types=1);

$pageKey = $pageKey ?? null;

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$script  = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!$pageKey) {
  if (str_starts_with($uriPath, '/admin')) $pageKey = 'admin';
  elseif (str_starts_with($uriPath, '/dashboard')) $pageKey = 'dashboard';
  elseif ($script === 'item.php' || str_starts_with($uriPath, '/item.php')) $pageKey = 'item';
  elseif ($script === 'categories.php') $pageKey = 'categories';
  elseif ($script === 'rules.php') $pageKey = 'info';
  else $pageKey = 'home';
}
?>

<?php if ($pageKey === 'home'): ?>
    </div><!-- /.grid -->
  </div><!-- /.wrap -->

  <!-- Leaflet JS тільки на головній -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php else: ?>
  </div><!-- /.wrap -->
<?php endif; ?>

<?php if ($pageKey !== 'admin'): ?>
  <!-- MOBILE BOTTOM MENU (іконки беруться з base.css; тут НЕ дублюємо emoji) -->
  <nav class="mnav" aria-label="Навігація">
    <a href="/" class="mnav__item <?= $pageKey==='home'?'is-active':'' ?>">
      <span class="mnav__ico" aria-hidden="true"></span>
      <span class="mnav__txt">Головна</span>
    </a>

    <a href="/categories.php" class="mnav__item <?= $pageKey==='categories'?'is-active':'' ?>">
      <span class="mnav__ico" aria-hidden="true"></span>
      <span class="mnav__txt">Категорії</span>
    </a>

    <a href="/rules.php" class="mnav__item <?= $pageKey==='info'?'is-active':'' ?>">
      <span class="mnav__ico" aria-hidden="true"></span>
      <span class="mnav__txt">Інфо</span>
    </a>

    <a href="/dashboard/" class="mnav__item <?= $pageKey==='dashboard'?'is-active':'' ?>">
      <span class="mnav__ico" aria-hidden="true"></span>
      <span class="mnav__txt">Кабінет</span>
    </a>
  </nav>
<?php endif; ?>

<!-- ЄДИНИЙ JS: loader, який сам підтягує lib/pages -->
<script src="/assets/js/app.js?v=1" defer></script>

</body>
</html>
