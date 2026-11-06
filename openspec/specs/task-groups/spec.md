# Task Groups Specification

## Purpose
Группы задач: создание (отдельно или в контексте задачи), привязка/отвязка задач, сводка по статусам. Статус группы вручную не задаётся — вычисляется по составу задач и пересчитывается асинхронно с debounce-lock.

## Requirements

### Requirement: Создание группы
The API SHALL создавать группу через `POST /api/task-groups` (`name` обязателен, trim, непустой, ≤255; `taskIds[]` опционален) со статусом `Initial`; каждый `taskId` должен существовать (`404 TASK_NOT_FOUND`) и быть доступен пользователю (`403 UNAUTHORIZED_TASK_ACCESS`). При непустом `taskIds` запрашивается пересчёт статуса.

#### Scenario: Группа с задачами
- **GIVEN** пользователь имеет доступ к задачам
- **WHEN** `POST /api/task-groups` с `name` и непустым `taskIds`
- **THEN** `201`, `TaskGroupViewModel` (статус `Initial`), запрошен пересчёт
- **Covers:** `tests/Integration/TaskGroup/TaskGroupControllerTest::testCreateTaskGroupReturnsCreated`, `TaskGroupCreateServiceTest::testCreatesTaskGroupWithTaskIds`

#### Scenario: Пустая группа
- **GIVEN** `taskIds` пуст
- **WHEN** создание группы
- **THEN** `201`, статус `Initial`, пересчёт не запрашивается
- **Covers:** `testCreatesEmptyTaskGroupWithoutRecalculation`

#### Scenario: Создание в контексте задачи
- **GIVEN** доступ к `taskId` из пути
- **WHEN** `POST /api/tasks/{taskId}/task-groups` с `name`
- **THEN** `201`, группа привязана к задаче из пути; `taskIds` из body валидируются, но **игнорируются** (текущее поведение)
- **Covers:** `TaskGroupCreateServiceTest`

### Requirement: Вычисление статуса группы
The API SHALL вычислять статус группы: 0 задач → `Initial`; есть задача в `Initial` или `Active` → `InProgress`; иначе → `Completed`. Пересчёт выполняется асинхронно (`TaskGroupsRecalculationRequestedEvent` → `RecalculateTaskGroupsMessageHandler`), батчами по 1000, и статус сохраняется, только если изменился.

#### Scenario: Пересчёт по составу
- **GIVEN** группа с задачами в терминальных статусах
- **WHEN** обработано событие пересчёта
- **THEN** статус группы — `Completed`, сохранён одним `save`
- **Covers:** `TaskGroupRecalculationServiceTest::testRecalculateByTaskIdPersistsCompletedStatus`, `testRecalculateSkipsSaveWhenStatusUnchanged`

#### Scenario: Дебаунс повторов
- **GIVEN** цепочка событий пересчёта по одной задаче в течение 30 секунд
- **WHEN** handler уже обрабатывает первый
- **THEN** повторные события пропущены через cache-lock (`task_group_recalc.task.{taskId}`, TTL 30 с, снимается после обработки)

### Requirement: Привязка и отвязка идемпотентны
The API SHALL привязывать задачу к группе (`POST /api/tasks/{taskId}/task-groups/{id}/attach`) и отвязывать (`DELETE /api/tasks/{taskId}/task-groups/{id}/tasks/{removeTaskId}` — `200` с `TaskGroupViewModel`) идемпотентно: повторные операции не дублируют связь и не запрашивают пересчёт, если состав не изменился.

#### Scenario: Повторная привязка
- **GIVEN** задача уже привязана к группе
- **WHEN** `POST .../{id}/attach` повторно
- **THEN** `200` без дубликата связи и без пересчёта
- **Covers:** `TaskGroupAttachTaskServiceTest::testAttachIsIdempotent`

### Requirement: Права на изменение группы
The API SHALL разрешать `attach`, `removeTask` и `DELETE` группы только владельцу группы (`403 UNAUTHORIZED_TASK_GROUP_ACCESS` иначе); доступ к `taskId` из пути определяется `TaskAccessPolicy`; для `DELETE` дополнительно требуется, чтобы задача из пути входила в группу (`404 TASK_GROUP_NOT_FOUND`); `removeTask` не проверяет доступ к `removeTaskId`. Чтение (detail, список по задаче, summary) не проверяет владельца.

