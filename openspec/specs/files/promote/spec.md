# Files Promote Specification

## Purpose

Повышение временного файла до постоянного при привязке к ассету или аватару: условия повышения и неизменяемые поля файла.

## Requirements

### Requirement: Повышение при привязке
The API SHALL при прикреплении файла к ассету или аватару переводить его в `Permanent`; `Temporary` уже постоянные файлы остаются без изменений. Поведение применено при:
- прикреплении файлов (создание/обновление ассета) — каждый файл;
- аватаре в `PUT /api/users/{id}`.

#### Scenario: Повышение до постоянного
- **GIVEN** временный файл владельца
- **WHEN** привязка к ассету или аватару
- **THEN** `status = Permanent`; `id`, имя, MIME, размер, `createdAt` не меняются (обновляется `updatedAt`)
- **Covers:** `FileUploadTmpServiceTest::testUpdateUserPromotesAvatarFile`

## Notes

- Состав файла — в [files](../spec.md); привязка к ассету — в [assets/images](../../assets/images/spec.md); обновление профиля — в [users/update](../../users/update/spec.md).
- Реализация: повышение выполняется как `move` в storage с последующим `save` в БД, без общей транзакции.
- Источники: `api/src/Application/Service/File/Lifecycle/FileLifecycleService.php`, `api/src/Application/Policy/File/AttachFilePolicy.php`, `api/tests/Application/Service/File/Action/FileUploadTmpServiceTest.php`.
