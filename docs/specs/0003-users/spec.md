# Users — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/src/Presentation/Controller/User/UserController.php`, `api/src/Application/Service/User/**`, `api/src/Application/Model/User/**`, `api/tests/Integration/User/**` |

## Контекст

Профиль пользователя: чтение, поиск/фильтрация, обновление, удаление и аватар.
Пользователь управляет только своим профилем, но читать может любого.

## Акторы и доступ

- Все маршруты — `ROLE_USER`, JWT обязателен.
- `GET` доступен любому аутентифицированному пользователю.
- `PUT` и `DELETE` — только self (`UserAccessPolicy::assertSelfAccess`).
- Аватар можно прикрепить только из собственных файлов (`AttachFilePolicy`).

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | видеть список/страницу пользователей | выбирать участников |
| US-2 | пользователь | фильтровать и сортировать пользователей | быстро находить нужных |
| US-3 | пользователь | обновить свой профиль и аватар | поддерживать данные актуальными |
| US-4 | пользователь | удалить свой аккаунт | прекратить использование |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | `GET /api/users` — страница без фильтров: `page`/`perPage` (default 1/20, max 100), сортировка `createdAt DESC`, ответ `UserPageModel` |
| FR-2 | `GET /api/users/query` — пагинация, фильтры, сортировка |
| FR-3 | `GET /api/users/query/meta` — whitelist фильтров/сортировок/пагинации |
| FR-4 | `PUT` — полная замена профиля, кроме `password` (`null` = не менять) |
| FR-5 | `DELETE` — удаление self и связанных данных в транзакции, включая refresh-токены и task groups владельца; задачи остаются |
| FR-6 | Email уникален; смена на занятый → `409` |

## Сценарии

### S-1: Список и чтение

- **Given** пользователь аутентифицирован
- **When** `GET /api/users` или `GET /api/users/{id}`
- **Then** `200`; список — `UserPageModel`, один — `UserViewModel`
- **Тесты:** `UserControllerTest::testListUsersReturnsOk`, `testGetUserReturnsOk`

### S-2: Поиск с фильтром и сортировкой

- **Given** есть пользователи
- **When** `GET /api/users/query?filter[email][eq]=...&sort=-createdAt&page=1&perPage=20`
- **Then** `200`, `UserPageModel` с `items`, `total`, `page`, `perPage`
- **Тесты:** `UserSearchQueryServiceTest::testReturnsMappedPage`,
  `testFilterByEmail`, `testSortByEmail`, `testPagination`

### S-3: Обновление своего профиля

- **Given** пользователь обновляет self
- **When** `PUT /api/users/{id}` с валидным `UserUpdateModel`
- **Then** `200`, обновлённый `UserViewModel`, событие `UserUpdatedEvent`
- **Тесты:** `testUpdateUserReturnsOk`, `UserUpdateServiceTest::testUpdatesOwnProfile`

### S-4: Запрет на чужой профиль

- **Given** пользователь пытается изменить/удалить другого
- **When** `PUT`/`DELETE`
- **Then** `403 UNAUTHORIZED_USER_ACCESS`
- **Тесты:** `testCannotUpdateAnotherUser`, `testCannotDeleteAnotherUser`

### S-5: Удаление аккаунта

- **Given** пользователь удаляет self
- **When** `DELETE /api/users/{id}`
- **Then** `204`; в транзакции удалены assets, task users, preferences, files, refresh-токены и task groups владельца; задачи остаются
- **Тест:** `UserDeleteServiceTest::testDeletesOwnAccount`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| GET | `/api/users` | `page`, `perPage` | `200`, `UserPageModel` | `400`, `401` |
| GET | `/api/users/query` | query | `200`, `UserPageModel` | `400`, `401` |
| GET | `/api/users/query/meta` | — | `200`, `SearchQueryMetaModel` | `401` |
| GET | `/api/users/{id}` | path | `200`, `UserViewModel` | `401`, `404 USER_NOT_FOUND`, `500` на невалидном 36-символьном id |
| PUT | `/api/users/{id}` | `UserUpdateModel` | `200`, `UserViewModel` | `400`, `401`, `403`, `404`, `409`, `500` при гонке email |
| DELETE | `/api/users/{id}` | path | `204` | `401`, `403`, `404`, `500` (невалидный UUID) |

### Request `UserUpdateModel`

