# Соглашения об именовании (`api/src`)

Жёсткие правила именования классов в `api/src/`. Каждый класс имеет **ровно один суффикс**, однозначно определяющий его роль.

**Исключения:** `Kernel.php` (точка входа Symfony) и **Domain** (агрегаты, Value Object, часть enum) — без суффикса роли, имя = Ubiquitous Language.

---

## Слои

```
api/src/
├── Domain/          # Агрегаты, ValueObject, Enum, Event, Exception
├── Application/     # Use cases, Model, Enum, Service, Dispatcher, Cache, Port
├── Infrastructure/  # Doctrine, Redis, RabbitMQ, JWT, адаптеры
├── Presentation/    # HTTP-контроллеры и консольные команды
└── Utils/           # Статические утилиты (Shared/Utils)
```

**Domain** — максимально чистый: агрегаты, VO, доменные события/исключения/enum. Без репозиториев, pagination, criteria, HTTP.

**Application** — use cases и все application-структуры вокруг них.

**Масштаб:** ориентир — до ~10⁶ `User` и ~10⁶ `Task` (и связанные агрегаты). Полный перебор через `listAll()` запрещён для job’ов / Service / Console; только `listBatch` / `BatchUtils` + `listByIds`. Подробно: `Application/CLAUDE.md` → «Масштаб и batch-переборы»; эталон — `TaskGroupRecalculationService`.

---

## Таблица суффиксов

| Суффикс | Назначение | Слой | Пример |
|---------|-----------|------|--------|
| **Model** | Wire VO: request + response (JSON наружу), criteria, pagination, page / facts из port | Application | `UserUpdateModel`, `UserModel`, `UserRegisterResponseModel`, `UserPaginatedModel` |
| **Entity** | Модель таблицы БД (Doctrine) | Infrastructure | `TaskEntity` |
| **Enum** | PHP enum (**только Application**; в Domain — без суффикса) | Application | `FilterOperatorEnum` |
| **Exception** | Доменное исключение | Domain | `TaskNotFoundException` |
| **Event** | Доменное событие | Domain | `TaskCreatedEvent` |
| **RepositoryInterface** | Контракт репозитория (Port) | Application | `UserRepositoryInterface` |
| **Interface** | Любой другой контракт | Domain, Application | `CodedExceptionInterface`, `CacheInterface`, `ArrayableModelInterface` |
| **Command** | Вход use case на **изменение** | Application | `CreateTaskCommand` |
| **Query** | Вход use case на **чтение** | Application | `GetTaskQuery`, `SearchUsersQuery` |
| **CommandHandler** | `Command → Model/void` | Application | `CreateTaskCommandHandler` |
| **QueryHandler** | `Query → Model/void` | Application | `SearchUsersQueryHandler` |
| **Mapper** | Domain ↔ Entity | Infrastructure | `UserMapper` |
| **ModelMapper** | Domain → response Model | Application | `UserModelMapper` |
| **Cache** | Политика ключей кэша; единственный потребитель `CacheInterface` в Application | Application | `TaskCache` |
| **Dispatcher** | Постановка async-работы: дедуп, нормализация, lock, dispatch Domain Event | Application | `TaskGroupRecalculationDispatcher` |
| **Policy** | Preconditions use case: auth, uniqueness, IO; shared access rules | Application | `UpdateUserPolicy`, `TaskAccessPolicy` |
| **Repository** | Реализация репозитория | Infrastructure | `DoctrineUserRepository` |
| **Service** | Оркестрация (Application) или тех. реализация (Infrastructure) | Application, Infrastructure | `TaskGroupRecalculationService`, `RedisCacheService` |
| **Provider** | Внешний ресурс | Infrastructure | `LexikTokenProvider` |
| **Adapter** | Обёртка над библиотекой | Infrastructure | `SymfonyPasswordHasherAdapter` |
| **MessageHandler** | Асинхронный обработчик Event | Infrastructure | `TaskCreatedMessageHandler` |
| **Controller** | HTTP | Presentation | `UserController` |
| **Console** | CLI | Presentation | `RecalculateTaskGroupsConsole` |
| **Utils** | Статические утилиты | Utils | `UidUtils` |

---

## Application: вход / выход / внутри

```
Model (+ path/security) → Command / Query  →  Handler  →  Model | void
                              ↕
                         Domain
```

