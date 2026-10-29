# Files — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-28 |
| Источники | `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/**`, `api/src/Application/Model/File/**`, `api/src/Application/Policy/File/**`, `api/src/Infrastructure/Storage/LocalFileStorageAdapter.php`, `api/config/services.yaml`, `api/tests/Integration/File/**`, `api/tests/Application/Service/File/**` |

## Контекст

Файлы загружаются сначала как временные, затем при привязке к ассету/аватару
становятся постоянными. Временные файлы старше TTL удаляются по расписанию.
Скачивание — потоковое, по подписанному URL, без JWT: так ссылка работает в `<img src>`.

## Акторы и доступ

- Загрузка и метаданные — `ROLE_USER`, JWT обязателен.
- Метаданные читает только владелец (`FileAccessPolicy`).
- Скачивание не требует JWT. Пропуск — query `exp` и `sig` в `url`, который API
  уже отдал в ответе (ассет задачи, аватар, деталь своего ассета).
- Загрузка доступна любому аутентифицированному пользователю.
- Каталог storage не публичный. UUID без подписи не скачивается.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | загрузить изображение | использовать его в ассете/аватаре |
| US-2 | пользователь | получить метаданные файла | отображать имя/тип/размер |
| US-3 | пользователь | скачать свой файл | использовать оригинал |
| US-4 | система | удалять просроченные временные файлы | не копить мусор |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | Загрузка — `multipart/form-data`, поле `file` |
| FR-2 | Разрешены `image/jpeg`, `image/png`, `image/webp`, `image/gif` |
| FR-3 | Размер > 0 и ≤ 10 MiB (10 485 760 байт) |
| FR-4 | Новый файл получает `Temporary`, ключ `tmp/{id}` |
| FR-5 | При привязке файл становится `Permanent`, ключ `permanent/{id}` |
| FR-6 | Скачивание отдаёт `Content-Disposition: inline`, оригинальный MIME |
| FR-7 | Временные файлы старше 24 часов удаляются командой/планировщиком |
| FR-8 | Download URL содержит `exp` (unix) и `sig` = HMAC-SHA256 от `fileId\|exp`, ключ `%kernel.secret%`. Срок — `app.file.download_url_ttl_seconds` (3600). `exp` строго больше `time()` |

## Сценарии

### S-1: Загрузка

- **Given** аутентифицированный пользователь и изображение ≤10 MiB
- **When** `POST /api/files` с полем `file`
- **Then** `201`, `FileViewModel` со `status = Temporary`; файл записан в storage
- **Тест:** `FileUploadTmpServiceTest::testUploadCreatesTemporaryFileInStorage`

### S-2: Повышение до постоянного

- **Given** временный файл владельца
- **When** привязка к ассету или аватару
- **Then** `status = Permanent`, storage key `permanent/{id}`
- **Тест:** `FileUploadTmpServiceTest::testUpdateUserPromotesAvatarFile`

### S-3: Метаданные и скачивание

- **Given** файл принадлежит пользователю
- **When** `GET /api/files/{id}` с JWT владельца
- **Then** `200`, `FileViewModel`
- **When** `GET /api/files/{id}/download?exp&sig` с подписью из выданного `url`
- **Then** `200`, `StreamedResponse`, без JWT
- **Тест:** `FileControllerTest::testSignedDownloadUrlWorksWithoutJwt`

### S-4: Чужой файл и плохая подпись

- **Given** файл принадлежит другому пользователю
- **When** `GET /api/files/{id}` с чужим JWT
- **Then** `403 UNAUTHORIZED_FILE_ACCESS`
- **When** download без query или с чужой/просроченной подписью
- **Then** `403 UNAUTHORIZED_FILE_ACCESS` (даже для владельца)
- **Тест:** `FileControllerTest::testSignedDownloadUrlWorksWithoutJwt` (download без query)

### S-5: Очистка

- **Given** временные файлы старше TTL
- **When** `app:files:cleanup-tmp`
- **Then** файлы удалены из storage и БД, выведен счётчик

### S-6: Без аутентификации

- **Given** нет JWT
- **When** `POST /api/files` или `GET /api/files/{id}`
- **Then** `401`
- **When** download без подписи
- **Then** `403`, не `401`
- **Тест:** `FileControllerTest::testFilesRequireAuthentication`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| POST | `/api/files` | multipart `file` | `201`, `FileViewModel` | `400 VALIDATION_ERROR`, `401`, `500` при ошибке storage |
| GET | `/api/files/{id}` | path | `200`, `FileViewModel` | `401`, `403`, `404`, `500` на невалидном 36-символьном id |
| GET | `/api/files/{id}/download` | path + query `exp`, `sig` | `200`, поток | `403`, `404`, `500` |

### Upload

- Поле обязательно: иначе `400` (`Field "file" is required.`).
- MIME определяется `UploadedFile::getClientMimeType()`; при пустом значении —
  по расширению (`jpg/jpeg`, `png`, `gif`, `webp`), иначе `400`.
- Размер — `getSize()`, при `false/0` — `filesize()`; пустой файл → `400`.
- Оригинальное имя не санитизируется (только trim/длина ≤255 в `FileModel`).
- Содержимое не проверяется (не валидируется, что это изображение).

