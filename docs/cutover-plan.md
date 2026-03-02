# Cutover і rollback

## Паралельний запуск
- Legacy (`vseporuch-old`) і новий стек (`frontend` + `backend`) працюють паралельно через nginx.
- Для поетапного перемикання використовується `CUTOVER_MODE`:
  - `legacy` — всі запити на legacy;
  - `hybrid` — read-flow на новий стек, write-flow залишається legacy;
  - `v2` — всі запити на новий стек.
- Redirect map підтримує сумісність URL для `index.php`, `item.php`, `categories.php`, `user.php`.

## Smoke/UAT на staging
1. Перевірити `/health`, `/ready`, `/metrics`.
2. Перевірити read-flow: home → категорії → деталі оголошення.
3. Перевірити write-flow: реєстрація → створення оголошення → модерація в адмінці.
4. Перевірити завантаження фото і відображення з `/uploads/*`.
5. Підписати чекліст UAT перед перемиканням `CUTOVER_MODE=hybrid`/`v2`.

## Поетапний production cutover
1. **Етап 1 (read-only):** `CUTOVER_MODE=hybrid`, увімкнути тільки читання на v2.
2. **Етап 2 (write-flow):** перевести auth/listings/categories/uploads на v2.
3. **Етап 3 (full):** `CUTOVER_MODE=v2`, legacy тільки як fallback.

## Rollback
- Перед кожним етапом: backup БД + запуск smoke test.
- При регресії:
  1. повернути `CUTOVER_MODE=legacy`;
  2. виконати rollback деплою на попередній реліз;
  3. за потреби rollback міграцій;
  4. відновити дані з backup.
- RTO ціль: до 15 хвилин, RPO ціль: до 5 хвилин.
