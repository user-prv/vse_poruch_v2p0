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