### Response `FileViewModel`

`id`, `ownerId`, `originalName`, `mimeType`, `sizeBytes`, `status`, `createdAt`
(ATOM), `updatedAt` (ATOM). `storageKey` не возвращается.

`FileStatusEnum`: `Temporary`, `Permanent`.

### Download

`Content-Type` = сохранённый `mimeType`; `Content-Disposition: inline;
filename="<originalName>"`. Тело — поток из storage (без буферизации в память).

JWT не проверяется. `exp` и `sig` обязательны. Подпись считает
`FileDownloadSignature`, в URL её кладёт `SymfonyFileDownloadUrlProvider`
(`FileItemModel.url`). Пересланная ссылка работает до `exp`, даже если доступ
к задаче уже отозван. Содержимое файла не проверяется.

## Модели и инварианты

`FileModel`:

- `id` — UUID v7; `ownerId`; `originalName` (trim, 1..255);
- `mimeType` (trim, 1..127); `sizeBytes` > 0;
- `storageKey` (trim, 1..512) = `tmp/{id}` или `permanent/{id}`;
- `status`, `createdAt`, `updatedAt`;
- `createTemporary()` → `Temporary`, `tmp/{id}`;
- `promotedToPermanent()` → `Permanent`, `permanent/{id}`; для `Permanent`
  возвращает self;
- промоушен не меняет `id`, имя, MIME, размер, `createdAt`; обновляет
  `updatedAt`.

## Хранилище

- Адаптер `LocalFileStorageAdapter` (`FileStorageInterface`):
  `write`, `readStream`, `move`, `delete` по `storageKey`.
- База — `app.file_storage.path`.
- Файлы лежат вне `public/`, доступ только через контроллер.

## Жизненный цикл

```text
upload        -> Temporary, tmp/{id}
attach        -> move tmp/{id} -> permanent/{id}, Permanent
delete        -> удалить storage + строку БД
cleanup (>24h)-> удалить storage + строку БД (только Temporary)
```

- `attachFiles()` повышает каждый файл; уже `Permanent` остаётся как есть.
- `syncFileIds(old, new)`: `array_diff` → добавить/удалить; удаление файла
  удаляет и storage.
- При ошибке записи в storage после `save` строка БД удаляется (компенсация).

## Очистка

- `FileCleanupTmpService`: `updatedBefore = now - 24h`;
  `listExpiredTemporary(updatedBefore)`;
- для каждого: `storage->delete`, затем `repository->delete`;
- возвращает количество удалённых.
- `app:files:cleanup-tmp` выводит `Deleted N temporary file(s).`
- Scheduler: cron `0 3 * * *` (env `SCHEDULER_FILE_CLEANUP_CRON`).

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | нет поля `file`, пустой файл, размер > лимита, MIME не разрешён |
| `UNAUTHORIZED_FILE_ACCESS` | 403 | чужие метаданные; download без верной непросроченной подписи |
| `FILE_NOT_FOUND` | 404 | файл не найден |
| `INVALID_FILE_STATE` | 403 | объявлен, но нигде не выбрасывается |
| — | 500 | ошибка чтения/записи storage; невалидный 36-символьный UUID |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| MIME | доверяет client MIME / расширению, содержимое не проверяется | принято: проверку содержимого не делаем | `FileController::resolveUploadedMimeType` |
| Тесты | успешные upload/get не покрыты Integration-тестами | полнота контракта | `FileControllerTest` |
| Очистка | storage удаляется до БД, без транзакции | согласованность | `FileCleanupTmpService` |
| Имя | `Content-Disposition` не экранирует кавычки/переводы строк | безопасный заголовок | `FileController::download` |
| Промоушен | `move` + `save` без транзакции; при сбое `save` файл уже перемещён | компенсация | `FileLifecycleService::promote` |

## Первоисточники

- `api/src/Presentation/Controller/File/FileController.php`
- `api/src/Presentation/Console/File/CleanupTmpFilesConsole.php`
- `api/src/Application/Model/File/**`
- `api/src/Application/Service/File/**`
- `api/src/Application/Policy/File/**`
- `api/src/Application/Enum/File/FileStatusEnum.php`
- `api/src/Application/Port/File/{FileRepositoryInterface,FileStorageInterface,FileDownloadUrlProviderInterface}.php`
- `api/src/Infrastructure/Storage/LocalFileStorageAdapter.php`
- `api/src/Infrastructure/Routing/SymfonyFileDownloadUrlProvider.php`
- `api/src/Infrastructure/Routing/FileDownloadSignature.php`
- `api/config/services.yaml` (`app.file.*`, allowed MIME)
- `api/src/Infrastructure/Scheduler/AppScheduleProvider.php`
- `api/tests/Integration/File/FileControllerTest.php`
- `api/tests/Application/Service/File/Action/FileUploadTmpServiceTest.php`

## Открытые вопросы

- [x] Проверку реального MIME/содержимого не делаем.
- [x] Участник качает картинку, если API уже отдал её `url` (в списке ассетов задачи это `mainImage`). Остальные изображения детали по-прежнему только у владельца.
- [x] Скачивание — подписанный URL, не JWT: `<img src>` не шлёт `Authorization`.
