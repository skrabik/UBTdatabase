# Схема БД: аккаунты Яндекс.Дзен и посты

## Описание

- **zen_account** — аккаунты (каналы) Яндекс.Дзен. У каждого: название, описание, ссылка на канал, тематика. Аккаунты можно создавать и удалять.
- **zen_post** — посты для постинга в канал. Привязаны к аккаунту. Статусы: черновик (`draft`), ожидает постинга (`pending`), запощено (`posted`). Можно указывать запланированное время и время публикации.

## ER-диаграмма (Mermaid)

```mermaid
erDiagram
    zen_account ||--o{ zen_post : "имеет посты"

    zen_account {
        int id PK
        varchar name "Название"
        text description "Описание"
        varchar url "Ссылка на канал"
        varchar theme "Тематика"
        int created_at
        int updated_at
    }

    zen_post {
        int id PK
        int account_id FK
        varchar title "Заголовок"
        text content "Текст поста"
        varchar status "draft|pending|posted"
        int scheduled_at "Запланировано на"
        int posted_at "Опубликовано в"
        int created_at
        int updated_at
    }
```

## Связи

| Таблица      | Связь           | Описание |
|-------------|-----------------|----------|
| zen_account | 1 → N zen_post  | У одного аккаунта много постов. При удалении аккаунта посты удаляются (CASCADE). |

## Статусы поста (zen_post.status)

| Значение  | Описание            |
|----------|---------------------|
| `draft`  | Черновик            |
| `pending`| Ожидает постинга    |
| `posted` | Запощено            |

## Применение миграции

```bash
php yii migrate
```
