# Platform (сквозные механизмы) — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/config/**`, `api/src/Presentation/Controller/BaseController.php`, `api/src/Application/Exception/**`, `api/src/Application/Model/Common/**`, `api/src/Shared/Utils/**`, `api/src/Infrastructure/**`, `api/migrations/**`, `docker/**`, `api/tests/**` |

## Контекст

Сквозные правила API: аутентификация, формат ошибок, query-механики,
асинхронность, кэш, планировщик, масштабирование, окружение и тестовая
инфраструктура.

## Акторы и доступ

- Аноним — только `/api/auth/*`.
- Пользователь (`ROLE_USER`) — все `/api/*`.
- Worker — `async` + `scheduler_default`.
- CLI — `app:*`.

## Требования

| ID | Требование |
|----|------------|
| FR-1 | JWT stateless, HS256, TTL 3600; claim `username` = id пользователя |
| FR-2 | Ошибки приложения — `{error, codeId}`; `codeId` из `ErrorCodeEnum` |
| FR-3 | Идентификаторы — UUID v7; в БД `BINARY(16)`, в JSON строка |
| FR-4 | Даты — `DateTimeInterface::ATOM` |
| FR-5 | Query: `page`, `perPage`, `sort`, `filter[field][op]` |
| FR-6 | Async-события — RabbitMQ; в тестах `in-memory://` |
| FR-7 | Кэш — Redis в prod, Array в тестах |
| FR-8 | Полный перебор больших коллекций — батчами по 1000 |
| FR-9 | Файлы — локальный storage вне `public/` |

## Безопасность

```yaml
firewalls:
  api_auth: {pattern: ^/api/auth, stateless: true, security: false}
  api:      {pattern: ^/api, stateless: true, provider: app_user_provider, jwt: ~}
access_control:
  - {path: ^/api/auth, roles: PUBLIC_ACCESS}
  - {path: ^/api, roles: IS_AUTHENTICATED_FULLY}
```

- Провайдер — `DoctrineUserProvider` → `UserRepositoryInterface::findById`.
- `SecurityUser` реализует `UserInterface`, `getRoles()` → `["ROLE_USER"]`.
- `getCurrentUserId()` в `BaseController` берёт id из `SecurityUser`; иначе
  `LogicException` (500).
- `#[IsGranted('ROLE_USER')]` на всех защищённых контроллерах.
- Пароли — `PasswordHasherInterface` (в тестах cost 4).
- `401` без своего `codeId` (нет или битый JWT) — `ErrorResponseModel` с
  `INVALID_CREDENTIALS`. Логин и refresh оставляют свои `codeId`.

JWT (`lexik_jwt_authentication.yaml`):

| Параметр | Значение |
|----------|----------|
| `secret_key` / `public_key` | `%env(JWT_SECRET)%` |
| `pass_phrase` | `''` |
| `token_ttl` | `3600` |
| `signature_algorithm` | `HS256` |

`LexikTokenProvider::generateToken()` — claims `roles`, `username` (id),
`email`. Refresh/logout/blacklist нет.

## Ошибки

Формат:

```json
{"error": "human readable", "codeId": "VALIDATION_ERROR"}
```

`ErrorResponseUtils::json($e, $status)`:

- `CodedExceptionInterface` → свой `codeId`;
- любое другое исключение → `VALIDATION_ERROR`.

`ErrorCodeEnum`:

| Значение | HTTP (типично) |
|----------|----------------|
| `VALIDATION_ERROR` | 400 |
| `INVALID_CREDENTIALS` | 401 |
| `UNAUTHORIZED_USER_ACCESS` | 403 |
| `UNAUTHORIZED_TASK_ACCESS` | 403 |
| `UNAUTHORIZED_ASSET_ACCESS` | 403 |
| `UNAUTHORIZED_FILE_ACCESS` | 403 |
| `UNAUTHORIZED_TASK_GROUP_ACCESS` | 403 (не используется) |
| `INVALID_FILE_STATE` | 403 |
| `USER_NOT_FOUND` | 404 |
| `TASK_NOT_FOUND` | 404 |
| `ASSET_NOT_FOUND` | 404 |
| `FILE_NOT_FOUND` | 404 |
| `TASK_GROUP_NOT_FOUND` | 404 |
| `NOTIFICATION_PREFERENCE_NOT_FOUND` | 404 |
| `USER_ALREADY_EXISTS` | 409 |
| `INVALID_TASK_STATUS_TRANSITION` | 409 |

