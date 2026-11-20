# Task Groups Status Specification

## Purpose

Вычисление статуса группы по составу задач и асинхронный пересчёт, защищённый локом от параллельного запуска.

## Requirements

### Requirement: Вычисление статуса группы
The API SHALL вычислять статус группы: 0 задач → `Initial`; есть задача в `Initial` или `Active` → `InProgress`; иначе → `Completed`. Пересчёт выполняется асинхронно (`TaskGroupRecalculationByTaskRequestedEvent` → `RecalculateTaskGroupsByTaskMessageHandler` для групп задачи, `TaskGroupRecalculationByGroupIdsRequestedEvent` → `RecalculateTaskGroupsByGroupIdsMessageHandler` для указанных групп), батчами по 1000, и статус сохраняется, только если изменился.

#### Scenario: Пересчёт по составу
- **GIVEN** группа с задачами в терминальных статусах
- **WHEN** обработано событие пересчёта
- **THEN** статус группы — `Completed`, сохранён одним `save`
- **Covers:** `TaskGroupRecalculationServiceTest::testRecalculateByTaskIdPersistsCompletedStatus`, `testRecalculateSkipsSaveWhenStatusUnchanged`

#### Scenario: Один пересчёт по задаче
- **GIVEN** обработчик уже пересчитывает группы задачи
- **WHEN** приходит повторное событие пересчёта по той же задаче
- **THEN** повторный пересчёт пропущен — лок `task_group_recalc.task.{taskId}` уже взят
- **Covers:** `RecalculateTaskGroupsByTaskMessageHandlerTest::testSkipsRecalculationWhileChannelIsLocked`

#### Scenario: Один пересчёт по набору групп
- **GIVEN** обработчик уже пересчитывает указанные группы
- **WHEN** приходит повторное событие пересчёта по тому же набору групп
- **THEN** повторный пересчёт пропущен — лок `task_group_recalc.groups.{sha256(sorted ids)}` уже взят
- **Covers:** `RecalculateTaskGroupsByGroupIdsMessageHandlerTest::testSkipsRecalculationWhileChannelIsLocked`

#### Scenario: Лок освобождается после пересчёта
- **GIVEN** пересчёт канала завершился
- **WHEN** приходит новое событие пересчёта по тому же каналу
- **THEN** пересчёт выполняется: лок снят, ресурс снова свободен
- **Covers:** `RecalculateTaskGroupsByTaskMessageHandlerTest::testReleasesLockAfterRecalculation`, `RecalculateTaskGroupsByGroupIdsMessageHandlerTest::testReleasesLockAfterRecalculation`

#### Scenario: Триггеры пересчёта
- **GIVEN** группа существует
- **WHEN** создаётся группа с задачами, привязывается новая задача, отвязывается существующая, меняется статус задачи или задача удаляется
- **THEN** пересчёт запрашивается; при создании пустой группы, удалении группы и создании задачи — не запрашивается

## Notes

- Состав группы — в [task-groups](../spec.md); ключи lock пересчёта — в [task-groups](../spec.md) → Notes.
- Пересчёт состава сбрасывает кеш сводки групп (см. [summary](../summary/spec.md)): счётчики статусов в сводке выведены из задач, поэтому после пересчёта сводка считается заново.
- Источники: `api/src/Application/Service/TaskGroup/Lifecycle/TaskGroupRecalculationService.php`, `api/src/Application/Cache/TaskGroup/TaskGroupSummaryCache.php`, `api/src/Application/Event/TaskGroup/TaskGroupRecalculationByTaskRequestedEvent.php`, `api/src/Application/Event/TaskGroup/TaskGroupRecalculationByGroupIdsRequestedEvent.php`, `api/src/Application/Port/Messaging/MessageBusInterface.php`, `api/src/Application/Port/TaskGroup/TaskGroupLockInterface.php`, `api/src/Infrastructure/Lock/TaskGroupLock.php`, `api/src/Infrastructure/Messaging/MessengerMessageBus.php`, `api/src/Infrastructure/Messaging/TaskGroup/Handler/**`, `api/tests/Application/Service/TaskGroup/Lifecycle/TaskGroupRecalculationServiceTest.php`, `api/tests/Infrastructure/Messaging/TaskGroup/Handler/**`, `api/tests/Infrastructure/Lock/TaskGroupLockTest.php`.