| Слой | Суффикс | Назначение | Выходит из Handler? |
|------|---------|------------|---------------------|
| **Command** / **Query** | Command, Query | Intent use case + Model + context | нет (вход) |
| **Model** | Model | Request / response / pagination / criteria / page из port | **да** (response); request — нет |
| **Enum** | Enum | Application enum (операторы, whitelist полей) | только как поле Model |

Domain-агрегат / Domain VO (`User`, `Email`) **не** выходит из Handler. Перед возвратом — `*ModelMapper` → Model (примитивы / скаляры).

**Enum в Model:** закрытое множество значений в поле Model — **не** `string`, а typed enum:
- Domain enum (`TaskStatusEnum`, `NotificationTypeEnum`, …) — когда значение из UL / Domain;
- Application `*Enum` (`FilterOperatorEnum`, …) — когда значение application-контракта (whitelist, meta).

Примеры: `TaskModel::$status` → `TaskStatusEnum`; `NotificationPreferenceModel::$type` → `NotificationTypeEnum`; `list` каналов → `list<NotificationChannelEnum>`.

```
HTTP array → *Model::fromArray(...) → new Command|Query($model, …context) → Handler → Model
CLI / tests → new *Model(...)        → new Command|Query($model, …context) → Handler → void|Model
```

---

## Domain vs Application

### Domain (без суффикса роли у UL-типов)

```
User.php                              # агрегат
Task.php
Common/ValueObject/Email.php          # shared VO
Task/ValueObject/TaskName.php         # контекстный VO (когда появится)
Task/Enum/TaskStatusEnum.php              # enum — не ValueObject/
TaskGroup/Enum/TaskGroupStatusEnum.php
```

### Application Model

```
Model/User/UserUpdateModel.php              # request write
Model/User/UserLoginModel.php               # request
Model/User/UserRegisterResponseModel.php    # response (коллизия с UserRegisterModel)
Model/User/UserModel.php                    # response
Model/User/UserPageModel.php                # list<UserModel> + page meta
Model/User/UserPaginatedModel.php          # list<User> + total (из port)
Model/Common/PaginationModel.php
Model/User/UserFilterCriterionModel.php
```
---

## Domain Value Object

1. Имя = Ubiquitous Language, **без** суффикса (`Email`, `TaskName`, не `EmailValueObject`)
2. Все class VO — только в папке `ValueObject/`
3. Shared — `Domain/Common/ValueObject/`; контекстные — `Domain/{Aggregate}/ValueObject/`
4. **Запрет дублей и одинаковых коротких имён** по всему Domain (один `Email`, не `User\Email` + `Common\Email`)
5. Enum **не** класть в `ValueObject/` — отдельно `Enum/`
6. VO заводить при реальных инвариантах / поведении, не на каждое поле
7. На границе наружу — `toString()` / скаляр в Mapper / ModelMapper

```
Domain/
├── Common/
│   └── ValueObject/
│       └── Email.php
├── User/
│   ├── User.php
│   └── Exception/
└── Task/
    ├── Task.php
    ├── ValueObject/          # при необходимости
    └── Enum/
        └── TaskStatusEnum.php
```

---

## Вход API = Model → Command / Query

Контроллер собирает typed Model из payload, оборачивает в Command/Query (+ path/security) и вызывает Handler. Валидацию полей не делает.

`Presentation\Request` **нет**. Отдельный слой Request **нет** — payload = `*Model`.

| Источник | Тип | Куда |
|----------|-----|------|
| body / query string | `array` → `*Model::fromArray` | `Command` / `Query` как `$model` |
| path / security | уже `string` | отдельные аргументы конструктора Command/Query |

`*Model::fromArray` — только wire → typed (типы / минимальный shape через `InputAssertUtils`), чтобы Model была type-valid.  
Use-case contract без IO (whitelist sort/filter, cross-field) — при сборке Command/Query.  
Preconditions с IO (auth, uniqueness, exists) — в `*Policy`; Handler вызывает Policy до domain-логики.  
Доменные инварианты — в VO / агрегате.  
Command/Query **не** перепроверяют типы полей Model.

```php
// только payload
new RegisterUserCommand(UserRegisterModel::fromArray($this->requestPayload($request)));

// payload + типизированный контекст
new UpdateUserCommand(
    UserUpdateModel::fromArray($this->requestPayload($request)),
    userId: $id,
    currentUserId: $this->getCurrentUserId(),
);

// без body — Model не нужен
new DeleteUserCommand(userId: $id, currentUserId: $this->getCurrentUserId());
```

