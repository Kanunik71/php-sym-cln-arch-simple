# Infrastructure: Layer-first

В `Infrastructure/` каталоги группируются **по техническому слою** (что делает адаптер), а не по сущности.

`Application/` тоже layer-first, но первый уровень там — роль use case (`Service/`, `Model/`, `Port/`, …), и уже внутри — сущность. Здесь первый уровень — технология. Класть `Infrastructure/Task/` нельзя.

```
# ❌ сущность на первом уровне Infrastructure
Infrastructure/Task/DoctrineTaskRepository.php
Infrastructure/User/DoctrineUserRepository.php

# ✅ слой, затем технология и роль
Infrastructure/Persistence/Doctrine/Repository/DoctrineTaskRepository.php
Infrastructure/Persistence/Doctrine/Repository/DoctrineUserRepository.php
```

Новый класс кладётся в существующий слой по роли. Новый первый уровень появляется только для новой технической задачи (хранилище, брокер, внешний API).

---

## Слои

```
Infrastructure/
├── Persistence/    # БД: Entity, Mapper, Repository, транзакции
├── Cache/          # Redis-адаптер CacheInterface
├── Lock/           # мьютексы на symfony/lock
├── Messaging/      # async MessageHandler по сущности
├── Scheduler/      # периодические задачи (Symfony Scheduler)
├── Security/       # JWT, пароли, Symfony Security
├── Storage/        # файловое хранилище
├── Routing/        # URL скачивания файлов
└── Notification/   # почта, SMS, Twig-шаблоны
```

### Persistence

Хранение Application Model в БД. Сейчас единственная технология — Doctrine; при появлении другой (например Elasticsearch) она станет соседней папкой рядом с `Doctrine/`.

```
Persistence/Doctrine/
├── DoctrineTransactionManager.php   # TransactionManagerInterface
├── EntityReferenceAdapter.php       # getReference для FK без загрузки агрегата
├── Entity/                          # модели таблиц
├── Mapper/                          # Application *Model ↔ Entity
├── Repository/                      # реализации Application *RepositoryInterface
├── Utils/                           # статические хелперы под Doctrine
└── Query/
    ├── Common/                      # DoctrineFilterApplier, DoctrinePaginationApplier
    └── {Entity}/                    # fetcher, criteria applier, *DoctrineMap
```

| Папка | Что лежит | Примеры |
|-------|-----------|---------|
| корень `Doctrine/` | транзакции и ссылки на Entity | `DoctrineTransactionManager`, `EntityReferenceAdapter` |
| `Entity/` | Doctrine-сущности таблиц | `TaskEntity`, `TaskUserEntity`, `UserEntity`, `AssetEntity`, `AssetFileEntity`, `FileEntity`, `TaskGroupEntity`, `UserNotificationPreferenceEntity` |
| `Mapper/` | `*Model` ↔ Entity (`toModel` / `toEntity`, для read-связей — `toViewModel` / `toDetailModel`) | `TaskMapper`, `UserMapper`, `AssetMapper`, `FileMapper`, `TaskGroupMapper`, `NotificationPreferenceMapper` |
| `Repository/` | Application Port (`*RepositoryInterface`) | `DoctrineUserRepository`, `DoctrineAssetRepository`, `DoctrineFileRepository`, `DoctrineTaskRepository`, `DoctrineTaskGroupRepository`, `DoctrineNotificationPreferenceRepository` |
| `Utils/` | статические Doctrine-хелперы | `DoctrineUuidUtils`, `DoctrineAssociationAssertUtils`, `DoctrineCollectionAssertUtils` |
| `Query/Common/` | общий operator→DQL, pagination/sort | `DoctrineFilterApplier`, `DoctrinePaginationApplier` |
| `Query/{Entity}/` | fetcher + field→DQL map + criteria applier | `UserEntityFetcher`, `UserListCriteriaApplier`, `UserFilterFieldDoctrineMap`, `AssetEntityFetcher`, `TaskEntityFetcher`, `FileEntityFetcher`, `TaskGroupEntityFetcher`, `NotificationPreferenceEntityFetcher` |

Application `*FilterFieldEnum` — whitelist API filter-полей (value, type). `*SortFieldEnum` — whitelist sort-полей.
**DQL path** — только в Infrastructure `*FilterFieldDoctrineMap` / `*SortFieldDoctrineMap`.

