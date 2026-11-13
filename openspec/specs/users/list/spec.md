# Users List Specification

## Purpose

Постраничный список пользователей без фильтров: `GET /api/users` отдаёт страницу `UserPageModel`, отсортированную по `createdAt DESC`, с пагинацией `page`/`perPage` (по умолчанию 1/20, максимум 100).

## Requirements

### Requirement: Страница списка пользователей
The API SHALL для аутентифицированного пользователя (см. [auth](../../auth/spec.md)) возвращать на `GET /api/users` страницу пользователей (`items`, `total`, `page`, `perPage`) с пагинацией (`page` ≥ 1, `perPage` 1..100, по умолчанию 1/20) без фильтров, отсортированную по `createdAt DESC`; невалидные `page`/`perPage` SHALL давать `400` с `codeId = VALIDATION_ERROR`.

#### Scenario: Список пользователей
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users`
- **THEN** `200` с `UserPageModel`; `items` отсортированы по `createdAt DESC`
- **Covers:** `tests/Integration/User/UserControllerTest::testListUsersReturnsOk`, `tests/Application/Service/User/Query/UserListQueryServiceTest::testReturnsMappedUsers`, `testPaginatesByCreatedAtDesc`

#### Scenario: Невалидный perPage
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users?perPage=0`
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Integration/User/UserControllerTest::testListRejectsInvalidPerPage`

## Notes

- Сортировка, фильтры и мета-описание — в разделе [query](../query/spec.md).
- Состав профиля — в [users](../spec.md); правила валидации полей при записи — в [update](../update/spec.md).
