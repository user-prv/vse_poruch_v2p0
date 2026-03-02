# Runbook

## Deploy
1. Прогнати CI (`lint`, `test`, `build`).
2. Накатити міграції БД.
3. Розгорнути backend, потім frontend, потім gateway.
4. Перевірити `/health`, `/ready`, `/metrics`.

## Recovery
- Перевірити доступність БД і health endpoints.
- У разі проблем БД: rollback міграцій, відновлення з backup.
- У разі функціональних регресій: встановити `CUTOVER_MODE=legacy` і виконати rollback релізу.

## Observability
- Логи через Logrus, формат задається `LOG_FORMAT=json|plain`, рівень `LOG_LEVEL`.
- Access log + audit події (`auth.*`, `admin.*`).
- Метрики Prometheus доступні на `/metrics`.

## Security
- Валідація вхідних даних через Gin binding.
- Escape user-input перед збереженням (базовий захист від XSS).
- Rate limiting для `auth` і API.
- SQL-запити параметризовані через GORM (захист від SQL injection).
