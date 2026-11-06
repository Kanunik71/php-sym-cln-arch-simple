# Auth Specification

## Purpose
Аутентификация API: регистрация по email/паролю, логин с выдачей JWT + refresh-токена, ротация refresh-токенов, logout и отзыв сессий при смене пароля. Все маршруты публичные (firewall `api_auth`, JWT не проверяется).

## Requirements

### Requirement: Регистрация без аутентификации
The API SHALL создавать пользователя по `POST /api/auth/register` (`email`, `password` обязательны; `fname`/`lname`/`location.city`/`phone` опциональны) и возвращать `201` с данными пользователя **без** access-/refresh-токенов. Email нормализуется (trim, lower-case) и должен быть уникальным.

#### Scenario: Успешная регистрация
- **GIVEN** email свободен
- **WHEN** `POST /api/auth/register` с валидными `email` и `password`
- **THEN** ответ `201`, поля `UserRegisterResponseModel`, без `token` и `refreshToken`
- **Covers:** `tests/Integration/Auth/AuthControllerTest::testRegisterAndLoginFlow`

#### Scenario: Дубликат email
- **GIVEN** пользователь с таким email существует
- **WHEN** `POST /api/auth/register`
- **THEN** `409`, `codeId = USER_ALREADY_EXISTS`
- **Covers:** `testRegisterDuplicateEmailReturnsConflict`

#### Scenario: Необязательные поля
- **GIVEN** запрос без `fname`/`lname` или без `location`
- **WHEN** регистрация
- **THEN** `201`, соответствующие поля `null`
- **Covers:** `testRegisterWithoutNames`, `testRegisterWithCity`

### Requirement: Политика пароля
The API SHALL требовать пароль длиной 6–72 байта (непустой; пробелы по краям сохраняются) при регистрации и при смене пароля, иначе `400`, `codeId = VALIDATION_ERROR`.

#### Scenario: Слишком короткий пароль
- **GIVEN** пароль короче 6 байт
- **WHEN** регистрация или смена пароля
- **THEN** `400`, `codeId = VALIDATION_ERROR`
- **Covers:** `testRegisterRejectsShortPassword`, `testUpdateRejectsShortPassword`

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

### Requirement: Смена пароля отзывает все refresh-сессии
The API SHALL при непустом `password` в `PUT /api/users/{id}` отзывать все refresh-токены пользователя в той же транзакции, что и сохранение нового хэша профиля; `password = null` означает «не менять», пустая строка — `400 VALIDATION_ERROR`.

#### Scenario: Смена пароля отзывает сессии
- **GIVEN** у пользователя есть refresh-токены
- **WHEN** `PUT /api/users/{id}` с непустым `password`
- **THEN** все прежние refresh-токены перестают работать; отзыв зафиксирован одной транзакцией с новым хэшем
- **Covers:** `testPasswordChangeRevokesRefreshTokens`

### Requirement: JWT-claims
The API SHALL выдавать access JWT (HS256, TTL 900) с claims `roles = ["ROLE_USER"]`, `username = userId`, `email`; при bearer-аутентификации по id загружается пользователь через `DoctrineUserProvider`.

#### Scenario: Защищённые маршруты
- **GIVEN** пользователь аутентифицирован
- **WHEN** запрос на любой `/api/*` вне `/api/auth/*`
- **THEN** доступ разрешён; без или с битым JWT — `401 INVALID_CREDENTIALS`

### Requirement: Асинхронное приветствие
The API SHALL после регистрации публиковать `UserRegisteredEvent` в `async` (RabbitMQ); handler `UserRegisteredMessageHandler` вызывает отправку email-уведомления `user_registered` (см. [notifications](../notifications/spec.md)). После логина, refresh и logout событий нет.

#### Scenario: Письмо после регистрации
- **GIVEN** успешная регистрация
- **WHEN** worker обрабатывает `UserRegisteredEvent`
- **THEN** отправлено email-уведомление `user_registered`

### Requirement: Очистка refresh-токенов
The API SHALL ежедневно (cron `30 3 * * *`, `SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`) удалять только строки refresh-токенов, у которых `expiresAt` или `revokedAt` старше 7 суток, командой `app:auth:cleanup-refresh-tokens`. Окно удержания обеспечивает отзыв family при повторном использовании отозванного токена.

#### Scenario: Окно удержания
- **GIVEN** отозванный refresh-токен младше 7 суток
- **WHEN** выполняется очистка
- **THEN** строка сохранена; повтор использования токена отзывает его family

## Notes

- Известные расхождения с целевой архитектурой: гонка unique-email не превращается в `409` (`500`); нет `UserLoginServiceTest` и теста `401 INVALID_CREDENTIALS` — см. [overview](../overview/spec.md).
- Валидация полей: `email` — trim/lowercase/`FILTER_VALIDATE_EMAIL`/≤255; `fname`/`lname` — trim, пусто → `null`, ≤100; `phone` — E.164, ≤20. `codeId`: `VALIDATION_ERROR` (400), `USER_ALREADY_EXISTS` (409), `INVALID_CREDENTIALS` (401), `INVALID_REFRESH_TOKEN` (401).
- Refresh-токен: формат `rt_` + 32 случайных байта (base64url); family — одна сессия логина; ротация — атомарный `UPDATE ... WHERE revoked_at IS NULL`.
- Access-токен после logout остаётся валиден до истечения TTL (≤900 с).
- События из старой baseline (TTL 3600, без refresh-токенов) заменены на вышеуказанные при реализации 0002-auth.