#### Scenario: Чужая группа
- **GIVEN** пользователь — не владелец группы
- **WHEN** `POST /api/tasks/{taskId}/task-groups/{id}/attach`
- **THEN** `403 UNAUTHORIZED_TASK_GROUP_ACCESS`

#### Scenario: Чтение по доступу к задаче
- **GIVEN** пользователь имеет доступ к `taskId`, входящей в группу
- **WHEN** `GET /api/tasks/{taskId}/task-groups/{id}`
- **THEN** `200` даже если он не владелец группы

### Requirement: Сводка по группам
The API SHALL на `GET /api/task-groups/summary` возвращать `200` список всех групп БД (`createdAt desc`) с `taskStatusCounts` (`{initial, active, canceled, finished}`) для каждой; пустая группа — все нули. Текущее поведение: без пагинации и фильтра по владельцу (см. Notes).

#### Scenario: Счётчики статусов
- **GIVEN** группы с задачами разных статусов
- **WHEN** `GET /api/task-groups/summary`
- **THEN** `200`, для каждой группы корректные счётчики по четырём статусам
- **Covers:** `testSummaryReturnsOk`, `TaskGroupSummarizeQueryServiceTest::testReturnsStatusCountsPerGroup`

### Requirement: Удаление группы
The API SHALL удалять группу через `DELETE /api/tasks/{taskId}/task-groups/{id}` (`204`) вместе со связями `task_group_task` (`ON DELETE CASCADE`); пересчёт при этом не запрашивается.

#### Scenario: Удаление группы
- **GIVEN** `taskId` входит в группу
- **WHEN** `DELETE /api/tasks/{taskId}/task-groups/{id}`
- **THEN** `204`; группа и связи удалены
- **Covers:** `TaskTaskGroupControllerTest::testDeleteTaskGroupReturnsNoContent`

### Requirement: Консольный пересчёт
The API SHALL предоставлять `app:task-groups:recalculate <id>` для принудительного пересчёта группы (в scheduler не зарегистрирована).

#### Scenario: Ручной пересчёт
- **GIVEN** id группы
- **WHEN** `app:task-groups:recalculate`
- **THEN** статус группы пересчитан и сохранён при изменении
- **Covers:** `TaskGroupRecalculateService::execute`

## Notes

- `TaskGroupViewModel`: `id`, `ownerId`, `name`, `status`, `createdAt` (ATOM). `TaskGroupSummaryModel`: `id`, `name`, `taskStatusCounts`. `TaskGroupStatusEnum`: `Initial`, `InProgress`, `Completed` (события `TaskGroupStatusChangedEvent` больше нет).
- Debounce-lock: `task_group_recalc.task.{taskId}` и `task_group_recalc.groups.{sha256(sorted ids)}`, TTL 30 с; при исключении в handler lock живёт до истечения TTL.
- Триггеры пересчёта: создание группы с задачами, attach новой задачи, detach существующей, смена статуса задачи (`requestByTaskId`), удаление задачи (`requestByGroupIds`). Не триггерят: создание пустой группы, удаление группы, создание задачи.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_TASK_ACCESS`/`UNAUTHORIZED_TASK_GROUP_ACCESS` (403), `TASK_NOT_FOUND`/`TASK_GROUP_NOT_FOUND` (404).
- Известные расхождения: summary возвращает все группы без пагинации и фильтра по владельцу (масштаб); в `POST /api/tasks/{taskId}/task-groups` поле `taskIds` валидируется, но игнорируется; тестами не покрыты `400/404` — см. [overview](../overview/spec.md).
- Источники: `api/src/Presentation/Controller/TaskGroup/**`, `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`, `api/src/Application/Service/TaskGroup/**`, `api/src/Application/Policy/TaskGroup/TaskGroupAccessPolicy.php`, `api/src/Infrastructure/Messaging/TaskGroup/Handler/RecalculateTaskGroupsMessageHandler.php`.
