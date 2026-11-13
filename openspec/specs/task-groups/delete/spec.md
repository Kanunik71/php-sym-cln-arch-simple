# Task Groups Delete Specification

## Purpose

Удаление группы задач владельцем: каскадное удаление связей с задачами и отсутствие пересчёта при удалении.

## Requirements

### Requirement: Удаление группы
The API SHALL удалять группу через `DELETE /api/tasks/{taskId}/task-groups/{id}` (`204`) вместе со связями `task_group_task` (`ON DELETE CASCADE`); пересчёт при этом не запрашивается.

#### Scenario: Удаление группы
- **GIVEN** `taskId` входит в группу
- **WHEN** `DELETE /api/tasks/{taskId}/task-groups/{id}`
- **THEN** `204`; группа и связи удалены
- **Covers:** `TaskTaskGroupControllerTest::testDeleteTaskGroupReturnsNoContent`

## Notes

- Права на изменение группы — в [access](../access/spec.md); состав группы — в [task-groups](../spec.md).
- Источники: `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`, `api/src/Application/Service/TaskGroup/Action/TaskGroupDeleteService.php`, `api/tests/Integration/Task/TaskTaskGroupControllerTest.php`.
