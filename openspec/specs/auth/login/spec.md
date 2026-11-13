# Auth Login Specification

## Purpose

Вход в систему по `POST /api/auth/login`: проверка credentials, выдача пары токенов, состав access JWT.

## Requirements

### Requirement: Логин с парой токенов
The API SHALL при верных credentials на `POST /api/auth/login` возвращать `200` с `token` (access JWT, TTL 900 с), `refreshToken`, `tokenType = Bearer`, `expiresIn = 900`; при верных credentials регистрация не выполняется.

#### Scenario: Успешный логин
- **GIVEN** зарегистрированный пользователь
- **WHEN** `POST /api/auth/login` с корректными `email` и `password`
- **THEN** `200`, непустые `token` и `refreshToken`, `expiresIn = 900`
- **Covers:** `testRegisterAndLoginFlow`

#### Scenario: Неверный пароль
- **GIVEN** пользователь существует
- **WHEN** `POST /api/auth/login` с неверным паролем (или email не найден)
- **THEN** `401`, `codeId = INVALID_CREDENTIALS` без раскрытия причины
- **Covers:** `tests/Application/Service/User/Action/UserLoginServiceTest::testThrowsWhenPasswordIsWrong`, `testThrowsWhenEmailIsUnknown`, `tests/Integration/Auth/AuthControllerTest::testLoginRejectsWrongPassword`, `testLoginRejectsUnknownEmail`

### Requirement: JWT-claims
The API SHALL выдавать access JWT (HS256, TTL 900) с claims `roles = ["ROLE_USER"]`, `username = userId`, `email`; при bearer-аутентификации по id загружается пользователь через `DoctrineUserProvider`.

#### Scenario: Защищённые маршруты
- **GIVEN** пользователь аутентифицирован
- **WHEN** запрос на любой `/api/*` вне `/api/auth/*`
- **THEN** доступ разрешён; без или с битым JWT — `401 INVALID_CREDENTIALS`

## Notes

- Формат refresh-токена и семейства сессии — в [auth](../spec.md); правила ролей и доступа — в требовании «Модель доступа» в [auth](../spec.md).
- Источники: `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/User/Action/UserLoginService.php`, `api/src/Application/Service/Auth/UserTokenPairService.php`, `api/src/Infrastructure/Security/LexikTokenProvider.php`, `api/src/Infrastructure/Security/DoctrineUserProvider.php`, `api/tests/Integration/Auth/AuthControllerTest.php`.
