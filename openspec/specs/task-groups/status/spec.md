# Task Groups Status Specification

## Purpose

Вычисление статуса группы по составу задач и асинхронный пересчёт с debounce-lock.

## Requirements

### Requirement: Вычисление статуса группы
The API SHALL вычислять статус группы: 0 задач → `Initial`; есть задача в `Initial` или `Active` → `InProgress`; иначе → `Completed`. Пересчёт выполняется асинхронно (`TaskGroupsRecalculationRequestedEvent` → `RecalculateTaskGroupsMessageHandler`), батчами по 1000, и статус сохраняется, только если изменился.

#### Scenario: Пересчёт по составу
- **GIVEN** группа с задачами в терминальных статусах
- **WHEN** обработано событие пересчёта
- **THEN** статус группы — `Completed`, сохранён одним `save`
- **Covers:** `TaskGroupRecalculationServiceTest::testRecalculateByTaskIdPersistsCompletedStatus`, `testRecalculateSkipsSaveWhenStatusUnchanged`

#### Scenario: Дебаунс повторов
- **GIVEN** цепочка событий пересчёта по одной задаче в течение 30 секунд
- **WHEN** обработчик уже обрабатывает первый
- **THEN** повторные события пропущены через cache-lock (`task_group_recalc.task.{taskId}`, TTL 30 с, снимается после обработки)

#### Scenario: Триггеры пересчёта
- **GIVEN** группа существует
- **WHEN** создаётся группа с задачами, привязывается новая задача, отвязывается существующая, меняется статус задачи или задача удаляется
- **THEN** пересчёт запрашивается; при создании пустой группы, удалении группы и создании задачи — не запрашивается

## Notes

- Состав группы — в [task-groups](../spec.md); ключи debounce-lock — в [task-groups](../spec.md) → Notes.
- Источники: `api/src/Application/Service/TaskGroup/Lifecycle/TaskGroupRecalculationService.php`, `api/src/Application/Dispatcher/TaskGroup/TaskGroupRecalculationDispatcher.php`, `api/src/Infrastructure/Messaging/TaskGroup/Handler/RecalculateTaskGroupsMessageHandler.php`, `api/tests/Application/Service/TaskGroup/Lifecycle/TaskGroupRecalculationServiceTest.php`.
