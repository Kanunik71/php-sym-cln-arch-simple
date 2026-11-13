# Platform Specification

## Purpose
Сквозные механизмы API: аутентификация и access-правила, формат ошибок, query-механики, асинхронность и планировщик, кэш, батчирование, хранение файлов и тестовая инфраструктура.

## Requirements

### Requirement: Аутентификация и роли
The API SHALL требовать JWT для всех `/api/*` кроме `/api/auth/*` (firewall `api_auth` — `security: false`; на `/api` — JWT-аутентификация). Все пользователи имеют только `ROLE_USER`; поля роли в БД нет. Worker-ы (`messenger-worker`) и CLI (`app:*`) работают по тем же правилам приложения; отдельных ролей/административного bypass нет.

#### Scenario: Доступ анонима
- **GIVEN** нет JWT
- **WHEN** любой `/api/*` вне `/api/auth/*`
- **THEN** `401`
- **Covers:** `tests/DatabaseWebTestCase` поведение на 401

#### Scenario: Успешный JWT
- **GIVEN** валидный JWT (HS256; claims `username` = userId, `email`; TTL 900 — см. [auth](../auth/spec.md))
- **WHEN** любой защищённый `/api/*`
- **THEN** пользователь загружается по id через `DoctrineUserProvider`; `getCurrentUserId()` возвращает userId

### Requirement: Формат ошибок
The API SHALL возвращать ошибки приложения как JSON `{"error": "<human readable>", "codeId": "<CODE>"}`; `codeId` — из whitelist `ErrorCodeEnum` (см. Notes), а HTTP-статус задаёт код приложения независимо от `codeId`.

#### Scenario: Ошибка домена
- **GIVEN** запрос нарушает бизнес-правило (например, недопустимый переход статуса)
- **WHEN** обработка падает с `CodedException`
- **THEN** ответ `{"error": "...", "codeId": "INVALID_TASK_STATUS_TRANSITION"}`

#### Scenario: Ошибки без собственного кода
- **GIVEN** `401` без собственного `codeId` (нет/битый JWT), необработанное исключение или невалидный UUID в path
- **WHEN** приложение не создаёт `ErrorResponseModel`
- **THEN** `401` переписывается в `codeId = INVALID_CREDENTIALS`; `InvalidArgumentException` → `400`; прочее без кода → `500` с `VALIDATION_ERROR` (в prod без текста исключения)

#### Scenario: Невалидный JSON
- **GIVEN** невалидный JSON в теле запроса
- **WHEN** `POST`/`PUT`/`PATCH`
- **THEN** `400 VALIDATION_ERROR`

### Requirement: UUID и даты в контракте
The API SHALL использовать UUID v7 как идентификаторы (в БД `BINARY(16)`, в JSON — строка) и отдавать даты в `DateTimeInterface::ATOM`.

#### Scenario: Форматы полей
- **GIVEN** любой ответ с объектами
- **WHEN** клиент читает `id` и дату
- **THEN** `id` — UUID-строка, дата — ATOM (ISO-8601 с часами и секундами)

### Requirement: Идентификатор в пути
The API SHALL принимать в path-параметре `{id}` 36-символьную строку из hex-символов и дефисов; строка, не удовлетворяющая этому виду, SHALL давать `404` (маршрут не совпадает); строка вида 36 hex-символов, не разбираемая как UUID, SHALL давать `400` с `codeId = VALIDATION_ERROR` и текстом `Invalid UUID.`.

#### Scenario: Идентификатор не по формату
- **GIVEN** в `{id}` передана строка, не совпадающая с 36 hex-символами и дефисами
- **WHEN** клиент запрашивает ресурс по этому идентификатору
- **THEN** `404` с `codeId = VALIDATION_ERROR` (маршрут не совпал)

#### Scenario: Идентификатор не разбирается как UUID
- **GIVEN** в `{id}` передана 36-символьная hex-строка без корректного расположения дефисов
- **WHEN** клиент запрашивает ресурс по этому идентификатору
- **THEN** `400` с `codeId = VALIDATION_ERROR` и текстом `Invalid UUID.`

### Requirement: Query-контракт листинг/поиска
The API SHALL поддерживать на листинговых/поисковых эндпоинтах пагинацию, сортировку и фильтры в едином формате (см. сценарии и Notes); whitelist полей и операторов задаётся на бэкенде.

