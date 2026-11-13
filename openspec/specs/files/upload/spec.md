# Files Upload Specification

## Purpose

Загрузка изображения по `POST /api/files` во временное хранилище.

## Requirements

### Requirement: Загрузка изображения
The API SHALL принимать `POST /api/files` (`multipart/form-data`, поле `file`), записи: MIME из `image/jpeg`, `image/png`, `image/webp`, `image/gif` (при пустом client MIME — по расширению); размер > 0 и ≤ 10 MiB (10 485 760 байт); содержимое файла не проверяется. Успех — `201` с `FileViewModel`, `status = Temporary`; файл записан в storage. Ошибки: `400 VALIDATION_ERROR` (нет поля, пустой файл, размер>лимита, запрещённый MIME), `401` без JWT.

#### Scenario: Успешная загрузка
- **GIVEN** аутентифицированный пользователь и изображение ≤10 MiB
- **WHEN** `POST /api/files` с полем `file`
- **THEN** `201`, `FileViewModel` со `status = Temporary`; файл записан в storage
- **Covers:** `tests/Application/Service/File/Action/FileUploadTmpServiceTest::testUploadCreatesTemporaryFileInStorage`

#### Scenario: Нарушения лимитов
- **GIVEN** файл > 10 MiB, пустой или с запрещённым MIME
- **WHEN** `POST /api/files`
- **THEN** `400 VALIDATION_ERROR`

#### Scenario: Без JWT
- **GIVEN** нет JWT
- **WHEN** `POST /api/files` или `GET /api/files/{id}`
- **THEN** `401`
- **Covers:** `FileControllerTest::testFilesRequireAuthentication`

## Notes

- Состав файла — в [files](../spec.md); правила доступа — в требовании «Модель доступа» в [auth](../../auth/spec.md).
- Реализация: MIME-тип берётся из заголовка клиента, при пустом — по расширению имени файла; содержимое файла не проверяется.
- Реализация: при ошибке записи в storage после сохранения строки БД строка БД удаляется (компенсация).
- Источники: `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/Action/FileUploadTmpService.php`, `api/src/Application/Policy/File/UploadFilePolicy.php`, `api/tests/Integration/File/FileControllerTest.php`.
