# План міграції `vseporuch-old` з PHP на Golang + React

> Формат відміток: `[ ]` — не виконано, `[~]` — в процесі, `[x]` — виконано.

## 0) Організація репозиторію та базова структура
- [x] Створити та зафіксувати цільову структуру директорій:
  - [x] `frontend/` — React застосунок
  - [x] `backend/` — Golang API (Gin)
  - [x] `docs/` — технічна документація, ADR, міграційні нотатки
  - [x] `infra/` — docker-compose, nginx, CI/CD шаблони
- [x] Перевірити, що існуючий фронтенд знаходиться в `frontend/` та оновити структуру за потреби.
- [x] Ініціалізувати `backend/` як окремий Go-модуль (`go mod init ...`).
- [x] Додати кореневий `README.md` з описом нової архітектури (frontend + backend + old legacy).

## 1) Аудит legacy-коду (`vseporuch-old`)
- [x] Провести інвентаризацію PHP-ендпоінтів і сторінок:
  - [x] Публічні сторінки (`index.php`, `item.php`, `categories.php`, `user.php`, тощо)
  - [x] API (`api/categories.php`, `api/listings.php`)
  - [x] Кабінет користувача (`account/*`, `dashboard/*`)
  - [x] Адмін-функціонал (`admin/*`)
- [x] Виписати всі бізнес-сценарії (перегляд оголошень, авторизація, реєстрація, модерація, додавання/редагування оголошень, завантаження фото).
- [x] Задокументувати залежності від БД (таблиці, зв’язки, індекси, тригери, критичні SQL-запити).
- [x] Задокументувати файлові сховища (`uploads/`, іконки, аватари) та правила доступу.

## 2) Проєктування цільової архітектури (Golang + React)
- [x] Визначити архітектурний стиль backend (наприклад: `handler -> service -> repository`).
- [x] Затвердити стек backend:
  - [x] Gin — HTTP роутинг та middleware
  - [x] GORM — ORM для роботи з БД
  - [x] Logrus — логування
  - [x] Конфігурація через env (`.env`, `config` пакет)
- [x] Визначити формат API (REST + JSON), версіонування (`/api/v1`).
- [x] Підготувати OpenAPI/Swagger специфікацію майбутніх ендпоінтів.
- [x] Визначити підхід до авторизації (JWT/сесії) і ролей (user/admin/moderator).

## 3) Робота з БД та віддаленим підключенням
- [x] Налаштувати безпечне віддалене підключення backend до БД:
  - [x] `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_SSLMODE`
  - [x] Таймаути, retries, connection pool
  - [x] Ротація секретів/зберігання в CI secrets або vault
- [x] Описати схему БД у вигляді GORM-моделей.
- [x] Підготувати SQL/GORM міграції (інструмент: goose/golang-migrate або інший узгоджений).
- [x] Реалізувати health-check ендпоінти (`/health`, `/ready`) з перевіркою доступності БД.

## 4) Bootstrap backend (`backend/`)
- [x] Ініціалізувати каркас API на Gin.
- [x] Додати middleware:
  - [x] CORS
  - [x] Request ID
  - [x] Recovery
  - [x] Access logging (Logrus)
- [x] Додати централізовану обробку помилок і єдиний формат відповіді API.
- [x] Розбити код на модулі: auth, users, listings, categories, admin, uploads.
- [x] Реалізувати базові unit/integration тести backend-модулів.

## 5) Реалізація функціоналу backend по доменах
- [x] Auth/Account:
  - [x] Реєстрація
  - [x] Логін/логаут
  - [x] Відновлення паролю
  - [x] Профіль користувача
- [x] Listings:
  - [x] Створення оголошення
  - [x] Редагування/видалення
  - [x] Пошук/фільтрація/пагінація
  - [x] Детальна сторінка оголошення
- [x] Categories:
  - [x] CRUD категорій
  - [x] Іконки/вкладені категорії (якщо є в legacy)
- [x] Uploads:
  - [x] Завантаження фото
  - [x] Валідація типу/розміру
  - [x] Безпечне зберігання та доступ
- [x] Admin:
  - [x] Модерація оголошень
  - [x] Підтвердження/блокування користувачів
  - [x] Дії над категоріями/контентом

## 6) Frontend міграція (`frontend/`, React)
- [x] Підтвердити/оновити React-архітектуру:
  - [x] `src/pages`, `src/components`, `src/features`, `src/shared`
  - [x] роутинг (React Router)
  - [x] API-клієнт (axios/fetch wrapper)
- [x] Перенести сторінки з PHP-шаблонів у React-компоненти:
  - [x] Home
  - [x] Categories
  - [x] Item details
  - [x] User profile
  - [x] Dashboard
  - [x] Admin panel
- [x] Реалізувати керування станом (Context/Zustand/Redux — обрати й зафіксувати).
- [x] Інтегрувати frontend з новим Go API (без прямих звернень до PHP).
- [x] Налаштувати обробку помилок, skeleton/loading стани та базову доступність (a11y).

## 7) Паралельний запуск і поетапний cutover
- [x] Запустити legacy і новий стек паралельно (feature flags/поетапне перемикання роутів).
- [x] Реалізувати сумісність URL (redirect map) для критичних сторінок.
- [x] Провести smoke/UAT тестування на staging.
- [x] Виконати поетапний продакшн cutover (спочатку read-only флоу, потім write-флоу).
- [x] Підготувати rollback-план на випадок регресій.

## 8) Якість, безпека, observability
- [x] Додати backend-логи через Logrus з єдиним форматом (json/plain) і рівнями логування.
- [x] Додати аудит-критичні логи (auth/admin/actions).
- [x] Налаштувати метрики та моніторинг (Prometheus/Grafana або еквівалент).
- [x] Провести security hardening:
  - [x] валідація input
  - [x] захист від SQL injection/XSS/CSRF (де релевантно)
  - [x] rate limiting для auth/API
- [x] Додати e2e сценарії для ключових бізнес-флоу.

## 9) CI/CD та експлуатація
- [x] Налаштувати CI для `frontend/` і `backend/`:
  - [x] lint
  - [x] test
  - [x] build
- [x] Додати Dockerfile для frontend/backend та docker-compose для локального запуску.
- [x] Налаштувати CD pipeline для staging/production.
- [x] Описати runbook: деплой, міграції БД, відновлення після збоїв.

## 10) Декомісія legacy
- [x] Зафіксувати критерії повного відключення `vseporuch-old`.
- [x] Архівувати legacy-код та статичні ресурси.
- [x] Вимкнути legacy-ендпоінти після контрольного періоду.
- [x] Оновити документацію та онбординг для команди.

## Рекомендовані віхи (milestones)
- [x] M1: Skeleton backend + підключення до віддаленої БД + базовий React routing.
- [x] M2: Auth + listings read-flow end-to-end.
- [x] M3: Listings write-flow + uploads + dashboard.
- [x] M4: Admin-модуль + безпека + observability.
- [x] M5: Production cutover + декомісія legacy.
