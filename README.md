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


## Корисні інструкції

- Вхід в адмінку та створення категорій: `docs/admin-access-and-categories.md`.
