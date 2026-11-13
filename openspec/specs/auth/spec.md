# Auth Specification

## Purpose

Домен аутентификации: регистрация по email/паролю, логин с выдачей пары токенов, ротация refresh-токенов, logout с отзывом семейства сессии, ежедневная очистка просроченных токенов. Все маршруты домена публичные (firewall `api_auth`, JWT не проверяется). Поведение разложено по use-case'ам на подразделы: [register](register/spec.md), [login](login/spec.md), [refresh](refresh/spec.md), [logout](logout/spec.md), [cleanup](cleanup/spec.md). Отзыв сессий при смене пароля — в [users/update](../users/update/spec.md).

## Requirements

### Requirement: Политика пароля
The API SHALL требовать пароль длиной 6–72 байта (непустой; пробелы по краям сохраняются) при регистрации и при смене пароля, иначе `400`, `codeId = VALIDATION_ERROR`.

#### Scenario: Слишком короткий пароль
- **GIVEN** пароль короче 6 байт
- **WHEN** регистрация или смена пароля
- **THEN** `400`, `codeId = VALIDATION_ERROR`
- **Covers:** `testRegisterRejectsShortPassword`, `testUpdateRejectsShortPassword`

### Requirement: Формат refresh-токена и семейства сессии
The API SHALL выдавать refresh-токен в формате `rt_` + 32 случайных байта (base64url); одна сессия логина образует семейство (`familyId`), ротация сохраняет семейство; в БД хранится только `sha256`-хеш токена — само значение не пишется; `expiresAt` нового токена = now + 30 дней, без абсолютного потолка сессии.

#### Scenario: Хранение токена
- **GIVEN** пользователь получил refresh-токен
- **WHEN** токен сохраняется
- **THEN** в БД хранится только `sha256`-хеш токена; plaintext не записывается

## Notes

- Реализация: уникальность `email` обеспечивается проверкой `existsByEmail` в Policy и `UNIQUE`-constraint на колонке; при гонке между проверкой и записью возможен `500` вместо `409 USER_ALREADY_EXISTS`.
- Валидация полей: `email` — trim/lowercase/`FILTER_VALIDATE_EMAIL`/≤255; `fname`/`lname` — trim, пусто → `null`, ≤100; `phone` — E.164, ≤20. `codeId`: `VALIDATION_ERROR` (400), `USER_ALREADY_EXISTS` (409), `INVALID_CREDENTIALS` (401), `INVALID_REFRESH_TOKEN` (401).
- Ротация токена — атомарный `UPDATE ... WHERE revoked_at IS NULL`.
- Access-токен после logout остаётся валиден до истечения TTL (≤900 с).
- Отзыв refresh-сессий при смене пароля — в [users/update](../users/update/spec.md).
- Источники: `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/Auth/**`, `api/src/Application/Service/User/Action/UserRegisterService.php`, `api/tests/Integration/Auth/AuthControllerTest.php`.
