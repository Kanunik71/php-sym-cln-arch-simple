# OpenSpec — документация спецификаций

Индекс документации проекта `cln-arch-sym-simple`. Спецификации поведения
системы живут в [`specs/`](specs/), изменения — в [`changes/`](changes/),
правила генерации артефактов — в [`config.yaml`](config.yaml).

Этот файл — навигация и справочные карты, а не спека: он не описывает
поведение API и не содержит требований.

## Карта доменов

| Домен | Что внутри | Подразделы (use-case'ы) |
|--------|------------|--------------------------|
| [auth](specs/auth/spec.md) | регистрация, логин, refresh, logout | [register](specs/auth/register/spec.md), [login](specs/auth/login/spec.md), [refresh](specs/auth/refresh/spec.md), [logout](specs/auth/logout/spec.md), [cleanup](specs/auth/cleanup/spec.md) |
| [users](specs/users/spec.md) | профиль, поиск, аватар | [list](specs/users/list/spec.md), [get](specs/users/get/spec.md), [query](specs/users/query/spec.md), [query-meta](specs/users/query-meta/spec.md), [update](specs/users/update/spec.md), [delete](specs/users/delete/spec.md) |
| [tasks](specs/tasks/spec.md) | задачи, подзадачи, статусы, участники | [create](specs/tasks/create/spec.md), [status](specs/tasks/status/spec.md), [access](specs/tasks/access/spec.md), [list](specs/tasks/list/spec.md), [delete](specs/tasks/delete/spec.md), [recalculation](specs/tasks/recalculation/spec.md) |
| [task-groups](specs/task-groups/spec.md) | группы задач, пересчёт статусов | [create](specs/task-groups/create/spec.md), [status](specs/task-groups/status/spec.md), [bind](specs/task-groups/bind/spec.md), [access](specs/task-groups/access/spec.md), [summary](specs/task-groups/summary/spec.md), [delete](specs/task-groups/delete/spec.md), [recalculate](specs/task-groups/recalculate/spec.md) |
| [assets](specs/assets/spec.md) | ассеты, изображения, привязка к задачам | [create](specs/assets/create/spec.md), [images](specs/assets/images/spec.md), [progress](specs/assets/progress/spec.md), [view](specs/assets/view/spec.md), [binding](specs/assets/binding/spec.md), [update](specs/assets/update/spec.md) |
| [files](specs/files/spec.md) | загрузка, скачивание, временные файлы | [upload](specs/files/upload/spec.md), [promote](specs/files/promote/spec.md), [metadata](specs/files/metadata/spec.md), [download](specs/files/download/spec.md), [cleanup](specs/files/cleanup/spec.md) |
| [notifications](specs/notifications/spec.md) | настройки и отправка | [preferences](specs/notifications/preferences/spec.md), [delivery](specs/notifications/delivery/spec.md) |
| [platform](specs/platform/spec.md) | JWT, ошибки, query, async, кэш, batch | — (сквозной, single-file) |

## Схема данных (обзор)

`users` ──< `assets` / `files` / `task_groups` / `user_notification_preferences` /
`refresh_tokens` (явное удаление при удалении пользователя);
`users` >──< `tasks` (`task_users`, unique `task_id`+`user_id`);
`users` >── `files` (avatar, `ON DELETE SET NULL`);
`tasks` ──< `tasks` (parent, `SET NULL`);
`tasks` >──< `assets` (`asset_task`, CASCADE) и >──< `task_groups` (`task_group_task`, CASCADE);
`assets` >──< `files` (`asset_file`, position).

Перечень таблиц, FK-политики и `codeId` whitelist — в
[`platform/spec.md`](specs/platform/spec.md#notes). Полные колонки —
миграция `api/migrations/Version20260930100000.php`.

## Глоссарий

- **Task** — задача, может иметь `parentId` (подзадача).
- **Task user** — участник `task_users` со своим статусом.
- **Task group** — статус вычисляется по составу группы.
- **Asset** — объект с ценой и типом, связан с задачами и файлами.
- **File** — `Temporary` / `Permanent`.
- **Preference** — настройка уведомлений по типу.
- **Transactional type** — обязательное уведомление.
- **Debounce lock** — кэш-lock от дублей пересчёта.

## Асинхронность (обзор)

- `TaskCreatedEvent` — только лог.
- `TaskGroupsRecalculationRequestedEvent` — пересчёт групп.
- `UserRegisteredEvent` — email.
- `UserUpdatedEvent` — email/SMS по настройке.

Детали реализации поведения — в `## Notes` соответствующих спек;
непокрытые тестами ветви — в секции `## Покрытие тестами`.

## Источник истины и архитектура

- Спеки в `openspec/specs/` описывают **фактическое поведение** кода (`api/src`)
  и тестов (`api/tests`) — это источник истины о текущем поведении системы.
- `api/CLAUDE.md` (+ `api/src/Application/CLAUDE.md`,
  `api/src/Infrastructure/CLAUDE.md`, `api/tests/CLAUDE.md`) описывают
  **текущие архитектурные соглашения**: слои, именование, вход/выход use case'ов.
  Они используются при проектировании изменений, а не как описание поведения.
- Changes в `openspec/changes/` описывают будущие изменения (дельты
  ADDED/MODIFIED/REMOVED) по процессу OpenSpec: proposal → specs → design → tasks.
- Расхождения фактического поведения с задокументированным контрактом
  фиксируются в `## Notes` спек и при необходимости становятся отдельными changes.
- Этот README — индекс и справочные карты (домены, схема, глоссарий);
  он не является capability-спекой и не участвует в процессе дельт.
