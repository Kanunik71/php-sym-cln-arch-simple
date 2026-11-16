# Application: архитектура слоя

Слой `Application` — место, где живёт use case. Документ отвечает на вопрос
«как устроен слой и как принимать решения в новом коде», а не «что где сейчас
лежит».

| Здесь | Не здесь |
|-------|----------|
| роли классов и их ответственность | HTTP-методы, поля JSON, статусы, `codeId` → [`openspec/specs/`](../../../openspec/specs/) |
| правила зависимостей и границ | карта слоёв и таблица суффиксов → [`api/CLAUDE.md`](../../CLAUDE.md) |
| инварианты, которые нельзя нарушать | Doctrine, Redis, Messenger, storage → [`src/Infrastructure/CLAUDE.md`](../Infrastructure/CLAUDE.md) |

Примеры построены на плейсхолдерах (`{Entity}`, `Foo`, `Bar`): документ
описывает правило, а не текущий набор классов. Конкретные имена появляются
только там, где имя и есть правило (суффиксы `Io`, `Mapper`, роли Model).

---

## Форма слоя

Первый уровень — **роль**, второй — сущность (`{Entity}/`). Персистентная
модель, VO, enum, события и исключения — такие же артефакты слоя, как Service
и Port; роль артефакта задаёт папка.

```
Application/
├── Service/        # {Entity}/Action | Query | Lifecycle — use case
├── Model/          # persistence + request + response
├── ValueObject/    # инвариант поля
├── Enum/           # закрытое множество
├── Mapper/         # persistence Model → response Model
├── Cache/          # политика ключей
├── Dispatcher/     # постановка async-работы
├── Policy/         # предусловия use case
├── Port/           # контракт к Infrastructure
├── Event/          # факт для очереди
└── Exception/      # coded-исключение
```

- **Зависимости:** `Presentation → Application → Port ← Infrastructure`.
- `Application` не импортирует `Infrastructure`; внешний мир — только через `Port/`.
- `Application` может использовать `Shared/Utils` — чистые статические хелперы.

✅ `Application/Service/{Entity}/Action/FooService.php` — роль → сущность.

❌ `Application/{Entity}/Service/FooService.php` — сущность на первом уровне ломает
навигацию «что за класс» и размывает границу роли.

---

## Service

| Подроль | Когда | Вход | Выход |
|---------|-------|------|--------|
| **Action** | создание, изменение, удаление, аутентификация | request Model + контекст | response Model \| void |
| **Query** | чтение: одна сущность, список, поиск, meta | Model/контекст | response Model, list, page |
| **Lifecycle** | side effects после persist, batch-обход | свой метод (`after*Persisted`, `recalculate*`) или `execute` для job/CLI | void \| факты для вызывающего |

Правила:

- `execute` — единственный вход use case. Обёрток `*Command` / `*Query` /
  `*Handler` нет: вход — request Model плюс контекст (`userId`, `currentUserId`).
- Наружу из Action/Query — **только response Model \| void**. Persistence
  `*Model` — внутренний факт, вход репозитория, а не JSON ответа.
- Action: Policy → собрать/изменить Model → persist → Lifecycle → response Model.
- Query не имеет side effects; чтение и маппинг — вся его работа.
- Lifecycle не вызывается контроллером напрямую: его зовёт Action/Query,
  job/CLI или `*MessageHandler`.
- Presentation не содержит use-case логики: разобрать вход → `execute`.
  Console разбирает CLI-аргументы и вызывает `*Service`.
- Чтение/удаление без body не заводит request Model: контекст передаётся
  именованными аргументами.

```php
// ✅ Presentation только разбирает вход
$service->execute(FooRequestModel::fromArray($payload), id: $id, currentUserId: $actor);

// ✅ без body — контекст, а не пустая Model
$service->execute(id: $id, currentUserId: $actor);

// ❌ Controller решает задачу use case
if ($repo->exists($id)) { ... }
$repo->save($model);
```

**list ≠ query.** `list` — простой список без критериев; фильтры, сортировка и
пагинация — отдельный use case поиска. Расширять `list` фильтрами нельзя:
иначе появляются два места, где живёт выборка.

---

## Model

Один суффикс `Model` — три роли, различаются папкой:

| Роль | Где | Кто потребитель |
|------|-----|-----------------|
| **persistence** | `Model/{Entity}/` | репозиторий, Lifecycle |
| **request** | `Model/{Entity}/Action/` | `Service::execute` |
| **response** | `Model/{Entity}/Read/` | Presentation, Infrastructure (facts) |

Правила:

- Роли не смешиваются. Request Model не отдаётся наружу; persistence Model не
  сериализуется в ответ; response Model не принимается на вход записи.
