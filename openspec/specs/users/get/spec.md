# Users Get Specification

## Purpose

Чтение профиля одного пользователя по его идентификатору: `GET /api/users/{id}` отдаёт JSON-объект профиля (состав — в требовании «Состав профиля в ответе» в [users](../spec.md)).

## Requirements

### Requirement: Чтение профиля пользователя
The API SHALL для аутентифицированного пользователя (см. [auth](../../auth/spec.md)) возвращать на `GET /api/users/{id}` код `200` с JSON-объектом профиля запрошенного пользователя (состав — в требовании «Состав профиля в ответе» в [users](../spec.md)); несуществующий идентификатор SHALL давать `404` с `codeId = USER_NOT_FOUND`.

#### Scenario: Чтение профиля
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users/{id}` существующего пользователя
- **THEN** `200` с JSON-объектом профиля; состав полей — в [users](../spec.md)
- **Covers:** `tests/Integration/User/UserControllerTest::testGetUserReturnsOk`, `tests/Application/Service/User/Query/UserQueryServiceTest::testReturnsMappedUser`, `testReturnsAnotherUser`

#### Scenario: Неизвестный пользователь
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users/{id}` с несуществующим идентификатором
- **THEN** `404` с `codeId = USER_NOT_FOUND`
- **Covers:** `tests/Integration/User/UserControllerTest::testGetUnknownUserReturnsNotFound`, `tests/Application/Service/User/Query/UserQueryServiceTest::testThrowsWhenUserNotFound`

## Notes

- Чтение доступно любому аутентифицированному пользователю; ограничение self действует только на запись — см. [update](../update/spec.md) и [delete](../delete/spec.md).
- Известное расхождение: regex `{id}` в маршруте допускает любые 36 символов `[0-9a-fA-F-]`, поэтому невалидный UUID проходит маршрутизацию и падает в `500` вместо `400`; см. [overview](../../overview/spec.md).
