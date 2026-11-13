# Assets Binding Specification

## Purpose

Привязка и отвязка ассетов в контексте задачи: идемпотентность привязки, каскадное удаление ассета при отвязке последней задачи.

## Requirements

### Requirement: Привязка и отвязка в контексте задачи
The API SHALL поддерживать `POST /api/tasks/{taskId}/assets/{id}/attach` (идемпотентно, без дубликата) и `DELETE /api/tasks/{taskId}/assets/{id}`; обе операции требуют доступ к задаче **и** владение ассетом (`403 UNAUTHORIZED_*`). При отвязке последней привязанной задачи ассет удаляется вместе с изображениями (БД + storage); при других привязанных задачах ассет сохраняется.

#### Scenario: Привязка и повтор
- **GIVEN** доступ к задаче и владение ассетом
- **WHEN** `POST /api/tasks/{taskId}/assets/{id}/attach` (дважды)
- **THEN** оба раза `200`, связь не дублируется
- **Covers:** `TaskAssetControllerTest::testAttachAssetReturnsOk`

#### Scenario: Отвязка последней задачи
- **GIVEN** ассет привязан к единственной задаче
- **WHEN** `DELETE /api/tasks/{taskId}/assets/{id}` владельцем
- **THEN** `204`; ассет и его изображения удалены из БД и storage
- **Covers:** `testDetachAssetReturnsNoContent`, `AssetDetachFromTaskServiceTest::testDeletesAssetWhenLastTaskDetached`

#### Scenario: Отвязка при нескольких задачах
- **GIVEN** ассет привязан к двум задачам
- **WHEN** отвязывается одна
- **THEN** ассет остаётся, связь с выбранной задачей удалена
- **Covers:** `testKeepsAssetWhenOtherTasksRemain`

#### Scenario: Отвязка не-владельцем
- **GIVEN** пользователь не владеет ассетом
- **WHEN** `DELETE /api/tasks/{taskId}/assets/{id}`
- **THEN** `403 UNAUTHORIZED_ASSET_ACCESS`
- **Covers:** `testRejectsDetachByNonOwner`

## Notes

- Состав ассета — в [assets](../spec.md); флаг активности — в [progress](../progress/spec.md); доступ к задаче — в [tasks/access](../../tasks/access/spec.md).
- Источники: `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/Action/AssetAttachToTaskService.php`, `api/src/Application/Service/Asset/Action/AssetDetachFromTaskService.php`, `api/tests/Integration/Task/TaskAssetControllerTest.php`.
