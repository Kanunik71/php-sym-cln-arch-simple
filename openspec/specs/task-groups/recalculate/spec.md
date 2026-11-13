# Task Groups Recalculate Specification

## Purpose

Принудительный пересчёт группы задач из консоли: команда, её аргументы и условие сохранения нового статуса.

## Requirements

### Requirement: Консольный пересчёт
The API SHALL предоставлять `app:task-groups:recalculate <id>` для принудительного пересчёта группы (в scheduler не зарегистрирована).

#### Scenario: Ручной пересчёт
- **GIVEN** id группы
- **WHEN** `app:task-groups:recalculate`
- **THEN** статус группы пересчитан и сохранён при изменении
- **Covers:** `TaskGroupRecalculateService::execute`

## Notes

- Правила вычисления статуса и debounce-lock — в [status](../status/spec.md); планировщик — в [platform](../../platform/spec.md).
- Источники: `api/src/Presentation/Console/TaskGroup/RecalculateTaskGroupsConsole.php`, `api/src/Application/Service/TaskGroup/Lifecycle/TaskGroupRecalculateService.php`.
