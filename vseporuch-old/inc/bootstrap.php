<?php
declare(strict_types=1);

session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

// Базові налаштування (потім додаси БД/Firebase)
define('APP_NAME', 'Поруч');

function redirect(string $to): never {
  header("Location: {$to}");
  exit;
}
