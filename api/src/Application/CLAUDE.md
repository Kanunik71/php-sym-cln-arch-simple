# Application: layer-first + entity

В `Application/` первый уровень — **роль**. Внутри — сущность (`Task/`, `User/`, …) или `Common/` / `Http/`. Слоя `Domain/` нет: персистентная модель, VO, enum, события и исключения живут здесь.

```
Application/
├── Service/       # use case: Action (write), Query (read), Lifecycle
├── Model/         # persistence + request (Action/) + response (Read/)
├── Enum/          # закрытые множества, отдельно от Model
├── ValueObject/   # инварианты полей (Email, Phone)
├── Mapper/        # persistence *Model → response *ViewModel
├── Cache/         # политика ключей; единственный потребитель CacheInterface
├── Dispatcher/    # async intent: дедуп, lock, dispatch Event
├── Policy/        # preconditions use case + shared access
├── Port/          # контракты к Infrastructure
├── Event/         # факты для Messenger
└── Exception/     # coded-исключения use case
```

Action/Query наружу отдают **только response Model | void** (`*ViewModel`, page, list). Персистентная `*Model` (`UserModel`, `TaskModel`, …) — вход репозитория и Lifecycle, не JSON ответа.

---

## Service

```
Service/User/
├── Action/     # UserRegisterService, UserUpdateService, UserDeleteService, UserLoginService
├── Query/      # UserListQueryService, UserQueryService, UserSearchQueryService, UserSearchMetaQueryService
└── Lifecycle/  # UserLifecycleService — side effects после persist
```

| Папка | HTTP | Вход | Выход |
|-------|------|------|--------|
| **Action** | create, update, delete, login, register | `execute(*Model?, …context)` | `*ViewModel` \| `void` |
| **Query** | `list` / `get` / `query` | `execute(…)` | `*ViewModel`, `list`, page |
| **Lifecycle** | не вход контроллера | свой метод (`after*Persisted`, `recalculate*`, `deleteRecords`) или `execute` для job/CLI | `void` \| факты для вызывающего |

| Use case | HTTP | Сервис |
|----------|------|--------|
| список без criteria | `list()` | `*ListQueryService` |
| pagination + criteria + sort | `query()` | `*SearchQueryService` |
| одна сущность | `get()` | `*QueryService` |
| meta whitelist для `/query` | `queryMeta()` | `*SearchMetaQueryService` |

`list` фильтрами не расширяем. Criteria — только в `Search*`. Пилот: `UserController::query()` → `UserSearchQueryService`.

Контроллер не содержит use-case логики: `fromArray` → `execute`. Console парсит CLI и вызывает `*Service` (`RecalculateTaskGroupsConsole` → `TaskGroupRecalculateService`).

```
HTTP array → *Model::fromArray → Service::execute($model, …context) → *ViewModel | list | page | void
CLI / tests → new *Model(...)  → Service::execute($model, …context)
Get / List / Delete без body   → execute(userId:, currentUserId:) — request Model не заводить
```

Типичный Action: Policy → собрать / изменить `*Model` → `*Repository::save` → Lifecycle (событие, cache, dispatcher) → `*ViewModelMapper`.

---

## Model / Enum / ValueObject

```
Model/Common/ArrayableModelInterface.php       # fromArray / toArray
Model/Common/ArrayableModelListInterface.php   # fromArrayList / toArrayList
Model/Common/FileItemModel.php                 # id + url (response)
Model/Common/PaginationModel.php
Model/Common/SortModel.php
Model/Common/Filter/StringFilterModel.php
Model/Common/Filter/IntFilterModel.php
Model/Common/Filter/DateTimeFilterModel.php
Model/Common/Query/SearchQueryMetaModel.php
Model/Http/ErrorResponseModel.php

Model/User/UserModel.php                       # persistence
Model/User/UserLocationModel.php
Model/User/UserFilterCriterionModel.php        # ArrayableModelListInterface
Model/User/Action/UserUpdateModel.php          # request write
Model/User/Action/UserSearchModel.php          # request search
Model/User/Action/UserLoginModel.php
Model/User/Read/UserViewModel.php              # response
Model/User/Read/UserPageModel.php              # list<UserViewModel> + page meta
Model/User/Read/UserPaginatedModel.php         # list<UserModel> + total (из port, не arrayable)
Model/User/Read/UserRegisterResponseModel.php  # коллизия с UserRegisterModel

Model/Task/TaskModel.php
Model/Task/Action/TaskCreateModel.php
Model/Task/Read/TaskViewModel.php
Model/Task/Read/TaskLightModel.php

Enum/Common/FilterOperatorEnum.php
Enum/Common/SortDirectionEnum.php
Enum/User/UserFilterFieldEnum.php
Enum/User/UserSortFieldEnum.php
Enum/Task/TaskStatusEnum.php                   # бывший «доменный» enum — тоже здесь

ValueObject/Common/Email.php
ValueObject/Common/Phone.php
```

