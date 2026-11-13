# Архитектура `api/src` — соглашения

Короткий документ: **где что лежит, как называется, как течёт данные**.
Детали по слоям — в вложенных файлах:

| Файл | Про что |
|------|---------|
| [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md) | use case'ы, Model, Port, Policy, Cache, Dispatcher, batch-переборы, delete-оркестрация |
| [`src/Infrastructure/CLAUDE.md`](src/Infrastructure/CLAUDE.md) | Doctrine, кэш, очереди, security, storage, роутинг, notification |
| [`tests/CLAUDE.md`](tests/CLAUDE.md) | базы тестов, фикстуры, правила интеграционных проверок |

---

## Слои

```
api/src/
├── Application/     # use case'ы: Service, Model, Policy, Port, Mapper, Cache, Dispatcher, Event, Exception, Enum, ValueObject
├── Infrastructure/  # Doctrine, Redis, RabbitMQ, JWT, storage, адаптеры
├── Presentation/    # HTTP-контроллеры и консольные команды
└── Shared/Utils/    # статические утилиты (Asserts, Filter, Http)
```

Слоя `Domain/` нет. Персистентная модель, Value Object, enum, события и
исключения живут в `Application/` — это осознанное решение, а не недоработка:
VO и инварианты отделены от use case'ов папками, а зависимости от внешнего
мира идут только через `Port/`.

### Правило зависимостей

```
Presentation  ──►  Application  ──►  Port  ◄──  Infrastructure
                                  └──►  Shared/Utils
```

- `Application` **не импортирует** `Infrastructure` — только `Port/`.
- `Infrastructure` реализует `Port/`, наружу отдаёт `Application/Model`.
- `Presentation` читает `Application` и вызывает `*Service::execute`.
- `Shared/Utils` — чистые статические хелперы, о доменах не знают.

Проверка: `grep -rn "use App\\Infrastructure" api/src/Application` — пусто.

---

## Таблица суффиксов

Каждый класс **в `Application/` и `Presentation/`** имеет ровно один суффикс,
однозначно определяющий его роль.

### Application

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Model** | Wire VO: persistence, request, response, criteria, pagination, page | `UserModel`, `UserUpdateModel`, `UserPageModel`, `PaginationModel` |
| **Enum** | Закрытое множество значений | `TaskStatusEnum`, `FilterOperatorEnum` |
| **Exception** | Исключение use case с `codeId` | `TaskNotFoundException` |
| **Event** | Факт для Messenger | `UserUpdatedEvent` |
| **RepositoryInterface** | Контракт репозитория (Port) | `UserRepositoryInterface` |
| **Interface** | Прочий контракт | `CacheInterface`, `ArrayableModelInterface` |
| **Service** | Use case: оркестрация | `UserUpdateService` |
| **Policy** | Предусловия use case (auth, uniqueness, exists) | `UpdateUserPolicy`, `TaskAccessPolicy` |
| **Cache** | Политика ключей кэша | `TaskGroupCache` |
| **Dispatcher** | Постановка async-работы: дедуп, lock, dispatch | `TaskGroupRecalculationDispatcher` |
| **ModelMapper** | Persistence `*Model` → response `*Model` | `UserViewModelMapper`, `FileItemModelMapper` |

### Infrastructure

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Entity** | Модель таблицы БД (Doctrine) | `UserEntity` |
| **Mapper** | `*Model` ↔ Entity | `UserMapper` |
| **Repository** | Реализация `*RepositoryInterface` | `DoctrineUserRepository` |
| **Adapter** | Обёртка над библиотекой | `LocalFileStorageAdapter`, `SymfonyMailerAdapter` |
| **Provider** | Внешний ресурс | `LexikTokenProvider`, `SymfonyFileDownloadUrlProvider` |
| **MessageHandler** | Асинхронный обработчик Event | `UserUpdatedMessageHandler` |
| **Service** | Техническая реализация | `RedisCacheService` |

### Presentation / Shared

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Controller** | HTTP | `UserController` |
| **Console** | CLI | `RecalculateTaskGroupsConsole` |
| **Utils** | Статические утилиты | `UidUtils`, `BatchUtils` |

