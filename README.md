# vse_poruch_v2p0

Монорепозиторій для поетапної міграції legacy-системи `vseporuch-old` на стек **Golang (Gin + GORM + Logrus)** + **React (Vite)**.

## Структура

- `backend/` — API на Go.
- `frontend/` — клієнт на React.
- `vseporuch-old/` — тимчасовий legacy-референс (placeholder).
- `docs/` — артефакти міграції: аудит, архітектура, хвилі міграції, реліз/rollback, чеклісти.

## Швидкий старт

### Backend

```bash
cd backend
cp .env.example .env
go mod tidy
go run ./cmd/api
```

Сервіс стартує на `http://localhost:8080`.
Healthcheck: `GET /health`.

### Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Застосунок стартує на `http://localhost:5173`.

## Тести

### Backend

```bash
cd backend
go test ./...
```

### Frontend

```bash
cd frontend
npm test
```

## Примітка щодо legacy

Папка `vseporuch-old/` наразі містить технічний placeholder для зберігання legacy-коду під час міграції.
