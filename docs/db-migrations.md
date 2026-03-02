# Міграції БД

Інструмент: `golang-migrate` (узгоджено).

## План міграцій
1. `0001_init_users_categories_listings.up.sql` — базові таблиці `users`, `categories`, `listings`.
2. `0002_listing_photos_and_password_resets.up.sql` — `listing_photos`, `password_resets`.
3. `0003_indexes_and_constraints.up.sql` — індекси/унікальності/FK/check constraints.
4. `0004_seed_base_categories.up.sql` — стартові категорії (опційно).

## Міграційні артефакти
Міграції збережені у `backend/migrations` з парами `*.up.sql` / `*.down.sql`.

## Робота з секретами
- Локально: `.env` (тільки для dev), без коміту в репозиторій.
- CI: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` через GitHub Actions secrets.
- Продакшн: Vault/Secrets Manager з ротацією мінімум раз на 90 днів.
- Ротація без даунтайму: dual credentials (старий+новий), перевірка read/write, відключення старого секрету.