### Без суффикса

| Слой | Что | Пример |
|------|-----|--------|
| Application | Value Object — имя = Ubiquitous Language | `Email`, `Phone` |
| Infrastructure | Framework-coupled имена | `SymfonyPasswordHasher`, `TwigEnvironmentFactory`, `SecurityUser` |
| Infrastructure | Вспомогательные семейства Doctrine | `*EntityFetcher`, `*Applier`, `*DoctrineMap`, `Doctrine*Utils` |

**Отклонения от правила в текущем коде** (не чинить здесь — фиксируются как
change, если понадобится):

- `SymfonyPasswordHasher` — обёртка над библиотекой без суффикса `Adapter`.

---

## Application: вход / выход / внутри

```
HTTP array → *Model::fromArray → *Service::execute($model, …context) → response Model | void
CLI        → new *Model(...)    → *Service::execute($model, …context) → response Model | void
```

| Слой | Роль | Выход наружу |
|------|------|--------------|
| **Model** | request + response (JSON) | да, response Model |
| **Enum** | закрытое множество | только как поле Model |
| **Service** | use case | **только** response Model \| void |
| **Policy** | предусловия (auth, uniqueness, exists) | ничего (бросает исключение) |
| **Port** | контракт к Infrastructure | — |

`Action`/`Query`/`Lifecycle` — подпапки `Service/{Entity}/`, различаются
входом, а не типом:

| Подпапка | HTTP | Вход | Выход |
|----------|------|------|-------|
| **Action** | create, update, delete, login | `execute(*Model?, …context)` | `*ViewModel` \| void |
| **Query** | list / get / query | `execute(…)` | `*ViewModel`, list, page |
| **Lifecycle** | не вход контроллера | свой метод (`after*Persisted`, `recalculate*`) | void |

Подробности — в [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md).

---

## Model

Три роли в одном суффиксе `Model`, различаются папкой:

| Размещение | Роль | Пример |
|-----------|------|--------|
| `Model/{Entity}/` | Persistence — зеркало колонок, вход репозитория | `UserModel`, `TaskModel` |
| `Model/{Entity}/Action/` | Request (запись) | `UserUpdateModel`, `TaskCreateModel` |
| `Model/{Entity}/Read/` | Response (чтение) | `UserViewModel`, `TaskPageModel` |
| `Model/Common/` | Общее: пагинация, сортировка, фильтры, meta | `PaginationModel`, `SearchQueryMetaModel` |
| `Model/Http/` | Формат ошибки | `ErrorResponseModel` |

- Persistence `*Model` наружу не сериализуется — только `Read/*ViewModel`.
- Entity не покидают Infrastructure.
- Enum в поле Model — typed enum, не `string`.

---

## Mapper

| Слой | Суффикс | Направление | Пример |
|------|---------|-------------|--------|
| Application | `*ModelMapper` | persistence `*Model` → response `*Model` | `UserViewModelMapper` |
| Infrastructure | `*Mapper` | `*Model` ↔ Entity | `UserMapper` |

`FileItemModelMapper` — особый случай: `fileId` / `fileIds` → `FileItemModel`
(собирает подписанный URL).

---

## Port

Контракты к Infrastructure — в `Application/Port/`, реализации — в
`Infrastructure/`. Persist / lookup / search — один `*RepositoryInterface`
на сущность.

Возврат из репозитория — по роли метода:

| Возврат | Когда | Пример |
|---------|-------|--------|
| persistence `*Model` | write / load для изменения | `findOrFail` → `UserModel` |
| response / facts Model | результат чтения, не равный строке таблицы | `search` → `UserPaginatedModel` |
| скаляр / void | факт, мутация связи | `existsByEmail`, `attachTask` |

Entity наружу не выходит.

---

## Cache / Dispatcher / Policy

- `CacheInterface` — только в `*Cache` (`TaskGroupCache`). `*Dispatcher` берёт
  lock через `*Cache`, а не через `CacheInterface` напрямую.
- `*Dispatcher` — решает «ставить ли async-задачу» (дедуп, нормализация,
  lock) и делает `MessageBusInterface::dispatch`. Работу не выполняет
  (`*Service`), очередь не обрабатывает (`*MessageHandler`).
