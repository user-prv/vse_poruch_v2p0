# Міграції БД

Інструмент: `golang-migrate` (узгоджено).

Початковий план:
1. Створити таблиці users/categories/listings/listing_photos.
2. Додати індекси по status/category_id/created_at.
3. Додати FK constraints.

Секрети БД мають надходити через CI secrets/Vault, а не з репозиторію.
