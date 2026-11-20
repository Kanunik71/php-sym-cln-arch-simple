# Presentation: тонкий слой входа

Правила слоя, а не карта классов; примеры на `{Entity}` / `Foo`. Смежные
документы: карта слоёв и суффиксы — [`api/CLAUDE.md`](../../CLAUDE.md), роли
Application — [`Application/CLAUDE.md`](../Application/CLAUDE.md), wire-контракт
(поля JSON, статусы, `codeId`) — [`openspec/specs/`](../../../openspec/specs/),
покрытие тестами — [`tests/CLAUDE.md`](../../tests/CLAUDE.md).

## Форма слоя и зависимости

`Controller/{Entity}/*Controller` (HTTP) и `Console/{Entity}/*Console` (CLI).
Общий код — только `Controller/BaseController`: идентичность (`getCurrentUserId`),
разбор тела (`requestPayload`), оформление отказа (`errorResponse`). Второй общий
класс означал бы, что логика уезжает из Presentation. Первый уровень —
**сущность**, роль задаёт суффикс: в тонком слое «какой ресурс» важнее «какая
роль» (в `Application` первый уровень — роль, в `Infrastructure` — технология).
Вложенный ресурс — в папке родителя:
✅ `Controller/Task/TaskAssetController`
для `/api/tasks/{taskId}/assets`, ❌ `Controller/Asset/TaskAssetController`.

