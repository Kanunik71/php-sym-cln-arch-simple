# Assets Specification

## Purpose
Ассеты пользователя: объекты с ценой и типом (`Virtual`/`Physical`), привязанные к задачам и изображениям (файлам). Создание (standalone или в контексте задачи), привязка/отвязка, обновление, удаление.

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

### Requirement: Изображения — только свои файлы
The API SHALL при указании `imageFileIds` требовать, чтобы каждый файл принадлежал текущему пользователю (`403 UNAUTHORIZED_FILE_ACCESS`) и существовал (`404 FILE_NOT_FOUND`); статус файла не проверяется (`Temporary` повышается до `Permanent` при привязке, `Permanent` допустим). Порядок файлов определяет позиции; первый файл — `mainImage`. `PUT` заменяет `imageFileIds` целиком (включая пустой список — все изображения удаляются); новые файлы прикрепляются, удалённые — удаляются из БД и storage.

#### Scenario: Чужой файл
- **GIVEN** `imageFileIds` содержит файл другого пользователя
- **WHEN** создание или обновление ассета
- **THEN** `403 UNAUTHORIZED_FILE_ACCESS`

#### Scenario: Смена набора изображений
- **GIVEN** владелец обновляет ассет
- **WHEN** `PUT /api/assets/{id}` с новым списком `imageFileIds`
- **THEN** добавленные прикреплены (повышены при необходимости), отсутствующие удалены из БД и storage; порядок задаёт `mainImage`

### Requirement: Вывод `inProgress`
The API SHALL вычислять флаг `inProgress` как «есть связанная задача в `Initial` или `Active`»; флаг следует за статусами связанных задач.

#### Scenario: Активность ассета следует за задачами
- **GIVEN** ассет привязан к задаче
- **WHEN** задача создаётся/активируется/завершается
- **THEN** `inProgress` = true для `Initial`/`Active` и false для `Canceled`/`Finished`
- **Covers:** `AssetAttachToTaskServiceTest::testAttachesExistingAssetAndInProgressFollowsStatuses`

### Requirement: Просмотр ассетов
The API SHALL возвращать: `GET /api/assets` — страницу **своих** ассетов (`page`/`perPage`, 1 и 20 по умолчанию, максимум 100, без фильтров, порядок `createdAt DESC, id DESC`, `AssetPageModel`); `GET /api/assets/{id}` — `AssetDetailModel` **только владельцу** (`403 UNAUTHORIZED_ASSET_ACCESS`, `404 ASSET_NOT_FOUND`); `GET /api/tasks/{taskId}/assets` — `AssetViewModel[]` всех владельцев, кому есть доступ к задаче.

#### Scenario: Список и деталь
- **GIVEN** у пользователя есть ассеты
- **WHEN** `GET /api/assets` или `GET /api/assets/{id}`
- **THEN** `200`; список — `AssetPageModel`, деталь — `AssetDetailModel` (`tasks`, `images`, `inProgress`)
- **Covers:** `testListAssetsReturnsOk`, `testListRejectsInvalidPerPage`, `testGetAssetReturnsOk`, `AssetListQueryServiceTest::testPaginatesByCreatedAtThenId`

#### Scenario: Ассеты задачи
- **GIVEN** доступ к задаче, к ней привязаны ассеты разных владельцев
- **WHEN** `GET /api/tasks/{taskId}/assets`
- **THEN** `200`, `AssetViewModel[]` из всех владельцев
- **Covers:** `TaskAssetControllerTest::testListAssetsReturnsOk`

#### Scenario: Чужая деталь
- **GIVEN** ассет другого пользователя
- **WHEN** `GET /api/assets/{id}`
- **THEN** `403 UNAUTHORIZED_ASSET_ACCESS`

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

## Notes

- `AssetViewModel`: `id`, `ownerId`, `name`, `taskIds`, `price`, `type`, `createdAt` (ATOM), `inProgress`, `mainImage` (`{id, url}` или `null`). `AssetDetailModel` дополнительно содержит `tasks` (`{id, name, status, parentId}[]`) и полный `images` (`{id, url}[]`). `AssetPageModel`: `items`, `total`, `page`, `perPage`.
- `price` в БД — `NUMERIC(10,2)`, неотрицательное, до 2 знаков (`MoneyUtils`); тип неизменяем после создания.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_ASSET_ACCESS`/`UNAUTHORIZED_TASK_ACCESS`/`UNAUTHORIZED_FILE_ACCESS` (403), `ASSET_NOT_FOUND`/`TASK_NOT_FOUND`/`FILE_NOT_FOUND` (404); невалидный 36-символьный UUID — `500`.
- Событий Messenger для ассетов нет. Создание/обновление: `save()`, затем attach/sync файлов — без общей транзакции.
- Известные расхождения: нет общей транзакции для БД+файлов (`attachFiles` может упасть после `save`, ассет остаётся); `INVALID_FILE_STATE` объявлен, но не выбрасывается; `taskIds` не проверяются на дубли — см. [overview](../overview/spec.md).
- Источники: `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/**`, `api/tests/Integration/Asset/**`.
