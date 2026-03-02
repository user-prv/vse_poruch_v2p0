<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

$pageTitle = APP_NAME . ' — карта поруч';

/**
 * ГОЛОВНА СТОРІНКА
 */
$pageCss    = ['/assets/css/home.css'];
$pageJsPage = 'home';
$layout     = 'home';

include __DIR__ . '/inc/header.php';
include __DIR__ . '/views/home_sidebar.php';
include __DIR__ . '/views/home_map.php';
include __DIR__ . '/inc/footer.php';