Можно: `Application/Service/**/{Action,Query}` (единственный вход HTTP),
`Application/Model/**` (`fromArray` на входе, response Model на выходе),
`Application/Exception/**`, `Application/Utils/Wire/**` (`InputAssertUtils`:
разбор payload и CLI-аргументов), Symfony
(`HttpFoundation`, `Route`, `IsGranted`, `Console\*`). Нельзя:
`Application/Service/**` вне `Action`/`Query`, `Port/`, `Policy/`, `Cache/`,
`TransactionManagerInterface`, Doctrine, `Infrastructure/**`,
`Application/Utils/Collection/**` (это внутренность use case'ов). Проверки —
`ArchitectureTest` (`testControllerEntersThroughUseCase`,
`testJobEntersThroughService`, `testPresentationDoesNotDependOnInfrastructure`).

Идентичность приходит через контракт Symfony: `BaseController::getCurrentUserId()`
берёт `UserInterface::getUserIdentifier()`, чей инвариант «identifier — id
агрегата, а не email» держит `SecurityUser` (пин —
`tests/Infrastructure/Security/SecurityUserTest.php`). Поэтому `SecurityUser`
контроллерам не нужен.

## Controller

Один метод = один endpoint = ровно один `Action`/`Query::execute`.

- **body** — `requestPayload($request)`, он же отсекает не-объект; ❌ `json_decode`
  руками. **query** — `*Model::fromArray($request->query->all())`; ❌
  `$request->query->get('page')`. **путь** — параметр метода, **контекст**
  (`userId`, `currentUserId`) — именованный аргумент `execute`, пустая
  request-Model «ради одного id» не заводится:
  ✅ `$service->execute(FooCreateModel::fromArray($this->requestPayload($request)), id: $id, userId: $actor);`
- **Выход** — `$this->json($viewModel)`, create → 201, `void` → 204 (❌ 200 с
  `null`). Не-JSON — обёртка готового Model (`StreamedResponse` из download-Model:
  `stream`, `mimeType`, `originalName`): ❌ контроллер открывает файлы и выводит
  заголовки из предметных данных. Response Model на выходе не правится.
- `multipart`, заголовки, подписанные query-параметры — техника Presentation, она
  законна. Ветвление по домену (какой mime разрешён, что значит этот размер) —
  правило Application: request Model или use case.
  ❌ `if ($repo->exists($id)) { … }` и `$payload['ownerId'] = $actor;` в контроллере.

## Маршруты и доступ

| Что | Правило |
|-----|---------|
| путь, имя | `/api/{resource}` (множественное, kebab); вложенный — `/api/{parent}/{parentId}/{child}`; `name: '{resource}_'` на классе + действие на методе ⇒ `{resource}_{action}` (`task_assets_attach`) |
| методы, параметры | `methods` явно; `GET`/`DELETE` без body; UUID — `requirements: ['id' => '[0-9a-fA-F-]{36}']`, параметр из префикса — на классе |
| доступ | `#[IsGranted('ROLE_USER')]` на классе ресурса; на методе — только если метод отличается от класса. Без атрибута — публичные контракты: `Auth` и скачивание по подписанному URL |

Права не проверяются в теле метода: `getCurrentUserId()` — идентичность, решение
о доступе — Policy use case.

## Console

Имя `app:{domain}:{action}`, `description` — одно предложение; `#[AsCommand]` +
`extends Command`, в конструкторе `parent::__construct()`. Вход только в
`*Service` (`Lifecycle`, технический шаг): у job нет актора, Policy и response
Model, поэтому `Action`/`Query` не вызываются. Аргументы — в `configure()`, разбор
через `InputAssertUtils::requiredString`; размер батча и retention — параметр
конфигурации, не литерал. Итог — одна строка `writeln` и `Command::SUCCESS`,
`FAILURE` — когда задача не выполнена по данным, а не по исключению. Оркестрация,
keyset и `BatchUtils::stream` — в Service (❌ `while` с батчами в команде);
расписание задаёт `Infrastructure/Scheduler` через `RunCommandMessage`.

## Ошибки → HTTP

Контрактные статусы метода переводит контроллер, остальное — `ApiExceptionSubscriber`.

| Кто | Что | Статусы |
|-----|-----|---------|
| Контроллер | доменные исключения use case из спеки метода | `*NotFound` → 404, `Unauthorized*` → 403, `*AlreadyExists` → 409, `InvalidCredentials`/`InvalidRefreshToken` → 401 |
| Контроллер | нарушение формы payload (`InvalidArgumentException` из `fromArray` / `requestPayload`) | 400 |
| Subscriber | что дошло до ядра: `HttpExceptionInterface`, security, непереведённое | 400/401/403, 500 (в prod текст маскируется) |

- Статус и `codeId` — из спеки эндпоинта, это один контракт. Тело отказа — только
  `$this->errorResponse($exception, $status)`: это HTTP-обёртка над
  `ErrorResponseModel::fromThrowable` (Application), ❌ собирать конверт в
  контроллере вручную и ❌ `catch (Throwable)` (неизвестное обязано стать 500).
  Одинаковый статус у нескольких исключений — один `catch` через `|`.
  `ApiExceptionSubscriber` строит конверт сам и в Presentation не заходит.
- Новое исключение ⇒ `catch` в затронутых контроллерах + запись в спеке +
  интеграционный тест на `codeId`. 400 намеренно в двух местах: явный `catch`
  держит контракт метода на месте, fallback страхует забытое исключение.

## Чеклист

1. Контроллер — только `Action`/`Query`, один `execute` на метод; Console — только `*Service`.
2. Вход: `requestPayload` / `*Model::fromArray($request->query->all())` / именованный контекст.
3. Выход: `$this->json($viewModel)` | 201 | 204 | StreamedResponse из готового Model; response Model не правится.
4. Нет репозиториев, Policy, Cache, транзакций, Doctrine, `Infrastructure/**` — идентичность только через `UserInterface`.
5. Маршрут: `/api/{resource}`, `{resource}_{action}`, явные `methods`, UUID в `requirements`, `IsGranted` на классе.
6. Команда: `app:{domain}:{action}`, `configure()` + `InputAssertUtils`, `SUCCESS`/`FAILURE`, без обходов.
7. Ошибки: статус из спеки, тело только через `$this->errorResponse`, без `catch (Throwable)`.
8. Каждый контракт ошибки покрыт интеграционным тестом (status + `codeId`).
