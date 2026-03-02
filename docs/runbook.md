# Runbook

## Deploy
1. Прогнати CI (`lint`, `test`, `build`).
2. Перевірити актуальний `CUTOVER_MODE` та change ticket на реліз.
3. Створити pre-deploy backup БД і перевірити, що останній restore dry-run успішний.
4. Накатити міграції БД (лише після backup).
5. Розгорнути backend, потім frontend, потім gateway.
6. Перевірити `/health`, `/ready`, `/metrics` і базовий smoke read/write.

## Rollback runbook (production)

### Тригери rollback
- 5xx rate перевищує gate-поріг довше 10 хв.
- Критичний бізнес-флоу (auth, create listing, moderation, uploads) недоступний.
- `p95` latency перевищує поріг у 2 рази довше 15 хв.
- Виявлено ризик втрати/пошкодження даних.

### Крок 1. Перемикання mode
1. Зафіксувати час інциденту й версію релізу в incident timeline.
2. Встановити `CUTOVER_MODE=legacy` у конфігурації gateway.
3. Перезавантажити/перечитати конфіг сервісу (наприклад, `docker compose up -d gateway`).
4. Перевірити доступність legacy-маршрутів та ключових сторінок (`/`, `/item.php`, `/categories.php`, `/user.php`).

### Крок 2. Rollback релізу
1. Визначити останній стабільний tag/image (`PREV_STABLE_TAG`).
2. Викотити backend/frontend до `PREV_STABLE_TAG`.
3. Переконатися, що нові інстанси healthy, а старі зняті з балансування.
4. Повторити smoke для read/write-flow у legacy mode.

### Крок 3. Rollback міграцій
> Виконувати тільки якщо інцидент пов'язаний зі схемою/даними.

1. Перевести застосунок у maintenance (або freeze write-операцій).
2. Запустити `down` міграції до попередньої стабільної ревізії.
3. Валідувати схему: наявність очікуваних таблиць/індексів, цілісність FK.
4. Увімкнути write-операції після успішної валідації.

### Крок 4. Відновлення backup
> Виконувати, якщо rollback релізу/міграцій не відновив консистентність даних.

1. Визначити backup point-in-time з RPO ≤ 5 хв до інциденту.
2. Відновити БД у новий інстанс та перевірити контрольні запити.
3. Перемкнути застосунок на відновлений інстанс БД.
4. Провести data sanity-check: кількість користувачів, оголошень, останні транзакції, upload metadata.
5. Задокументувати фактичні RTO/RPO у postmortem.

### Крок 5. Завершення інциденту
1. Підтвердити стабільність 30 хв без деградації.
2. Закрити інцидент, додати RCA і action items.
3. Оновити gate-критерії/алерти, якщо були виявлені прогалини.

## Recovery (non-cutover інциденти)
- Перевірити доступність БД і health endpoints.
- У разі проблем БД: rollback міграцій, за потреби відновлення з backup.
- У разі функціональних регресій: встановити `CUTOVER_MODE=legacy` і виконати rollback релізу за процедурою вище.

## Observability
- Логи через Logrus, формат задається `LOG_FORMAT=json|plain`, рівень `LOG_LEVEL`.
- Access log + audit події (`auth.*`, `admin.*`).
- Метрики Prometheus доступні на `/metrics`.

## Security
- Валідація вхідних даних через Gin binding.
- Escape user-input перед збереженням (базовий захист від XSS).
- Rate limiting для `auth` і API.
- SQL-запити параметризовані через GORM (захист від SQL injection).
