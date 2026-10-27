# Tasks — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/**`, `api/src/Application/Model/Task/**`, `api/tests/Integration/Task/TaskControllerTest.php`, `api/tests/Application/Service/Task/**` |

## Контекст

Задачи с иерархией (`parentId`) и участниками (`task_users`). Статус задачи
меняется по строгим переходам; участники имеют собственный статус. Задача с
пустым списком участников открыта любому аутентифицированному пользователю.

## Акторы и доступ

- Все маршруты — `ROLE_USER`, JWT обязателен.
- Доступ к конкретной задаче — `TaskAccessPolicy`: `task_users` пуст **или**
  пользователь есть в списке (с любым статусом).
- Владельца/создателя у задачи нет; создатель не получает особых прав.
- `GET /api/tasks` не вызывает Policy: запрос возвращает задачи, где
  пользователь есть в `task_users`, **или** задачи без участников; сортировка
  `created_at DESC, id DESC`.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | создавать задачи и подзадачи | декомпозировать работу |
| US-2 | участник | видеть доступные задачи | работать со своим списком |
| US-3 | участник | менять статус задачи | отражать прогресс |
| US-4 | участник | удалять задачу | убирать лишнее |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | Создание всегда даёт статус `Initial`, участников нет |
| FR-2 | Переходы: `Initial→Active`, `Active→Canceled`, `Active→Finished` |
| FR-3 | `Active` требует `estimateTime`; `Canceled` требует `cancelReason` |
| FR-4 | `finishedDate`/`cancellationDate` ставит сервер |
| FR-5 | `parentId` должен существовать; циклы/глубина/доступ не проверяются |
| FR-6 | Пустой список участников = задача открыта всем |
| FR-7 | Список задач кэшируется на 60 секунд |
| FR-8 | Смена/удаление задачи запускает пересчёт групп |

## Сценарии

### S-1: Создание задачи

- **Given** аутентифицированный пользователь
- **When** `POST /api/tasks` с `name`
- **Then** `201`, `TaskViewModel`, статус `Initial`, `users = []`
- **Тесты:** `TaskControllerTest::testCreateTaskReturnsCreated`,
  `TaskCreateServiceTest::testCreatesTask`

### S-2: Создание подзадачи

- **Given** существует родитель
- **When** `POST /api/tasks` с `parentId`
- **Then** `201`, `parentId` сохранён
- **Тест:** `TaskCreateServiceTest::testCreatesChildTaskWithParentId`

### S-3: Активация и завершение

- **Given** задача в `Initial`
- **When** `PATCH /api/tasks/{id}/status` c `status=Active`, `estimateTime`, `userId`
- **Then** `200`; при `status=Finished` — `finishedDate`
- **Тест:** `TaskChangeStatusServiceTest::testActivateAndFinishTask`

### S-4: Недопустимый переход

- **Given** задача в `Initial`
- **When** `status=Finished`
- **Then** `409 INVALID_TASK_STATUS_TRANSITION`
- **Тесты:** `TaskControllerTest::testInvalidStatusTransitionReturnsConflict`,
  `TaskChangeStatusServiceTest::testInvalidStatusTransitionIsRejected`

### S-5: Отмена

- **Given** задача в `Active`
- **When** `status=Canceled`, `cancelReason`
- **Then** `200`, `cancelReason`, `cancellationDate`
- **Тест:** `testCancelRequiresReasonAndSetsDates`

### S-6: Чужая задача

- **Given** задача с участниками, пользователя нет среди них
- **When** `GET`, `PATCH /status` или `DELETE`
- **Then** `403 UNAUTHORIZED_TASK_ACCESS`
- **Тесты:** `testCannotChangeStatusOfAnotherUsersTask`,
  `TaskQueryServiceTest::testThrowsWhenAccessingAnotherUsersTask`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| GET | `/api/tasks` | — | `200`, `TaskViewModel[]` | `401` |
| POST | `/api/tasks` | `TaskCreateModel` | `201`, `TaskViewModel` | `400`, `404 TASK_NOT_FOUND`, `401` |
| GET | `/api/tasks/{id}` | path | `200`, `TaskViewModel` | `400`/`500`, `403`, `404` |
| PATCH | `/api/tasks/{id}/status` | `TaskChangeStatusModel` | `200`, `TaskViewModel` | `400`, `403`, `404`, `409` |
| DELETE | `/api/tasks/{id}` | path | `204` | `403`, `404` |

### Request `TaskCreateModel`

| Поле | Тип | Обяз. | Ограничения |
|------|-----|-------|-------------|
| `name` | string | да | trim; непустое; ≤255 |
| `estimateTime` | number/null | нет | приводится к `int`; ≥ 0 |
| `parentId` | string/null | нет | regex `[0-9a-fA-F-]{36}`; родитель должен существовать |

`status`, `users`, `cancelReason` при создании не принимаются.

### Request `TaskChangeStatusModel`

| Поле | Тип | Обяз. | Ограничения |
|------|-----|-------|-------------|
| `status` | string | да | `Active`, `Canceled`, `Finished`; `Initial` → `400` |
| `userId` | string/null | нет | непустая строка; только для `Active` |
| `estimateTime` | number/null | для `Active` | `int`; ≥ 0 |
| `cancelReason` | string/null | для `Canceled` | непустая строка; ≤1000 |

