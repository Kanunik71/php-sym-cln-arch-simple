# Assets Images Specification

## Purpose

Изображения ассета: прикрепление собственных файлов, порядок и главное изображение, замена набора при обновлении.

## Requirements

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

#### Scenario: Пустой набор изображений
- **GIVEN** владелец обновляет ассет
- **WHEN** `PUT /api/assets/{id}` с пустым `imageFileIds`
- **THEN** все изображения ассета удалены; `mainImage` — `null`

## Notes

- Состав ассета и `mainImage` — в [assets](../spec.md); обновление — в [update](../update/spec.md); статусы файлов — в [files](../../files/spec.md).
- Источники: `api/src/Application/Service/Asset/Action/AssetCreateService.php`, `api/src/Application/Service/Asset/Action/AssetUpdateService.php`, `api/src/Application/Policy/File/AttachFilePolicy.php`, `api/src/Application/Service/File/Lifecycle/FileLifecycleService.php`, `api/tests/Integration/Asset/AssetControllerTest.php`.
