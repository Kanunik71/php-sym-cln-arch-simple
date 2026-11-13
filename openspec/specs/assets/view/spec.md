# Assets View Specification

## Purpose

Просмотр ассетов: список своих, деталь по идентификатору и список ассетов задачи.

## Requirements

### Requirement: Просмотр ассетов
The API SHALL возвращать: `GET /api/assets` — страницу **своих** ассетов (`page`/`perPage`, 1 и 20 по умолчанию, максимум 100, без фильтров, порядок `createdAt DESC, id DESC`, `AssetPageModel`); `GET /api/assets/{id}` — `AssetDetailModel` **только владельцу** (`403 UNAUTHORIZED_ASSET_ACCESS`, `404 ASSET_NOT_FOUND`); `GET /api/tasks/{taskId}/assets` — `AssetViewModel[]` всех владельцев, кому есть доступ к задаче.

#### Scenario: Список и деталь
- **GIVEN** у пользователя есть ассеты
- **WHEN** `GET /api/assets` или `GET /api/assets/{id}`
- **THEN** `200`; список — `AssetPageModel`, деталь — `AssetDetailModel` (`tasks`, `images`, `inProgress`)
- **Covers:** `testListAssetsReturnsOk`, `testListRejectsInvalidPerPage`, `testGetAssetReturnsOk`, `AssetListQueryServiceTest::testPaginatesByCreatedAtThenId`

#### Scenario: Состав детального ответа
- **GIVEN** у пользователя есть ассет с задачами и изображениями
- **WHEN** `GET /api/assets/{id}`
- **THEN** `200`, деталь содержит `tasks` и `images` помимо полей ассета
- **Covers:** `tests/Integration/Asset/AssetControllerTest::testGetAssetReturnsOk`

#### Scenario: Ассеты задачи
- **GIVEN** доступ к задаче, к ней привязаны ассеты разных владельцев
- **WHEN** `GET /api/tasks/{taskId}/assets`
- **THEN** `200`, `AssetViewModel[]` из всех владельцев
- **Covers:** `TaskAssetControllerTest::testListAssetsReturnsOk`

#### Scenario: Чужая деталь
- **GIVEN** ассет другого пользователя
- **WHEN** `GET /api/assets/{id}`
- **THEN** `403 UNAUTHORIZED_ASSET_ACCESS`

## Notes

- Состав ассета — в [assets](../spec.md); конверт постраничного ответа — в [query](../../query/spec.md); доступ к задаче — в [tasks/access](../../tasks/access/spec.md).
- Источники: `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/Query/AssetListQueryService.php`, `api/src/Application/Service/Asset/Query/AssetQueryService.php`, `api/src/Application/Service/Asset/Query/AssetListTaskQueryService.php`, `api/tests/Integration/Asset/AssetControllerTest.php`.
