# Users Query Specification

## Purpose

Поиск пользователей с фильтрами, сортировкой и пагинацией: `GET /api/users/query` отдаёт страницу с конвертом `{items, total, page, perPage}` (конверт — в [platform](../../platform/spec.md)), `GET /api/users/query/meta` — описание поддерживаемых полей, операторов и сортировки.

## Requirements

### Requirement: Поиск с фильтрами и сортировкой
The API SHALL для аутентифицированного пользователя (см. [auth](../../auth/spec.md)) поддерживать `GET /api/users/query` с пагинацией (`page` ≥ 1, `perPage` 1..100) и сортировкой (`field` asc / `-field` desc, дефолт `createdAt desc`); ответ — страница с конвертом `{items, total, page, perPage}` (конверт — в [platform](../../platform/spec.md)), где `items` — профили в составе из требования «Состав профиля в ответе» в [users](../spec.md).

#### Scenario: Фильтр и сортировка
- **GIVEN** есть пользователи
- **WHEN** `GET /api/users/query?filter[email][eq]=...&sort=-createdAt&page=1&perPage=20`
- **THEN** `200`, страница с отфильтрованными и отсортированными `items` (профили)
- **Covers:** `tests/Application/Service/User/Query/UserSearchQueryServiceTest::testReturnsMappedPage`, `testFilterByEmail`, `testSortByEmail`, `testPagination`, `tests/Integration/User/UserControllerTest::testQueryUsersReturnsOk`

### Requirement: Фильтруемые поля поиска пользователей
The API SHALL поддерживать в `GET /api/users/query` фильтры: `email`, `fname`, `lname`, `city` (eq/ne/like/in/isNull/isNotNull), `createdAt` (eq/ne/gt/gte/lt/lte/isNull/isNotNull), `taskId` (eq/in — join `task_users`, `DISTINCT`).

#### Scenario: Один оператор на поле
- **GIVEN** в фильтре указано поле
- **WHEN** поле содержит не ровно один оператор
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Integration/User/UserControllerTest::testQueryInvalidFilterReturnsBadRequest`, `tests/Application/Service/User/Query/UserSearchQueryServiceTest::testRejectsInvalidSortField`

#### Scenario: Подсчёт total
- **GIVEN** применены фильтры
- **WHEN** считается `total` страницы
- **THEN** `total` — отдельный `COUNT(DISTINCT id)` с теми же фильтрами

## Notes

- `like` без `%`/`_` оборачивается в `%value%`; `in` требует непустой массив; неизвестное поле фильтра или поле сортировки — `400 VALIDATION_ERROR`.
- Мета-описание поиска (`GET /api/users/query/meta`) — в разделе [query-meta](../query-meta/spec.md).
- Сортируемые поля: `email`, `fname`, `lname`, `city`, `createdAt`; фильтруемые — в требовании «Фильтруемые поля поиска пользователей».