- `*Policy` — предусловия use case: auth, uniqueness, exists, use-case
  contract. Бросает `Application/Exception`. Не персистит и не возвращает
  response Model.
- Метод `*Policy` с обращением к Repository, Port, filesystem, HTTP или
  cache — суффикс **`Io`** (`assertEmailAvailableIo`); без внешних вызовов —
  без суффикса (`assertSelfAccess`).

---

## Нетипизированные данные

`array` и `mixed` — **только на границе** (HTTP body, JSON decode, wire
serde). Внутри Application, Policy, Service и тестов — typed Model, VO, enum.

| Где | Как |
|-----|-----|
| Presentation (контроллер) | `requestPayload()` → `*Model::fromArray` → `*Service::execute` |
| Application (Service, Policy) | typed Model, VO — без `array<string, mixed>` в сигнатурах и полях |
| CLI / Application-тесты | `new *Model(...)` — **не** `fromArray`, не хелперы с `array $overrides` |
| Integration-тесты | `*Model::toArray()` — только для тела HTTP-запроса |

**Избегать:** payload / overrides как `array` между слоями; `fromArray` вне
wire-границы; «магические» ключи массива там, где уже есть конструктор Model.

---

## list vs query (HTTP)

| Метод | HTTP | Use case | Назначение |
|-------|------|----------|-----------|
| `list()` | `GET /api/{resource}` | `*ListQueryService` | простой список |
| `query()` | `GET /api/{resource}/query` | `*SearchQueryService` | pagination + criteria + sort |

`list` фильтрами не расширяем. Criteria — только в `Search*`.
Пилот: `UserController::query()` → `UserSearchQueryService`.

---

## Масштаб

Ориентир — до ~10⁶ `User` и ~10⁶ `Task`. Без `listAll()`.

- **HTTP-эндпоинт** — полный перебор запрещён: ответ пагинирован (`search` с `PaginationModel`) или работает по известным id (`listByIds`).
- **Фоновая задача** — полный перебор разрешён, но только keyset-обходом и стримингом: `BatchUtils::stream` + `listBatchBefore` / `listByIds`. `OFFSET`-пагинация для обхода квадратична и недопустима.

Эталон полного обхода — `TaskGroupSummarizeQueryService`; эталон по известным id — `TaskGroupRecalculationService`.
Подробно — в [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md) → «Масштаб и batch-переборы».

---

## Чеклист

1. Слой и **один** суффикс (кроме VO и framework-coupled имён Infrastructure)
2. `*Service::execute` наружу — **только** response Model \| void
3. Payload — `*Model::fromArray`; `array`/`mixed` — только wire-граница
4. Persistence `*Model` отдельно от request (`Action/`) и response (`Read/`)
5. Enum — `Application/Enum/`; в полях Model — typed enum, не `string`
6. VO — `Application/ValueObject/`, уникальное короткое имя
7. `list` без criteria; фильтры — только в `query`
8. `*RepositoryInterface` — только в `Application/Port/`; Entity наружу не выходит
9. `CacheInterface` — только в `*Cache`
10. Async-постановка — `*Dispatcher`; эффект после commit — Lifecycle или `*MessageHandler`
11. Preconditions с IO / auth — `*Policy`; метод с I/O — суффикс `Io`
12. DQL / CriteriaApplier — только в Infrastructure
13. Межагрегатный `onDelete: CASCADE` — нет; `cascade remove` — только внутри одного агрегата
14. Перебор больших коллекций — только в фоновых задачах, keyset-обходом (`BatchUtils::stream` + `listBatchBefore` / `listByIds`); в HTTP-эндпоинтах — только пагинация или `listByIds`

---

## Чего в этом документе нет

- **Слоя `Domain/`** — нет и не планируется; см. «Слои».
- **`Command` / `Query` / `*CommandHandler` / `*QueryHandler`** — вход use case'а
  это `*Model` + контекст, обёртка в отдельный класс не используется.
- **`*RepositoryInterface` в Domain** — репозитории только в `Application/Port/`.
- **Presentation/Request** — отдельного слоя нет; payload = `*Model`.