**DQL / QueryBuilder:** имена свойств Entity не хардкодить строками — брать из `*Entity::FIELD_*`
(Doctrine property name, не SQL column). Пример: `UserEntity::FIELD_EMAIL`, `AssetEntity::FIELD_OWNER`.
Алиасы QB (`'a'`, `'u'`) — локальные литералы, ок. API whitelist полей — Application `*Enum` + `*DoctrineMap`,
не `FIELD_*` напрямую из Application.

**SQL / DBAL (Connection):** имена таблиц и колонок БД не хардкодить строками — брать из констант Entity:

| Константа | Что | Пример |
|-----------|-----|--------|
| `TABLE` | имя таблицы | `TaskEntity::TABLE` |
| `COLUMN_*` | SQL column name | `TaskEntity::COLUMN_STATUS` |
| `JOIN_TABLE_*` | join-таблица (на owning Entity) | `TaskGroupEntity::JOIN_TABLE_TASK` |
| `JOIN_COLUMN_*` | колонка join-таблицы | `TaskGroupEntity::JOIN_COLUMN_TASK_ID` |

`FIELD_*` — только DQL/property. `COLUMN_*` / `TABLE` / `JOIN_*` — только raw SQL.
В `#[ORM\Table]` / `#[ORM\Column(name: …)]` / JoinColumn — тоже константы, не дублировать литералы.
Результат `fetch*`: ключи row — через те же `COLUMN_*` / `JOIN_COLUMN_*`.
Алиасы SQL (`tgt`, `t`) и computed-алиасы (`task_count`) — литералы, ок.

Репозиторий снаружи отдаёт Application `*Model` / `*ViewModel` / скаляр, внутри работает с Entity. Mapper — единственное место, где Model встречается с Doctrine Entity. Связи на запись — через `EntityReferenceAdapter::getReference`, без lazy-load.

**Гидратация:** fetcher загружает ровно те связи, которые читает mapper. `toModel` для light-пути не трогает коллекции. Неинициализированная коллекция на view/detail → `LogicException` (`DoctrineCollectionAssertUtils`). Пустая коллекция ок. Inverse-связи, не нужные модели, не грузим.

| Модель | Fetcher | Что загружено |
|--------|---------|----------------|
| Asset | `AssetEntityFetcher` | light: корень (`ownerId` scalar); view / detail: `tasks`, `assetFiles` |
| Task | `TaskEntityFetcher` | light: корень (`parentId` scalar); view: `taskUsers` (`userId` scalar) |
| User | `UserEntityFetcher` | корень (`avatarFileId` scalar) |
| File | `FileEntityFetcher` | корень (`ownerId` scalar) |
| NotificationPreference | `NotificationPreferenceEntityFetcher` | корень (`userId` scalar) |
| TaskGroup | `TaskGroupEntityFetcher` | корень (`ownerId` scalar) |

Application Model (criteria, pagination) читается в Infrastructure только в search/query path (`*CriteriaApplier`, search repository).

#### Каскадные удаления

Удаление видно в коде: БД не удаляет данные неявно, если из этого следует
вычисляемое состояние другого агрегата.

Каскад в БД (`ON DELETE CASCADE` / `SET NULL`) допустим, если удаление связи
**не меняет вычисляемое состояние** другого агрегата (статус, счётчик, прогресс,
кеш) и Application **не обязан реагировать** на её удаление. Тогда связь —
чистая link-строка или nullable-ссылка, и каскад остаётся в схеме.

| FK | Правило | Почему |
|----|---------|--------|
| `task_group_task.task_group_id` | `CASCADE` | удаляется сама группа, её статус никому не нужен; каскад покрывает bulk-удаление групп (DQL) в обход ORM |
| `asset_task.asset_id` | `CASCADE` | удаляется сам ассет, его прогресс не важен |
| `tasks.parent_id` | `SET NULL` | статус задачи из иерархии не вычисляется, кеша нет — подзадача остаётся корневой |
| `users.avatar_file_id` | `SET NULL` | аватар — не вычисляемое поле; удаление файла не ломает удаление пользователя |
| `task_group_task.task_id` | **NO ACTION** | удаление связи меняет статус группы → Application отвязывает явно и запрашивает пересчёт |
| `asset_task.task_id` | **NO ACTION** | удаление связи меняет прогресс ассета → то же |
| `*.owner_id` | **NO ACTION** | удаление агрегата — решение use case (`UserDeleteService` и др.) |

