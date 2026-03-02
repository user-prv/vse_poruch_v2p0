<?php
declare(strict_types=1);

function isLoggedIn(): bool {
  return !empty($_SESSION['user']);
}

function currentUserEmail(): ?string {
  return $_SESSION['user']['email'] ?? null;
}

function currentUserId(): ?int {
  return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function requireLogin(): void {
  if (!isLoggedIn()) redirect('/account/login.php');
}