- Коллизия имён ролей решается суффиксом `*ResponseModel`, а не общей Model.
- Persistence Model — зеркало состояния, а не форма запроса и не DTO.
- Закрытое множество в поле — **typed enum**, не `string`. Enum живёт в
  `Application/Enum/` — и доменный статус, и application-контракт (операторы
  фильтра, направления сортировки).
- Нетипизированный `array` в полях Model допустим только на wire-границе
  (см. «Границы типизации»).
- Model — wire-структура: данные и минимальные инварианты формы. Бизнес-правила
  с I/O — в Policy, инварианты поля — в VO/persistence Model, а не в
  `fromArray`.

```php
// ✅ разные роли — разные классы
final class FooModel { /* persistence */ }
final class FooRequestModel { /* Action: wire → typed */ }
final class FooViewModel { /* Read: typed → wire */ }

// ❌ одна Model на всё: request, persistence и ответ клиенту
final class FooModel { public array $payload; }
```

Файл в ответе — отдельная структура `id + url`, а не пара `*FileId` + `*Url` в
одной Model. Поля именуются по роли в сущности (`avatar`, `images`), а не общим
`files[]`. В write-payload передаётся id файла; URL собирается на выходе.

---

## ValueObject

- VO заводится под **инвариант** (формат, диапазон, нормализация), а не под
  каждое поле. Поле без инварианта остаётся скаляром в Model.
- Имя VO = ubiquitous language, без суффикса роли: `Email`, `Phone`, `Money`.
  Суффиксы вроде `*ValueObject` или `UserEmail` вводят второй словарь.
- Короткое имя уникально в пределах `ValueObject/`. Общие — `Common/`,
  контекстные — `{Entity}/`.
- Инвариант проверяется в конструкторе VO; наружу VO не сериализуется — на
  границе отдаётся скаляр.

```php
// ✅ инвариант в одном месте
$email = new Email($raw);          // бросает на невалидном входе

// ❌ проверка формата продублирована в сервисах
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
```

---

## Port

- Контракт к внешнему миру — только `Application/Port/`, реализация — Infrastructure.
- Один `*RepositoryInterface` на агрегат: persist, lookup, search.
- Наружу из порта — Application Model или скаляр. Doctrine Entity и ORM-объекты
  границу не пересекают.
- Возврат — по роли метода:

| Возврат | Когда |
|---------|-------|
| persistence `*Model` | write / load для изменения |
| response / facts Model | чтение, результат которого не равен строке таблицы |
| скаляр / void | факт (`exists`), мутация связи (`attach`) |

- Search с criteria / pagination / sort принимает Application Model в
  сигнатуре. Сборка критериев из `array` — забота Infrastructure, не Service.
- Не заводить новый тип «под одну колонку», если хватает скаляра или уже
  существующей Model.

```php
// ✅ порт говорит на языке Application
public function search(FooSearchModel $query): FooPageModel;
public function existsByCode(string $code): bool;

// ❌ из порта утекает ORM
public function findBy(array $criteria): array;   // и внутри Entity
```

---

## Оркестрация

Каждая роль — одна ответственность. Смешение ролей и есть источник
«сервисов на 800 строк».

| Роль | Ответственность | Не делает |
|------|-----------------|-----------|
| `*Mapper` (App) | persistence Model → response Model; собирает URL | не ходит в БД |
| `*Cache` | политика ключей и TTL; единственный потребитель `CacheInterface` | не бизнес-логика |
| `*Dispatcher` | решает, ставить ли async-задачу: дедуп, lock, `dispatch` | не выполняет работу, не обрабатывает очередь |
| `*Policy` | предусловия use case: auth, uniqueness, exists, контракт | не персистит, не возвращает response Model |
| `*MessageHandler` (Infra) | реакция на Event | не use case |

**Имя Mapper** — по тому, что маппится (`UserViewModelMapper`, `FileItemModelMapper`,
`TaskLightModelMapper`), а не по роли. Обязательного инфикса `Model` нет:
`EmailMapper`, `SearchCriteriaMapper` — нормальные имена. Слой задаёт папка, а
не суффикс: `Application/Mapper/` против Infrastructure `Mapper/`.

**Policy.** Именование: per use case — `{Operation}{Entity}Policy`,
общее правило доступа — `{Entity}AccessPolicy`. Публичный метод, который
обращается к Repository, Port, filesystem, HTTP или cache, получает суффикс
**`Io`**; метод без внешних вызовов — без суффикса. По суффиксу видно, можно ли
звать метод в чистой проверке.

```php
// ✅ по имени понятно, что будет I/O
$policy->assertCodeAvailableIo($code);
$policy->assertSelfAccess($actor, $ownerId);

// ❌ I/O спрятан за «чистым» именем
$policy->assertCodeAvailable($code);   // внутри репозиторий
```

**Event / Exception.**

