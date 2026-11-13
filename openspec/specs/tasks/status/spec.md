# Tasks Status Specification

## Purpose

Переходы статусов задачи по `PATCH /api/tasks/{id}/status`: допустимые переходы, обязательные поля перехода, работа с участниками и датами завершения.

## Requirements

### Requirement: Переходы статусов
The API SHALL допускать переходы `Initial→Active`, `Active→Canceled`, `Active→Finished` через `PATCH /api/tasks/{id}/status` и отклонять остальные (`409 INVALID_TASK_STATUS_TRANSITION`). Переход в `Active` требует `estimateTime`, в `Canceled` — непустой `cancelReason` (≤1000); `finishedDate`/`cancellationDate` ставит сервер; self-transition запрещён (`status=Initial` — `400`).

#### Scenario: Активация и завершение
- **GIVEN** задача в `Initial`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Active`, `estimateTime`, `userId`, затем `status=Finished`
- **THEN** `200`; при завершении выставлен `finishedDate`
- **Covers:** `TaskChangeStatusServiceTest::testActivateAndFinishTask`

#### Scenario: Недопустимый переход
- **GIVEN** задача в `Initial`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Finished`
- **THEN** `409 INVALID_TASK_STATUS_TRANSITION`
- **Covers:** `testInvalidStatusTransitionReturnsConflict`, `TaskChangeStatusServiceTest::testInvalidStatusTransitionIsRejected`

#### Scenario: Отмена с причиной
- **GIVEN** задача в `Active`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Canceled` и `cancelReason`
- **THEN** `200`, сохранены `cancelReason` и `cancellationDate`
- **Covers:** `testCancelRequiresReasonAndSetsDates`

### Requirement: Участники задачи
The API SHALL при переходе в `Active` с переданным `userId` добавлять пользователя в участники: новый участник создаётся со статусом `Active`, существующий переводится в `Active` принудительно; уникальность пары задача-пользователь соблюдается; создатель задачи в участники не добавляется.

#### Scenario: Добавление участника
- **GIVEN** задача в `Initial`
- **WHEN** `PATCH /api/tasks/{id}/status` с `status=Active` и `userId`
- **THEN** пользователь добавлен в `users` со статусом `Active`

## Notes

- Правила переходов и статусов задачи — в [tasks](../spec.md); доступ к смене статуса — в [access](../access/spec.md).
- Реализация: `withForcedStatus()` переходы не проверяет — статус участника меняется принудительно.
- Источники: `api/src/Presentation/Controller/Task/TaskController.php`, `api/src/Application/Service/Task/Action/TaskChangeStatusService.php`, `api/src/Application/Service/Task/Lifecycle/TaskUserListService.php`, `api/tests/Integration/Task/TaskControllerTest.php`.
