# Files Specification

## Purpose
Загрузка файлов: изображение сначала сохраняется как временное, при привязке (ассет/аватар) становится постоянным; метаданные и скачивание (по подписанному URL, без JWT); очистка просроченных временных файлов по расписанию.

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

### Requirement: Повышение при привязке
The API SHALL при прикреплении файла к ассету или аватару переводить его в `Permanent`; `Temporary` уже постоянные файлы остаются без изменений. Поведение применено при:
- `attachFiles()` (создание/ассет-обновление) — каждый файл;
- аватаре в `PUT /api/users/{id}`.

#### Scenario: Повышение до постоянного
- **GIVEN** временный файл владельца
- **WHEN** привязка к ассету или аватару
- **THEN** `status = Permanent`; `id`, имя, MIME, размер, `createdAt` не меняются (обновляется `updatedAt`)
- **Covers:** `FileUploadTmpServiceTest::testUpdateUserPromotesAvatarFile`

### Requirement: Метаданные — только владельцу
The API SHALL отдавать `GET /api/files/{id}` (`200`, `FileViewModel`) только владельцу файла (`403 UNAUTHORIZED_FILE_ACCESS` иначе, `404 FILE_NOT_FOUND`).

#### Scenario: Чужой файл
- **GIVEN** файл принадлежит другому пользователю
- **WHEN** `GET /api/files/{id}` с чужим JWT
- **THEN** `403 UNAUTHORIZED_FILE_ACCESS`
- **Covers:** `FileControllerTest::testSignedDownloadUrlWorksWithoutJwt` (metadata)

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

### Requirement: Очистка временных файлов
The API SHALL удалять только `Temporary` файлы старше 24 часов из storage и БД по команде `app:files:cleanup-tmp` (cron `0 3 * * *`, `SCHEDULER_FILE_CLEANUP_CRON`) с выводом счётчика удалённых. `Permanent` не удаляются.

#### Scenario: Очистка
- **GIVEN** временные файлы старше TTL
- **WHEN** `app:files:cleanup-tmp`
- **THEN** файлы удалены из storage и БД; вывод вида `Deleted N temporary file(s).`

## Notes

- `FileViewModel`: `id`, `ownerId`, `originalName`, `mimeType`, `sizeBytes`, `status`, `createdAt`/`updatedAt` (ATOM); `storageKey` не возвращается. `FileStatusEnum`: `Temporary`, `Permanent`.
- storage: `LocalFileStorageAdapter` (`FileStorageInterface`: `write`, `readStream`, `move`, `delete`); ключи `tmp/{id}` и `permanent/{id}`; каталог `app.file_storage.path`, вне `public/`, UUID без подписи не скачивается.
- `attachFiles()` повышает `Temporary → Permanent`; `syncFileIds(old, new)` — array_diff: добавить/удалить (удалённый файл стирается и из storage). При ошибке записи в storage после `save` — компенсация: строка БД удаляется.
- Ошибка чтения/записи storage — `500`.
- Реализация: MIME-тип берётся из заголовка клиента, при пустом — по расширению имени файла; содержимое файла не проверяется.
- Реализация: при очистке временных файлов запись в storage удаляется раньше строки БД.
- Реализация: повышение файла до постоянного выполняется как `move` в storage с последующим `save` в БД, без общей транзакции.
- Реализация: `Content-Disposition` подставляет исходное имя файла без экранирования кавычек и переводов строк.
- Источники: `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/**`, `api/src/Policy/File/**`, `api/src/Infrastructure/Storage/LocalFileStorageAdapter.php`, `api/tests/Integration/File/FileControllerTest.php`.
