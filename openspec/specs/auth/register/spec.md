# Auth Register Specification

## Purpose

Регистрация пользователя по `POST /api/auth/register`: создание учётной записи, состав ответа, публикация события приветствия.

## Requirements

### Requirement: Регистрация без аутентификации
The API SHALL создавать пользователя по `POST /api/auth/register` (`email`, `password` обязательны; `fname`/`lname`/`location.city`/`phone` опциональны) и возвращать `201` с данными пользователя **без** access-/refresh-токенов. Email нормализуется (trim, lower-case) и должен быть уникальным.

#### Scenario: Успешная регистрация
- **GIVEN** email свободен
- **WHEN** `POST /api/auth/register` с валидными `email` и `password`
- **THEN** ответ `201` с данными пользователя, без `token` и `refreshToken`
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

### Requirement: Состав ответа регистрации
The API SHALL возвращать на `POST /api/auth/register` JSON-объект с полями: `id` (uuid-строка), `fname` (string|null), `lname` (string|null), `email` (string), `location` (объект `{city}`|null), `phone` (string|null); `createdAt`, `avatar` и токены в ответе SHALL отсутствовать.

#### Scenario: Поля ответа регистрации
- **GIVEN** email свободен
- **WHEN** `POST /api/auth/register` с валидными `email` и `password`
- **THEN** ответ содержит поля `id`, `fname`, `lname`, `email`, `location`, `phone`; полей `createdAt`, `avatar`, `token`, `refreshToken` нет
- **Covers:** `tests/Integration/Auth/AuthControllerTest::testRegisterAndLoginFlow`

### Requirement: Асинхронное приветствие
The API SHALL после регистрации публиковать `UserRegisteredEvent` в `async` (RabbitMQ); обработчик вызывает отправку email-уведомления `user_registered` (см. [notifications](../../notifications/spec.md)). После логина, refresh и logout событий нет.

#### Scenario: Письмо после регистрации
- **GIVEN** успешная регистрация
- **WHEN** worker обрабатывает `UserRegisteredEvent`
- **THEN** отправлено email-уведомление `user_registered`

## Notes

- Политика пароля — в [auth](../spec.md); состав профиля пользователя — в [users](../../users/spec.md).
- Источники: `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/User/Action/UserRegisterService.php`, `api/src/Infrastructure/Messaging/User/Handler/UserRegisteredMessageHandler.php`, `api/tests/Integration/Auth/AuthControllerTest.php`.
