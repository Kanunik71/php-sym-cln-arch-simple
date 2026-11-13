# Users Specification

## Purpose

Домен пользователей: профиль (чтение, обновление, аватар, смена пароля), поиск с фильтрами и сортировкой, удаление аккаунта. Читать можно всех; менять — только себя. Поведение разложено по use-case'ам на подразделы: [list](list/spec.md), [get](get/spec.md), [query](query/spec.md), [query-meta](query-meta/spec.md), [update](update/spec.md), [delete](delete/spec.md).

## Requirements

### Requirement: Идентификационные поля пользователя
The API SHALL хранить идентификационные поля пользователя: `id` — uuid (UUID v7, 36 символов, уникальный, генерируется сервером), `email` — string (≤ 255 символов, trim + lower-case, корректный адрес, уникальный, обязателен), `password` — string (≤ 255 символов, хэш; входной пароль 6..72 байта).

#### Scenario: Идентификационные поля профиля
- **GIVEN** профиль пользователя прочитан
- **WHEN** клиент читает ответ
- **THEN** присутствуют `id` и `email`; `password` не отдаётся

### Requirement: Поля профиля пользователя
The API SHALL хранить поля профиля пользователя: `fname` — string (≤ 100 символов, trim, пусто → `null`, nullable), `lname` — string (≤ 100 символов, trim, пусто → `null`, nullable), `city` — string (≤ 100 символов, trim, пусто → `null`, nullable), `phone` — string (≤ 20 символов, формат E.164 `+79001234567`, nullable).

#### Scenario: Незаполненные поля профиля
- **GIVEN** у пользователя не заданы `fname`, `lname`, `city`, `phone`
- **WHEN** клиент читает профиль
- **THEN** эти поля равны `null`

### Requirement: Служебные поля пользователя
The API SHALL хранить служебные поля пользователя: `createdAt` — date-time (ATOM, проставляется сервером, обязателен), `updatedAt` — date-time (ATOM, обновляется при каждой записи, обязателен), `avatarFileId` — uuid (ссылка на файл-аватар, nullable).

#### Scenario: Служебные поля профиля
- **GIVEN** профиль пользователя прочитан
- **WHEN** клиент читает ответ
- **THEN** присутствуют `createdAt`; `avatarFileId` — `null`, если аватар не задан

### Requirement: Представление пользователя в API
The API SHALL представлять пользователя в ответах в виде: `id`, `fname`, `lname`, `email`, `createdAt`, `location` (`{city}` | `null`), `avatar` (`{id, url}` | `null`), `phone`.

#### Scenario: Форма профиля в ответе
- **GIVEN** профиль пользователя прочитан
- **WHEN** клиент читает ответ
- **THEN** в ответе присутствуют `id`, `email`, `createdAt`; `fname`, `lname`, `location`, `phone` — `null`, если не заданы

## Notes

- Карта разделов: [list](list/spec.md) (`GET /api/users`), [get](get/spec.md) (`GET /api/users/{id}`), [query](query/spec.md) (`GET /api/users/query`), [query-meta](query-meta/spec.md) (`GET /api/users/query/meta`), [update](update/spec.md) (`PUT /api/users/{id}`), [delete](delete/spec.md) (`DELETE /api/users/{id}`).
- Правила нормализации и валидации полей при записи профиля — в [update](update/spec.md).
- Модели домена: `UserViewModel` — `id`, `fname`, `lname`, `email`, `createdAt`, `location`, `avatar`, `phone`; `UserPageModel` — `items`, `total`, `page`, `perPage`; `UserUpdateModel` — `email` (обязателен), `fname`, `lname`, `password`, `location`, `avatarFileId`, `phone`.
- Реестр `codeId` домена: `VALIDATION_ERROR` (400), `UNAUTHORIZED_USER_ACCESS` и `UNAUTHORIZED_FILE_ACCESS` (403), `USER_NOT_FOUND` и `FILE_NOT_FOUND` (404), `USER_ALREADY_EXISTS` (409).
- Аутентификация и `401` — см. [auth](../auth/spec.md); файлы и аватары — [files](../files/spec.md); уведомления — [notifications](../notifications/spec.md).
- Домен `users` не содержит операции создания: регистрация живёт в [auth](../auth/spec.md).
- Маршрут `/api/users/me/notification-preferences` относится к домену [notifications](../notifications/spec.md), а не к `users`.
- Источники: `api/src/Presentation/Controller/User/UserController.php`, `api/src/Application/Service/User/**`, `api/src/Infrastructure/Persistence/Doctrine/Query/User/**`, `api/tests/Integration/User/UserControllerTest.php`.
