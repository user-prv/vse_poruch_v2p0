# VsePoruch v2 (Golang + React)

Цей репозиторій містить:

- `frontend/` — React SPA для нового UI.
- `backend/` — Golang API на Gin/GORM.
- `vseporuch-old/` — legacy PHP-реалізація.
- `docs/` — технічна документація міграції.
- `infra/` — docker-compose, nginx та CI/CD заготовки.

## Архітектура

- Frontend спілкується тільки з `backend` через REST `/api/v1`.
- Backend використовує шарову архітектуру `handler -> service -> repository` (цільовий стиль).
- Legacy код використовується як джерело істини для поетапного cutover.


## Test seed data

Run seed after migrations to populate test categories/listings:

```bash
psql "$DATABASE_URL" -f backend/seed.sql
```

Seed creates 10 categories and 30 listings with mixed statuses (`draft`, `pending_verification`, `active`, `rejected`, `archived`).