Правила Model-payload:
1. Request: операция + `Model` (`UserUpdateModel`). Response: сущность + `Model` (`UserModel`); при коллизии — `*ResponseModel` (`UserRegisterResponseModel`).
2. Даже одна Model в Command — ок (единый стиль).
3. Get / List / Delete без body — без Model.
4. `toArray()` — wire-ключи (для Integration / serde); array только на краю.

Ключ текущего пользователя в Command — `currentUserId` (или `userId`, если это единственный actor-контекст сущности).

---

## Нетипизированные данные

`array` и `mixed` — **только на границе** (HTTP body, JSON decode, wire serde). Внутри Application, Domain, Handler, Policy, Service и тестов — typed **Model**, Domain VO, enum, Command / Query.

| Где | Как |
|-----|-----|
| Presentation (контроллер) | `requestPayload()` → `*Model::fromArray` → Command / Query |
| Application (Handler, Policy, Service) | typed Model, Domain — без `array<string, mixed>` в сигнатурах и полях |
| CLI / Application tests | `new *Model(...)` — **не** `fromArray`, не хелперы с `array $overrides` |
| Integration tests | `*Model::toArray()` — только для HTTP request body; assert — по response Model |

**Избегать:**
- payload / overrides как `array` между слоями и в тестовых хелперах;
- `fromArray` вне wire-границы (контроллер, serde, тесты парсинга Model);
- «магические» ключи массива там, где уже есть конструктор Model / Command / Query.

`*Model::fromArray` — parse wire → typed Model, не универсальный способ собрать объект в коде.

---

## List vs Query (HTTP)

| Метод | HTTP | Use case | Назначение |
|-------|------|----------|------------|
| `list()` | `GET /api/{resource}` | `List*Query` | простой список |
| `query()` | `GET /api/{resource}/query` | `Search*Query` | pagination + criteria + sort |

`list` фильтрами не расширяем. Criteria — только в `Search*`.

Пилот: `UserController::query()` → `SearchUsersQuery`.

---

## Command vs Query

| Тип | Когда | Пример |
|-----|-------|--------|
| Command | create, update, delete, … | `CreateTaskCommand` |
| Query | get, list, search | `GetUserQuery`, `SearchUsersQuery` |

```
SearchUsersQuery → SearchUsersQueryHandler → UserPageModel
ListUsersQuery   → ListUsersQueryHandler → UserModel[]
DeleteUserCommand → DeleteUserCommandHandler → void
```

---

## Enum

| Слой | Папка | Суффикс | Пример |
|------|-------|---------|--------|
| Domain | `Domain/{Aggregate}/Enum/` | **нет** | `TaskStatusEnum`, `TaskGroupStatusEnum`, `AssetTypeEnum` |
| Application | `Application/Enum/` | **Enum** | `FilterOperatorEnum`, `UserFilterFieldEnum`, `UserSortFieldEnum` |

Не класть enum внутрь `Model/` / `ValueObject/`.

В полях **Model** — ссылка на Domain enum или Application `*Enum` (не `string` для закрытого множества). См. «Enum в Model» выше.

---

## Mapper

| Суффикс | Направление | Слой |
|---------|-------------|------|
| **Mapper** | Domain ↔ Entity | Infrastructure |
| **ModelMapper** | Domain → response Model | Application |

---

## Cache / Service / Dispatcher / Policy / Port

- `CacheInterface` — только в `*Cache`
- Application `*Service` — синхронная оркестрация без DQL
- Application `*Dispatcher` — решение «ставить ли async-задачу» (дедуп, нормализация, lock) + `MessageBusInterface::dispatch`; не выполняет работу (`*Service`) и не обрабатывает очередь (`*MessageHandler`)
- Application `*Policy` — preconditions use case (auth, uniqueness, exists) и shared access rules; бросает Domain Exception; не выполняет persist и не возвращает response Model
- Публичный метод `*Policy` с обращением к Repository, Port (кроме pure config), filesystem, HTTP или cache — суффикс **`Io`** (`assertEmailAvailableIo`); без внешних вызовов — без суффикса (`assertSelfAccess`)
- Criteria applier / QueryBuilder — Infrastructure
- Persist / lookup / search — **Application Port** (`*RepositoryInterface`); Domain не содержит репозиториев

