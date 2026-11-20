# Архитектура `api/src` — соглашения

Короткий документ: **где что лежит, как называется, как течёт данные**.
Детали по слоям — в вложенных файлах:

| Файл | Про что |
|------|---------|
| [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md) | правила слоя Application: роли Service, три роли Model, VO, Port, оркестрация, delete, масштаб, `Utils` |
| [`src/Infrastructure/CLAUDE.md`](src/Infrastructure/CLAUDE.md) | Doctrine, кэш, очереди, security, storage, роутинг, notification |
| [`src/Presentation/CLAUDE.md`](src/Presentation/CLAUDE.md) | правила слоя Presentation: тонкий вход, границы импортов, маршруты и доступ, маппинг исключений → HTTP, формы команд |
| [`src/Shared/CLAUDE.md`](src/Shared/CLAUDE.md) | Shared: какие хелперы имеют право быть общими, что уехало в `Application/Utils` и `Presentation/Http` |
| [`tests/CLAUDE.md`](tests/CLAUDE.md) | базы тестов, фикстуры, правила интеграционных проверок |

---

## Слои

```
api/src/
├── Application/     # use case'ы: Service, Model, Policy, Port, Mapper, Cache, Event, Exception, Enum, ValueObject, Utils
├── Infrastructure/  # Doctrine, Redis, RabbitMQ, JWT, storage, адаптеры
├── Presentation/    # HTTP-контроллеры и консольные команды
└── Shared/Utils/    # кросс-слойная техника: UidUtils, DateUtils, CacheKeyUtils, ContainerUtils
```

Внутри `Application/` персистентная модель, Value Object, enum, события и
исключения живут рядом с use case'ами, отделённые папками; зависимости от
внешнего мира идут только через `Port/`.

### Правило зависимостей

```
Presentation  ──►  Application  ──►  Port  ◄──  Infrastructure
                                  └──►  Shared/Utils
```

- `Application` **не импортирует** `Infrastructure` — только `Port/`.
- `Infrastructure` реализует `Port/`, наружу отдаёт `Application/Model`.
- `Presentation/Controller` вызывает `Action`/`Query::execute`; `Console` и
  `MessageHandler` входят в Service.
- `Shared/Utils` — техника без привязки к слою (`UidUtils`, `DateUtils`,
  `CacheKeyUtils`, `ContainerUtils`); про домен и про wire-форматы не знает,
  `Application` не импортирует. Разбор wire-значений живёт в
  `Application/Utils/Wire`.

Проверка: `grep -rn "use App\\Infrastructure" api/src/Application` — пусто;
граница входа — `tests/Application/ArchitectureTest.php`.

---

## Таблица суффиксов

Каждый класс **в `Application/` и `Presentation/`** имеет ровно один суффикс,
однозначно определяющий его роль.

### Application

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Model** | Wire-структура: persistence, request, response, criteria, pagination, page | `UserModel`, `UserUpdateModel`, `UserPageModel`, `PaginationModel` |
| **Enum** | Закрытое множество значений | `TaskStatusEnum`, `FilterOperatorEnum` |
| **Vo** | Value Object: инвариант поля | `EmailVo`, `PhoneVo`, `MoneyVo` |
| **Exception** | Исключение use case с `codeId` | `TaskNotFoundException` |
| **Event** | Факт для Messenger | `UserUpdatedEvent` |
| **RepositoryInterface** | Контракт репозитория (Port) | `UserRepositoryInterface` |
| **Interface** | Прочий контракт | `CacheInterface`, `ArrayableModelInterface` |
| **Service** | `Action`/`Query` — use case, оркестрация; прочие подпапки — переиспользуемый шаг | `UserUpdateService`, `RefreshTokenService` |
| **Policy** | Предусловия use case (auth, uniqueness, exists) | `UpdateUserPolicy`, `TaskAccessPolicy` |
| **Cache** | Политика ключей и TTL | `TaskGroupSummaryCache` |
| **Mapper** | Application `*Model` → response `*Model` | `UserViewModelMapper`, `FileItemModelMapper` |
| **Utils** | Чистые функции: `Utils/Wire` — разбор wire-значений и whitelist, `Utils/Collection` — массивы, батчи, enum | `InputAssertUtils`, `ArrayUtils` |

### Infrastructure

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Entity** | Модель таблицы БД (Doctrine) | `UserEntity` |
| **Mapper** | `*Model` ↔ Entity | `UserMapper` |
| **Repository** | Реализация `*RepositoryInterface` | `DoctrineUserRepository` |
| **Adapter** | Обёртка над библиотекой | `LocalFileStorageAdapter`, `SymfonyMailerAdapter`, `TaskGroupLock` |
| **Provider** | Внешний ресурс | `LexikTokenProvider`, `SymfonyFileDownloadUrlProvider` |
| **MessageHandler** | Асинхронный обработчик Event | `UserUpdatedMessageHandler` |
| **Service** | Техническая реализация | `RedisCacheService` |

### Presentation / Shared

| Суффикс | Назначение | Пример |
|---------|-----------|--------|
| **Controller** | HTTP | `UserController` |
| **Console** | CLI | `RecalculateTaskGroupsConsole` |
| **Utils** | Статические хелперы (живут только в `Shared`) | `UidUtils`, `DateUtils` |

### Без суффикса

| Слой | Что | Пример |
|------|-----|--------|
| Infrastructure | Framework-coupled имена | `SymfonyPasswordHasher`, `TwigEnvironmentFactory`, `SecurityUser` |
| Infrastructure | Вспомогательные семейства Doctrine | `*EntityFetcher`, `*Applier`, `*DoctrineMap`, `Doctrine*Utils` |

