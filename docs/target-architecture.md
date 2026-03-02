# Цільова архітектура

## API контракти (критичні)

- `POST /api/v1/auth/login`
- `GET /api/v1/users/me`
- `GET /api/v1/users`
- `POST /api/v1/users`

Формат: JSON over REST, помилки у форматі:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "..."
  }
}
```

## Шари backend

`handler -> service -> repository -> db`

- `handler`: валідація/мапінг HTTP.
- `service`: бізнес-логіка.
- `repository`: доступ до даних.
- `db`: GORM та підключення.

## Логування

- Бібліотека: Logrus.
- Формат: JSON.
- Рівні: `debug`, `info`, `warn`, `error`.
- `request_id` додається в Gin middleware та прокидується у лог.

## Доступ до віддаленої БД

- env-конфіг через `.env`.
- Параметри: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSLMODE`.
- Пул з'єднань: `max_open_conns`, `max_idle_conns`, `conn_max_lifetime`.
- Таймаути: `connect_timeout` у DSN.

## Frontend state management

- React hooks + локальний state.
- API client в `services/api/client.ts`.
- Централізована обробка помилок через `ApiError`.
- Route guards для ролей/авторизації.
