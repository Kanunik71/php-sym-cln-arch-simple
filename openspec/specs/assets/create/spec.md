# Assets Create Specification

## Purpose

Создание ассета по `POST /api/assets` — отдельно и в контексте задачи.

## Requirements

### Requirement: Создание ассета
The API SHALL создавать ассет через `POST /api/assets` (`name` обязателен, trim, ≤255; `price` обязателен, ≥0, ≤99 999 999.99, округление до 2 знаков; `type` обязателен: `Virtual` или `Physical`; `taskIds[]`, `imageFileIds[]` опциональны) с `201` и `AssetViewModel`; владелец — всегда текущий пользователь (`ownerId` из body не принимается). При создании доступны `201`, ошибки: `400 VALIDATION_ERROR`, `403 UNAUTHORIZED_*`, `404 TASK/FILE_NOT_FOUND`.

#### Scenario: Standalone-создание с задачами и картинками
- **GIVEN** пользователь имеет доступ к задачам и владеет загруженными файлами
- **WHEN** `POST /api/assets` с `name`, `price`, `type`, `taskIds`, `imageFileIds`
- **THEN** `201`, `AssetViewModel`; задачи привязаны, изображения прикреплены (`Temporary` повышены до `Permanent`)
- **Covers:** `tests/Integration/Asset/AssetControllerTest::testCreateAssetReturnsCreated`, `AssetCreateServiceTest::testCreatesStandaloneAssetWithTaskIds`

#### Scenario: Создание в контексте задачи
- **GIVEN** пользователь имеет доступ к `taskId`
- **WHEN** `POST /api/tasks/{taskId}/assets` с валидным созданием
- **THEN** `201`; ассет привязан **только** к задаче из пути (идентифицирующим является `taskId` из пути, `taskIds` из body игнорируются)
- **Covers:** `tests/Integration/Task/TaskAssetControllerTest::testCreateAssetReturnsCreated`

#### Scenario: Невалидный тип
- **GIVEN** `type` отсутствует или не `Virtual`/`Physical`
- **WHEN** создание
- **THEN** `400 VALIDATION_ERROR`

#### Scenario: Порядок прикрепления файлов относительно сохранения
- **GIVEN** в `imageFileIds` передан файл, который не удалось прикрепить
- **WHEN** создание ассета
- **THEN** ассет уже сохранён и остаётся в БД; ошибка прикрепления не откатывает сохранение
- **Covers:** `tests/Application/Service/Asset/Action/AssetCreateServiceTest::testKeepsAssetSavedWhenFileAttachFails`

## Notes

- Состав ассета — в [assets](../spec.md); изображения — в [images](../images/spec.md).
- Реализация: сохранение ассета и прикрепление файлов идут последовательно, без общей транзакции — при сбое прикрепления ассет остаётся сохранённым.
- Реализация: повторяющиеся `taskIds` не отбрасываются — в БД связь хранится один раз, но в ответе `taskIds` задача присутствует столько раз, сколько указано в запросе.
- Источники: `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/Action/AssetCreateService.php`, `api/tests/Integration/Asset/AssetControllerTest.php`.
