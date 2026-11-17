# Tasks Recalculation Specification

## Purpose

Пересчёт связанных групп задач при изменениях в задачах: публикация события пересчёта при смене статуса, удалении и создании задачи.

## Requirements

### Requirement: Пересчёт групп при изменениях
The API SHALL при смене статуса задачи публиковать `TaskGroupsRecalculationByTaskRequestedEvent` (`taskId`), при удалении — `TaskGroupsRecalculationByGroupIdsRequestedEvent` с id затронутых групп (см. [task-groups](../../task-groups/spec.md)); при создании задачи событие публикуется, но обработчик только логирует. Список задач не кэшируется.

#### Scenario: Пересчёт после смены статуса
- **GIVEN** задача входит в группы
- **WHEN** сменён статус задачи
- **THEN** группы пересчитаны асинхронно через `RecalculateTaskGroupsByTaskMessageHandler`

#### Scenario: Пересчёт после удаления
- **GIVEN** задача входит в группы
- **WHEN** задача удалена
- **THEN** событие публикуется со списком затронутых `taskGroupIds`

## Notes

- Правила вычисления статуса группы и lock пересчёта — в [task-groups](../../task-groups/spec.md).
- Реализация: список задач не кэшируется.
- Источники: `api/src/Application/Service/Task/Action/TaskChangeStatusService.php`, `api/src/Application/Service/Task/Action/TaskDeleteService.php`, `api/src/Infrastructure/Messaging/TaskGroup/Handler/**`.
