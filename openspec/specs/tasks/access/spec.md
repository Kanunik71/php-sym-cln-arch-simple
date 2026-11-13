# Tasks Access Specification

## Purpose

Правила доступа к задаче: чтение, смена статуса и удаление в зависимости от участия в задаче и владения ею.

## Requirements

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

#### Scenario: Открытая задача
- **GIVEN** задача без участников
- **WHEN** любой аутентифицированный пользователь читает задачу
- **THEN** `200` — доступ не ограничен составом участников

## Notes

- Состав задачи — в [tasks](../spec.md); удаление — в [delete](../delete/spec.md).
- Источники: `api/src/Application/Policy/Task/TaskAccessPolicy.php`, `api/src/Application/Service/Task/Query/TaskQueryService.php`, `api/src/Application/Service/Task/Action/TaskDeleteService.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
