# Tasks Specification

## Purpose

Домен задач: создание задач и подзадач, переходы статусов, участники, доступ, лист, удаление, пересчёт связанных групп. Поведение разложено по use-case'ам на подразделы: [create](create/spec.md), [status](status/spec.md), [access](access/spec.md), [list](list/spec.md), [delete](delete/spec.md), [recalculation](recalculation/spec.md).

## Requirements

### Requirement: Поля задачи
The API SHALL хранить задачу со следующими наблюдаемыми полями:

- `id` — uuid, 36 симв., уникальный
- `ownerId` — uuid владельца
- `name` — string ≤255, обязателен
- `parentId` — uuid родителя, nullable
- `estimateTime` — int ≥ 0, nullable
- `status` — `Initial` | `Active` | `Canceled` | `Finished`
- `createdAt` — date-time ATOM
- `users` — участники `{userId, status}`[]

#### Scenario: Формат идентификационных полей
- **GIVEN** существует задача
- **WHEN** проверяются её идентификационные поля
- **THEN** `id` — 36-символьный UUID v7, `ownerId` — идентификатор создателя, `status` — одно из четырёх значений

### Requirement: Состав задачи в ответе
The API SHALL возвращать задачу в JSON-объекте с полями: `id` (uuid-строка), `ownerId` (uuid-строка), `name` (string), `parentId` (uuid-строка|null), `estimateTime` (int|null), `status` (одно из `Initial`/`Active`/`Canceled`/`Finished`), `createdAt` (date-time ATOM), `users` (массив `{userId, status}`), `cancelReason` (string|null), `finishedDate` (date-time|null), `cancellationDate` (date-time|null); дочерние задачи, ассеты и группы в ответе не возвращаются.

#### Scenario: Форма задачи в ответе
- **GIVEN** существует задача
- **WHEN** клиент читает задачу
- **THEN** JSON содержит поля `id`, `ownerId`, `name`, `parentId`, `estimateTime`, `status`, `createdAt`, `users`, `cancelReason`, `finishedDate`, `cancellationDate`

## Notes

- `TaskStatusEnum`: `Initial`→`Active`; `Active`→`Canceled|Finished`; `Canceled`/`Finished` — терминальные. `keepsAssetInProgress()` = `Initial`/`Active` (используется пересчётом групп и `inProgress` ассетов). `TaskUserStatusEnum`: `Initial→Active|Declined`; `Active→Declined|Finished`; `withForcedStatus()` переходы не проверяет.
- Участники меняются только при переходе в `Active` с `userId` (существующий → принудительно `Active`, новый создаётся `Active`; `UNIQUE(task_id, user_id)`); создатель в участники не добавляется.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_TASK_ACCESS` (403), `TASK_NOT_FOUND` (404), `INVALID_TASK_STATUS_TRANSITION` (409).
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/**`, `api/src/Application/Enum/Task/**`, `api/tests/Integration/Task/TaskControllerTest.php`.
