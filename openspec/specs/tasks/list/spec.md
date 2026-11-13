# Tasks List Specification

## Purpose

Лист задач пользователя по `GET /api/tasks`: постраничная выдача, состав выдачи по участию и открытости задач, сортировка.

## Requirements

### Requirement: Лист задач
The API SHALL возвращать `GET /api/tasks` как страницу (1 и 20 по умолчанию, максимум 100; без фильтров) задач, где пользователь есть в `task_users` или список участников пуст; сортировка `createdAt DESC, id DESC`; статус задачи доступ не ограничивает; лист Policy не проверяет.

#### Scenario: Лист доступных задач
- **GIVEN** у пользователя есть свои и открытые задачи
- **WHEN** `GET /api/tasks?page=1&perPage=20`
- **THEN** `200`, `TaskPageModel` (`items`, `total`, `page`, `perPage`), сортировка `createdAt DESC, id DESC`
- **Covers:** `tests/Integration/Task/TaskControllerTest::testListTasksReturnsOk`

## Notes

- Конверт постраничного ответа — в [query](../../query/spec.md); состав задачи — в [tasks](../spec.md).
- Реализация: лист задач не кэшируется.
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/Query/TaskListQueryService.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
