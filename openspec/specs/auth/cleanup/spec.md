# Auth Cleanup Specification

## Purpose

Периодическая очистка просроченных refresh-токенов по расписанию.

## Requirements

### Requirement: Очистка refresh-токенов
The API SHALL ежедневно (cron `30 3 * * *`, `SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`) удалять только строки refresh-токенов, у которых `expiresAt` или `revokedAt` старше 7 суток, командой `app:auth:cleanup-refresh-tokens`. Окно удержания обеспечивает отзыв family при повторном использовании отозванного токена.

#### Scenario: Окно удержания
- **GIVEN** отозванный refresh-токен младше 7 суток
- **WHEN** выполняется очистка
- **THEN** строка сохранена; повторное использование токена отзывает его family

## Notes

- Формат refresh-токена и семейства сессии — в [auth](../spec.md); ротация и отзыв family — в [refresh](../refresh/spec.md).
- Планировщик и правила запуска — в [async](../../async/spec.md).
- Источники: `api/src/Presentation/Console/Auth/CleanupRefreshTokensConsole.php`, `api/src/Application/Service/Auth/RefreshTokenService.php`.