| Папка | Суффикс | В JSON из Action/Query? |
|-------|---------|-------------------------|
| `Model/{Entity}/*Model` | Model | нет — persistence / внутренний факт |
| `Model/{Entity}/Action/` | Model | нет — request |
| `Model/{Entity}/Read/` | Model | **да** — response |
| `Enum/` | Enum | только как поле Model |
| `ValueObject/` | нет | нет — скаляр через `toString()` на границе |

Имя VO = ubiquitous language (`Email`, `Phone`), без суффикса роли. Заводить при инварианте, не на каждое поле. Общие — `ValueObject/Common/`; контекстные — `ValueObject/{Entity}/`. Короткое имя уникально по всему `ValueObject/`.

---

## Files in Model

### Enum fields
- Закрытое множество в поле Model — typed enum, не `string`.
- Статус / тип сущности (`TaskStatusEnum`, `NotificationTypeEnum`, `NotificationChannelEnum`, …) и application-контракт (`FilterOperatorEnum`, …) — оба в `Application/Enum/`.
- `fromArray` — `InputAssertUtils::requiredEnum` / `enumList`.

### Read (response Model)
- Файл в ответе API — `FileItemModel` (`id` + `url`) в `Model/Common/`.
- Поля именуются по **роли** в сущности (`avatar`, `images`), не общим `files[]`.
- Один файл → `?FileItemModel`; коллекция → `list<FileItemModel>`.
- Пары `*FileId` + `*Url` в одном response Model не использовать.

### Write (Model)
- Payload write — `?string` / `list<string>` fileId (после upload).
- `FileItemModel` только на выходе Action/Query.

### Mapping
- `*ViewModelMapper::fromModel($model, FileDownloadUrlProviderInterface $urlProvider)`.
- Service не вызывает `buildDownloadUrl*`; URL строит mapper (`FileItemModelMapper::fromFileId` / `fromFileIds`).
- `FileDownloadUrlProviderInterface` — Application Port; реализация в Infrastructure.
- Read, которому нужен fetch-join (`AssetDetailModel`, `TaskViewModel` из репозитория), может собрать Infrastructure Doctrine mapper. Action/Query всё равно возвращает response Model.

---

## Port

Контракты к Infrastructure — в `Application/Port/`:

```
Port/User/UserRepositoryInterface.php
Port/Task/TaskRepositoryInterface.php
Port/TaskGroup/TaskGroupRepositoryInterface.php
Port/Asset/AssetRepositoryInterface.php
Port/File/FileRepositoryInterface.php
Port/File/FileStorageInterface.php
Port/File/FileDownloadUrlProviderInterface.php
Port/Notification/NotificationPreferenceRepositoryInterface.php
Port/Notification/MailerInterface.php
Port/Notification/SmsSenderInterface.php
Port/Notification/NotificationTemplateRendererInterface.php
Port/Security/TokenProviderInterface.php
Port/CacheInterface.php
Port/PasswordHasherInterface.php
Port/TransactionManagerInterface.php
```

- Persist / lookup / search — один `*RepositoryInterface` на сущность.
- Связи, нужные response Model (Asset tasks/files, Task users), грузит Infrastructure fetcher. Mapper не догружает lazy.
- Search с criteria / pagination / sort — `search(...)` (Application Model в сигнатуре). Сейчас: `UserRepositoryInterface::search` → `UserPaginatedModel`.
- Реализация — Infrastructure (`DoctrineUserRepository` и соседние адаптеры).

### Что возвращает Port / Repository

Entity **не** выходит наружу. По роли метода:

