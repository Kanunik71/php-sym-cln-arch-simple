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
│       ├── Cache/     — AssertTaskCacheTrait
│       └── Common/    — AssertMessengerTrait
├── fixtures/       # бинарные файлы (avatar.jpg)
├── DatabaseTestCase.php          # Kernel + TestBed, схема на setUp
├── DatabaseWebTestCase.php       # HTTP без заранее залогиненного пользователя
└── AuthenticatedWebTestCase.php  # demo user + login
```

**Не создавать `tests/Unit/`** — слои лежат в корне `tests/`, как в `src/`. Слоя `Domain/` нет: персистентная модель — Application `*Model`, VO — `Application/ValueObject`.

---

## Слои

| Слой | Базовый класс | Что проверять |
|------|----------------|---------------|
| **`Application/Service/{Entity}/{Action,Query,Lifecycle}`** | `DatabaseTestCase`, если есть БД / cache / messenger; иначе `PHPUnit\Framework\TestCase` | один `*Service` через `$this->bed->get(…)`; ответ `*ViewModel` vs персистентная `*Model` (`Asserts*ModelTrait`); search filter/sort; `clear()` + `find*` |
| **`Application/Model` / `ValueObject` / `Utils`** | `TestCase` | `fromArray` / whitelist, инварианты VO и утилит — без БД |
| **Integration `{Name}ControllerTest`** | `AuthenticatedWebTestCase` или `DatabaseWebTestCase` | HTTP status + error `codeId` |
| **ModelMapper отдельно** | — | не тестируем |

```
Application/
├── Service/{Asset,File,Notification,Task,TaskGroup,User}/
│   ├── Action/      # write use case, execute()
│   ├── Query/       # read use case, execute()
│   └── Lifecycle/   # side effects после persist, пересчёт, cleanup
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
```

| Папка Service | Роль | Вход |
|---------------|------|------|
| **Action** | изменение | `execute(*Model?, …context) → *ViewModel \| void` |
| **Query** | чтение (`list` / `get` / `search`) | `execute(…) → *ViewModel \| list \| page` |
| **Lifecycle** | не вход контроллера | свой публичный метод (`after*Persisted`, `recalculate*`, иногда `execute`) |

### Application Service
1. С БД, cache или messenger — `extends DatabaseTestCase`. Репозитории, MessageBus и Cache **не** мокать; seed через `$this->bed->…`. Чистая логика без IO (`statusFromMembership`, search meta, `TaskUserListService`) — `PHPUnit\Framework\TestCase`, сервис собирается через `new`.
2. В тесте вызывается сервис под проверкой: `$this->bed->get(*Service::class)`. У Action/Query точка входа — `execute(…)`. У Lifecycle — его публичный метод. Чужой `*Service` — только чтобы увидеть последствие (list после create, login после смены пароля), не для подготовки состояния.
3. Прямой доступ к репозиторию — `$this->bed->*Repository()` (`userRepository`, `taskRepository`, `assetRepository`, `fileRepository`, `taskGroupRepository`, `notificationPreferenceRepository`), не `$this->bed->get(*RepositoryInterface::class)`.
4. Setup состояния — snapshot-хелперы TestBed (`createUser`, `createActiveTask`, `createFinishedTask`, `createTaskGroup`, `uploadTmpImage`, …). Не менять модель в тесте методами перехода, если тест не про этот переход.
5. Выход Action/Query — `*ViewModel` против персистентной `*Model` (`AssertsUserModelTrait`, `AssertsTaskModelTrait`, `AssertsTaskGroupModelTrait`, `AssertsAssetModelTrait`, `AssertsAssetDetailModelTrait`). Persist: `$this->bed->clear()` + `find*ById`.
6. User `/query` (filter/sort/pagination) — `UserSearchQueryServiceTest`. Whitelist meta без БД — `UserSearchMetaQueryServiceTest`.
7. Async side effects: `AssertMessengerTrait::assertDispatched($eventClass, times:, predicate:)` по `messenger.transport.async`. По умолчанию транспорт после assert сбрасывается (`resetTransport: true`). Lock диспетчера (`TaskGroupRecalculationDispatcher::releaseTaskLock` / `releaseGroupIdsLock`) снимать явно, если тот же task/group должен уйти в dispatch ещё раз.
8. Cache invalidation списка Task: `AssertTaskCacheTrait` — `warmTaskUserListCache($userId)` + `assertTaskUserListCacheInvalidated($userId, $callback?)`. Эталон — `TaskLifecycleServiceTest`.
9. **Typed fixtures:** вход use case — `new *Model(...)`. **Не** `*Model::fromArray`, **не** хелперы с `array $overrides`. `fromArray` / `fromArrayList` — только в `Application/Model` (парсинг wire).

### Integration
1. Один `{Name}ControllerTest` на контроллер из `Presentation/Controller/` (у Task их несколько: `Task`, `TaskAsset`, `TaskTaskGroup`).
2. Защищённые маршруты — `AuthenticatedWebTestCase` (`createAuthenticatedClient`, `demoAuthorizedServer`). Публичный Auth — `DatabaseWebTestCase`.
3. Success — status (+ smoke вроде register → login).
4. Errors — status **и** `ErrorResponseModel.codeId` (`decodeErrorModel`, `ErrorCodeEnum`).
5. Не дублировать field-map Model и use-case / Infra-эффекты из Application.
6. Request body — `(new *Model(...))->toArray()`; не собирать payload руками как `array`.
