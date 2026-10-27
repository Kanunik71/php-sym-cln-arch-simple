# Task groups — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/src/Presentation/Controller/TaskGroup/**`, `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`, `api/src/Application/Service/TaskGroup/**`, `api/tests/Integration/TaskGroup/**`, `api/tests/Integration/Task/TaskTaskGroupControllerTest.php` |

## Контекст

Группа объединяет задачи. Статус группы не задаётся вручную: он вычисляется по
составу задач и пересчитывается асинхронно с debounce-lock.

## Акторы и доступ

- Все маршруты — `ROLE_USER`, JWT обязателен.
- Доступ определяется доступом к `taskId` из пути (`TaskAccessPolicy`) и
  членством задачи в группе (`TaskGroupAccessPolicy::assertContainsTaskIo`).
- Владелец группы не проверяется; `TaskGroupAccessPolicy` проверяет только
  наличие задачи в группе.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | создать группу для задач | сгруппировать работу |
| US-2 | пользователь | видеть сводку по группам и счётчики статусов | контролировать прогресс |
| US-3 | пользователь | привязывать/отвязывать задачи | менять состав |
| US-4 | система | пересчитывать статус группы | держать его актуальным |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | Имя группы обязательно, trim, ≤255; уникальности нет |
| FR-2 | Новая группа получает `Initial` |
| FR-3 | Статус вычисляется: 0 задач → `Initial`; есть `Initial`/`Active` → `InProgress`; иначе `Completed` |
| FR-4 | Пересчёт асинхронный, батчами по 1000, с debounce-lock 30 секунд |
| FR-5 | Повторная привязка/отвязка идемпотентна |
| FR-6 | Summary возвращает все группы БД без фильтра по владельцу |
| FR-7 | Удаление группы удаляет её и связи `task_group_task` (CASCADE) |

## Сценарии

### S-1: Создание группы

- **Given** пользователь имеет доступ к задачам
- **When** `POST /api/task-groups` с `name` и `taskIds`
- **Then** `201`, `TaskGroupViewModel`; при непустом `taskIds` запрошен пересчёт
- **Тесты:** `TaskGroupControllerTest::testCreateTaskGroupReturnsCreated`,
  `TaskGroupCreateServiceTest::testCreatesTaskGroupWithTaskIds`

### S-2: Пустая группа

- **Given** `taskIds` пуст
- **When** создание
- **Then** `201`, статус `Initial`, пересчёт не запрашивается
- **Тест:** `testCreatesEmptyTaskGroupWithoutRecalculation`

### S-3: Summary

- **Given** группы с задачами разных статусов
- **When** `GET /api/task-groups/summary`
- **Then** `200`, для каждой группы `taskStatusCounts`; пустая — все нули
- **Тесты:** `testSummaryReturnsOk`,
  `TaskGroupSummarizeQueryServiceTest::testReturnsStatusCountsPerGroup`

### S-4: Привязка и отвязка

- **Given** задача и группа существуют
- **When** `POST .../{id}/attach`
- **Then** `200`; повторная привязка — `200` без пересчёта
- **Тесты:** `TaskTaskGroupControllerTest::testAttachTaskGroupReturnsOk`,
  `TaskGroupAttachTaskServiceTest::testAttachIsIdempotent`

### S-5: Пересчёт статуса

- **Given** группа с задачами
- **When** обработан `TaskGroupsRecalculationRequestedEvent`
- **Then** статус сохранён и опубликован `TaskGroupStatusChangedEvent`, если изменился
- **Тесты:** `TaskGroupRecalculationServiceTest::testRecalculateByTaskIdPersistsCompletedStatus`,
  `testRecalculateSkipsSaveWhenStatusUnchanged`,
  `TaskGroupRecalculationStatusTest::testStatusFromMembership`

### S-6: Удаление группы

