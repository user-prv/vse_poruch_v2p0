# Runbook

## Deploy
1. Прогнати CI (lint/test/build).
2. Накатити міграції.
3. Розгорнути backend, потім frontend.

## Recovery
- Перевірити `/health` і `/ready`.
- У разі проблем БД: rollback міграцій, відновлення з backup.

## Observability
- Логи через Logrus JSON.
- Метрики Prometheus/Grafana — planned.
