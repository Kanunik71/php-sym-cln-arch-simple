# Users Update Specification

## Purpose

Обновление собственного профиля: полная замена данных профиля (`PUT /api/users/{id}`), смена пароля с отзывом refresh-сессий и установка аватара из собственных файлов.

## Requirements

### Requirement: Обновление своего профиля
The API SHALL разрешать `PUT /api/users/{id}` только самому пользователю и выполнять полную замену профиля по валидному `UserUpdateModel`; успех — `200` с обновлённым `UserViewModel` и публикация `UserUpdatedEvent`; попытка изменить чужой профиль — `403` с `codeId = UNAUTHORIZED_USER_ACCESS`.

#### Scenario: Обновление self
- **GIVEN** пользователь обновляет свой профиль
- **WHEN** `PUT /api/users/{id}` с валидным `UserUpdateModel`
- **THEN** `200`, обновлённый `UserViewModel`, опубликовано `UserUpdatedEvent`
- **Covers:** `tests/Integration/User/UserControllerTest::testUpdateUserReturnsOk`, `tests/Application/Service/User/Action/UserUpdateServiceTest::testUpdatesOwnProfile`

#### Scenario: Чужой профиль
- **GIVEN** пользователь пытается изменить другого
- **WHEN** `PUT /api/users/{id}`
- **THEN** `403` с `codeId = UNAUTHORIZED_USER_ACCESS`
- **Covers:** `tests/Integration/User/UserControllerTest::testCannotUpdateAnotherUser`, `tests/Application/Service/User/Action/UserUpdateServiceTest::testThrowsWhenUpdatingAnotherUser`

### Requirement: Поля запроса профиля
The API SHALL принимать на `PUT /api/users/{id}` payload в виде `UserUpdateModel` с полями: `email` (обязателен), `fname`, `lname`, `password`, `location` (`{city}`), `avatarFileId`, `phone`; все поля кроме `email` опциональны.

#### Scenario: Обязательный email
- **GIVEN** запрос на обновление профиля без `email`
- **WHEN** API принимает payload
- **THEN** `400` с `codeId = VALIDATION_ERROR`, профиль не изменён

### Requirement: Нормализация и валидация полей профиля
The API SHALL при записи профиля нормализовать и проверять поля: `email` — trim, lower-case, корректный адрес, ≤255 символов; `fname`, `lname`, `location.city` — trim, пусто → `null`, ≤100 символов; `phone` — E.164, ≤20 символов; `password` — 6..72 байта; `avatarFileId` — UUID; нарушение SHALL давать `400` с `codeId = VALIDATION_ERROR`.

#### Scenario: Некорректное значение поля
- **GIVEN** в запросе на запись профиля передано некорректное значение поля
- **WHEN** API принимает payload
- **THEN** `400` с `codeId = VALIDATION_ERROR`, профиль не изменён

### Requirement: Полная замена профиля
The API SHALL обнулять отсутствующие или пустые `fname`, `lname`, `location`, `phone` при `PUT`; `email` обязателен; ограничения и нормализация полей — по правилам валидации профиля (см. требование «Нормализация и валидация полей профиля» в этом разделе).

#### Scenario: Очистка полей при полной замене
- **GIVEN** `PUT` без `fname`/`lname`/`location`/`phone` или с `null`/пустой строкой
- **WHEN** сохранение профиля
- **THEN** соответствующие поля обнуляются (полная замена)
- **Covers:** `tests/Application/Service/User/Action/UserUpdateServiceTest::testClearsOptionalFields`

### Requirement: Занятый email
The API SHALL отвечать `409` с `codeId = USER_ALREADY_EXISTS` на `PUT /api/users/{id}`, если `email` занят другим пользователем.

#### Scenario: Конфликт email
- **GIVEN** `email` занят другим пользователем
- **WHEN** `PUT /api/users/{id}`
- **THEN** `409` с `codeId = USER_ALREADY_EXISTS`
- **Covers:** `tests/Integration/User/UserControllerTest::testUpdateDuplicateEmailReturnsConflict`, `tests/Application/Service/User/Action/UserUpdateServiceTest::testThrowsWhenEmailAlreadyExists`

### Requirement: Смена пароля отзывает все refresh-сессии
The API SHALL при непустом `password` в `PUT /api/users/{id}` заменить хэш пароля и отозвать все refresh-токены пользователя в той же транзакции; `password = null` — «не менять», пустая строка — `400` с `codeId = VALIDATION_ERROR`; длина пароля проверяется по правилам валидации профиля (см. требование «Нормализация и валидация полей профиля» в этом разделе).

#### Scenario: Смена пароля
- **GIVEN** у пользователя есть refresh-токены
- **WHEN** `PUT /api/users/{id}` с валидным паролем
- **THEN** все прежние refresh-токены отозваны той же транзакцией, что и сохранение нового хэша
- **Covers:** `tests/Integration/User/UserControllerTest::testPasswordChangeRevokesRefreshTokens`, `testCanUpdatePasswordAndLogin`, `tests/Application/Service/User/Action/UserUpdateServiceTest::testUpdatesPassword`

#### Scenario: Слишком короткий пароль
- **GIVEN** пароль короче 6 байт
- **WHEN** `PUT /api/users/{id}` со сменой пароля
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Integration/User/UserControllerTest::testUpdateRejectsShortPassword`

### Requirement: Аватар только из своих файлов
The API SHALL при установке `avatarFileId` требовать, чтобы файл принадлежал текущему пользователю (`403` с `codeId = UNAUTHORIZED_FILE_ACCESS` иначе) и существовал (`404` с `codeId = FILE_NOT_FOUND`).

#### Scenario: Чужой файл
- **GIVEN** `avatarFileId` — файл другого пользователя
- **WHEN** `PUT /api/users/{id}`
- **THEN** `403` с `codeId = UNAUTHORIZED_FILE_ACCESS`

#### Scenario: Несуществующий файл
- **GIVEN** `avatarFileId` не существует
- **WHEN** `PUT /api/users/{id}`
- **THEN** `404` с `codeId = FILE_NOT_FOUND`

## Notes

- Политика пароля и её границы описаны в [auth](../../auth/spec.md); здесь — только поведение `PUT /api/users/{id}`.
- Известные расхождения: `avatarFileId` проверяется regex `[0-9a-fA-F-]{36}`, а не строгим UUID; транзакция `PUT` покрывает профиль и отзыв refresh-токенов, но `syncFileIds()` выполняется после commit — см. [overview](../../overview/spec.md).
