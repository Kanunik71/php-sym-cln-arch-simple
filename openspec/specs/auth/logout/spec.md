# Auth Logout Specification

## Purpose

Выход из системы по `POST /api/auth/logout`: отзыв семейства сессии по предъявленному refresh-токену.

## Requirements

### Requirement: Logout отзывает family сессии
The API SHALL на `POST /api/auth/logout` (`refreshToken`) отзывать всю `familyId` предъявленного токена, включая уже ротированный, и возвращать `204` даже для неизвестного токена; другие family пользователя не затрагиваются.

#### Scenario: Успешный logout
- **GIVEN** пользователь имеет активный refresh-токен
- **WHEN** `POST /api/auth/logout` с этим токеном
- **THEN** `204`; повторный `/refresh` с ним → `401`
- **Covers:** `testLogoutRevokesRefreshToken`

#### Scenario: Неизвестный токен
- **GIVEN** `refreshToken` неизвестен
- **WHEN** `POST /api/auth/logout`
- **THEN** `204`, существование токена не раскрывается

## Notes

- Формат refresh-токена и семейства сессии — в [auth](../spec.md); ротация токена — в [refresh](../refresh/spec.md).
- Access-токен после logout остаётся валинен до истечения TTL (≤900 с).
- Источники: `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/User/Action/UserLogoutService.php`, `api/src/Application/Service/Auth/RefreshTokenService.php`, `api/tests/Integration/Auth/AuthControllerTest.php`.
