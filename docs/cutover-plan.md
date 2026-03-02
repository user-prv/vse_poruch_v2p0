# Cutover і rollback

## Паралельний запуск
- Legacy і новий стек працюють паралельно.
- Критичні read-flow переводяться першими.
- Redirect map для ключових URL підтримується на nginx.

## Rollback
- Feature flags для повернення на legacy роутинг.
- Перед кожним етапом: backup БД + smoke tests.
- При регресії: rollback deployment + disable нові write-flow.
