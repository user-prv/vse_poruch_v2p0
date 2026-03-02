# Migration waves & checklist

## Пріоритезація

- **Wave 1**: auth + users + ролі + базовий shell UI.
- **Wave 2**: ключові бізнес-сутності та операційні сценарії.
- **Wave 3**: інтеграції, звіти, оптимізації.

## Цикл міграції фічі

1. Опис API контракту.
2. Реалізація backend (`handler + service + repository`).
3. Реалізація frontend UI.
4. Функціональна перевірка проти legacy.

## Checklist відповідності legacy -> new

- [ ] auth/login parity
- [ ] user profile parity
- [ ] user list parity
- [ ] role-based access parity
- [ ] critical CRUD #1 parity
- [ ] critical CRUD #2 parity

## Дані та сумісність

- Міграції схеми: через GORM AutoMigrate (на старті) + керовані SQL міграції надалі.
- Скрипти синхронізації: `docs/data-sync-procedure.md`.
- Backward compatibility: JSON-формати API з versioning `/api/v1`.
