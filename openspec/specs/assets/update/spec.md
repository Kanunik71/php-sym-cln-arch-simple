# Assets Update Specification

## Purpose

Обновление и удаление ассета его владельцем: замена полей и набора изображений, неизменность типа и владельца, каскадное удаление файлов.

## Requirements

### Requirement: Обновление и удаление
The API SHALL разрешать `PUT /api/assets/{id}` (`name`, `price`, `imageFileIds` заменяются целиком; `type`, задачи, владелец неизменны — `200`, `AssetViewModel`) и `DELETE /api/assets/{id}` (`204`) только владельцу; удаление вместе с изображениями выполняет удаление строк файлов и purge storage.

#### Scenario: Обновление владельцем
- **GIVEN** владелец ассета
- **WHEN** `PUT /api/assets/{id}` с новым `name`/`price`/`imageFileIds`
- **THEN** `200`, поля обновлены
- **Covers:** `testUpdateAssetReturnsOk`, `AssetUpdateServiceTest::testUpdatesAsset`

#### Scenario: Удаление владельцем
- **GIVEN** владелец ассета с изображениями
- **WHEN** `DELETE /api/assets/{id}`
- **THEN** `204`; строки файлов удалены из БД и storage
- **Covers:** `testDeleteAssetReturnsNoContent`

#### Scenario: Изменение типа и владельца
- **GIVEN** владелец ассета
- **WHEN** `PUT /api/assets/{id}` с `type` или `ownerId`
- **THEN** тип, задачи и владелец не изменяются — значения из body игнорируются

## Notes

- Состав ассета — в [assets](../spec.md); изображения — в [images](../images/spec.md).
- Источники: `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Application/Service/Asset/Action/AssetUpdateService.php`, `api/src/Application/Service/Asset/Action/AssetDeleteService.php`, `api/src/Application/Service/Asset/Lifecycle/AssetLifecycleService.php`, `api/tests/Integration/Asset/AssetControllerTest.php`.
