# Users Delete Specification

## Purpose

Удаление собственного аккаунта: `DELETE /api/users/{id}` удаляет пользователя вместе со связанными данными в одной транзакции; чужие аккаунты удалить нельзя.

## Requirements

### Requirement: Удаление своего аккаунта
The API SHALL удалять только self (`DELETE /api/users/{id}`) и в одной транзакции удалять связанные данные: user'овые assets, task_users, preferences, files, refresh-токены и его task groups; задачи остаются, refresh-токены удаляются явно (FK без `ON DELETE CASCADE`); успех — `204`, событие не публикуется.

#### Scenario: Удаление self
- **GIVEN** пользователь удаляет свой аккаунт
- **WHEN** `DELETE /api/users/{id}`
- **THEN** `204`; assets, task users, preferences, files, refresh-токены и его task groups удалены; задачи остались
- **Covers:** `tests/Integration/User/UserControllerTest::testDeleteUserReturnsNoContent`, `tests/Application/Service/User/Action/UserDeleteServiceTest::testDeletesOwnAccount`

#### Scenario: Откат при сбое удаления
- **GIVEN** удаление связанных данных прерывается ошибкой
- **WHEN** выполняется `DELETE /api/users/{id}`
- **THEN** транзакция откатывается, удалённые записи восстановлены
- **Covers:** `tests/Application/Service/User/Action/UserDeleteServiceTest::testRollsBackWhenDeleteFailsMidway`

### Requirement: Чужой аккаунт не удаляется
The API SHALL отвечать `403` с `codeId = UNAUTHORIZED_USER_ACCESS` на `DELETE /api/users/{id}` для аккаунта, отличного от текущего пользователя.

#### Scenario: Удаление чужого аккаунта
- **GIVEN** пользователь пытается удалить другого
- **WHEN** `DELETE /api/users/{id}`
- **THEN** `403` с `codeId = UNAUTHORIZED_USER_ACCESS`
- **Covers:** `tests/Integration/User/UserControllerTest::testCannotDeleteAnotherUser`, `tests/Application/Service/User/Action/UserDeleteServiceTest::testThrowsWhenDeletingAnotherUser`

## Notes

- Событие `UserUpdatedEvent` при удалении не публикуется — см. [notifications](../../notifications/spec.md).
- Ограничение self — см. [update](../update/spec.md); правила валидации профиля — там же.
