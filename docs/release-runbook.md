# Release runbook

## Staging

- Розгортання backend + frontend у staging.
- Smoke тест:
  - healthcheck
  - login
  - критичні сценарії ролей
- Regression тест по чеклісту міграції.

## Прод-реліз

- Канарний реліз (5% -> 25% -> 50% -> 100%).
- Моніторинг:
  - 5xx rate
  - latency p95
  - auth failures

## Decommission legacy

Умови:
1. 2+ тижні стабільності без критичних інцидентів.
2. 100% трафіку на новий стек.
3. Підписаний acceptance від стейкхолдерів.

Після цього:
- legacy переводиться у read-only/архів.
- документація оновлюється.
