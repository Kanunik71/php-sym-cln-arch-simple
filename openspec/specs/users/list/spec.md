# Users List Specification

## Purpose

Постраничный список пользователей без фильтров: `GET /api/users` отдаёт страницу с конвертом `{items, total, page, perPage}` (конверт — в [query](../../query/spec.md)), отсортированную по `createdAt DESC`, с пагинацией `page`/`perPage` (по умолчанию 1/20, максимум 100).

## Requirements

### Requirement: Страница списка пользователей
The API SHALL для аутентифицированного пользователя (см. [auth](../../auth/spec.md)) возвращать на `GET /api/users` страницу пользователей с конвертом `{items, total, page, perPage}` (конверт — в [query](../../query/spec.md)), где `items` — профили в составе из требования «Состав профиля в ответе» в [users](../spec.md).

#### Scenario: Список пользователей
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users`
- **THEN** `200` со страницей; `items` — профили, отсортированы по `createdAt DESC`
- **Covers:** `tests/Integration/User/UserControllerTest::testListUsersReturnsOk`, `tests/Application/Service/User/Query/UserListQueryServiceTest::testReturnsMappedUsers`, `testPaginatesByCreatedAtDesc`

### Requirement: Пагинация и сортировка списка пользователей
The API SHALL на `GET /api/users` применять пагинацию (`page` ≥ 1, `perPage` 1..100, по умолчанию 1/20), сортировку по `createdAt DESC` и не применять фильтры; невалидные `page`/`perPage` SHALL давать `400` с `codeId = VALIDATION_ERROR`.

#### Scenario: Невалидный perPage
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users?perPage=0`
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Integration/User/UserControllerTest::testListRejectsInvalidPerPage`

## Notes

- Сортировка, фильтры и мета-описание — в разделе [query](../query/spec.md).
- Состав профиля в `items` — в [users](../spec.md); правила валидации полей при записи — в [update](../update/spec.md).