- **Given** `taskId` входит в группу
- **When** `DELETE /api/tasks/{taskId}/task-groups/{id}`
- **Then** `204`; группа и связи удалены; пересчёт не запрашивается
- **Тест:** `TaskTaskGroupControllerTest::testDeleteTaskGroupReturnsNoContent`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| GET | `/api/task-groups/summary` | — | `200`, `TaskGroupSummaryModel[]` | `401` |
| POST | `/api/task-groups` | `TaskGroupCreateModel` | `201`, `TaskGroupViewModel` | `400`, `403`, `404 TASK_NOT_FOUND`, `401` |
| GET | `/api/tasks/{taskId}/task-groups` | — | `200`, `TaskGroupViewModel[]` | `403`, `404 TASK_NOT_FOUND` |
| POST | `/api/tasks/{taskId}/task-groups` | `TaskGroupCreateModel` | `201`, `TaskGroupViewModel` | `400`, `403`, `404` |
| POST | `/api/tasks/{taskId}/task-groups/{id}/attach` | — | `200`, `TaskGroupViewModel` | `403`, `404 TASK_NOT_FOUND`, `404 TASK_GROUP_NOT_FOUND` |
| GET | `/api/tasks/{taskId}/task-groups/{id}` | — | `200`, `TaskGroupViewModel` | `403`, `404` |
| DELETE | `/api/tasks/{taskId}/task-groups/{id}` | — | `204` | `403`, `404` |
| DELETE | `/api/tasks/{taskId}/task-groups/{id}/tasks/{removeTaskId}` | — | `200`, `TaskGroupViewModel` | `403`, `404` |

### Request `TaskGroupCreateModel`

| Поле | Тип | Обяз. | Ограничения |
|------|-----|-------|-------------|
| `name` | string | да | trim; непустое; ≤255 |
| `taskIds` | string[] | нет | каждый — regex `[0-9a-fA-F-]{36}`; проверяется доступ к каждой задаче |

В `POST /api/tasks/{taskId}/task-groups` поле `taskIds` валидируется, но
игнорируется — используется только `taskId` из пути.

### Response-модели

`TaskGroupViewModel`: `id`, `ownerId`, `name`, `status`, `createdAt` (ATOM).

`TaskGroupSummaryModel`: `id`, `name`, `taskStatusCounts`:
`{initial, active, canceled, finished}`.

`TaskGroupStatusEnum`: `Initial`, `InProgress`, `Completed`.

## Инварианты и пересчёт

`TaskGroupModel`:

- `id` — UUID v7; `ownerId` — создатель; `createdAt = now`;
- `create()` → `Initial`; `withStatus()` меняет только статус;
- имя валидируется `StringAssertUtils::notBlankMaxLength(..., 255)`.

Алгоритм `TaskGroupRecalculationService::statusFromMembership()`:

```text
taskCount == 0            -> Initial
hasNonTerminalTask == true -> InProgress
иначе                      -> Completed
```

`hasNonTerminalTask` — есть задача со статусом из
`TaskStatusEnum::thatKeepAssetInProgress()` = `[Initial, Active]`.

- `recalculateGroups()`: пустой вход → выход; факты через
  `listMembershipFactsByIds()`; если статус не изменился — без `save` и события.
- `recalculateByTaskId()`: `listByTaskId()` + `BatchUtils::each(..., 1000)`.
- `recalculateByGroupIds()`: `BatchUtils::each(ids, listByIds)`.
- `listBatch()` реализован, но не используется; `listAll()` используется в summary.

Триггеры пересчёта:

- создание группы с непустым `taskIds`;
- attach новой задачи;
- detach существующей задачи;
- смена статуса задачи (`requestByTaskId`);
- удаление задачи (`requestByGroupIds` для затронутых групп).

Не запрашивают пересчёт: создание пустой группы, удаление группы, создание
задачи.

## Права доступа

`TaskGroupAccessPolicy::assertContainsTaskIo(groupId, taskId)` → если задачи нет
в группе, `TaskGroupNotFoundException` (`404`).

`TaskAccessPolicy` — доступ к `taskId` из пути:
`task_users` пуст или пользователь в списке.

Следствия:

- владелец группы не проверяется;
- `attach` не проверяет членство и владельца, только доступ к задаче и
  существование группы;