| Возврат | Когда | Пример |
|---------|--------|--------|
| **persistence `*Model`** | write / load для изменения | `findOrFail` → `UserModel`, `TaskGroupModel`; `save` → тот же `*Model` |
| **response / facts Model** | результат чтения, который не равен строке таблицы | `search` → `UserPaginatedModel`; `listTaskStatusCountsByIds` → `TaskStatusCountsModel`; `findDetailOrFail` → `AssetDetailModel` |
| **скаляр / void** | факт, мутация связи | `existsByEmail`, `containsTask`, `attachTask` |

Не заводить отдельный тип «под одну колонку SQL», если хватает скаляра или уже существующей Model. Action/Query наружу по-прежнему только **response Model | void**.

### Масштаб и batch-переборы

В проде `User` и `Task` (и связанные коллекции вроде `TaskGroup`) могут быть порядка **миллиона** строк. Долгие job’ы (пересчёт и подобные) могут идти **часами**.

| Можно | Нельзя |
|-------|--------|
| `find*` / `listByIds` / `search` с pagination | `listAll()` как полный scan миллиона моделей |
| chunk id → `listByIds($chunk)` → обработка | `foreach ($repo->listAll() as …)` в Console / Service / MessageHandler |
| `BatchUtils::each` / `BatchUtils::chunk` (`Shared/Utils`, default 1000) | держать весь набор моделей в памяти |

`listAll()` — только для **заведомо малых** выборок (тесты, узкий admin-list без гарантии роста). Для перебора «всех пользователей / тасок / групп»:

1. Порциями — `listBatch($offset, $limit)` (полный scan) или chunk известных id → `listByIds($chunk)`.
2. Обработать chunk; не копить результат всего прогона в одном массиве.

Эталон по известным id: `TaskGroupRecalculationService::recalculateByGroupIds` — `BatchUtils::each($ids, …)` + `listByIds`.
Эталон полного scan: `listBatch($offset, $limit)` в цикле (offset += limit, пока batch не пустой).

Presentation Console только парсит CLI и вызывает `*Service`; batch-оркестрация — в Lifecycle `*Service`.

---

## Mapper / Cache / Dispatcher / Policy

- `*ViewModelMapper` — persistence `*Model` → response `*ViewModel`
- `FileItemModelMapper` — `fileId` / `fileIds` → `FileItemModel`
- `*Cache` — единственные потребители `CacheInterface` (`TaskCache`, `TaskGroupCache`)
- `*Dispatcher` — «ставить ли async-задачу» (дедуп, нормализация, lock через `*Cache`) + `MessageBusInterface::dispatch`; не выполняет работу Lifecycle и не обрабатывает очередь
- `*Policy` — preconditions (auth, uniqueness, exists, use-case contract); методы `assert*` / `resolve*`; бросает `Application\Exception`
- CriteriaApplier / DQL — Infrastructure, не Service

### Policy

```
Policy/User/UpdateUserPolicy.php       # per use case
Policy/Task/TaskAccessPolicy.php       # shared access rule
```

| Тип | Именование | Пример |
|-----|------------|--------|
| per use case | `{Operation}{Entity}Policy` | `RegisterUserPolicy`, `ChangeTaskStatusPolicy` |
| shared access | `{Entity}AccessPolicy` | `TaskAccessPolicy`, `UserAccessPolicy`, `AssetAccessPolicy` |

**Суффикс метода `Io`:** публичный метод Policy с обращением к Repository, Port (кроме pure config из ctor), filesystem, HTTP или cache — `…Io` (`assertEmailAvailableIo`, `resolveAuthenticatedUserIo`, `assertUserCanAccessIo`, `assertParentExistsIo`). Без внешних вызовов — без суффикса (`assertSelfAccess`, `assertOwner`, `assertCanUpdate`).

Action: load `*Model` → `$policy->assert…()` → изменить модель → persist → Lifecycle → response Model.

### Delete orchestration

Удаление родителя **не** должно полагаться на `onDelete: CASCADE` или скрытый ORM cascade по другим агрегатам. Порядок (удалить / отвязать / переназначить) — в `*DeleteService` и Lifecycle `*Service`.

Типичный порядок:

1. Policy (доступ)
2. Удалить / отвязать дочерние агрегаты и owned-коллекции (`*Repository::delete` / `detach*` / …)
3. Side effects вне БД (object storage, cache) — после commit

