# Files Download Specification

## Purpose

Скачивание файла по подписанному URL без JWT: проверка подписи и срока действия, потоковая выдача содержимого.

## Requirements

### Requirement: Скачивание по подписанному URL без JWT
The API SHALL отдавать `GET /api/files/{id}/download` как потоковое содержимое файла без JWT, только при валидной непросроченной подписи выданного URL; заголовки — сохранённый MIME и `Content-Disposition: inline`.

#### Scenario: Скачивание по выданному URL
- **GIVEN** подписанный `url` из ответа API (`exp` — unix, строго больше `time()`, TTL 3600 с; `sig` — HMAC-SHA256 от `fileId|exp`)
- **WHEN** `GET /api/files/{id}/download` без JWT
- **THEN** `200`, потоковое содержимое, `Content-Disposition: inline; filename="<originalName>"`
- **Covers:** `FileControllerTest::testSignedDownloadUrlWorksWithoutJwt`

#### Scenario: Плохая подпись
- **GIVEN** download без query или с чужой/просроченной подписью (даже при JWT владельца)
- **WHEN** `GET /api/files/{id}/download`
- **THEN** `403 UNAUTHORIZED_FILE_ACCESS`, не `401`; неизвестный `id` — `404`; ошибка storage — `500`

#### Scenario: Ссылка живёт после отзыва доступа
- **GIVEN** подписанный URL выдан до изменения доступа
- **WHEN** до истечения `exp` URL используется третьим лицом
- **THEN** файл отдаётся (ссылка самодостаточна до `exp`)

## Notes

- Состав файла — в [files](../spec.md); правила ролей и доступа — в требовании «Модель доступа» в [auth](../../auth/spec.md).
- Реализация: `Content-Disposition` подставляет исходное имя файла без экранирования кавычек и переводов строк.
- Источники: `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/Query/FileDownloadQueryService.php`, `api/src/Infrastructure/Routing/SymfonyFileDownloadUrlProvider.php`, `api/src/Infrastructure/Routing/FileDownloadSignature.php`, `api/tests/Integration/File/FileControllerTest.php`.
