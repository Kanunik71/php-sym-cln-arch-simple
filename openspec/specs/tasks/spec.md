# Tasks Specification

## Purpose
Задачи с иерархией (`parentId`) и участниками (`task_users`). Статусы меняются по строгим переходам, участники имеют собственный статус; доступ к задаче определяется списком участников, удаление — только владелец.

## Requirements

### Requirement: Создание задачи и подзадачи
The API SHALL создавать задачу через `POST /api/tasks` (`name` обязателен, trim, непустой, ≤255; `estimateTime` ≥ 0; `parentId` — 36-символьный id, родитель должен существовать, иначе `404 TASK_NOT_FOUND`) всегда со статусом `Initial` и без участников; `status`, `users`, `cancelReason` при создании не принимаются. При указании `parentId` проверяется только существование родителя — принадлежность родителя пользователю, глубина вложенности и отсутствие циклов не проверяются.

#### Scenario: Создание задачи
- **GIVEN** аутентифицированный пользователь
- **WHEN** `POST /api/tasks` с `name`
- **THEN** `201`, `TaskViewModel`, статус `Initial`, `users = []`
- **Covers:** `tests/Integration/Task/TaskControllerTest::testCreateTaskReturnsCreated`, `TaskCreateServiceTest::testCreatesTask`

#### Scenario: Создание подзадачи
- **GIVEN** существует родительская задача
- **WHEN** `POST /api/tasks` с `parentId`
- **THEN** `201`, `parentId` сохранён
- **Covers:** `TaskCreateServiceTest::testCreatesChildTaskWithParentId`

#### Scenario: Несуществующий родитель
- **GIVEN** `parentId` указывает на несуществующую задачу
- **WHEN** `POST /api/tasks`
- **THEN** `404 TASK_NOT_FOUND`

#### Scenario: Родитель другого пользователя
- **GIVEN** `parentId` указывает на задачу, принадлежащую другому пользователю
- **WHEN** `POST /api/tasks`
- **THEN** `201`, подзадача создана с указанным `parentId` — доступ к родителю не проверяется

### Requirement: Переходы статусов
The API SHALL допускать переходы `Initial→Active`, `Active→Canceled`, `Active→Finished` через `PATCH /api/tasks/{id}/status` и отклонять остальные (`409 INVALID_TASK_STATUS_TRANSITION`). Переход в `Active` требует `estimateTime`, в `Canceled` — непустой `cancelReason` (≤1000); `finishedDate`/`cancellationDate` ставит сервер; self-transition запрещён (`status=Initial` — `400`).

#### Scenario: Активация и завершение
- **GIVEN** задача в `Initial`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Active`, `estimateTime`, `userId`, затем `status=Finished`
- **THEN** `200`; при завершении выставлен `finishedDate`
- **Covers:** `TaskChangeStatusServiceTest::testActivateAndFinishTask`

#### Scenario: Недопустимый переход
- **GIVEN** задача в `Initial`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Finished`
- **THEN** `409 INVALID_TASK_STATUS_TRANSITION`
- **Covers:** `testInvalidStatusTransitionReturnsConflict`, `TaskChangeStatusServiceTest::testInvalidStatusTransitionIsRejected`