| Поле | Тип | Обяз. | Семантика |
|------|-----|-------|-----------|
| `fname` | string/null | нет | отсутствует/null/пусто → `null`; ≤100 |
| `lname` | string/null | нет | отсутствует/null/пусто → `null`; ≤100 |
| `email` | string | **да** | `Email`; смена на занятый → `409` |
| `password` | string/null | нет | `null` → старый хэш; пустая строка → `400`; иначе 6–72 байта без обрезки краёв и отзыв всех refresh-сессий в той же транзакции, что и профиль |
| `location` | object/null | нет | отсутствует/null → `city = null` |
| `avatarFileId` | string/null | нет | отсутствует/null → аватар очищается; regex `[0-9a-fA-F-]{36}` |
| `phone` | string/null | нет | отсутствует/null → `null`; E.164 |

`PUT` заменяет профиль: `null` у большинства полей очищает значение.

## Query-контракт

Формат: `?page=1&perPage=20&sort=-createdAt&filter[field][operator]=value`.

| Параметр | Default | Ограничения |
|----------|---------|-------------|
| `page` | 1 | ≥ 1 |
| `perPage` | 20 | 1..100 |
| `sort` | `createdAt desc` | `field` = asc, `-field` = desc |
| `filter` | — | объект; на поле ровно один оператор |

Фильтры (`UserFilterFieldEnum`):

| Поле | Тип | Операторы |
|------|-----|-----------|
| `email`, `fname`, `lname`, `city` | string | `eq`, `ne`, `like`, `in`, `isNull`, `isNotNull` |
| `createdAt` | datetime | `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `isNull`, `isNotNull` |
| `taskId` | string | `eq`, `in` (join `task_users`, `DISTINCT`) |

Сортировка (`UserSortFieldEnum`): `email`, `fname`, `lname`, `city`,
`createdAt`. Направления `asc`/`desc`.

`like` без `%`/`_` оборачивается в `%value%`; `in` требует непустой массив.

Meta (`/query/meta`): `filters` (`{field, type, operators}[]`), `sortFields`,
`sortDirections` (`asc`/`desc`), `defaultSort = {field: createdAt, direction: desc}`,
`pagination = {defaultPage: 1, defaultPerPage: 20, maxPerPage: 100}`.

## Модели и инварианты

- `UserModel`: `id`, `fname`, `lname`, `email`, `passwordHash`, `city`,
  `phone`, `avatarFileId`, `createdAt`, `updatedAt`.
- `UserViewModel`: `id`, `fname`, `lname`, `email`, `createdAt`, `location`,
  `avatar` (`FileItemModel|null`), `phone`.
- `UserRegisterResponseModel`: без `createdAt`/`avatar`.
- `UserPageModel`: `items`, `total`, `page`, `perPage`.
- `total` — отдельный `COUNT(DISTINCT u.id)` с теми же фильтрами.
- `GET /api/users` — та же страница, что поиск без фильтров: default sort `createdAt DESC`.

## Права доступа

| Действие | Кто может |
|----------|-----------|
| list/query/meta/get | любой аутентифицированный |
| update/delete | только self |
| прикрепить avatar | только владелец файла |

## Async и события

- `UserUpdatedEvent` после успешного `PUT` → `async` →
  `UserUpdatedMessageHandler` → `NotificationService::notify(user_updated)`.
- При `DELETE` событие не публикуется.
- Preferences, files и refresh-токены удаляются напрямую в `UserDeleteService`.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидный JSON/поля/фильтр/сортировка/пагинация |
| `UNAUTHORIZED_USER_ACCESS` | 403 | чужой профиль |
| `UNAUTHORIZED_FILE_ACCESS` | 403 | аватар чужого файла |
| `USER_NOT_FOUND` | 404 | пользователь не найден |
| `FILE_NOT_FOUND` | 404 | `avatarFileId` не найден |
| `USER_ALREADY_EXISTS` | 409 | email занят |
| — | 500 | невалидный 36-символьный UUID |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| UUID в path | regex пропускает 36 дефисов → `500` в GET/DELETE | `400` | `UserController` |
| `PUT` | транзакция покрывает профиль и отзыв refresh-токенов; `syncFileIds()` после commit | атомарность вместе с файлами | `UserUpdateService` |
| `avatarFileId` | проверяется regex, не UUID | строгая валидация | `UserUpdateModel` |

## Первоисточники

- `api/src/Presentation/Controller/User/UserController.php`
- `api/src/Application/Model/User/**`
- `api/src/Application/Service/User/**`
- `api/src/Application/Policy/User/**`, `Policy/File/AttachFilePolicy.php`
- `api/src/Application/Port/User/UserRepositoryInterface.php`
- `api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineUserRepository.php`
- `api/src/Infrastructure/Persistence/Doctrine/Query/User/**`
- `api/tests/Integration/User/UserControllerTest.php`
- `api/tests/Application/Service/User/**`, `tests/Application/Model/User/**`

## Открытые вопросы

Нет.