Удаление и побочные эффекты **между агрегатами** — ответственность Application `*Service`, не FK и не молчаливый ORM cascade. Infrastructure отражает уже принятое решение и чистит **внутренности** одного агрегата.

| Механизм | Где | Когда допустим |
|----------|-----|----------------|
| `onDelete: 'CASCADE'` на `JoinColumn` | FK в БД | только по таблице выше: link-строка без своих атрибутов, удаление которой ничего не вычисляет |
| `onDelete: 'SET NULL'` | опциональный FK | только для nullable-ссылок, чьё значение не вычисляется из других данных: `Task.parent`, `User.avatarFile` |
| `cascade: ['remove']` / `orphanRemoval: true` | Doctrine ORM | **Только** коллекции того же aggregate root: `Task` → `TaskUser`, `Asset` → `AssetFile`. |

```php
// ❌ каскад по связи, удаление которой меняет вычисляемое состояние
// Application не увидит, что связь исчезла, и не пересчитает статус группы
#[ORM\InverseJoinColumn(name: self::JOIN_COLUMN_TASK_ID, referencedColumnName: TaskEntity::COLUMN_ID, onDelete: 'CASCADE')]

// ✅ FK без cascade; TaskDeleteService явно отвязывает задачу от групп и ассетов
#[ORM\InverseJoinColumn(name: self::JOIN_COLUMN_TASK_ID, referencedColumnName: TaskEntity::COLUMN_ID)]
```

```php
// ✅ внутри агрегата — Application вызывает assetRepository->delete(); link-rows чистит mapping
#[ORM\OneToMany(..., cascade: ['persist', 'remove'], orphanRemoval: true)]
private Collection $assetFiles;
```

Side effects (object storage, cache, events) **никогда** не делегировать БД-cascade — только Application.
Эталон: `AssetLifecycleService::delete` → `deleteRecords` (БД) и `FileLifecycleService::purgeStorage` после commit.

Межагрегатные связи Application отвязывает **явно, до `delete()`**: `TaskDeleteService`
снимает связь с группами и ассетами и удаляет задачу в одной транзакции — иначе
FK violation вместо тихой потери связи.

Оркестрация delete — `api/src/Application/AGENTS.md` → Delete orchestration.

### Cache

Реализация `CacheInterface` (Application Port). Не знает о Task/User/Asset — только ключ, TTL и callback. Потребителей в Application сейчас нет: роль `*Cache` пуста, порт и адаптер оставлены как готовая инфраструктура.

| Класс | Роль |
|-------|------|
| `RedisCacheService` | Адаптер Symfony Cache / Redis |

### Lock

Адаптеры Application-портов блокировок на `symfony/lock`. Один named resource `task_group_recalc`: Redis в dev/prod, `in-memory` в тестах (`config/packages/lock.yaml`).

Ключи лока и TTL — деталь реализации: Application знает только «канал пересчёта» (задача или набор групп), но не формат ключа.

| Класс | Роль |
|-------|------|
| `TaskGroupLock` | `TaskGroupLockInterface`: ресурс `task_group_recalc.task.{taskId}` / `task_group_recalc.groups.{sha256(sorted ids)}`, TTL 120 с, `acquire(blocking: false)` |
| `SymfonyLockHandle` | `refresh()` для длинной работы и `release()` в `finally` |

### Messaging

Асинхронная реакция на Application Event через Symfony Messenger. Это не use case: `*MessageHandler` вызывает Application `*Service` (и при необходимости берёт лок).

Внутри `Messaging/` группировка по сущности, затем роль:

```
Messaging/
├── MessengerMessageBus.php       # MessageBusInterface (Port) на Symfony Messenger
├── Task/Handler/TaskCreatedMessageHandler.php
├── TaskGroup/Handler/
│   ├── RecalculateTaskGroupsByTaskMessageHandler.php
│   └── RecalculateTaskGroupsByGroupIdsMessageHandler.php
└── User/Handler/
    ├── UserRegisteredMessageHandler.php
    └── UserUpdatedMessageHandler.php
```