- HTTP-статус задаёт контроллер; `codeId` не определяет статус.
- `BaseController::requestPayload()` бросает `InvalidArgumentException` на
  невалидный JSON; контроллеры, которые его ловят, возвращают `400`.
- `ApiExceptionSubscriber` на `/api`: необработанное исключение →
  `ErrorResponseModel`. `InvalidArgumentException` (в том числе битый UUID) →
  `400`; остальное без кода → `500` и `VALIDATION_ERROR` (в prod без текста
  исключения).
- `401` от firewall, если тело ещё не `ErrorResponseModel`, переписывается в
  `{error, codeId: INVALID_CREDENTIALS}`.

## Query-механика

Формат:

```text
GET /api/users/query?page=2&perPage=50&sort=-createdAt
    &filter[email][eq]=a@b.c&filter[city][in][]=Moscow&filter[city][in][]=Kazan
```

| Параметр | Default | Ограничения |
|----------|---------|-------------|
| `page` | 1 | ≥ 1 |
| `perPage` | 20 | 1..100 |
| `sort` | use-case default | `field` = asc, `-field` = desc |
| `filter[field][op]` | — | один оператор на поле |

`FilterOperatorEnum`: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `like`, `in`,
`isNull`, `isNotNull`.

- `forString()`: `eq/ne/like/in/isNull/isNotNull`
- `forInt()`: `eq/ne/gt/gte/lt/lte/in/isNull/isNotNull`
- `forDateTime()`: `eq/ne/gt/gte/lt/lte/isNull/isNotNull`
- `needsValue()` = false для `isNull`/`isNotNull`.
- `like` без wildcard оборачивается в `%...%`.
- Значения `in` — список; пустой список недопустим.
- Whitelist полей/операторов — Application `*FilterFieldEnum` +
  Infrastructure `*FilterFieldDoctrineMap`.
- Meta endpoint (`/query/meta`) отдаёт `filters`, `sortFields`,
  `sortDirections`, `defaultSort`, `pagination`; сейчас только у Users.

`PaginationModel`: `DEFAULT_PAGE=1`, `DEFAULT_PER_PAGE=20`, `MAX_PER_PAGE=100`,
`offset() = (page-1)*perPage`.

## Модели и маппинг

- Wire → typed: `*Model::fromArray` + `InputAssertUtils`.
- `BaseController::requestPayload()` требует JSON-объект (не список).
- Persistence `*Model` не сериализуется в ответ; наружу — `*ViewModel` /
  `*PageModel`.
- Mapper: `*ViewModelMapper` (Application) и Doctrine `*Mapper` (Infrastructure).
- Entity не выходит за пределы Infrastructure.

## Async / Messenger

```yaml
failure_transport: failed
transports:
  async: '%env(MESSENGER_TRANSPORT_DSN)%'   # queue messages
  failed: '%env(MESSENGER_FAILURE_TRANSPORT_DSN)%'  # queue failed
  sync: 'sync://'
routing:
  TaskCreated: async
  TaskGroupsRecalculationRequestedEvent: async
  UserRegisteredEvent: async
  UserUpdatedEvent: async
```

- `async` retry: `max_retries: 3`, `delay: 1000`, `multiplier: 2`.
- `failure_transport: failed` — очередь `failed` на том же брокере. Worker её не
  читает; разбор — `messenger:failed:show` / `messenger:failed:retry`.
- В тестах `async` и `failed`: `in-memory://`.
- Worker: `messenger:consume async scheduler_default -vv --time-limit=3600`
  (docker-профиль `workers`, `restart: unless-stopped`).

## Кэш

- `framework.cache.app` — `cache.adapter.redis`, `REDIS_URL`.
- В тестах — `cache.adapter.array`.
- `CacheInterface` используется только в `*Cache`:
  - `TaskGroupCache` — debounce-lock'и пересчёта, TTL 30 секунд.
- Ключи: `task_group_recalc.task.{taskId}`,
  `task_group_recalc.groups.{sha256(sorted ids)}`.

## Scheduler

- `AppScheduleProvider` (`#[AsSchedule]`), stateful, `processOnlyLastMissedRun`.
- Одна задача: `app:files:cleanup-tmp` по cron из
  `app.scheduler.file_cleanup_cron` (default `0 3 * * *`, env
  `SCHEDULER_FILE_CLEANUP_CRON`).
- Запуск — worker `scheduler_default`.

## Масштаб и batch

