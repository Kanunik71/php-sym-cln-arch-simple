# Assets — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-29 |
| Источники | `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/**`, `api/src/Application/Model/Asset/**`, `api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineAssetRepository.php`, `api/tests/Integration/{Asset,Task}/**`, `api/tests/Application/Service/Asset/**` |

## Контекст

Ассет — объект с ценой и типом (`Virtual`/`Physical`), принадлежащий
пользователю, связанный с задачами и изображениями (файлами). Ассеты можно
создавать отдельно, в контексте задачи, привязывать/отвязывать и удалять.

## Акторы и доступ

- Все маршруты — `ROLE_USER`, JWT обязателен.
- Владелец ассета (`ownerId`) — единственный, кто может читать детально,
  изменять и удалять ассет.
- Для операций в контексте задачи дополнительно проверяется доступ к задаче
  (`TaskAccessPolicy`).
- Список ассетов задачи доступен любому, у кого есть доступ к задаче, и
  возвращает ассеты всех владельцев.
- `detach` проверяет доступ к задаче и владельца ассета.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | вести каталог своих ассетов | учитывать цены и типы |
| US-2 | участник задачи | добавлять ассеты к задаче | связывать ресурсы с работой |
| US-3 | участник задачи | привязывать/отвязывать существующие ассеты | управлять составом |
| US-4 | пользователь | видеть, «в работе» ли ассет | понимать его актуальность |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | `name` обязателен, trim, ≤255; `price` ≥ 0, `NUMERIC(10,2)`, ≤2 знаков |
| FR-2 | `type` обязателен при создании: `Virtual` или `Physical` |
| FR-3 | `imageFileIds` — список UUID; порядок = позиции; первый = `mainImage` |
| FR-4 | Изображения должны принадлежать пользователю; `Temporary` повышаются при привязке, `Permanent` допускаются |
| FR-5 | `inProgress` = есть связанная задача в `Initial` или `Active` |
| FR-6 | `GET /api/assets` — страница своих ассетов: `page`/`perPage` (1 и 20 по умолчанию, максимум 100), без фильтров |
| FR-7 | Привязка идемпотентна; отвязка последней задачи удаляет ассет |
| FR-8 | Удаление ассета удаляет его изображения (БД + storage) |

## Сценарии

### S-1: Создание standalone-ассета

- **Given** пользователь с задачами и загруженными файлами
- **When** `POST /api/assets` c `name`, `price`, `type`, `taskIds`, `imageFileIds`
- **Then** `201`, `AssetViewModel`; задачи привязаны, изображения прикреплены
- **Тесты:** `AssetControllerTest::testCreateAssetReturnsCreated`,
  `AssetCreateServiceTest::testCreatesStandaloneAssetWithTaskIds`

### S-2: Создание в контексте задачи

- **Given** доступ к задаче
- **When** `POST /api/tasks/{taskId}/assets`
- **Then** `201`; ассет привязан только к задаче из пути; `taskIds` из body
  игнорируются
- **Тест:** `TaskAssetControllerTest::testCreateAssetReturnsCreated`

### S-3: Список и деталь

- **Given** ассеты пользователя/задачи
- **When** `GET /api/assets` или `GET /api/assets/{id}` или
  `GET /api/tasks/{taskId}/assets`
- **Then** `200`; свой список — `AssetPageModel`, деталь — `AssetDetailModel`,
  список задачи — `AssetViewModel[]`
- **Тесты:** `AssetControllerTest::testListAssetsReturnsOk`,
  `testListRejectsInvalidPerPage`, `testGetAssetReturnsOk`,
  `TaskAssetControllerTest::testListAssetsReturnsOk`,
  `AssetListQueryServiceTest::testPaginatesByCreatedAtThenId`

### S-4: Привязка

- **Given** доступ к задаче и владение ассетом
- **When** `POST /api/tasks/{taskId}/assets/{id}/attach`
- **Then** `200`; повторная привязка — `200` без дубликата
- **Тест:** `TaskAssetControllerTest::testAttachAssetReturnsOk`,
  `AssetAttachToTaskServiceTest::testAttachesExistingAssetAndInProgressFollowsStatuses`

### S-5: Отвязка

