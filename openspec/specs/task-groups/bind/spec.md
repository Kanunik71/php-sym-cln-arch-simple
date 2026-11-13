# Task Groups Bind Specification

## Purpose

Привязка и отвязка задач в составе группы: идемпотентность операций, запрет дублей связи, запуск пересчёта при изменении состава.

## Requirements

### Requirement: Привязка и отвязка идемпотентны
The API SHALL привязывать задачу к группе (`POST /api/tasks/{taskId}/task-groups/{id}/attach`) и отвязывать (`DELETE /api/tasks/{taskId}/task-groups/{id}/tasks/{removeTaskId}` — `200` с `TaskGroupViewModel`) идемпотентно: повторные операции не дублируют связь и не запрашивают пересчёт, если состав не изменился.

#### Scenario: Повторная привязка
- **GIVEN** задача уже привязана к группе
- **WHEN** `POST .../{id}/attach` повторно
- **THEN** `200` без дубликата связи и без пересчёта
- **Covers:** `TaskGroupAttachTaskServiceTest::testAttachIsIdempotent`

#### Scenario: Несуществующая группа или задача
- **GIVEN** в пути несуществующая группа или задача
- **WHEN** `POST .../{id}/attach`
- **THEN** `404 TASK_GROUP_NOT_FOUND` или `404 TASK_NOT_FOUND`
- **Covers:** `tests/Application/Service/TaskGroup/Action/TaskGroupAttachTaskServiceTest::testThrowsWhenTaskGroupUnknown`, `testThrowsWhenTaskUnknown`

#### Scenario: Отвязка задачи
- **GIVEN** задача входит в группу
- **WHEN** `DELETE /api/tasks/{taskId}/task-groups/{id}/tasks/{removeTaskId}`
- **THEN** `200` с `TaskGroupViewModel`; связь удалена, запрошен пересчёт

## Notes

- Права на изменение группы — в [access](../access/spec.md); пересчёт статуса — в [status](../status/spec.md).
- Источники: `api/src/Application/Service/TaskGroup/Action/TaskGroupAttachTaskService.php`, `api/src/Application/Service/TaskGroup/Action/TaskGroupRemoveTaskService.php`, `api/tests/Application/Service/TaskGroup/Action/TaskGroupAttachTaskServiceTest.php`.
