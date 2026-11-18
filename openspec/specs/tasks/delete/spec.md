# Tasks Delete Specification

## Purpose

Удаление задачи владельцем по `DELETE /api/tasks/{id}`: снятие связей с группами и ассетами, обнуление родителя у дочерних задач, удаление задач вместе с аккаунтом.

## Requirements

### Requirement: Удаление задачи владельцем
The API SHALL позволять владельцу `DELETE /api/tasks/{id}` с `204`, удаляя `task_users`, снимая связи с ассетами и группами, а у дочерних задач обнуляя `parentId`; ассеты и группы остаются, события `TaskDeleted` нет. Удаление пользователя удаляет его задачи тем же путём и снимает его с чужих.

#### Scenario: Успешное удаление
- **GIVEN** владелец удаляет свою задачу с подзадачей и ассетами
- **WHEN** `DELETE /api/tasks/{id}`
- **THEN** `204`; участники удалены, связи с ассетами и группами сняты, у соседей `parentId` стал `null`
- **Covers:** `tests/Integration/Task/TaskControllerTest` (delete)

#### Scenario: Удаление при удалении пользователя
- **GIVEN** пользователь удаляет аккаунт
- **WHEN** `UserDeleteService` выполняется
- **THEN** его задачи удалены (с пересчётом затронутых групп), из чужих задач он снят

## Notes

- Правила доступа и владения — в [access](../access/spec.md); удаление аккаунта — в [users/delete](../../users/delete/spec.md).
- Связи с ассетами и группами снимает Application до удаления задачи — в БД таких каскадов нет; правило — в `api/src/Infrastructure/CLAUDE.md` → «Каскадные удаления».
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/Action/TaskDeleteService.php`, `api/src/Application/Service/User/Action/UserDeleteService.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
