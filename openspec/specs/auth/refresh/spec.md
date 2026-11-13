# Auth Refresh Specification

## Purpose

Ротация refresh-токена по `POST /api/auth/refresh`: выдача новой пары токенов с атомарным отзывом предъявленного, отзыв семейства при повторном использовании.

## Requirements

### Requirement: Ротация refresh-токена
The API SHALL на `POST /api/auth/refresh` (`refreshToken`) выдавать новую пару токенов и отзывать предъявленный refresh-токен атомарно; `expiresAt` нового токена = now + 30 дней (без абсолютного потолка сессии). В БД хранится только `sha256`-хеш токена; plaintext не пишется.

#### Scenario: Успешная ротация
- **GIVEN** пользователь вошёл и получил пару токенов
- **WHEN** `POST /api/auth/refresh` с текущим `refreshToken`
- **THEN** `200`, новая пара; старый refresh больше не работает (`401 INVALID_REFRESH_TOKEN`)
- **Covers:** `testRefreshRotatesToken`

#### Scenario: Повторное использование отозванного токена
- **GIVEN** refresh-токен уже был ротирован
- **WHEN** клиент повторно присылает старый токен
- **THEN** `401 INVALID_REFRESH_TOKEN`; все активные токены его family отозваны
- **Covers:** `testRefreshReuseRevokesFamily`

#### Scenario: Неизвестный или истёкший токен
- **GIVEN** refresh-токен неизвестен, истёк или отозван
- **WHEN** `POST /api/auth/refresh`
- **THEN** `401 INVALID_REFRESH_TOKEN` без раскрытия причины

## Notes

- Формат refresh-токена и семейства сессии — в [auth](../spec.md); выдача пары токенов — в [login](../login/spec.md).
- Источники: `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/User/Action/UserRefreshService.php`, `api/src/Application/Service/Auth/RefreshTokenService.php`, `api/tests/Integration/Auth/AuthControllerTest.php`.
