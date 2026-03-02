# Cutover і rollback

## Модель переходу
- Legacy (`vseporuch-old`) і новий стек (`frontend` + `backend`) працюють паралельно через nginx.
- Для поетапного перемикання використовується `CUTOVER_MODE`:
  - `legacy` — всі запити обслуговує legacy.
  - `hybrid` — read-flow йде в v2, write-flow лишається в legacy.
  - `v2` — весь трафік обслуговує v2, legacy доступний тільки як аварійний fallback.
- Redirect map підтримує сумісність URL для `index.php`, `item.php`, `categories.php`, `user.php`.

## Backlog переходу `legacy -> hybrid -> v2`
| Етап | Scope | Owner | ETA | Основні ризики | Мітигація |
|---|---|---|---|---|---|
| `CUTOVER_MODE=legacy` (підготовка) | Freeze legacy-змін, smoke+UAT на staging, перевірка backup/restore, dry-run rollback, валідація redirect map | Release manager + QA lead + SRE on duty | T0 + 3 дні | Неповний UAT, невалідний backup, дрейф конфігів між staging/prod | Обов'язковий чекліст підпису, тестове відновлення backup, diff env-конфігів перед релізом |
| `CUTOVER_MODE=hybrid` | Перемикання read-flow на v2, моніторинг помилок/latency, щоденний triage інцидентів, контроль даних між legacy/v2 | Tech lead backend + Frontend lead + SRE on duty | T0 + 7 днів (стабілізація 72 год) | Розходження даних, деградація latency, приховані edge-case у читанні | Канарейка 10%→50%→100%, алерти на SLI, звірка топ-категорій/карток оголошень кожні 4 год |
| `CUTOVER_MODE=v2` | Перемикання write-flow, повний трафік на v2, підвищений on-call, readiness до миттєвого rollback | Engineering manager + Backend lead + DBA + SRE | T0 + 14 днів (стабілізація 14 днів) | Помилки запису, несумісність міграцій, пікове навантаження | Blue/green реліз, обов'язковий pre-deploy backup, feature freeze на 48 год після cutover |

## Gate-критерії для переходів

### Gate: `legacy` → `hybrid`
- **Smoke/UAT:**
  - Успішні smoke на `/health`, `/ready`, `/metrics`.
  - UAT read-flow (home → categories → item details) виконаний на staging і production canary без blocker/critical.
- **Помилки:**
  - 5xx rate backend `< 1%` за останні 60 хвилин.
  - Frontend JS error rate `< 2%` сесій.
- **Latency:**
  - `p95` для read API `< 450ms`, `p99 < 900ms`.
- **Rollback readiness:**
  - Перевірено перемикання `CUTOVER_MODE=legacy` на staging за ≤ 10 хв.
  - Є свіжий backup БД (не старше 30 хв) + підтверджений restore dry-run.

### Gate: `hybrid` → `v2`
- **Smoke/UAT:**
  - Повний smoke read+write-flow (auth, listing create/edit, moderation, uploads) зелений.
  - UAT для ролей user/admin/moderator підписаний QA lead і product owner.
- **Помилки:**
  - 5xx rate `< 0.5%` протягом 24 год.
  - Частка failed write-операцій `< 0.3%`.
- **Latency:**
  - `p95` write API `< 700ms`, `p99 < 1200ms` на піковому навантаженні.
- **Rollback readiness:**
  - Оновлений rollback runbook перевірений drill-ом впродовж останніх 7 днів.
  - Rollback-реліз готовий (попередній образ/tag доступний, міграції мають down-скрипти).

### Gate: стабілізація після `v2`
- **Smoke/UAT:** щоденний smoke у перші 7 днів, далі через день до D+14.
- **Помилки:** blocker/critical = 0 протягом 14 днів; 5xx `< 0.3%`.
- **Latency:** стабільний `p95 < 500ms` для критичних endpoint.
- **Rollback readiness:** до завершення D+14 зберігається «гарячий» rollback-пакет і чергування SRE/DBA.

## Операційні правила під час cutover
1. Перед кожним етапом: backup БД + smoke tests + перевірка dashboard/алертів.
2. Усі зміни `CUTOVER_MODE` виконуються через change ticket і журналюються в incident timeline.
3. При регресії rollback ініціюється без очікування завершення релізного вікна (див. `docs/runbook.md`).
4. Цільові SLO для cutover: **RTO ≤ 15 хв**, **RPO ≤ 5 хв**.
