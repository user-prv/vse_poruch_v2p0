# Аудит legacy (`vseporuch-old`)

## Публічні сторінки
- `index.php` — головна
- `item.php` — перегляд оголошення
- `categories.php` — категорії
- `user.php` — профіль користувача

## API
- `api/categories.php`
- `api/listings.php`

## Кабінет користувача
- `account/index.php`, `account/login.php`, `account/register.php`, `account/logout.php`
- `dashboard/index.php`, `dashboard/profile.php`, `dashboard/listing_add.php`, `dashboard/listing_edit.php`, `dashboard/listing_delete.php`, `dashboard/photo_delete.php`

## Адмін
- `admin/index.php`, `admin/users.php`, `admin/categories.php`, `admin/listings.php`, `admin/verification.php`
- `admin/*_action.php`, `admin/_guard.php`

## Бізнес-сценарії
- Перегляд/пошук оголошень
- Реєстрація/логін/логаут/скидання пароля
- Створення/редагування/видалення оголошень
- Завантаження фото й аватарів
- Адмін-модерація оголошень/користувачів/категорій

## Залежності від БД

### Таблиці та зв’язки
- `users` (PK `id`) — акаунт, профіль, роль/адмінські прапорці, стани блокування.
- `listings` (PK `id`, FK `user_id -> users.id`, FK `category_id -> categories.id`) — оголошення, статуси (`active`, `blocked`, `deleted`, `pending`), поля модерації.
- `categories` (PK `id`, self-FK `parent_id -> categories.id`) — дерево категорій, `icon_path`, `sort_order`, `is_active`.
- `listing_photos` (PK `id`, FK `listing_id -> listings.id`) — фото з `sort_order`.
- `password_resets` (PK `id`, FK `user_id -> users.id`) — токени відновлення пароля з `expires_at` та `used_at`.

### Критичні запити (legacy)
- Вибірка оголошень із `JOIN users` для фільтрації заблокованих власників (`api/listings.php`).
- Детальна картка оголошення + фото (`item.php`, `listing_photos`).
- Адмін-модерація масово змінює `listings.status`/`moderation_status` (`admin/listings_action.php`, `admin/verification_action.php`).
- Профіль/кабінет користувача читає/оновлює `users` та авторські `listings` (`dashboard/profile.php`, `dashboard/listing_edit.php`).

### Рекомендовані індекси для міграції
- `users(email)` unique.
- `listings(status, moderation_status)`.
- `listings(category_id)`, `listings(user_id)`.
- `listing_photos(listing_id, sort_order)`.
- `password_resets(token)` unique, `password_resets(expires_at)`.

## Файлові сховища
- `vseporuch-old/uploads/` — фото оголошень, аватари, іконки категорій.
- Доступ у legacy реалізований через прямий static file serving.

## Матриця відповідності legacy → v2

| Legacy endpoint / сторінка | Новий route / API / component | Статус | Коментар |
|---|---|---|---|
| `index.php` | Route `/` → `HomePage`, API `GET /api/v1/listings` | ✅ Закрито | Пошук + пагінація реалізовані через `q`, `page`, `limit`. |
| `item.php` | Route `/item/:id` → `ItemDetailsPage`, API `GET /api/v1/listings/{id}` | ✅ Закрито | Деталі оголошення + `photo_paths`. |
| `categories.php` | Route `/categories` → `CategoriesPage`, API `GET /api/v1/categories` | ✅ Закрито | Відображається список категорій. |
| `user.php` | Route `/user/:id` → `UserProfilePage`, API `GET /api/v1/profile/{id}` | ✅ Закрито | Базовий профіль користувача. |
| `api/listings.php` | API `GET /api/v1/listings`, `POST /api/v1/listings`, `PUT /api/v1/listings/{id}`, `DELETE /api/v1/listings/{id}` | ✅ Закрито | CRUD + фільтри `status`, `author_id`, `q`. |
| `api/categories.php` | API `GET/POST/PUT/DELETE /api/v1/categories` | ✅ Закрито | CRUD категорій доступний через REST. |
| `account/login.php` | Route `/login` → `LoginPage`, API `POST /api/v1/auth/login` | ✅ Закрито | Логін по email. |
| `account/register.php` | Route `/register` → `RegisterPage`, API `POST /api/v1/auth/register` | ✅ Закрито | Реєстрація по email. |
| `account/logout.php` | UI action logout + API `POST /api/v1/auth/logout` | ✅ Закрито | Logout викликається з header/App shell. |
| `account/index.php` | Route guard + `/dashboard` | 🟡 Частково | Є вхід у кабінет, але без окремої landing-сторінки акаунта. |
| `dashboard/profile.php` | Route `/dashboard` → `DashboardPage`, API `GET /api/v1/profile/{id}` | 🟡 Частково | Немає `PUT /profile` для редагування профілю. |
| `dashboard/listing_add.php` | UI create form у `DashboardPage`, API `POST /api/v1/listings` | ✅ Закрито | Створення оголошення працює. |
| `dashboard/listing_edit.php` | UI edit form у `DashboardPage`, API `PUT /api/v1/listings/{id}` | ✅ Закрито | Редагування оголошення працює. |
| `dashboard/listing_delete.php` | UI delete action у `DashboardPage`, API `DELETE /api/v1/listings/{id}` | ✅ Закрито | Soft delete (status=`deleted`). |
| `dashboard/photo_delete.php` | API `POST /api/v1/uploads/photo` | 🔴 Прогалина | Є upload, але немає endpoint для видалення/пересортування фото. |
| `account/reset_password.php` (legacy flow) | Route `/reset-password` → `ResetPasswordPage`, API `POST /api/v1/auth/reset-password` | 🟡 Частково | Лише stub без token-based підтвердження. |
| `admin/index.php` | Route `/admin` → `AdminPage` | ✅ Закрито | Є окремий екран адміністратора. |
| `admin/listings.php` + `admin/listings_action.php` | API `POST /api/v1/admin/listings/{id}/moderate` | ✅ Закрито | Модерація статусу оголошень. |
| `admin/users.php` + `admin/verification.php` | API `POST /api/v1/admin/users/{id}/verify`, `POST /api/v1/admin/users/{id}/block` | 🟡 Частково | Немає персистентних полів `verified/blocked` у `users`. |
| `admin/categories.php` + `admin/*_action.php` | API `POST /api/v1/admin/categories/{id}/icon` + CRUD `/api/v1/categories` | 🟡 Частково | Іконка підтверджується відповіддю, але не оновлює `icon_path` у БД. |

