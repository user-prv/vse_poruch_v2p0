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
Ключові сутності (ціль для GORM-моделей):
- users
- listings
- categories
- listing_photos
- moderation/verification пов'язані таблиці (за скриптами `admin/*_action.php`)

## Файлові сховища
- `vseporuch-old/uploads/` — фото оголошень, аватари, іконки категорій
- Доступ у legacy реалізований через прямий static file serving.
