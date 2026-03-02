<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

$pageTitle = 'Правила та місія — ' . APP_NAME;
$pageKey = 'rules';

include __DIR__ . '/inc/header.php';
?>

<div class="wrap">
  <div class="card">
    <div class="pad">

      <h1 style="font-weight:900;font-size:20px;margin-top:0;">
        Місія платформи «Поруч»
      </h1>

      <p class="muted">
        «Поруч» — це платформа для людей, які хочуть знаходити одне одного поруч.
      </p>

      <p>
        Ми створюємо простий і чесний простір, де можна:
      </p>

      <ul>
        <li>купувати та продавати товари поблизу,</li>
        <li>знаходити послуги поруч із домом,</li>
        <li>підтримувати локальні заклади,</li>
        <li><strong>допомагати тим, хто цього потребує.</strong></li>
      </ul>

      <p>
        Ми віримо, що сильні спільноти починаються з сусідів.
      </p>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <h2 style="font-weight:900;font-size:16px;">🧡 Соціальна відповідальність</h2>

      <p>
        На «Поруч» є місце не лише для комерції, а й для людяності.
      </p>

      <p>
        Розділ <strong>«Допомога поруч»</strong> створений для:
      </p>

      <ul>
        <li>літніх людей,</li>
        <li>людей з інвалідністю,</li>
        <li>тих, хто тимчасово потребує підтримки.</li>
      </ul>

      <p class="muted">
        Такі оголошення не є бізнесом і не призначені для заробітку.
      </p>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <h2 style="font-weight:900;font-size:16px;">📌 Основні принципи</h2>

      <ol>
        <li><strong>Чесність</strong> — не вводьте в оману інших користувачів.</li>
        <li><strong>Повага</strong> — заборонені образи та дискримінація.</li>
        <li><strong>Локальність</strong> — розміщуйте реальні оголошення поруч.</li>
        <li><strong>Відповідальність</strong> — ви відповідаєте за свій контент.</li>
      </ol>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <h2 style="font-weight:900;font-size:16px;">🚫 Заборонено</h2>

      <ul>
        <li>шахрайство та фейкові оголошення</li>
        <li>продаж заборонених законом товарів</li>
        <li>спам та масове дублювання</li>
        <li>зловживання розділом «Допомога поруч»</li>
      </ul>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <h2 style="font-weight:900;font-size:16px;">🛡 Модерація</h2>

      <p>
        Адміністрація платформи має право:
      </p>

      <ul>
        <li>видаляти або приховувати оголошення,</li>
        <li>обмежувати доступ до акаунту,</li>
        <li>вимагати підтвердження контактних даних.</li>
      </ul>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <h2 style="font-weight:900;font-size:16px;">🔐 Безпека</h2>

      <p>
        «Поруч» не є стороною угод між користувачами.
      </p>

      <p class="muted">
        Будьте уважні, не передавайте гроші наперед та зустрічайтесь у публічних місцях.
      </p>

      <hr style="margin:24px 0;border:none;border-top:1px solid var(--border);">

      <p style="font-weight:900;">
        «Поруч» — це не просто платформа. Це місце, де люди стають ближчими ❤️
      </p>

    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>