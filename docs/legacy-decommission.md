# Legacy decommission plan

## Критерії повного відключення `vseporuch-old`
- 0 blocker/critical дефектів протягом 14 днів після `CUTOVER_MODE=v2`.
- 100% ключових бізнес-флоу працюють у v2.
- Немає звернень до legacy endpoint у web/access логах.

## План після стабілізації (`D+14` після `v2`)
| Крок | Owner | ETA | Ризики | Контроль |
|---|---|---|---|---|
| Підтвердити completion стабілізації (SLO, інциденти, support tickets) | Product owner + QA lead | D+14 | Приховані дефекти, що проявляються рідко | Фінальний UAT + перевірка incident backlog |
| Архівувати `vseporuch-old` і artifacts | Tech lead + SRE | D+15 | Неповний архів, втрата критичних assets | Перевірка checksum архіву, тест відновлення в sandbox |
| Видалити legacy service з infra | SRE | D+16 | Помилкове видалення залежних ресурсів | Change review + staged apply + post-check health |
| Очистити redirect map від legacy-роутів | Frontend lead + Backend lead | D+17 | 404/redirect loops для застарілих URL | Моніторинг 3xx/4xx, rollback redirect-конфігу |

## Архівація legacy
- Створити git tag `legacy-final`.
- Створити архів директорії `vseporuch-old` і статичних assets (`uploads` snapshot).
- Зберегти архів у backup storage з retention-політикою.

## Вимкнення legacy endpoint
1. Переконатися, що `CUTOVER_MODE=v2` зафіксовано на всіх середовищах.
2. Прибрати `legacy` service з `docker-compose`/інфраструктури.
3. Залишити redirect map тільки на нові роути v2.

## Cleanup redirect map
1. Видалити правила для `index.php`, `item.php`, `categories.php`, `user.php`, що вказують на legacy.
2. Залишити лише канонічні маршрути v2 та SEO-safe 301 redirect на нові шляхи.
3. Перевірити відсутність redirect loop через smoke (`curl -I` для топ-URL).

## Оновлення документації та онбордингу
- README і runbook описують тільки v2 стек.
- Для команди додати onboarding: запуск локально через `infra/docker-compose.yml`, запуск тестів і перевірка smoke-чекліста.