## Backlog задач для прогалин

### Backend задачі
- [ ] **Профіль користувача (редагування):** додати `PUT /api/v1/profile/{id}` з валідаціями (email format, нормалізація, immutable role) і аудит-логом.
- [ ] **Reset password (повний flow):** додати `POST /api/v1/auth/reset-password/request` + `POST /api/v1/auth/reset-password/confirm` із токеном, TTL, одноразовим використанням.
- [ ] **Фото оголошень:** додати `DELETE /api/v1/listings/{id}/photos/{photoId}` і `PATCH /api/v1/listings/{id}/photos/reorder`.
- [ ] **Admin user state:** додати поля `users.is_verified`, `users.is_blocked`, `users.block_reason`, `users.blocked_at`; оновити `VerifyUser/BlockUser` для збереження стану.
- [ ] **Admin category icon:** `POST /api/v1/admin/categories/{id}/icon` має зберігати `categories.icon_path`.
- [ ] **DB індекси:** додати композитні індекси `listings(status, category_id)`, `listings(author_id, status)`, `password_resets(token)` unique + `password_resets(expires_at)`.
- [ ] **Валідації listing payload:** валідувати існування `author_id/category_id`, обмеження довжини `title/body`, мінімум 1 фото для `active`.

### Frontend задачі
- [ ] **Account landing:** додати окремий route `/account` з навігацією в `/dashboard`, `/profile`, `/security`.
- [ ] **Edit profile screen:** форма редагування профілю (email, display fields) + optimistic/error states.
- [ ] **Reset password UX:** 2-кроковий flow (request email → confirm by token) з deep-link `reset-password?token=...`.
- [ ] **Photo manager у dashboard:** список фото з видаленням/drag-and-drop reorder та синхронізацією з новими API.
- [ ] **Admin users table:** відобразити verified/blocked стани, причину блокування, action buttons.
- [ ] **Admin categories icon flow:** UI для upload/preview іконки категорії з persist status.

## Smoke checklist (критичні user-flows для закритих пунктів)

### Public + Listings
- [ ] Відкрити `/` → отримати список оголошень, фільтр `q` змінює результат.
- [ ] Відкрити `/item/:id` з існуючим `id` → бачити title/body/status/photo_paths.
- [ ] На `/dashboard` створити оголошення → воно з’являється у `GET /api/v1/listings`.
- [ ] На `/dashboard` відредагувати оголошення → зміни відображаються у `/item/:id`.
- [ ] Видалити оголошення → API повертає `deleted=true`, статус стає `deleted`.

### Categories
- [ ] Відкрити `/categories` → список завантажився без помилки.
- [ ] Створити категорію (admin/user з правами в поточній реалізації) → новий запис видно в `GET /api/v1/categories`.
- [ ] Оновити категорію (`PUT`) → нові `name/parent_id/icon_path` збережені.

### Auth + Account
- [ ] Зареєструвати email через `/register` → отримати `201` і `id`.
- [ ] Логін через `/login` → отримати token і доступ до `/dashboard` через `RouteGuard`.
- [ ] Logout з header → сесія очищена, редірект на `/login`.
- [ ] Відкрити `/user/:id` → профіль користувача успішно повертається API.

### Admin
- [ ] Відкрити `/admin` під admin-роллю → сторінка доступна; не-admin заблокований guard-ом.
- [ ] Виконати moderation (`POST /admin/listings/{id}/moderate`) → статус оголошення змінено.
- [ ] Виконати verify/block користувача (`POST /admin/users/{id}/verify|block`) → API повертає `verified/blocked=true`.
