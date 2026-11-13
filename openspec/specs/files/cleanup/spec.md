# Files Cleanup Specification

## Purpose

Периодическая очистка просроченных временных файлов по расписанию.

## Requirements

### Requirement: Очистка временных файлов
The API SHALL удалять только `Temporary` файлы старше 24 часов из storage и БД по команде `app:files:cleanup-tmp` (cron `0 3 * * *`, `SCHEDULER_FILE_CLEANUP_CRON`) с выводом счётчика удалённых. `Permanent` не удаляются.

#### Scenario: Очистка
- **GIVEN** временные файлы старше TTL
- **WHEN** `app:files:cleanup-tmp`
- **THEN** файлы удалены из storage и БД; вывод вида `Deleted N temporary file(s).`

#### Scenario: Постоянные файлы не удаляются
- **GIVEN** постоянные файлы любой давности
- **WHEN** `app:files:cleanup-tmp`
- **THEN** постоянные файлы сохранены

## Notes

- Состав файла — в [files](../spec.md); планировщик — в [platform](../../platform/spec.md).
- Реализация: при очистке запись в storage удаляется раньше строки БД.
- Источники: `api/src/Presentation/Console/File/CleanupTmpFilesConsole.php`, `api/src/Application/Service/File/Lifecycle/FileCleanupTmpService.php`.
