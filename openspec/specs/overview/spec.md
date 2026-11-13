# Project Overview Specification

## Purpose
Обзор системы `cln-arch-sym-simple`: акторы и модель доступа, сквозные инварианты контракта, карта доменов и схема данных. Детальное поведение каждого домена — в его спеке (см. ниже).

## Requirements

### Requirement: Модель акторов
The API SHALL предоставлять доступ: аноним — только `/api/auth/*` (регистрация, логин, refresh, logout); аутентифицированный пользователь (`ROLE_USER`, JWT) — все остальные `/api/*`; worker обрабатывает `async` и `scheduler_default`; CLI — консольные команды `app:*`. Отдельных ролей и степеней администратора не существует.

#### Scenario: Поведение акторов
- **GIVEN** разные типы запросов (HTTP, worker, CLI)
- **WHEN** каждый обращается к продукту
- **THEN** HTTP — JWT-гейт вне авторизации; worker читает из очередей; CLI выполняет `app:*`

### Requirement: Сквозной контракт ответов
The API SHALL для всех ответов соблюдать: JSON; ошибки приложения — `{error, codeId}` (см. [platform](../platform/spec.md)); идентификаторы — UUID v7 (`BINARY(16)` в БД, строка в JSON); даты — `DateTimeInterface::ATOM`; денежные значения — `NUMERIC(10,2)`, неотрицательные, ≤2 знаков.

#### Scenario: Единообразие ответов
- **GIVEN** любой домен API
- **WHEN** клиент читает ответ
- **THEN** при успехе — JSON с UUID-строками и датами ATOM; при ошибке — `{error, codeId}`

### Requirement: Постоянство и персистентность
The API SHALL писать в БД через репозитории (Port → Doctrine) и часть операций — в транзакциях; полные переборы больших коллекций — батчами по 1000 (без `listAll()`); async-события доставляются через RabbitMQ (`in-memory://` в тестах); файлы хранятся вне `public/` и доступны только владельцу через API.

#### Scenario: Инварианты записи
- **GIVEN** любая операция записи приложения
- **WHEN** выполняется
- **THEN** доступ к данным — через Port; большие объёмы обрабатываются батчами; файлы не отдаются напрямую из storage

## Notes

- Карта доменов: [auth](../auth/spec.md) (регистрация, логин, refresh, logout), [users](../users/spec.md) (профиль, поиск, аватар), [tasks](../tasks/spec.md) (задачи, подзадачи, статусы, участники), [task-groups](../task-groups/spec.md) (группы, пересчёт статусов), [assets](../assets/spec.md) (ассеты, изображения, привязка), [files](../files/spec.md) (загрузка, скачивание, временные файлы), [notifications](../notifications/spec.md) (настройки и отправка), [platform](../platform/spec.md) (JWT, ошибки, query, async, кэш, batch).
- Домен `users` разложен по use-case'ам на подразделы: [list](../users/list/spec.md), [get](../users/get/spec.md), [query](../users/query/spec.md), [query-meta](../users/query-meta/spec.md), [update](../users/update/spec.md), [delete](../users/delete/spec.md).
- Схема данных (обзор): `users` ──< `assets` / `files` / `task_groups` / `user_notification_preferences` / `refresh_tokens` (явное удаление при удалении пользователя); `users` >──< `tasks` (`task_users`, unique task_id+user_id); `users` >── `files` (avatar, `ON DELETE SET NULL`); `tasks` ──< `tasks` (parent, `SET NULL`); `tasks` >──< `assets` (`asset_task`, CASCADE) и >──< `task_groups` (`task_group_task`, CASCADE); `assets` >──< `files` (`asset_file`, position). Полные колонки — миграция `api/migrations/Version20260930100000.php`.
- Глоссарий: Task (задача, может иметь `parentId` — подзадача); Task user (участник `task_users`, свой статус); Task group (статус вычисляется по составу); Asset (объект с ценой и типом, связан с задачами и файлами); File (`Temporary`/`Permanent`); Preference (настройка уведомлений по типу); Transactional type (обязательное уведомление); Debounce lock (кэш-lock от дублей пересчёта).
- Асинхронность (обзор): `TaskCreated` — только лог; `TaskGroupsRecalculationRequestedEvent` — пересчёт групп; `UserRegisteredEvent` — email; `UserUpdatedEvent` — email/SMS по настройке.
- Известные расхождения с целевой архитектурой — в Notes соответствующих спек.

## Источник истины и архитектура

- Спеки в `openspec/specs/` описывают **фактическое поведение** кода (`api/src`)
  и тестов (`api/tests`) — это источник истины о текущем поведении системы.
- `api/CLAUDE.md` (+ `api/src/Application/CLAUDE.md`,
  `api/src/Infrastructure/CLAUDE.md`, `api/tests/CLAUDE.md`) описывает
  **текущие архитектурные соглашения**: слои, именование, вход/выход use case'ов.
  Он используется при проектировании изменений, а не как описание поведения.
- Changes в `openspec/changes/` описывают будущие изменения (дельты
  ADDED/MODIFIED/REMOVED) по процессу OpenSpec: proposal → specs → design → tasks.
- Расхождения фактического поведения с задокументированным контрактом
  фиксируются в `## Notes` спек и при необходимости становятся отдельными changes.
