# Users Specification

## Purpose

Домен пользователей: профиль (чтение, обновление, аватар, смена пароля), поиск с фильтрами и сортировкой, удаление аккаунта. Читать можно всех; менять — только себя. Поведение разложено по use-case'ам на подразделы: [list](list/spec.md), [get](get/spec.md), [query](query/spec.md), [query-meta](query-meta/spec.md), [update](update/spec.md), [delete](delete/spec.md).

## Requirements

### Requirement: Поля пользователя
The API SHALL хранить пользователя со следующими наблюдаемыми полями:

- `id` — uuid, 36 симв., уникальный
- `fname` — string ≤100, nullable
- `lname` — string ≤100, nullable
- `email` — string ≤255, trim+lower, уникальный
- `city` — string ≤100, nullable
- `phone` — string ≤20, E.164
- `createdAt` — date-time ATOM

Пароль хранится только в виде хэша; его длина и правила задаются в [auth](../auth/spec.md).

#### Scenario: Формат идентификационных полей
- **GIVEN** существует пользователь
- **WHEN** проверяются его идентификационные поля
- **THEN** `id` — 36-символьный UUID v7, `email` — корректный адрес длиной ≤255 символов, уникальный среди пользователей

### Requirement: Состав профиля в ответе
The API SHALL возвращать профиль пользователя в JSON-объекте с полями: `id` (uuid-строка), `fname` (string|null), `lname` (string|null), `email` (string), `createdAt` (date-time ATOM), `location` (объект `{city}`|null), `avatar` (объект `{id, url}`|null), `phone` (string|null); хэш пароля, `updatedAt` и идентификатор файла аватара в ответе SHALL отсутствовать.

#### Scenario: Форма профиля в ответе
- **GIVEN** существует пользователь
- **WHEN** клиент читает профиль
- **THEN** JSON содержит поля `id`, `fname`, `lname`, `email`, `createdAt`, `location`, `avatar`, `phone`; `location.city` — город пользователя или `null`; хэша пароля, `updatedAt` и `avatarFileId` в ответе нет

#### Scenario: Аватар в профиле
- **GIVEN** у пользователя задан аватар
- **WHEN** клиент читает профиль
- **THEN** `avatar` — объект `{id, url}`, где `id` — идентификатор файла, `url` — подписанный URL скачивания с `exp` и `sig` (TTL 3600 с, см. [files](../files/spec.md))
- **Covers:** `tests/Application/Service/User/Query/UserQueryServiceTest::testReturnsMappedUser`

## Notes

- Карта разделов: [list](list/spec.md) (`GET /api/users`), [get](get/spec.md) (`GET /api/users/{id}`), [query](query/spec.md) (`GET /api/users/query`), [query-meta](query-meta/spec.md) (`GET /api/users/query/meta`), [update](update/spec.md) (`PUT /api/users/{id}`), [delete](delete/spec.md) (`DELETE /api/users/{id}`).
- Аутентификация и `401` — см. [auth](../auth/spec.md); файлы и аватары — [files](../files/spec.md); уведомления — [notifications](../notifications/spec.md).
- Хранимые, но не наблюдаемые в ответе поля: `password` (хэш, ≤255 симв.), `updatedAt` (date-time ATOM), `avatarFileId` (uuid, nullable).
- Домен `users` не содержит операции создания: регистрация живёт в [auth](../auth/spec.md).
- Маршрут `/api/users/me/notification-preferences` относится к домену [notifications](../notifications/spec.md), а не к `users`.
- Источники: `api/src/Presentation/Controller/User/UserController.php`, `api/src/Application/Service/User/**`, `api/src/Infrastructure/Persistence/Doctrine/Query/User/**`, `api/tests/Integration/User/UserControllerTest.php`.
