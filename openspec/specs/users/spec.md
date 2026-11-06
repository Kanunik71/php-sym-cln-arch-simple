# Users Specification

## Purpose
Профиль пользователя: список/страница, поиск с фильтрами и сортировкой (`/query`), чтение, обновление (включая аватар и смену пароля) и удаление своего аккаунта. Читать можно всех; менять — только себя.

## Requirements

### Requirement: Просмотр пользователей
The API SHALL при JWT-аутентификации возвращать `GET /api/users` — страницу (`page`/`perPage`, по умолчанию 1/20, максимум 100; без фильтров), отсортированную по `createdAt DESC`, с `UserPageModel`; `GET /api/users/{id}` — `200` с `UserViewModel`. Невалидный 36-символьный id в `{id}` даёт `500` (текущее поведение).

#### Scenario: Список и чтение
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users` или `GET /api/users/{id}`
- **THEN** `200`; список — `UserPageModel` (`items`, `total`, `page`, `perPage`), один — `UserViewModel`
- **Covers:** `tests/Integration/User/UserControllerTest::testListUsersReturnsOk`, `testGetUserReturnsOk`

### Requirement: Поиск с фильтрами
The API SHALL поддерживать `GET /api/users/query` с пагинацией (`page` ≥ 1, `perPage` 1..100), сортировкой (`field` asc / `-field` desc, дефолт `createdAt desc`) и фильтрами: `email`, `fname`, `lname`, `city` (eq/ne/like/in/isNull/isNotNull), `createdAt` (eq/ne/gt/gte/lt/lte/isNull/isNotNull), `taskId` (eq/in — join `task_users`, `DISTINCT`). На поле ровно один оператор; `total` считается отдельным `COUNT(DISTINCT id)` с теми же фильтрами.

#### Scenario: Фильтр и сортировка
- **GIVEN** есть пользователи
- **WHEN** `GET /api/users/query?filter[email][eq]=...&sort=-createdAt&page=1&perPage=20`
- **THEN** `200`, `UserPageModel` с отфильтрованными и отсортированными `items`
- **Covers:** `UserSearchQueryServiceTest::testReturnsMappedPage`, `testFilterByEmail`, `testSortByEmail`, `testPagination`

#### Scenario: Мета-описание query
- **GIVEN** пользователь аутентифицирован
- **WHEN** `GET /api/users/query/meta`
- **THEN** `200` с whitelist: `filters` (`{field, type, operators}[]`), `sortFields`, `sortDirections`, `defaultSort`, `pagination`

### Requirement: Обновление своего профиля
The API SHALL разрешать `PUT /api/users/{id}` только самому пользователю и выполнять полную замену профиля по валидному `UserUpdateModel`; успех — `200` с обновлённым `UserViewModel` и подписка `UserUpdatedEvent`.

#### Scenario: Обновление self
- **GIVEN** пользователь обновляет свой профиль
- **WHEN** `PUT /api/users/{id}` с валидным `UserUpdateModel`
- **THEN** `200`, обновлённый `UserViewModel`, опубликовано `UserUpdatedEvent`
- **Covers:** `testUpdateUserReturnsOk`, `UserUpdateServiceTest::testUpdatesOwnProfile`

#### Scenario: Очистка полей при полной замене
- **GIVEN** `PUT` без `fname`/`lname`/`location`/`phone` или с `null`/пустой строкой
- **WHEN** сохранение профиля
- **THEN** соответствующие поля обнуляются (полная замена); `fname`/`lname` ≤100; `phone` — E.164; `email` обязателен

#### Scenario: Занятый email
- **GIVEN** `email` занят другим пользователем
- **WHEN** `PUT /api/users/{id}`
- **THEN** `409 USER_ALREADY_EXISTS`

#### Scenario: Чужой профиль
- **GIVEN** пользователь пытается изменить другого
- **WHEN** `PUT /api/users/{id}`
- **THEN** `403 UNAUTHORIZED_USER_ACCESS`
- **Covers:** `testCannotUpdateAnotherUser`

#### Scenario: Смена пароля
- **GIVEN** у пользователя есть refresh-токены
- **WHEN** `PUT /api/users/{id}`: `password = null` — хэш не меняется; пустая строка — `400`; валидный пароль (6–72 байта) — все refresh-сессии отозваны той же транзакцией, что и сохранение нового хэша
- **THEN** поведение по правому колону сценария
- **Covers:** `testPasswordChangeRevokesRefreshTokens` (см. [auth](../auth/spec.md))

### Requirement: Аватар только из своих файлов
The API SHALL при установке `avatarFileId` требовать, чтобы файл принадлежал текущему пользователю (`403 UNAUTHORIZED_FILE_ACCESS` иначе) и существовал (`404 FILE_NOT_FOUND`).

#### Scenario: Чужой файл
- **GIVEN** `avatarFileId` — файл другого пользователя
- **WHEN** `PUT /api/users/{id}`
- **THEN** `403 UNAUTHORIZED_FILE_ACCESS`

### Requirement: Удаление аккаунта
The API SHALL удалять только self (`DELETE /api/users/{id}`) и в одной транзакции удалять связанные данные: user'овые assets, task_users, preferences, files, refresh-токены и его task groups; задачи остаются, refresh-токены удаляются явно (FK без `ON DELETE CASCADE`). Успех — `204`; событие не публикуется.

#### Scenario: Удаление self
- **GIVEN** пользователь удаляет свой аккаунт
- **WHEN** `DELETE /api/users/{id}`
- **THEN** `204`; assets, task users, preferences, files, refresh-токены и его task groups удалены; задачи остались
- **Covers:** `UserDeleteServiceTest::testDeletesOwnAccount`

#### Scenario: Удаление чужого аккаунта
- **GIVEN** пользователь пытается удалить другого
- **WHEN** `DELETE /api/users/{id}`
- **THEN** `403 UNAUTHORIZED_USER_ACCESS`
- **Covers:** `testCannotDeleteAnotherUser`

## Notes

- `UserViewModel`: `id`, `fname`, `lname`, `email`, `createdAt`, `location`, `avatar` (`FileItemModel|null`), `phone`. `UserPageModel`: `items`, `total`, `page`, `perPage`.
- `like` без `%`/`_` оборачивается в `%value%`; `in` требует непустой массив. Meta-дефолты: `defaultPage` 1, `defaultPerPage` 20, `maxPerPage` 100.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_USER_ACCESS`/`UNAUTHORIZED_FILE_ACCESS` (403), `USER_NOT_FOUND`/`FILE_NOT_FOUND` (404), `USER_ALREADY_EXISTS` (409).
- Известные расхождения: regex `{id}` пропускает любые 36 символов (недействительный UUID → `500` вместо `400`); транзакция `PUT` покрывает профиль и отзыв refresh-токенов, но `syncFileIds()` выполняется после commit; `avatarFileId` проверяется regex, а не UUID — см. [overview](../overview/spec.md).
- Источники: `api/src/Presentation/Controller/User/UserController.php`, `api/src/Application/Service/User/**`, `api/src/Infrastructure/Persistence/Doctrine/Query/User/**`, `api/tests/Integration/User/UserControllerTest.php`.
