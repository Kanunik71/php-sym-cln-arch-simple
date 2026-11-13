# Files Metadata Specification

## Purpose

Чтение метаданных файла по `GET /api/files/{id}` — только владельцу: состав ответа, запрет доступа для чужого файла.

## Requirements

### Requirement: Метаданные — только владельцу
The API SHALL отдавать `GET /api/files/{id}` (`200`, `FileViewModel`) только владельцу файла (`403 UNAUTHORIZED_FILE_ACCESS` иначе, `404 FILE_NOT_FOUND`).

#### Scenario: Чужой файл
- **GIVEN** файл принадлежит другому пользователю
- **WHEN** `GET /api/files/{id}` с чужим JWT
- **THEN** `403 UNAUTHORIZED_FILE_ACCESS`
- **Covers:** `FileControllerTest::testSignedDownloadUrlWorksWithoutJwt` (metadata)

#### Scenario: Неизвестный файл
- **GIVEN** идентификатор файла не существует
- **WHEN** `GET /api/files/{id}`
- **THEN** `404 FILE_NOT_FOUND`

## Notes

- Состав файла — в [files](../spec.md); скачивание по подписанному URL — в [download](../download/spec.md).
- Источники: `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/Query/FileQueryService.php`, `api/src/Application/Policy/File/FileAccessPolicy.php`, `api/tests/Integration/File/FileControllerTest.php`.
