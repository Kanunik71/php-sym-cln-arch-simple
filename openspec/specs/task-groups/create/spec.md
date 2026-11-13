# Task Groups Create Specification

## Purpose

Создание группы задач — отдельно (`POST /api/task-groups`) и в контексте задачи (`POST /api/tasks/{taskId}/task-groups`): состав задач, проверка доступа, запуск пересчёта.

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
- **THEN** `201`, группа привязана к задаче из пути; `taskIds` из body принимаются и проверяются на формат, но не используются — состав группы определяется задачей из пути
- **Covers:** `TaskGroupCreateServiceTest`

## Notes

- Состав группы — в [task-groups](../spec.md); пересчёт статуса — в [status](../status/spec.md).
- Источники: `api/src/Presentation/Controller/TaskGroup/TaskGroupController.php`, `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`, `api/src/Application/Service/TaskGroup/Action/TaskGroupCreateService.php`, `api/tests/Integration/TaskGroup/TaskGroupControllerTest.php`.