#### Scenario: Пагинация и сортировка
- **GIVEN** есть данные
- **WHEN** `GET /api/users/query?page=2&perPage=50&sort=-createdAt`
- **THEN** `200` страница со `total` и смещением `(page-1)*perPage`

#### Scenario: Фильтры и их строгость
- **GIVEN** `filter[field][op]=value` (ровно один оператор на поле), `like` без `%`/`_`, пустой или запрещённый запрос фильтра
- **WHEN** запрос
- **THEN** фильтр применён (`like` → `%value%`, пустой `in` → `400 VALIDATION_ERROR`, недопустимый оператор/proto — `400`)

### Requirement: Конверт постраничного ответа
The API SHALL отдавать постраничные ответы (листинг и поиск) единым JSON-конвертом `{items, total, page, perPage}`: `items` — массив элементов домена, `total` — общее число элементов по фильтру, `page` — номер текущей страницы (≥ 1), `perPage` — размер страницы (1..100, по умолчанию 20).

#### Scenario: Форма конверта страницы
- **GIVEN** запрошена страница результатов
- **WHEN** клиент читает ответ
- **THEN** JSON содержит `items` (массив), `total` (int), `page` (int), `perPage` (int)

### Requirement: Асинхронные события через RabbitMQ
The API SHALL доставлять доменные события (`TaskCreated`, `TaskGroupsRecalculationRequestedEvent`, `UserRegisteredEvent`, `UserUpdatedEvent`) через транспорт `async` (RabbitMQ); в тестах — `in-memory://`. Retry-политика `async`: 3 попытки, delay 1000 мс, multiplier 2, затем в `failed`; очередь `failed` worker не читает (разбор вручную: `messenger:failed:show`/`retry`). Worker: `messenger:consume async scheduler_default`.

#### Scenario: Доставка после рестарта приложения
- **GIVEN** публикация события при доступном broker
- **WHEN** worker обрабатывает сообщение
- **THEN** handler приложения выполнен (см. события в [auth](../auth/spec.md), [tasks](../tasks/spec.md), [task-groups](../task-groups/spec.md), [notifications](../notifications/spec.md))

#### Scenario: Повторные неудачные доставки
- **GIVEN** handler падает
- **WHEN** 3 попытки исчерпаны
- **THEN** сообщение уходит в `failed` и далее не автоповторяется

### Requirement: Планировщик
The API SHALL по `scheduler_default` выполнять только задачу `app:files:cleanup-tmp` по cron из `SCHEDULER_FILE_CLEANUP_CRON` (default `0 3 * * *`) и `app:auth:cleanup-refresh-tokens` (`SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`, default `30 3 * * *`); политика `processOnlyLastMissedRun`.

#### Scenario: Пропущенный запуск
- **GIVEN** worker был недоступен в момент cron
- **WHEN** worker поднимается
- **THEN** запускается только последний пропущенный прогон

### Requirement: Кэш
The API SHALL использовать Redis (`REDIS_URL`) как кэш в prod и array-кэш в тестах; `CacheInterface` применяется только в доменных `*Cache`-сервисах (для debounce-locks пересчёта групп: `task_group_recalc.task.{taskId}`, `task_group_recalc.groups.{sha256(sorted ids)}`, TTL 30 с). Лист задач не кэшируется.

#### Scenario: Дебаунс через кэш-lock
- **GIVEN** второй запрос пересчёта той же задачи в течение 30 с
- **WHEN** lock ещё активен
- **THEN** дубликат пропущен

### Requirement: Батчирование больших коллекций
The API SHALL обрабатывать большие коллекции только батчами (`BatchUtils`, size 1000) и постранично (`listBatch`), без полных `listAll()`; ориентир масштаба — ~10⁶ `User` и ~10⁶ `Task`.

#### Scenario: Пересчёт групп при больших объёмах
- **GIVEN** >1000 затронутых задач/групп
- **WHEN** пересчёт
- **THEN** работа идёт чанками по 1000 без загрузки всей коллекции в память

### Requirement: Файловый storage
The API SHALL хранить файлы в локальном каталоге вне `public/`, доступ — только через контроллер (download URL с подписью, см. [files](../files/spec.md)).

