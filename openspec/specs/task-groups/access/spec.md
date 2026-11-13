# Task Groups Access Specification

## Purpose

Права на изменение группы задач и правила чтения: владение для операций, доступ к задаче из пути, открытость чтения.

## Requirements

### Requirement: Права на изменение группы
The API SHALL разрешать `attach`, `removeTask` и `DELETE` группы только владельцу группы (`403 UNAUTHORIZED_TASK_GROUP_ACCESS` иначе); доступ к `taskId` из пути определяется правилами доступа к задаче; для `DELETE` дополнительно требуется, чтобы задача из пути входила в группу (`404 TASK_GROUP_NOT_FOUND`); `removeTask` не проверяет доступ к `removeTaskId`. Чтение (detail, список по задаче, summary) не проверяет владельца.

#### Scenario: Чужая группа
- **GIVEN** пользователь — не владелец группы
- **WHEN** `POST /api/tasks/{taskId}/task-groups/{id}/attach`
- **THEN** `403 UNAUTHORIZED_TASK_GROUP_ACCESS`

#### Scenario: Чтение по доступу к задаче
- **GIVEN** пользователь имеет доступ к `taskId`, входящей в группу
- **WHEN** `GET /api/tasks/{taskId}/task-groups/{id}`
- **THEN** `200` даже если он не владелец группы

## Notes

- Доступ к задаче — в [tasks/access](../../tasks/access/spec.md); состав группы — в [task-groups](../spec.md).
- Источники: `api/src/Application/Policy/TaskGroup/TaskGroupAccessPolicy.php`, `api/src/Application/Policy/Task/TaskAccessPolicy.php`, `api/src/Application/Service/TaskGroup/Action/TaskGroupDeleteService.php`, `api/tests/Integration/TaskGroup/TaskGroupControllerTest.php`.
