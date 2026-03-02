<?php
declare(strict_types=1);

/**
 * Тест підключення PDO через твій реальний стек
 * НІЧОГО не змінює в БД
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

echo "<pre>";

echo "1) bootstrap.php OK\n";
echo "2) auth.php OK\n";
echo "3) db.php підключено\n\n";

/* 1. Перевіряємо, чи існує функція db() */
if (!function_exists('db')) {
  echo "❌ Функція db() НЕ знайдена\n";
  exit;
}
echo "✅ Функція db() знайдена\n";

/* 2. Пробуємо отримати PDO */
try {
  $pdo = db();
  echo "✅ db() повернула обʼєкт\n";
} catch (Throwable $e) {
  echo "❌ db() впала з помилкою:\n";
  echo $e->getMessage();
  exit;
}

/* 3. Перевіряємо, що це PDO */
if (!($pdo instanceof PDO)) {
  echo "❌ db() НЕ PDO\n";
  var_dump($pdo);
  exit;
}
echo "✅ Це PDO\n";

/* 4. Виводимо базову інформацію */
echo "\nPDO info:\n";
echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
echo "Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";

/* 5. Простий SQL */
try {
  $q = $pdo->query("SELECT COUNT(*) FROM users");
  $count = $q->fetchColumn();
  echo "\n✅ SQL OK, users count = {$count}\n";
} catch (Throwable $e) {
  echo "\n❌ SQL помилка:\n";
  echo $e->getMessage();
  exit;
}

/* 6. Перевірка is_admin */
try {
  $q = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
  $col = $q->fetch();
  if ($col) {
    echo "✅ Колонка users.is_admin існує\n";
  } else {
    echo "❌ Колонка users.is_admin НЕ знайдена\n";
  }
} catch (Throwable $e) {
  echo "❌ Помилка перевірки is_admin:\n";
  echo $e->getMessage();
}

echo "\n🎉 PDO ПОВНІСТЮ ПРАЦЮЄ\n";
echo "</pre>";