#### Scenario: Прямой доступ
- **GIVEN** путь файла в каталоге storage
- **WHEN** запрос через nginx без контроллера
- **THEN** файл недоступен

### Requirement: Схема данных и миграции
The API SHALL поддерживать схему из 11 таблиц (перечень и FK-политики — в Notes) с UUID `BINARY(16)`; M2M-мосты удаляются каскадом, ссылки owner-file/parent обнуляются, refresh-токены отзываются приложением (без каскада FK). Docker `php` при `AUTO_MIGRATE=1` выполняет миграции при старте; worker — `AUTO_MIGRATE=0`.

#### Scenario: Каскадные удаления M2M
- **GIVEN** удаление задачи, участвующей в мостах
- **THEN** строки `asset_task`, `task_group_task` о нём удалены СУБД каскадом

### Requirement: Тестовая инфраструктура
The API SHALL в тестах использовать `in-memory://` транспорт для `async` и `failed`, array-кэш, password cost 4; тесты (`php bin/phpunit`) пересоздают схему на `setUp` (`DatabaseTestCase`/`DatabaseWebTestCase`); интеграционные проверки — HTTP-статус + `codeId`; suites `Unit` и `Integration`.

#### Scenario: Прогон тестов
- **GIVEN** чистая схема
- **WHEN** запускается Integration suite
- **THEN** схема пересоздаётся, тесты против in-memory транспорта и array-кэша проходят

## Notes

- Окружение (docker-compose): nginx 8080→80, php (build), mysql :3306, redis :6380, rabbitmq :5673/:15673; messenger-worker — профиль `workers`.
- Env: `APP_SECRET`, `JWT_SECRET`, `DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN`, `MESSENGER_FAILURE_TRANSPORT_DSN`, `MAILER_FROM`, `SCHEDULER_*_CRON`.
- Модели: `*Model::fromArray` + `InputAssertUtils`; wire persistence `*Model` не сериализуется в ответ — наружу только `*ViewModel`/`*PageModel`; entity не покидают Infrastructure.
- Пример поиска: `GET /api/users/query?page=2&perPage=50&sort=-createdAt&filter[email][eq]=a@b.c&filter[city][in][]=Moscow`. Пагинация: page ≥ 1/1, perPage 1..100/20. Сортировка: `field` asc, `-field` desc, дефолт по use-case. Операторы `FilterOperatorEnum`: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `like`, `in`, `isNull`, `isNotNull`; строки — eq/ne/like/in/isNull/isNotNull; int и даты — сравнения + in (int)/isNull/isNotNull; `in` требует непустого списка; meta-эндпоинт (`/query/meta`, только Users) отдаёт whitelist и дефолты.
- `codeId` whitelist (`ErrorCodeEnum`): `VALIDATION_ERROR` (400), `INVALID_CREDENTIALS` (401), `UNAUTHORIZED_USER_ACCESS`, `UNAUTHORIZED_TASK_ACCESS`, `UNAUTHORIZED_ASSET_ACCESS`, `UNAUTHORIZED_FILE_ACCESS`, `UNAUTHORIZED_TASK_GROUP_ACCESS` (403), `USER_NOT_FOUND`, `TASK_NOT_FOUND`, `ASSET_NOT_FOUND`, `FILE_NOT_FOUND`, `TASK_GROUP_NOT_FOUND` (404), `USER_ALREADY_EXISTS`, `INVALID_TASK_STATUS_TRANSITION` (409).
- Таблицы: `users`, `files`, `assets`, `asset_file`, `asset_task`, `tasks`, `task_users`, `task_groups`, `task_group_task`, `user_notification_preferences`, `refresh_tokens`. FK: `asset_task`, `task_group_task` — CASCADE; `tasks.parent_id`, `users.avatar_file_id` — SET NULL; остальные — default/RESTRICT.
- Источники: `api/config/packages/**`, `api/src/Presentation/Controller/BaseController.php`, `api/src/Application/Exception/**`, `api/src/Shared/Utils/**`, `api/src/Infrastructure/**`, `api/migrations/**`, `docker/**`.

## Покрытие тестами

- Не покрыты: сценарии «Идентификатор не по формату» и «Идентификатор не разбирается как UUID».
