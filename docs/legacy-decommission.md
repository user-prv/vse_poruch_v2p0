# Legacy decommission plan

## Критерії повного відключення `vseporuch-old`
- 0 blocker/critical дефектів протягом 14 днів після `CUTOVER_MODE=v2`.
- 100% ключових бізнес-флоу працюють у v2.
- Немає звернень до legacy endpoint у web/access логах.

## Архівація legacy
- Створити git tag `legacy-final`.
- Створити архів `vseporuch-old` і статичних assets (`uploads` snapshot).
- Зберегти архів у backup storage з retention-політикою.

## Вимкнення legacy endpoint
1. Встановити `CUTOVER_MODE=v2` на всіх середовищах.
2. Прибрати `legacy` service з `docker-compose`/інфраструктури.
3. Залишити redirect map тільки на нові роути.

## Оновлення документації та онбордингу
- README і runbook описують тільки v2 стек.
- Для команди додати onboarding: запуск локально через `infra/docker-compose.yml`, запуск тестів і перевірка smoke-чекліста.
