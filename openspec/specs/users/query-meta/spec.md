# Users Query Meta Specification

## Purpose

Мета-описание поиска пользователей: `GET /api/users/query/meta` отдаёт whitelist поддерживаемых полей, операторов, сортировки и пагинации — без самих данных.

## Requirements

### Requirement: Мета-описание поиска
The API SHALL для аутентифицированного пользователя (см. [auth](../../auth/spec.md)) возвращать на `GET /api/users/query/meta` код `200` с whitelist поддерживаемых полей и операторов: `filters` (`{field, type, operators}[]`), `sortFields`, `sortDirections`, `defaultSort`, `pagination`.

#### Scenario: Получение мета-описания
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users/query/meta`
- **THEN** `200` с `filters`, `sortFields`, `sortDirections`, `defaultSort`, `pagination`
- **Covers:** `tests/Integration/User/UserControllerTest::testQueryMetaReturnsOk`, `tests/Application/Service/User/Query/UserSearchMetaQueryServiceTest::testReturnsSearchUsersQueryMeta`

## Notes

- Фильтруемые поля: `email`, `fname`, `lname`, `city`, `createdAt`, `taskId`; сортируемые — `email`, `fname`, `lname`, `city`, `createdAt`.
- Типы полей: `string` (кроме `createdAt` — `dateTime`); операторы `taskId` — только `eq` и `in`.
- Мета-дефолты: `defaultPage` 1, `defaultPerPage` 20, `maxPerPage` 100; `sortDirections` — `asc`, `desc`; `defaultSort` — `createdAt desc`.
- Поиск и применение фильтров — в разделе [query](../query/spec.md).