Несколько записей в одном use case (`UserRegisterService`, `UserDeleteService`) — в `TransactionManagerInterface::run`. Object storage / cache / messenger — **после** commit.

```php
// эталон: multi-write + storage после commit (UserDeleteService)
$storageKeys = $this->transactionManager->run(function () use ($userId, $user): array {
    return $this->deleteDatabaseRecords($userId, $user);
});
$this->fileLifecycleService->purgeStorage($storageKeys);

// простой delete одного агрегата — AssetLifecycleService::delete (DB + storage)
$this->assetLifecycleService->delete($asset);
```

При удалении корня с несколькими зависимыми агрегатами (`UserDeleteService`) — явная оркестрация детей (assets, owned files, preferences, снятие пользователя с task), а не FK cascade. FK RESTRICT / violation = сигнал, что use case неполный.

Политика mapping: `api/src/Infrastructure/CLAUDE.md` → Cascade deletes;
`.cursor/rules/doctrine-cascade-deletes.mdc`.

---

## Event / Exception

```
Event/Task/TaskCreated.php
Event/User/UserRegisteredEvent.php
Event/User/UserUpdatedEvent.php
Event/TaskGroup/TaskGroupsRecalculationRequestedEvent.php
Event/TaskGroup/TaskGroupStatusChangedEvent.php

Exception/ErrorCodeEnum.php
Exception/CodedExceptionInterface.php
Exception/User/UserNotFoundException.php
```

- Event диспатчит Lifecycle или Dispatcher. Обработчик очереди — Infrastructure `*MessageHandler`, не Action.
- Exception use case — `Application/Exception/{Entity}/`, `extends DomainException`, `CodedExceptionInterface::getCodeId()` → `ErrorCodeEnum`.
- Ошибки контракта payload (тип, whitelist) — `InvalidArgumentException` на `fromArray` / сборке входа Service. Их ловит контроллер как 400.

---

## Payload Model → Service

```php
// body
$this->updateUser->execute(
    UserUpdateModel::fromArray($this->requestPayload($request)),
    userId: $id,
    currentUserId: $this->getCurrentUserId(),
);

// query string
$this->searchUsers->execute(UserSearchModel::fromArray($request->query->all()));

// без body
$this->deleteUser->execute(userId: $id, currentUserId: $this->getCurrentUserId());
```

- `*Model::fromArray` — wire → typed (типы / минимальный shape). Use-case contract без IO — в Service до записи (whitelist sort/filter). С IO — в `*Policy`.
- Инварианты поля — VO / persistence `*Model`, не повторная проверка типов в Service.
- Single wire Model — `ArrayableModelInterface`; criteria list — `ArrayableModelListInterface` (`fromArrayList` / `toArrayList`).
- Response Model — `ArrayableModelInterface` (`fromArray` для decode в тестах; наружу — public props). Page из port (`UserPaginatedModel`) — не arrayable.
- `toArray()` / `toArrayList()` — Integration и serde (wire-ключи).
- `array` / `mixed` — только на границе wire. В Service, Policy, Lifecycle и тестах Application — `new *Model(...)`, не `fromArray` и не `array $overrides`.

---

## Чеклист

1. Вход HTTP — `*Service::execute`; выход Action/Query — response Model | void
2. Payload → `*Model::fromArray`; без `Presentation\Request` и без Command/Query-обёртки
3. Persistence `*Model` отдельно от request (`Action/`) и response (`Read/`); при коллизии имён — `*ResponseModel`
4. Enum — `Enum/`; в полях Model — typed enum, не `string`
5. VO — `ValueObject/`, уникальное короткое имя, без суффикса роли
6. Repository — только `Application/Port/`; возврат persistence Model / response Model / скаляр; search с criteria — `search`
7. `list` без criteria; фильтры только в `query`
8. Policy: метод с I/O — суффикс `Io`; без I/O — без суффикса
9. Delete: межагрегатные дети и side effects — явно в `*DeleteService` / Lifecycle, не через DB/ORM cascade
10. Полный перебор User/Task/(связанных) — `listBatch` / `BatchUtils` + `listByIds`, не `listAll`; см. «Масштаб и batch-переборы»
11. Async-постановка — `*Dispatcher`; эффект после commit — Lifecycle или `*MessageHandler`
12. `CacheInterface` — только в `*Cache`
