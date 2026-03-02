# Цільова архітектура

## Backend
- Стиль: `handler -> service -> repository`.
- Стек: Gin + GORM + Logrus.
- Конфіг: env (`.env`) через пакет `internal/config`.
- API формат: REST + JSON, namespace `/api/v1`.
- Авторизація: JWT access/refresh (план).
- Ролі: `user`, `moderator`, `admin`.

## Health endpoints
- `/health` — liveness.
- `/ready` — readiness з ping до БД.
