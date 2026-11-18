# Тесты (`api/tests`)

```
tests/
├── Application/    # Service / Model / ValueObject — зеркало src/Application/
├── Utils/          # зеркало src/Shared/Utils/
├── Integration/    # HTTP-контроллеры (Presentation; KernelBrowser + БД)
├── Support/        # TestBed, traits
│   ├── TestBed.php
│   └── Trait/
│       ├── TestBed/   — *TestBedTrait (seed через репозиторий)
│       ├── Model/     — Asserts*ModelTrait
│       └── Common/    — AssertMessengerTrait
├── fixtures/       # бинарные файлы (avatar.jpg)
├── DatabaseTestCase.php          # Kernel + TestBed, схема на setUp
├── DatabaseWebTestCase.php       # HTTP без заранее залогиненного пользователя
└── AuthenticatedWebTestCase.php  # демо-пользователь + логин
```

**Не создавать `tests/Unit/`** — слои лежат в корне `tests/`, как в `src/`; тесты персистентной модели — в `tests/Application/Model`, VO — в `tests/Application/ValueObject`.

---

## Слои

| Слой | Базовый класс | Что проверять |
|------|----------------|---------------|
| **`Application/Service/{Entity}/{Action,Query,Lifecycle}`** | `DatabaseTestCase`, если есть БД / cache / messenger; иначе `PHPUnit\Framework\TestCase` | один `*Service` через `$this->bed->get(…)`; ответ `*ViewModel` vs персистентная `*Model` (`Asserts*ModelTrait`); search filter/sort; `clear()` + `find*` |
| **`Application/Model` / `ValueObject` / `Utils`** | `TestCase` | `fromArray` / whitelist, инварианты VO и утилит — без БД |
| **`Application/Cache/{Entity}`** | `TestCase` | ключ и TTL, попадание / промах / инвалидация — порт подменяется double'ом |
| **Integration `{Name}ControllerTest`** | `AuthenticatedWebTestCase` или `DatabaseWebTestCase` | HTTP status + error `codeId` |
| **`Application/Mapper` отдельно** | — | не тестируем |

```
Application/
├── ArchitectureTest.php                     # граница входа (сканирует src/, не зеркало)
├── Cache/{TaskGroup}/                       # *Cache: ключ, TTL, инвалидация
├── Event/{TaskGroup}/                       # *Event: контракт payload
├── Service/{Asset,File,Notification,Task,TaskGroup,User}/
│   ├── Action/      # write use case, execute()
│   ├── Query/       # read use case, execute()
│   └── Lifecycle/   # Service: side effects после persist, пересчёт, cleanup
├── Model/{Asset,File,Task,TaskGroup,User}/   # + SortModelTest в корне Model/
└── ValueObject/Common/                       # Email, Phone

Utils/                # ArrayUtils, BatchUtils, CacheKeyUtils, DateUtils, EnumUtils, MoneyUtils
├── Asserts/
└── Http/

Integration/          # один тест на контроллер, зеркало Presentation/Controller/
├── Asset/AssetControllerTest.php
├── Auth/AuthControllerTest.php
├── File/FileControllerTest.php
├── Notification/NotificationPreferenceControllerTest.php
├── Task/TaskControllerTest.php
├── Task/TaskAssetControllerTest.php
├── Task/TaskTaskGroupControllerTest.php
├── TaskGroup/TaskGroupControllerTest.php
└── User/UserControllerTest.php

Infrastructure/Persistence/Doctrine/CascadeDeleteRuleTest.php   # белый список ON DELETE в схеме и маппингах
```

| Папка Service | Роль | Вход |
|---------------|------|------|
| **Action** | use case, изменение | `execute(*Model?, …context) → *ViewModel \| void` |
| **Query** | use case, чтение (`list` / `get` / `search`) | `execute(…) → *ViewModel \| list \| page` |
| **прочие** (`Lifecycle`, `Auth`, …) | Service: переиспользуемый шаг | свой публичный метод (`after*Persisted`, `recalculate*`, иногда `execute`) |

## Окружение тестов

- Транспорты `async` и `failed` — `in-memory://`; кэш — array (см. `config/packages/messenger.yaml`, `when@test`).
- Password cost 4; схема БД пересоздаётся на `setUp` в `DatabaseTestCase`/`DatabaseWebTestCase`.
- Suites: `Unit` (`tests/Application`, `tests/Infrastructure`, `tests/Utils`) и `Integration` (`tests/Integration`); интеграционные проверки — HTTP-статус **и** `codeId`.
- `tests/Application/ArchitectureTest.php` — единственное исключение из «зеркало `src/Application`»: сканирует `src/` целиком и проверяет границу входа (Controller → Action/Query, job → Service, Service не зовёт use case, Service не открывает транзакцию). Роль класса определяется по папке: `Action`/`Query` — use case, остальное в `Application/Service/**` — Service.

### Application Service
1. С БД, cache или messenger — `extends DatabaseTestCase`. Репозитории, MessageBus и Cache **не** мокать; seed через `$this->bed->…`. Чистая логика без IO (`statusFromMembership`, search meta, `TaskUserListService`) — `PHPUnit\Framework\TestCase`, сервис собирается через `new`.
2. В тесте вызывается сервис под проверкой: `$this->bed->get(*Service::class)`. У Action/Query точка входа — `execute(…)`. У Lifecycle — его публичный метод. Чужой `*Service` — только чтобы увидеть последствие (list после create, login после смены пароля), не для подготовки состояния.
3. Прямой доступ к репозиторию — `$this->bed->*Repository()` (`userRepository`, `taskRepository`, `assetRepository`, `fileRepository`, `taskGroupRepository`, `notificationPreferenceRepository`), не `$this->bed->get(*RepositoryInterface::class)`.
4. Setup состояния — snapshot-хелперы TestBed (`createUser`, `createActiveTask`, `createFinishedTask`, `createTaskGroup`, `uploadTmpImage`, …). Не менять модель в тесте методами перехода, если тест не про этот переход.
5. Выход Action/Query — `*ViewModel` против персистентной `*Model` (`AssertsUserModelTrait`, `AssertsTaskModelTrait`, `AssertsTaskGroupModelTrait`, `AssertsAssetModelTrait`, `AssertsAssetDetailModelTrait`). Persist: `$this->bed->clear()` + `find*ById`.
6. User `/query` (filter/sort/pagination) — `UserSearchQueryServiceTest`. Whitelist meta без БД — `UserSearchMetaQueryServiceTest`.
7. Async side effects: `AssertMessengerTrait::assertDispatched($eventClass, times:, predicate:)` по `messenger.transport.async`. По умолчанию транспорт после assert сбрасывается (`resetTransport: true`).
8. **Typed fixtures:** вход use case — `new *Model(...)`. **Не** `*Model::fromArray`, **не** хелперы с `array $overrides`. `fromArray` / `fromArrayList` — только в `Application/Model` (парсинг wire).

### Integration
1. Один `{Name}ControllerTest` на контроллер из `Presentation/Controller/` (у Task их несколько: `Task`, `TaskAsset`, `TaskTaskGroup`).
2. Защищённые маршруты — `AuthenticatedWebTestCase` (`createAuthenticatedClient`, `demoAuthorizedServer`). Публичный Auth — `DatabaseWebTestCase`.
3. Success — status (+ smoke вроде register → login).
4. Errors — status **и** `ErrorResponseModel.codeId` (`decodeErrorModel`, `ErrorCodeEnum`).
5. Не дублировать field-map Model и use-case / Infra-эффекты из Application.
6. Request body — `(new *Model(...))->toArray()`; не собирать payload руками как `array`.