- `removeTask` проверяет доступ к `taskId` из пути, но не к `removeTaskId`;
- `UnauthorizedTaskGroupAccessException` (`403`) объявлен и ловится, но не
  выбрасывается — код недостижим.

## Async, события, кэш

| Событие | Публикует | Обработчик |
|---------|-----------|------------|
| `TaskGroupsRecalculationRequestedEvent` | Actions и Task lifecycle | `RecalculateTaskGroupsMessageHandler` |
| `TaskGroupStatusChangedEvent` | `TaskGroupLifecycleService` | **нет обработчика** |

- `TaskGroupLifecycleService::afterCreated()` публикует `TaskGroupStatusChangedEvent`
  со статусом `Initial` **до** привязки задач и пересчёта.
- `afterStatusChanged()` — только при реальной смене статуса.
- Debounce-lock (`TaskGroupCache`):
  - `task_group_recalc.task.{taskId}`;
  - `task_group_recalc.groups.{sha256(sorted ids)}`;
  - TTL 30 секунд; снимается handler-ом после обработки.
- При исключении в handler lock живёт до истечения TTL.

Консоль:

- `app:task-groups:recalculate <id>` → `TaskGroupRecalculateService::execute()`.
- В scheduler не зарегистрирована.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидное имя/`taskIds`/JSON |
| `UNAUTHORIZED_TASK_ACCESS` | 403 | нет доступа к задаче |
| `TASK_NOT_FOUND` | 404 | задача не найдена |
| `TASK_GROUP_NOT_FOUND` | 404 | группа не найдена или задача не входит в неё |
| `UNAUTHORIZED_TASK_GROUP_ACCESS` | 403 | объявлен, фактически не выбрасывается |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Summary | `listAll()` всех групп без пагинации и фильтра по владельцу | масштаб/изоляция | `TaskGroupSummarizeQueryService` |
| Событие | нет handler для `TaskGroupStatusChangedEvent` | событие должно потребляться | `Infrastructure/Messaging/TaskGroup` |
| Создание | `StatusChanged(Initial)` публикуется до привязки задач | событие отражает итоговый статус | `TaskGroupCreateService` |
| Доступ | владелец группы не проверяется | owner-based доступ | `TaskGroupAccessPolicy` |
| `403` | `UNAUTHORIZED_TASK_GROUP_ACCESS` недостижим | корректный `403` | `UnauthorizedTaskGroupAccessException` |
| `POST` в задаче | `taskIds` валидируются, но игнорируются | предсказуемый контракт | `TaskTaskGroupController` |
| Тесты | нет `remove_task`, `400/403/404`, проверок `codeId` | полнота | `tests/Integration/TaskGroup` |

## Первоисточники

- `api/src/Presentation/Controller/TaskGroup/TaskGroupController.php`
- `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`
- `api/src/Presentation/Console/TaskGroup/RecalculateTaskGroupsConsole.php`
- `api/src/Application/Model/TaskGroup/**`
- `api/src/Application/Service/TaskGroup/**`
- `api/src/Application/Policy/TaskGroup/TaskGroupAccessPolicy.php`
- `api/src/Application/Enum/TaskGroup/TaskGroupStatusEnum.php`
- `api/src/Application/Dispatcher/TaskGroup/TaskGroupRecalculationDispatcher.php`
- `api/src/Application/Cache/TaskGroup/TaskGroupCache.php`
- `api/src/Application/Event/TaskGroup/**`
- `api/src/Infrastructure/Persistence/Doctrine/Entity/TaskGroupEntity.php`
- `api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineTaskGroupRepository.php`
- `api/src/Infrastructure/Messaging/TaskGroup/Handler/RecalculateTaskGroupsMessageHandler.php`
- `api/tests/Integration/TaskGroup/**`, `tests/Integration/Task/TaskTaskGroupControllerTest.php`
- `api/tests/Application/Service/TaskGroup/**`

## Открытые вопросы

- [ ] Нужен ли handler для `TaskGroupStatusChangedEvent` (какой эффект)?
- [ ] Должен ли summary фильтроваться по владельцу и пагинироваться?
- [ ] Нужно ли проверять владельца группы при attach/remove/delete?
