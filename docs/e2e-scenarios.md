# E2E сценарії ключових бізнес-флоу

## 1) Гостьовий read-flow
1. Відкрити `/`.
2. Перейти в `/categories`.
3. Відкрити деталі оголошення `/items/:id`.

## 2) Користувацький write-flow
1. Реєстрація `/api/v1/auth/register`.
2. Логін `/api/v1/auth/login`.
3. Створення оголошення `/api/v1/listings`.
4. Завантаження фото `/api/v1/uploads/photo`.

## 3) Адмін-flow
1. Модерація оголошення `/api/v1/admin/listings/:id/moderate`.
2. Верифікація користувача `/api/v1/admin/users/:id/verify`.
3. Блокування користувача `/api/v1/admin/users/:id/block`.

## 4) Негативні перевірки
- Невалідний payload -> 400.
- Перевищення rate-limit -> 429.
- DB outage -> `/ready` повертає 503.
