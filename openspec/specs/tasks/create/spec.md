# Tasks Create Specification

## Purpose

Создание задачи и подзадачи по `POST /api/tasks`: обязательные и необязательные поля, проверка родителя, начальный статус и состав участников.

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
- **Covers:** `tests/Application/Service/Task/Action/TaskCreateServiceTest::testThrowsWhenParentUnknown`, `tests/Integration/Task/TaskControllerTest::testCreateChildTaskWithUnknownParentReturnsNotFound`

#### Scenario: Родитель другого пользователя
- **GIVEN** `parentId` указывает на задачу, принадлежащую другому пользователю
- **WHEN** `POST /api/tasks`
- **THEN** `201`, подзадача создана с указанным `parentId` — доступ к родителю не проверяется
- **Covers:** `tests/Application/Service/Task/Action/TaskCreateServiceTest::testCreatesChildTaskWithParentOwnedByAnotherUser`

#### Scenario: Пустое имя
- **GIVEN** в запросе `name` отсутствует или пуст
- **WHEN** `POST /api/tasks`
- **THEN** `400 VALIDATION_ERROR`, задача не создана
- **Covers:** `tests/Integration/Task/TaskControllerTest::testCreateTaskRejectsEmptyName`

## Notes

- Состав задачи в ответе — в [tasks](../spec.md); правила доступа — в [access](../access/spec.md).
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/Action/TaskCreateService.php`, `api/src/Application/Policy/Task/CreateTaskPolicy.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