- Ориентир: ~10⁶ `User` и ~10⁶ `Task`.
- `BatchUtils`: `DEFAULT_SIZE = 1000`; `chunk(list, size)`, `each(list, cb, size)`.
- Эталон: `TaskGroupRecalculationService` (`listByIds` + `BatchUtils::each`).
- `listAll()` нет. Полный перебор — `listBatch` (по 1000).
- Summary групп читает `listBatch`, в ответе по-прежнему весь список
  (`createdAt` desc). Пагинации и фильтра по владельцу нет.

## Данные и миграции

- Одна миграция `Version20260930100000` — начальная схема.
- Таблицы: `users`, `files`, `assets`, `asset_file`, `asset_task`, `tasks`,
  `task_users`, `task_groups`, `task_group_task`,
  `user_notification_preferences`, `refresh_tokens`.
- UUID — `BINARY(16)`, PK/unique-индексы на месте.
- FK-политики:
  - `asset_task`, `task_group_task` — `ON DELETE CASCADE` (M2M);
  - `tasks.parent_id`, `users.avatar_file_id` — `ON DELETE SET NULL`;
  - остальные — default/RESTRICT.
- Docker `php` выполняет миграции при `AUTO_MIGRATE=1`; worker — `AUTO_MIGRATE=0`.

## Окружение

| Сервис | Образ | Порт host |
|--------|-------|-----------|
| nginx | `nginx:alpine` | 8080 → 80 |
| php | build `docker/php` | 9000 (внутренний) |
| mysql | `mysql:8` | 3306 |
| redis | `redis:7-alpine` | 6380 |
| rabbitmq | `rabbitmq:3-management` | 5673 / 15673 |
| messenger-worker | build `docker/php` | — (profile `workers`) |

Переменные: `APP_SECRET`, `JWT_SECRET`, `DATABASE_URL`, `REDIS_URL`,
`MESSENGER_TRANSPORT_DSN`, `MESSENGER_FAILURE_TRANSPORT_DSN`, `MAILER_FROM`,
`SCHEDULER_FILE_CLEANUP_CRON`.

## Тесты

- PHPUnit 11; `bootstrap=tests/bootstrap.php`.
- Suites: `Unit` (`tests/Application`, `tests/Utils`), `Integration`
  (`tests/Integration`).
- `DatabaseTestCase` / `DatabaseWebTestCase` пересоздают схему на `setUp`.
- `AuthenticatedWebTestCase` создаёт demo-пользователя и логинится.
- В тестах: `async` и `failed` = `in-memory://`, cache = array, password cost 4.
- Проверки: статус + `codeId`; для ответов — decode в typed `*Model`.
- Команды (из `api/`): `php bin/phpunit`, `composer phpstan`,
  `composer cs-check`.

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Слои | нет `src/Domain`, нет `Application/Command|Query|Handler` | `api/CLAUDE.md` | ADR 0001 |
| Конфиг | `services.yaml` исключает `../src/Domain/`, которого нет | удалить/создать слой | `config/services.yaml` |
| Состояние | `GET /api/tasks` кэш 60 секунд может отдавать устаревший список | инвалидация | 0004 |

## Первоисточники

- `api/config/packages/{security,lexik_jwt_authentication,messenger,cache,framework,doctrine}.yaml`
- `api/config/services.yaml`, `api/config/routes.yaml`
- `api/src/Presentation/Controller/BaseController.php`
- `api/src/Application/Exception/{ErrorCodeEnum,CodedExceptionInterface}.php`
- `api/src/Application/Model/Http/ErrorResponseModel.php`
- `api/src/Application/Model/Common/{PaginationModel,SortModel}.php`
- `api/src/Application/Model/Common/Query/**`
- `api/src/Application/Enum/Common/{FilterOperatorEnum,SortDirectionEnum}.php`
- `api/src/Shared/Utils/**` (в т.ч. `BatchUtils`, `InputAssertUtils`, `UidUtils`)
- `api/src/Infrastructure/Security/**`, `Scheduler/AppScheduleProvider.php`
- `api/src/Infrastructure/Cache/RedisCacheService.php`
- `api/migrations/Version20260930100000.php`
- `api/phpunit.xml.dist`, `api/tests/{DatabaseTestCase,DatabaseWebTestCase,AuthenticatedWebTestCase}.php`
- `docker/docker-compose.yml`, `docker/php/**`, `docker/nginx/**`
- `api/composer.json`
- `api/src/Infrastructure/Http/ApiExceptionSubscriber.php`