### Response `TaskViewModel`

`id`, `name`, `parentId`, `estimateTime`, `status`, `createdAt`, `users`
(`list<{userId,status}>`), `cancelReason`, `finishedDate`, `cancellationDate`.
`updatedAt`, владелец, дочерние задачи, ассеты и группы не возвращаются.

## Статусы

`TaskStatusEnum`: `Initial`, `Active`, `Canceled`, `Finished`.

```text
Initial  -> Active
Active   -> Canceled | Finished
Canceled -> (нет)
Finished -> (нет)
```

- `keepsAssetInProgress()` = `true` для `Initial`/`Active`.
- `thatKeepAssetInProgress()` = `[Initial, Active]`.
- Прямой self-transition запрещён.

`TaskUserStatusEnum`: `Initial`, `Active`, `Declined`, `Finished`.

```text
Initial  -> Active | Declined
Active   -> Declined | Finished
Declined -> (нет)
Finished -> (нет)
```

`withForcedStatus()` (используется при назначении) переходы не проверяет.

## Участники

- Таблица `task_users`: `id`, `task_id`, `user_id`, `status`;
  `UNIQUE(task_id, user_id)`.
- Создатель не добавляется автоматически.
- Пустой список = задача открыта всем.
- Участник со статусом `Declined`/`Finished` сохраняет доступ.
- В Task-flow участники меняются только при `Active` с `userId`:
  существующий переводится в `Active` принудительно, новый создаётся `Active`.
- Участники не меняются при `Canceled`/`Finished` задачи.

## Права доступа

`TaskAccessPolicy::assertUserCanAccessIo()`:

```text
users === []  OR  exists(users, currentUserId)
```

- `TaskListQueryService` Policy не вызывает.
- `CreateTaskPolicy` проверяет только существование родителя.
- `ChangeTaskStatusPolicy` проверяет допустимость статуса и обязательные поля.
- Отдельных ролей/административного bypass нет.

## Async, события, кэш

| Событие | Когда | Обработчик |
|---------|-------|------------|
| `TaskCreated` | создание | `TaskCreatedMessageHandler` — только лог |
| `TaskGroupsRecalculationRequestedEvent` | смена статуса (`taskId`) и удаление (`taskGroupIds`) | `RecalculateTaskGroupsMessageHandler` |

Кэш списка (`TaskCache`):

- ключ `tasks.user.{userId}`, TTL 60 секунд;
- инвалидация: создание — список actor; смена статуса — actor + участники до/после;
  удаление — actor + участники до удаления;
- списки пользователей, видевших открытую задачу, не инвалидируются (до TTL).

Удаление задачи:

- `task_users` удаляются ORM (`cascade` + `orphanRemoval`);
- `asset_task` и `task_group_task` — `ON DELETE CASCADE`;
- `tasks.parent_id` дочерних — `ON DELETE SET NULL`;
- ассеты и группы остаются;
- события `TaskDeleted` нет.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидный JSON/поля/`Initial`/нет обязательного поля |
| `UNAUTHORIZED_TASK_ACCESS` | 403 | нет доступа к задаче |
| `TASK_NOT_FOUND` | 404 | задача/родитель не найдены |
| `INVALID_TASK_STATUS_TRANSITION` | 409 | недопустимый переход |
| — | 500 | невалидный 36-символьный UUID в path |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Список | `GET /api/tasks` без пагинации, фиксированная сортировка `created_at DESC, id DESC` | пагинация для ~10⁶ | `TaskListQueryService` |
| Владелец | у задачи нет `owner_id`, создатель не имеет прав | явная модель владения | `TaskEntity` |
| Открытые задачи | создатель теряет доступ после назначения других участников | предсказуемые права | `TaskAccessPolicy` |
| Кэш | не инвалидируются списки пользователей, видевших открытую задачу | консистентность | `TaskLifecycleService` |
| Родитель | не проверяются циклы, глубина, доступ | защита иерархии | `CreateTaskPolicy` |
| Тесты | не покрыты тела успешных ответов и часть `400`/`404` | полнота | `TaskControllerTest` |

## Первоисточники

- `api/src/Presentation/Controller/Task/TaskController.php`
- `api/src/Application/Model/Task/**`
- `api/src/Application/Service/Task/**`
- `api/src/Application/Policy/Task/**`
- `api/src/Application/Enum/Task/{TaskStatusEnum,TaskUserStatusEnum}.php`
- `api/src/Application/Cache/Task/TaskCache.php`
- `api/src/Application/Event/Task/TaskCreated.php`
- `api/src/Infrastructure/Persistence/Doctrine/Entity/{TaskEntity,TaskUserEntity}.php`
- `api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineTaskRepository.php`
- `api/src/Infrastructure/Persistence/Doctrine/Query/Task/**`
- `api/src/Infrastructure/Messaging/Task/Handler/TaskCreatedMessageHandler.php`
- `api/tests/Integration/Task/TaskControllerTest.php`
- `api/tests/Application/Service/Task/**`, `tests/Application/Model/Task/**`

## Открытые вопросы

- [ ] Нужен ли владелец задачи и особые права создателя?
- [ ] Нужна ли пагинация/фильтры для списка задач?
- [ ] Должны ли завершённые задачи оставаться доступными?