| Класс | Роль |
|-------|------|
| `MessengerMessageBus` | Реализация Application-порта `MessageBusInterface`: единственная точка, где Application достаёт Symfony Messenger |
| `TaskCreatedMessageHandler` | Логирует `TaskCreatedEvent` |
| `RecalculateTaskGroupsByTaskMessageHandler` | Пересчёт групп задачи: лок канала «по задаче», `release()` в `finally` |
| `RecalculateTaskGroupsByGroupIdsMessageHandler` | Пересчёт указанных групп: лок канала «по набору групп», `release()` в `finally` |
| `UserRegisteredMessageHandler` | `NotificationService::notify` (`UserRegistered`) |
| `UserUpdatedMessageHandler` | `NotificationService::notify` (`UserUpdated`) |

### Scheduler

Периодические задачи через Symfony Scheduler. Расписание — `Infrastructure/Scheduler/AppScheduleProvider.php`; запуск — worker `messenger:consume async scheduler_default` (сервис `messenger-worker`, profile `workers`). Периодичность — parameter `app.scheduler.*` / env `SCHEDULER_*_CRON`; задачи вызывают console commands через `RunCommandMessage`.

Сейчас одно задание: `app.scheduler.file_cleanup_cron` → `app:files:cleanup-tmp`.

### Security

Адаптеры аутентификации: JWT, хеширование паролей, мост Symfony Security ↔ `UserModel`.

| Класс | Роль |
|-------|------|
| `LexikTokenProvider` | Выдаёт JWT (`TokenProviderInterface`) |
| `SymfonyPasswordHasher` | Хеш / проверка пароля (`PasswordHasherInterface`) |
| `DoctrineUserProvider` | `UserProviderInterface`: грузит пользователя через `UserRepositoryInterface` |
| `SecurityUser` | `UserInterface` Symfony из `id` / `email` / `passwordHash` |

### Storage

| Класс | Роль |
|-------|------|
| `LocalFileStorageAdapter` | Локальная ФС (`FileStorageInterface`: write / read / delete по `storageKey`) |

### Routing

| Класс | Роль |
|-------|------|
| `SymfonyFileDownloadUrlProvider` | Абсолютный URL скачивания (`FileDownloadUrlProviderInterface`, маршрут `files_download`) |

### Notification

Внешние каналы и рендер шаблонов. Решение «кому и что слать» остаётся в Application `NotificationService`.

```
Notification/
├── Adapter/     # MailerInterface, SmsSenderInterface
└── Template/    # Twig NotificationTemplateRendererInterface
```

| Класс | Роль |
|-------|------|
| `SymfonyMailerAdapter` | Письмо через Symfony Mailer |
| `LoggingSmsSenderAdapter` | Заглушка SMS: пишет в лог |
| `TwigNotificationTemplateRenderer` | `notification/{type}.{channel}.twig` → `RenderedNotificationModel` |
| `TwigEnvironmentFactory` | Twig environment для шаблонов уведомлений |

---

## Чеклист

1. Первый уровень — технический слой, не `Task/` / `User/` / `Asset/`
2. Persistence: технология (`Doctrine/`), затем роль (`Entity/` / `Mapper/` / `Repository/` / `Query/`)
3. Messaging: адаптер порта — в корне слоя (`MessengerMessageBus`); обработчики — сущность (`Task/`), затем роль (`Handler/`)
4. Репозиторий реализует интерфейс из Application `Port/`; наружу — Application Model / скаляр по роли метода, **не** Entity
5. Mapper не уходит за пределы Persistence; направление — `*Model` ↔ Entity (`toModel` / `toEntity`)
6. `*MessageHandler` — не use case; побочный эффект через Application `*Service`
7. Именование классов — `api/AGENTS.md`
8. В DQL / QueryBuilder — `*Entity::FIELD_*`, не сырые имена свойств
9. В SQL / DBAL — `*Entity::TABLE` / `COLUMN_*` / `JOIN_*`, не литералы имён таблиц и колонок
10. Межагрегатный `onDelete: CASCADE` — нет (кроме M2M join-таблицы); ORM `cascade remove` / `orphanRemoval` — только внутри одного агрегата
