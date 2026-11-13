# Tasks Delete Specification

## Purpose

Удаление задачи владельцем по `DELETE /api/tasks/{id}`: каскадное удаление связей, обнуление родителя у дочерних задач, удаление задач вместе с аккаунтом.

## Requirements

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

- Правила доступа и владения — в [access](../access/spec.md); удаление аккаунта — в [users/delete](../../users/delete/spec.md).
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/Action/TaskDeleteService.php`, `api/src/Application/Service/User/Action/UserDeleteService.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