- Event диспатчит Lifecycle или Dispatcher. Обработчик очереди — Infrastructure
  `*MessageHandler`, не Action.
- Исключение use case — `Exception/{Entity}/`, `DomainException` +
  `codeId` из `ErrorCodeEnum`. Наружу это контракт: он описан в спеках.
- Нарушение контракта payload (тип, whitelist) — `InvalidArgumentException` на
  сборке входа; Presentation отображает его в 400. Это не use-case исключение.

---

## Delete orchestration

Удаление агрегата — **явный сценарий в Service**, а не побочный эффект ORM.

Порядок:

1. Policy — доступ и предусловия.
2. Удалить / отвязать / переназначить дочерние агрегаты и owned-коллекции.
3. Side effects вне БД (object storage, cache, события) — **после commit**.

Правила:

- Межагрегатный `onDelete: CASCADE` и скрытый ORM cascade запрещены: Application
  не должен «не знать» об удалении чужих данных.
- `cascade remove` / `orphanRemoval` допустимы только внутри одного агрегата
  (детали mapping — Infrastructure).
- Несколько записей в одном use case — в `TransactionManagerInterface::run`.
  Object storage, cache и messenger — после commit.
- FK `RESTRICT` / violation — сигнал, что use case неполный, а не повод включить
  cascade.

```php
// ✅ БД в транзакции, внешний мир — после коммита
$keys = $this->transactionManager->run(
    fn (): array => $this->deleteAggregateWithChildren($id),
);
$this->lifecycle->purgeStorage($keys);

// ❌ удаление ребёнка другого агрегата делегировано БД
#[ORM\ManyToOne(targetEntity: Bar::class, onDelete: 'CASCADE')]
```

---

## Масштаб и batch-переборы

Прод рассчитан на большие коллекции (порядка 10⁶ строк на агрегат). Метода
«прочитать всё» нет.

| Контекст | Правило |
|----------|---------|
| **HTTP-эндпоинт** | полный перебор запрещён: пагинация (`search` + page Model) или работа по известным id (`listByIds`) |
| **Фоновая задача** (Console / worker / scheduler) | перебор разрешён, но только keyset-обходом и стримингом |

- **Keyset, не OFFSET.** `OFFSET` заставляет БД перечитывать и выбрасывать
  пропущенные строки — суммарно ≈ N²/2 на полном обходе. Курсор по монотонному
  ключу (`id`, растущий со временем) держит обход линейным.
- **Streaming, не накопление.** Батч обрабатывается сразу; память —
  O(размер батча), не O(число строк).
- **Сортировка — в SQL.** Не сортировать собранный массив.
- **Исключение по контракту** (эндпоинт обязан вернуть все строки) —
  осознанная плата, фиксируется в спеке этого эндпоинта и не тиражируется на
  другие.
- Batch-оркестрация — в Lifecycle Service; Console только парсит CLI.

```php
// ✅ обход всех строк: keyset + потоковая обработка
BatchUtils::stream(
    fetch: fn (?string $cursor): array => $repo->listBatch($cursor, $size),
    cursorOf: fn (array $batch): string => end($batch)->getId(),
    handle: function (array $batch): void { /* обработать и забыть */ },
    startCursor: BatchUtils::FIRST_CURSOR,
);

// ✅ работа по известным id — батчами
BatchUtils::each($ids, fn (array $chunk) => $service->recalculate($repo->listByIds($chunk)));
```

---

## Границы типизации

`array` и `mixed` — **только на wire-границе**: HTTP body, JSON decode, serde.

- Внутри Application, Policy, Lifecycle и тестов — Model, VO, enum.
- `array $payload` / `array $overrides` в сигнатурах Service и Policy —
  запрещены: они теряют контракт и протаскивают «магические» ключи.
- `fromArray` — только на границе (wire → typed). Внутри — `new *Model(...)`.
- `toArray` — для wire и сериализации, не для передачи данных между классами.

```php
// ✅ wire → typed один раз, дальше — типы
$service->execute(FooRequestModel::fromArray($request->getPayload()));

// ❌ нетипизированное передаётся вглубь слоя
$service->execute($request->getPayload());
```

---

## Чеклист

1. Вход use case — `*Service::execute`; выход — response Model \| void
2. `array`/`mixed` — только wire-граница; внутри — Model/VO/enum
3. Роли Model разделены; persistence Model не уходит клиенту
4. Закрытое множество — typed enum; VO — только под инвариант
5. Port — контракт; наружу Model или скаляр, Entity не выходит
6. Policy с I/O — суффикс `Io`; Policy не персистит
7. Delete — явные дети и side effects; cascade только внутри агрегата
8. Перебор — пагинация/keyset; OFFSET и полный scan запрещены
9. Async ставит `*Dispatcher`, выполняет Lifecycle или `*MessageHandler`
10. `CacheInterface` — только в `*Cache`

