# Task Groups Specification

## Purpose

Домен групп задач: создание группы, привязка и отвязка задач, вычисление статуса по составу, права на изменение, сводка, удаление, консольный пересчёт. Поведение разложено по use-case'ам на подразделы: [create](create/spec.md), [status](status/spec.md), [bind](bind/spec.md), [access](access/spec.md), [summary](summary/spec.md), [delete](delete/spec.md), [recalculate](recalculate/spec.md).

## Requirements

### Requirement: Поля группы задач
The API SHALL хранить группу задач со следующими наблюдаемыми полями:

- `id` — uuid, 36 симв., уникальный
- `ownerId` — uuid владельца
- `name` — string ≤255, обязателен
- `status` — `Initial` | `InProgress` | `Completed`
- `createdAt` — date-time ATOM

#### Scenario: Формат идентификационных полей
- **GIVEN** существует группа
- **WHEN** проверяются её идентификационные поля
- **THEN** `id` — 36-символьный UUID v7, `ownerId` — идентификатор создателя, `status` — одно из трёх значений

### Requirement: Состав группы в ответе
The API SHALL возвращать группу в JSON-объекте с полями: `id` (uuid-строка), `ownerId` (uuid-строка), `name` (string), `status` (одно из `Initial`/`InProgress`/`Completed`), `createdAt` (date-time ATOM).

#### Scenario: Форма группы в ответе
- **GIVEN** существует группа
- **WHEN** клиент читает группу
- **THEN** JSON содержит поля `id`, `ownerId`, `name`, `status`, `createdAt`

## Notes

- `TaskGroupStatusEnum`: `Initial`, `InProgress`, `Completed` (события `TaskGroupStatusChangedEvent` больше нет).
- Debounce-lock: `task_group_recalc.task.{taskId}` и `task_group_recalc.groups.{sha256(sorted ids)}`, TTL 30 с; при исключении в обработчике lock живёт до истечения TTL.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_TASK_ACCESS`/`UNAUTHORIZED_TASK_GROUP_ACCESS` (403), `TASK_NOT_FOUND`/`TASK_GROUP_NOT_FOUND` (404).
- Источники: `api/src/Presentation/Controller/TaskGroup/**`, `api/src/Presentation/Controller/Task/TaskTaskGroupController.php`, `api/src/Application/Service/TaskGroup/**`, `api/src/Application/Policy/TaskGroup/TaskGroupAccessPolicy.php`, `api/src/Infrastructure/Messaging/TaskGroup/Handler/RecalculateTaskGroupsMessageHandler.php`.

## Покрытие тестами

- Не покрыты: ошибки `400` и `404` в сценариях создания, привязки и отвязки группы.