#### Scenario: Отмена с причиной
- **GIVEN** задача в `Active`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Canceled` и `cancelReason`
- **THEN** `200`, сохранены `cancelReason` и `cancellationDate`
- **Covers:** `testCancelRequiresReasonAndSetsDates`

### Requirement: Доступ к задаче
The API SHALL разрешать чтение и смену статуса, если список участников задачи пуст (задача открыта всем) или пользователь есть в `task_users` (с любым статусом, включая Declined/Finished); иначе `403 UNAUTHORIZED_TASK_ACCESS`. Владелец — только создатель, и его единственное особое право — `DELETE`: удалить задачу может только владелец, даже не будучи участником.

#### Scenario: Чужая задача
- **GIVEN** задача с участниками, пользователь не среди них и не владелец
- **WHEN** `GET /api/tasks/{id}` или `PATCH /api/tasks/{id}/status`
- **THEN** `403 UNAUTHORIZED_TASK_ACCESS`
- **Covers:** `testCannotChangeStatusOfAnotherUsersTask`, `TaskQueryServiceTest::testThrowsWhenAccessingAnotherUsersTask`

#### Scenario: Удаление не-владельцем
- **GIVEN** участник задачи, не являющийся владельцем
- **WHEN** `DELETE /api/tasks/{id}`
- **THEN** `403`

### Requirement: Лист задач
The API SHALL возвращать `GET /api/tasks` как страницу (1 и 20 по умолчанию, максимум 100; без фильтров) задач, где пользователь есть в `task_users` или список участников пуст; сортировка `createdAt DESC, id DESC`; статус задачи доступ не ограничивает; лист Policy не проверяет.

#### Scenario: Лист доступных задач
- **GIVEN** у пользователя есть свои и открытые задачи
- **WHEN** `GET /api/tasks?page=1&perPage=20`
- **THEN** `200`, `TaskPageModel` (`items`, `total`, `page`, `perPage`), сортировка `createdAt DESC, id DESC`

### Requirement: Пересчёт групп при изменениях
The API SHALL при смене статуса задачи публиковать `TaskGroupsRecalculationRequestedEvent` (`taskId`), при удалении — с `taskGroupIds` затронутых групп (см. [task-groups](../task-groups/spec.md)); при `TaskCreated` событие публикуется, но handler только логирует. Список задач не кэшируется.

#### Scenario: Пересчёт после смены статуса
- **GIVEN** задача входит в группы
- **WHEN** сменён статус задачи
- **THEN** группы пересчитаны асинхронно через `RecalculateTaskGroupsMessageHandler`

### Requirement: Удаление задачи владельцем
The API SHALL позволять владельцу `DELETE /api/tasks/{id}` с `204`, удаляя `task_users` через ORM cascade, связи `asset_task`/`task_group_task` (`ON DELETE CASCADE`), а у дочерних задач обнуляя `parent_id` (`ON DELETE SET NULL`); ассеты и группы остаются, события `TaskDeleted` нет. Удаление пользователя удаляет его задачи тем же путём и снимает его с чужих.

#### Scenario: Успешное удаление
- **GIVEN** владелец удаляет свою задачу с подзадачей и ассетами
- **WHEN** `DELETE /api/tasks/{id}`
- **THEN** `204`; участники удалены, связи с ассетами/группами каскадом, у соседей `parentId` стал `null`
- **Covers:** `tests/Integration/Task/TaskControllerTest` (delete)

#### Scenario: Удаление при удалении пользователя
- **GIVEN** пользователь удаляет аккаунт
- **WHEN** `UserDeleteService` выполняется
- **THEN** его задачи удалены (с пересчётом затронутых групп), из чужих задач он снят

## Notes

- `TaskViewModel`: `id`, `ownerId`, `name`, `parentId`, `estimateTime`, `status`, `createdAt`, `users` (`{userId, status}[]`), `cancelReason`, `finishedDate`, `cancellationDate`. `TaskPageModel`: `items`, `total`, `page`, `perPage`. Дочерние задачи, ассеты и группы в ответе не возвращаются.
- `TaskStatusEnum`: `Initial`→`Active`; `Active`→`Canceled|Finished`; `Canceled`/`Finished` — терминальные. `keepsAssetInProgress()` = `Initial`/`Active` (используется пересчётом групп и `inProgress` ассетов). `TaskUserStatusEnum`: `Initial→Active|Declined`; `Active→Declined|Finished`; `withForcedStatus()` переходы не проверяет.
- Участники меняются только при переходе в `Active` с `userId` (существующий → принудительно `Active`, новый создаётся `Active`; `UNIQUE(task_id, user_id)`); создатель в участники не добавляется.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_TASK_ACCESS` (403), `TASK_NOT_FOUND` (404), `INVALID_TASK_STATUS_TRANSITION` (409).
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/**`, `api/src/Application/Enum/Task/**`, `api/tests/Integration/Task/TaskControllerTest.php`.

## Покрытие тестами

- Не покрыты: тела успешных ответов и часть сценариев `400`/`404`.
- Не покрыт: сценарий «Родитель другого пользователя».