---

## Правила по слоям

### Application

```
Application/
├── Command/
├── Query/
├── Model/          # request + response + pagination/criteria + page из port
│   ├── Common/
│   ├── Http/
│   ├── User/       # UserUpdateModel, UserModel, UserRegisterResponseModel, …
│   └── Task/
├── Enum/           # отдельно от Model
│   ├── Common/
│   └── User/
├── Mapper/
├── Cache/
├── Dispatcher/
├── Policy/
├── Service/
└── Port/
    ├── User/
    │   └── UserRepositoryInterface.php
    ├── Task/
    ├── TaskGroup/
    ├── Asset/
    └── File/
```

### Presentation

```
Controller: list / query / get / mutations
Console: CLI
# нет Presentation/Request — вход через Command|Query::fromArray
```

---

## Search / criteria

1. `Search{Entity}Query` — pagination + criteria (+ sort) + security context  
2. `*FilterCriterionModel::fromArrayList($data)` → `FilterCriteriaUtils::fromArray` + entity builder; поле через `FilterFieldUtils::isAllowed($name, *FilterFieldEnum::class)`  
3. Common type → filter VO — `FilterFieldTypeEnum::fromOperatorMap()`; entity-specific filters — `match ($field)` в `*FilterCriterionModel`  
4. Sort: `SortModel::fromArray` только при наличии `sort` в wire; иначе default в `Search*Query`; whitelist — `SortFieldUtils` + `*SortFieldEnum::values()`
5. Single wire Model — `ArrayableModelInterface`; criteria list — `ArrayableModelListInterface` (`fromArrayList` / `toArrayList`); response decode — `fromArray`  

6. Filter VO — `Application/Model/Common/Filter/` (+ `toOperatorMap()` для wire)  
7. Операторы / поля — `Application/Enum/`  
8. Port `*RepositoryInterface::search` принимает list criteria → page `*Model`  
9. Infrastructure: `*CriteriaApplier` (цикл по list) + Doctrine map  

---

## Миграция

| Было | Станет |
|------|--------|
| слой Dto | убран; request + response — единый `Model/` (`*ResponseModel` при коллизии имён) |
| `Command::fromArray` с полями body | `*Model::fromArray` + `new Command($model, …context)` |
| criteria в Domain | Application Model + Port |
| `*RepositoryInterface` в Domain | Application `Port/` |
| `list` с фильтрами | отдельный `query` + `Search*` |
| Domain enum без суффикса (`TaskGroupStatus`) | с суффиксом `*Enum` (`TaskGroupStatusEnum`) |
| примитив с доменными правилами | Domain `ValueObject/` |

---

## Чеклист

1. Слой и **один** суффикс (кроме Domain UL: агрегат / VO / Domain enum)  
2. Handler наружу — **только Model \| void**
3. Payload — `*Model::fromArray`; Command/Query = intent + Model + context
4. Application Model — только внутри Application; Domain VO — только в Domain
5. Domain class VO — `ValueObject/`, уникальное UL-имя; enum — `Enum/`
6. Вход — Command/Query; `list` ≠ `Search*`/`query`
7. `*RepositoryInterface` — только в `Application/Port/`; возврат — Domain / Model / скаляр по роли метода (см. `Application/CLAUDE.md` → Port)
8. `CacheInterface` — только в `*Cache`
9. Async-постановка с правилами — `*Dispatcher` в `Application/Dispatcher/`, не в Handler и не в `*Service`
10. Preconditions с IO / auth — `*Policy` в `Application/Policy/`; Handler не содержит длинных цепочек `if/throw`; метод Policy с I/O — суффикс `Io`
11. DQL / CriteriaApplier — Infrastructure
12. `array` / `mixed` — только wire-граница; внутри — Model / VO; в Application tests — `new *Model(...)`, не `fromArray` и не `array $overrides`
13. Закрытое множество в поле Model — Domain enum или Application `*Enum`, не `string`
14. Перебор больших коллекций (User/Task ~10⁶) — `listBatch` / batch, не `listAll` (см. `Application/CLAUDE.md` → Масштаб)

---

## Тесты

Соглашения по `tests` (Integration-контроллеры: Get / Write / Delete / Query) — см. `tests/CLAUDE.md`.  
