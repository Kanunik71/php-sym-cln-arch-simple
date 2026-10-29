# Проект: обзор — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | код `api/src`, тесты `api/tests`, миграция `api/migrations/Version20260930100000.php` |

> Описывает фактическое поведение API на дату. Архитектурные расхождения с
> целевым `api/CLAUDE.md` — в [ADR 0001](../../adr/0001-as-is-vs-target-architecture.md)
> и в разделе «Известные расхождения».

## Контекст

`cln-arch-sym-simple` — monorepo с Symfony 7 API (`api/`) и Docker-стеком
(`docker/`). API управляет пользователями, задачами (включая подзадачи),
группами задач, ассетами, файлами и уведомлениями.

Клиент — внешний HTTP-клиент (или CLI) с JWT. Внутренние worker-ы обрабатывают
асинхронные события через RabbitMQ и выполняют периодические задачи.

## Акторы и доступ

| Актор | Доступ |
|-------|--------|
| Аноним | `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/refresh`, `POST /api/auth/logout` |
| Аутентифицированный пользователь (`ROLE_USER`) | все остальные `/api/*` |
| Worker (`messenger-worker`) | `async` + `scheduler_default` |
| CLI | консольные команды `app:*` |

- Аутентификация: JWT (Lexik, HS256, TTL 900), claim `username` = user id.
- Все пользователи имеют только `ROLE_USER`; ролей/поля роли в БД нет.
- `401` формирует Symfony Security; остальные ошибки приложения — через
  `{"error": "...", "codeId": "..."}`.

## Карта доменов

| Домен | Спека | Назначение |
|-------|-------|------------|
| Auth | [0002-auth](../0002-auth/spec.md) | регистрация, логин, refresh-токены, logout |
| Users | [0003-users](../0003-users/spec.md) | профиль, поиск, аватар |
| Tasks | [0004-tasks](../0004-tasks/spec.md) | задачи, подзадачи, статусы, участники |
| Task groups | [0005-task-groups](../0005-task-groups/spec.md) | группы задач, пересчёт статусов |
| Assets | [0006-assets](../0006-assets/spec.md) | ассеты, изображения, привязка к задачам |
| Files | [0007-files](../0007-files/spec.md) | загрузка, скачивание, временные файлы |
| Notifications | [0008-notifications](../0008-notifications/spec.md) | настройки и отправка уведомлений |
| Platform | [0009-platform](../0009-platform/spec.md) | JWT, ошибки, query-механики, async, кэш, batch |

## Глоссарий

| Термин | Значение |
|--------|----------|
| Task | задача; может иметь `parentId` (подзадача) |
| Task user | участник задачи (`task_users`), имеет собственный статус |
| Task group | группа задач; статус вычисляется по задачам |
| Asset | объект с ценой и типом, связан с задачами и файлами |
| File | загруженный файл: `Temporary` или `Permanent` |
| Preference | настройка уведомлений пользователя по типу |
| Transactional type | обязательное уведомление, не настраивается |
| Debounce lock | кратковременный cache-lock, подавляющий дубли пересчёта |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | API принимает и возвращает JSON; ошибки приложения имеют `error` и `codeId` |
| FR-2 | Идентификаторы — UUID v7 в RFC 4122 (строкой в JSON, `BINARY(16)` в БД) |
| FR-3 | Даты в ответах — `DateTimeInterface::ATOM` |
| FR-4 | Доступ к `/api/*` требует JWT, кроме `/api/auth/*` |
| FR-5 | Запись в БД идёт через репозитории; часть операций — в транзакции |
| FR-6 | Асинхронные события уходят в RabbitMQ (`async`), в тестах — `in-memory://` |
| FR-7 | Файлы хранятся вне `public/`, доступ к ним только владельцу |
| FR-8 | Денежные значения — `NUMERIC(10,2)`, неотрицательные, до 2 знаков |
| FR-9 | Полный перебор больших коллекций — через `listBatch` / `BatchUtils` |

## Схема данных (обзор)

Таблицы и ключевые связи:

```text
users ──< assets
users ──< files
users ──< task_groups
users ──< user_notification_preferences
users ──< refresh_tokens       (явное удаление в UserDeleteService)
users >──< tasks            (task_users, уникально task_id+user_id)
users >── files             (users.avatar_file_id → files.id, ON DELETE SET NULL)

tasks ──< tasks             (parent_id → tasks.id, ON DELETE SET NULL)
tasks >──< assets           (asset_task, CASCADE)
tasks >──< task_groups      (task_group_task, CASCADE)

assets >──< files           (asset_file, position)
```

Полные колонки — в миграции
`api/migrations/Version20260930100000.php`; детали по доменам — в
соответствующих спеках.

## Покрытие эндпоинтов

| Спека | HTTP-маршруты |
|-------|---------------|
| 0002-auth | `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/refresh`, `POST /api/auth/logout` |
| 0003-users | `GET /api/users`, `GET /api/users/query`, `GET /api/users/query/meta`, `GET/PUT/DELETE /api/users/{id}` |
| 0004-tasks | `GET/POST /api/tasks`, `GET/DELETE /api/tasks/{id}`, `PATCH /api/tasks/{id}/status` |
| 0005-task-groups | `GET /api/task-groups/summary`, `POST /api/task-groups`, `GET/POST /api/tasks/{taskId}/task-groups`, `POST .../{id}/attach`, `GET/DELETE .../{id}`, `DELETE .../{id}/tasks/{removeTaskId}` |
| 0006-assets | `GET/POST /api/assets`, `GET/PATCH/DELETE /api/assets/{id}`, `GET/POST /api/tasks/{taskId}/assets`, `POST .../{id}/attach`, `DELETE .../{id}` |
| 0007-files | `POST /api/files`, `GET /api/files/{id}`, `GET /api/files/{id}/download` |
| 0008-notifications | `GET/PUT /api/users/me/notification-preferences` |

Консольные команды: `app:files:cleanup-tmp`, `app:task-groups:recalculate`,
`app:auth:cleanup-refresh-tokens`.

## Асинхронность (обзор)

| Событие | Публикует | Обработчик | Эффект |
|---------|-----------|------------|--------|
| `TaskCreated` | создание задачи | `TaskCreatedMessageHandler` | только лог |
| `TaskGroupsRecalculationRequestedEvent` | Task/TaskGroup actions | `RecalculateTaskGroupsMessageHandler` | пересчёт групп, снятие lock |
| `UserRegisteredEvent` | регистрация | `UserRegisteredMessageHandler` | email-уведомление |
| `UserUpdatedEvent` | обновление профиля | `UserUpdatedMessageHandler` | email/SMS по preference |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Слои | нет `api/src/Domain`, нет `Application/Command|Query|Handler` | `api/CLAUDE.md` описывает Domain + Command/Query/Handler | ADR 0001 |
| VO | `ValueObject` в `Application/ValueObject` | `api/CLAUDE.md` — `Domain/` | ADR 0001 |
| Мапперы | `*ViewModelMapper` | `api/CLAUDE.md` — `*ModelMapper` | ADR 0001 |

## Первоисточники

- `api/README.md`, `docker/README.md`, `README.md`
- `api/config/packages/security.yaml`, `messenger.yaml`, `cache.yaml`, `services.yaml`
- `api/migrations/Version20260930100000.php`
- `api/src/Presentation/Controller/**`
- `api/src/Application/**`, `api/src/Infrastructure/**`

## Открытые вопросы

- [ ] Нужно ли заводить change-спеки на исправление найденных расхождений
      (отсутствующий handler)?
- [ ] Нужна ли отдельная baseline-спека по Docker/инфраструктуре?