- **Given** пользователь владеет ассетом и ассет привязан к задаче
- **When** `DELETE /api/tasks/{taskId}/assets/{id}`
- **Then** `204`; если задача была последней — ассет удалён; чужой ассет — `403`
- **Тесты:** `TaskAssetControllerTest::testDetachAssetReturnsNoContent`,
  `AssetDetachFromTaskServiceTest::testDeletesAssetWhenLastTaskDetached`,
  `testKeepsAssetWhenOtherTasksRemain`, `testRejectsDetachByNonOwner`

### S-6: Обновление и удаление

- **Given** пользователь владеет ассетом
- **When** `PUT /api/assets/{id}` или `DELETE /api/assets/{id}`
- **Then** `200` / `204`; при `PUT` `name`, `price` и `imageFileIds` заменяются целиком
- **Тесты:** `AssetControllerTest::testUpdateAssetReturnsOk`,
  `testDeleteAssetReturnsNoContent`, `AssetUpdateServiceTest::testUpdatesAsset`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| GET | `/api/assets` | `page`, `perPage` | `200`, `AssetPageModel` | `400`, `401` |
| POST | `/api/assets` | `AssetCreateModel` | `201`, `AssetViewModel` | `400`, `403 FILE`, `404 FILE/TASK`, `401` |
| GET | `/api/assets/{id}` | path | `200`, `AssetDetailModel` | `403`, `404`, `500` на невалидном 36-символьном id |
| PUT | `/api/assets/{id}` | `AssetUpdateModel` | `200`, `AssetViewModel` | `400`, `403`, `404`, `500` |
| DELETE | `/api/assets/{id}` | path | `204` | `403`, `404`, `500` |
| GET | `/api/tasks/{taskId}/assets` | — | `200`, `AssetViewModel[]` | `403`, `404 TASK`, `500` |
| POST | `/api/tasks/{taskId}/assets` | `AssetCreateModel` | `201`, `AssetViewModel` | `400`, `403`, `404`, `500` |
| POST | `/api/tasks/{taskId}/assets/{id}/attach` | — | `200`, `AssetViewModel` | `403`, `404 TASK/ASSET`, `500` |
| DELETE | `/api/tasks/{taskId}/assets/{id}` | — | `204` | `403`, `404`, `500` |

### Request `AssetCreateModel`

| Поле | Тип | Обяз. | Ограничения |
|------|-----|-------|-------------|
| `name` | string | да | trim; непустое; ≤255 |
| `price` | number | да | ≥ 0; ≤ 99 999 999.99; округление до 2 знаков (`MoneyUtils`) |
| `type` | string | да | `Virtual` \| `Physical` |
| `taskIds` | string[] | нет | UUID; доступ к каждой задаче проверяется |
| `imageFileIds` | string[] | нет | UUID; владение и статус файла проверяются |

`ownerId` в body не принимается — всегда текущий пользователь.

### Request `AssetUpdateModel`

| Поле | Тип | Обяз. | Семантика |
|------|-----|-------|-----------|
| `name` | string | да | trim; непустое; ≤255 |
| `price` | number | да | ≥ 0; ≤ 99 999 999.99; округление до 2 знаков |
| `imageFileIds` | string[] | нет | отсутствует/`null` → `[]`: все изображения удаляются |

`type`, `taskIds`, `ownerId` при обновлении не меняются.

### Response `AssetViewModel`

`id`, `ownerId`, `name`, `taskIds`, `price`, `type`, `createdAt` (ATOM),
`inProgress`, `mainImage` (`{id, url}|null`). `mainImage` — первый файл по
позиции.

`AssetPageModel`: `items` (`AssetViewModel[]`), `total`, `page`, `perPage`.
Порядок — `createdAt` DESC, затем `id` DESC.

### Response `AssetDetailModel`

`id`, `ownerId`, `name`, `tasks` (`TaskLightModel[]` — `id`, `name`, `status`,
`parentId`), `price`, `type`, `createdAt`, `inProgress`, `images`
(`{id, url}[]`).

## Модели и инварианты

- `AssetModel`: `id` (UUID v7), `ownerId`, `name`, `price`, `type`,
  `createdAt`, `updatedAt`.
