# Files Specification

## Purpose

Домен файлов: загрузка изображения как временного, повышение до постоянного при привязке, метаданные и скачивание по подписанному URL, очистка просроченных временных файлов. Поведение разложено по use-case'ам на подразделы: [upload](upload/spec.md), [promote](promote/spec.md), [metadata](metadata/spec.md), [download](download/spec.md), [cleanup](cleanup/spec.md).

## Requirements

### Requirement: Поля файла
The API SHALL хранить файл со следующими наблюдаемыми полями:

- `id` — uuid, 36 симв., уникальный
- `ownerId` — uuid владельца
- `originalName` — string ≤255
- `mimeType` — string ≤127
- `sizeBytes` — int > 0
- `status` — `Temporary` | `Permanent`
- `createdAt`/`updatedAt` — date-time ATOM

#### Scenario: Формат идентификационных полей
- **GIVEN** существует файл
- **WHEN** проверяются его идентификационные поля
- **THEN** `id` — 36-символьный UUID v7, `ownerId` — идентификатор загрузившего, `status` — одно из двух значений

### Requirement: Состав файла в ответе
The API SHALL возвращать файл в JSON-объекте с полями: `id` (uuid-строка), `ownerId` (uuid-строка), `originalName` (string), `mimeType` (string), `sizeBytes` (int), `status` (`Temporary`|`Permanent`), `createdAt` (date-time ATOM), `updatedAt` (date-time ATOM); ключ хранения в ответе не возвращается.

#### Scenario: Форма файла в ответе
- **GIVEN** существует файл
- **WHEN** клиент читает метаданные файла
- **THEN** JSON содержит поля `id`, `ownerId`, `originalName`, `mimeType`, `sizeBytes`, `status`, `createdAt`, `updatedAt`; поля `storageKey` нет

### Requirement: Хранение файлов вне `public/`

The API SHALL хранить файлы в локальном каталоге вне `public/`; доступ к содержимому SHALL быть возможен только через контроллер (подписанный download URL, см. [download](download/spec.md)), прямой запрос к storage-пути через nginx SHALL давать отказ.

#### Scenario: Прямой доступ к storage

- **GIVEN** известен путь файла в каталоге storage
- **WHEN** запрос идёт через nginx без контроллера
- **THEN** файл недоступен

## Notes

- storage: `LocalFileStorageAdapter` (`FileStorageInterface`: `write`, `readStream`, `move`, `delete`); ключи `tmp/{id}` и `permanent/{id}`; каталог `app.file_storage.path`, вне `public/`, UUID без подписи не скачивается.
- `attachFiles()` повышает `Temporary → Permanent`; `syncFileIds(old, new)` — array_diff: добавить/удалить (удалённый файл стирается и из storage). При ошибке записи в storage после `save` — компенсация: строка БД удаляется.
- Ошибка чтения/записи storage — `500`.
- Реализация: MIME-тип берётся из заголовка клиента, при пустом — по расширению имени файла; содержимое файла не проверяется.
- Реализация: при очистке временных файлов запись в storage удаляется раньше строки БД.
- Реализация: повышение файла до постоянного выполняется как `move` в storage с последующим `save` в БД, без общей транзакции.
- Реализация: `Content-Disposition` подставляет исходное имя файла без экранирования кавычек и переводов строк.
- Источники: `api/src/Presentation/Controller/File/FileController.php`, `api/src/Application/Service/File/**`, `api/src/Policy/File/**`, `api/src/Infrastructure/Storage/LocalFileStorageAdapter.php`, `api/tests/Integration/File/FileControllerTest.php`.