**Отклонения от правила в текущем коде** (не чинить здесь — фиксируются как
change, если понадобится):

- `SymfonyPasswordHasher` — обёртка над библиотекой без суффикса `Adapter`.

---

## Application: вход / выход / внутри

```
HTTP         → *Model::fromArray  → Action/Query::execute($model, …context) → response Model | void
CLI / worker → аргументы/handler  → Service-метод(...)                      → Model | скаляр | void
```

| Слой | Роль | Выход наружу |
|------|------|--------------|
| **Model** | request + response (JSON) | да, response Model |
| **Enum** | закрытое множество | только как поле Model |
| **Action** / **Query** | use case (вход из Controller или другого use case) | **только** response Model \| void |
| **Service** | переиспользуемый шаг (вход из use case, Console, MessageHandler) | Model \| скаляр \| void |
| **Policy** | предусловия (auth, uniqueness, exists) | ничего (бросает исключение) |
| **Port** | контракт к Infrastructure | — |

**Use case — это `Action` и `Query`.** Других ролей входа нет; всё остальное в
`Service/{Entity}/` — Service, имена подпапок (`Lifecycle`, `Auth`, `Common`,
новые) роли не задают. Граница входа:

| Подпапка | Вход | Вход из | Выход |
|----------|------|---------|-------|
| **Action** | `execute(*Model?, …context)` | Controller, Action/Query | `*ViewModel` \| void |
| **Query** | `execute(…)` | Controller, Action/Query | `*ViewModel`, list, page |
| **Service** (прочие) | свой метод (`after*Persisted`, `recalculate*`) или `execute` для job | Action/Query, Console, `*MessageHandler` | void \| факты для вызывающего |

Service не вызывает use case, Controller не вызывает Service, job не вызывает
use case. Транзакцию открывает вход (use case или job), Service — только
участвует. Проверяется тестом `tests/Application/ArchitectureTest.php`.

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

`Action/` и `Read/` — база; другие подпапки `Model/{Entity}/` — логическая
группировка, роль модели определяется назначением.

- Persistence `*Model` наружу не сериализуется — только `Read/*ViewModel`.
- Entity не покидают Infrastructure; в поле Model — typed enum, не `string`.
- Роли различаются именами: `*ResponseModel` — один из вариантов, не шаблон.

Правила Model, Enum и VO — в [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md#model).

---

## Mapper

| Слой | Суффикс | Направление | Пример |
|------|---------|-------------|--------|
| Application | `*Mapper` | persistence `*Model` → response `*Model` | `UserViewModelMapper` |
| Infrastructure | `*Mapper` | `*Model` ↔ Entity | `UserMapper` |

Слой различается папкой, а не суффиксом: `Application/Mapper/` против
Infrastructure `Persistence/Doctrine/Mapper/`. Инфикс `Model` в имени — часть
имени маппируемого типа (`UserViewModel`, `FileItemModel`), а не обязательная
часть суффикса.

`FileItemModelMapper` — особый случай: `fileId` / `fileIds` → `FileItemModel`
(собирает подписанный URL).

---

## Детали Application

Карта тем; правила — в [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md).

| Тема | Раздел |
|------|--------|
| Service: Action / Query / Lifecycle, `list` ≠ `query` | [Service](src/Application/CLAUDE.md#service) |
| Model, Enum, ValueObject | [Model](src/Application/CLAUDE.md#model) |
| Port: что и когда возвращает | [Port](src/Application/CLAUDE.md#port) |
| Mapper, Cache, Policy, Event, Exception | [Оркестрация](src/Application/CLAUDE.md#оркестрация) |
| Удаление агрегатов и транзакции | [Delete orchestration](src/Application/CLAUDE.md#delete-orchestration) |
| Масштаб, keyset, стриминг | [Масштаб и batch-переборы](src/Application/CLAUDE.md#масштаб-и-batch-переборы) |
| `array`/`mixed` только на wire | [Границы типизации](src/Application/CLAUDE.md#границы-типизации) |

---

## Чеклист

1. Слой и **один** суффикс (кроме framework-coupled имён Infrastructure)
2. `Presentation → Application → Port ← Infrastructure`: Application не импортирует Infrastructure
3. Use case — `Action`/`Query`; `execute` наружу — **только** response Model \| void
4. Граница входа: Controller → `Action`/`Query`, job → Service; Service не зовёт use case; транзакция — у входа
5. Persistence `*Model` отдельно от request (`Action/`) и response (`Read/`); Entity не выходит из Infrastructure
6. Enum — `Application/Enum/`, в поле Model — typed enum
7. `*RepositoryInterface` — только в `Application/Port/`; наружу Model или скаляр
8. `CacheInterface` — только в `*Cache`; кеш владеет ключом и TTL; async — через `Port\Messaging\MessageBusInterface`
9. Каскад в БД — только по белому списку `Infrastructure/CLAUDE.md`; `cascade remove` — только внутри одного агрегата
10. Перебор больших коллекций — только в фоне, keyset + `BatchUtils::stream`; в HTTP — пагинация или `listByIds`
11. Детальные правила Application — в [`src/Application/CLAUDE.md`](src/Application/CLAUDE.md#чеклист)

---

## Чего в этом документе нет

- **`Command` / `Query` / `*CommandHandler` / `*QueryHandler`** — вход use case'а
  это `*Model` + контекст, обёртка в отдельный класс не используется.
- **`*RepositoryInterface` в Domain** — репозитории только в `Application/Port/`.
- **Presentation/Request** — отдельного слоя нет; payload = `*Model`.
- **Списка файлов и классов** — это состояние кода, а не архитектура.