- `withNameAndPrice()` меняет только имя и цену; тип неизменяем.
- `AssetTypeEnum`: `Virtual`, `Physical`.
- `AssetFileEntity` — join-таблица `asset_file` с `position`;
  `UNIQUE(asset_id, file_id)`.
- `inProgress` вычисляется по связанным задачам:
  `TaskStatusEnum::keepsAssetInProgress()` (`Initial`, `Active`).
- `price` в БД — `NUMERIC(10,2)`; конвертация `MoneyUtils`.

## Права доступа

| Действие | Проверка |
|----------|----------|
| list own | фильтр `owner_id` |
| get / update / delete | `AssetAccessPolicy::assertOwner` |
| create | доступ к каждой задаче + владение файлами |
| create in task | доступ к задаче из пути |
| list by task | доступ к задаче |
| attach | доступ к задаче + владение ассетом |
| detach | доступ к задаче + владение ассетом + принадлежность ассета задаче |

## Файлы и изображения

- `imageFileIds` непустые → каждый файл должен принадлежать пользователю,
  иначе `403`. Статус не проверяется: `Temporary` и `Permanent` допустимы.
- После сохранения вызывается `FileLifecycleService::attachFiles(...)` —
  перевод `Temporary → Permanent` (уже постоянные остаются без изменений).
- Обновление вызывает `syncFileIds(old, new, userId)`: новые прикрепляются,
  удалённые удаляются из БД и storage.
- Удаление ассета: `FileLifecycleService::deleteRecords` → удаление строк файлов
  → `purgeStorage` после удаления ассета.
- Смена порядка изображений меняет `mainImage`.

## Побочные эффекты и транзакции

- Создание: `assetRepository->save()` (flush), затем `attachFiles` — без
  общей транзакции.
- Обновление: `save()` (flush), затем `syncFileIds` — без транзакции.
- Отвязка последней задачи: `AssetLifecycleService::delete` → удаление ассета,
  изображений и purge storage.
- Отвязка при нескольких задачах: `detachTask` + `detach` managed entity.
- Событий Messenger для ассетов нет.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидный JSON/поля/цена/тип/списки id/`page`/`perPage` |
| `UNAUTHORIZED_ASSET_ACCESS` | 403 | чужой ассет или ассет не в задаче |
| `UNAUTHORIZED_TASK_ACCESS` | 403 | нет доступа к задаче |
| `UNAUTHORIZED_FILE_ACCESS` | 403 | чужой файл |
| `ASSET_NOT_FOUND` | 404 | ассет не найден |
| `TASK_NOT_FOUND` | 404 | задача не найдена |
| `FILE_NOT_FOUND` | 404 | изображение не найдено |
| — | 500 | невалидный 36-символьный UUID; несогласованные id |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Транзакции | create/update без общей транзакции | атомарность БД + файлы | `AssetCreateService`, `AssetUpdateService` |
| Состояние файла | `INVALID_FILE_STATE` объявлен и ловится, но не выбрасывается | проверка состояния | `AttachFilePolicy` |
| Создание | `taskIds` не проверяются на дубли | нормализация | `AssetCreateService` |
| Storage | при ошибке `attachFiles` после `save` остаётся ассет | откат/компенсация | `AssetCreateService` |

## Первоисточники

- `api/src/Presentation/Controller/Asset/AssetController.php`
- `api/src/Presentation/Controller/Task/TaskAssetController.php`
- `api/src/Application/Model/Asset/**`
- `api/src/Application/Service/Asset/**`
- `api/src/Application/Policy/Asset/AssetAccessPolicy.php`
- `api/src/Application/Enum/Asset/AssetTypeEnum.php`
- `api/src/Application/Port/Asset/AssetRepositoryInterface.php`
- `api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineAssetRepository.php`
- `api/src/Infrastructure/Persistence/Doctrine/Mapper/AssetMapper.php`
- `api/src/Infrastructure/Persistence/Doctrine/Query/Asset/**`
- `api/src/Infrastructure/Persistence/Doctrine/Entity/{AssetEntity,AssetFileEntity}.php`
- `api/tests/Integration/Asset/AssetControllerTest.php`
- `api/tests/Integration/Task/TaskAssetControllerTest.php`
- `api/tests/Application/Service/Asset/**`
