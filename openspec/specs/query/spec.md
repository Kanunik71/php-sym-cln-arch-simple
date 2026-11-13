# Query Specification

## Purpose

Единый контракт листингов и поиска: пагинация, сортировка, фильтры и конверт постраничного ответа. Применяется всеми листинговыми и поисковыми эндпоинтами доменов ([users](../users/spec.md), [tasks](../tasks/spec.md), [assets](../assets/spec.md), [task-groups](../task-groups/spec.md)). Набор доступных полей, операторов и дефолт сортировки задаются use-case'ом.

## Requirements

### Requirement: Постраничный конверт

The API SHALL отдавать ответы листинга и поиска единым JSON-конвертом `{items, total, page, perPage}`: `items` — массив элементов домена, `total` — общее число элементов по фильтру, `page` — номер текущей страницы (≥ 1), `perPage` — размер страницы (1..100, по умолчанию 20).

#### Scenario: Форма конверта страницы

- **GIVEN** запрошена страница результатов
- **WHEN** клиент читает ответ
- **THEN** JSON содержит `items` (массив), `total` (int), `page` (int), `perPage` (int)

### Requirement: Параметры выборки

The API SHALL поддерживать на листинговых эндпоинтах query-параметры `page` (≥ 1, по умолчанию 1), `perPage` (1..100, по умолчанию 20) и `sort` в форме `field` (по возрастанию) или `-field` (по убыванию); дефолтное поле и направление сортировки задаются use-case'ом, неизвестное поле сортировки SHALL давать `400` с `codeId = VALIDATION_ERROR`.

#### Scenario: Пагинация и сортировка

- **GIVEN** есть данные
- **WHEN** `GET /api/users/query?page=2&perPage=50&sort=-createdAt`
- **THEN** `200` и страница со смещением `(page-1)*perPage`, отсортированная по `createdAt` по убыванию

#### Scenario: Неизвестное поле сортировки

- **GIVEN** в `sort` передано поле вне списка use-case'а
- **WHEN** листинговый запрос
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Application/Service/User/Query/UserSearchQueryServiceTest::testRejectsInvalidSortField`

### Requirement: Фильтры

The API SHALL принимать фильтры в форме `filter[field][op]=value` (ровно один оператор на поле), `like` SHALL оборачиваться в `%value%`, `in` SHALL требовать непустого списка; пустой `in`, неизвестное поле фильтра и недопустимый оператор SHALL давать `400` с `codeId = VALIDATION_ERROR`.

#### Scenario: Фильтр применён

- **GIVEN** `filter[email][eq]=a@b.c`
- **WHEN** поисковый запрос
- **THEN** `200` и `items` только с совпадающими элементами, `total` — число совпадений

#### Scenario: Фильтр отклонён

- **GIVEN** пустой `in` или недопустимый оператор/поле
- **WHEN** поисковый запрос
- **THEN** `400` с `codeId = VALIDATION_ERROR`
- **Covers:** `tests/Integration/User/UserControllerTest::testQueryInvalidFilterReturnsBadRequest`

### Requirement: Полный перебор в HTTP-эндпоинте запрещён
The API SHALL NOT обходить коллекцию целиком в HTTP-эндпоинте: ответ строится либо постранично, либо по известным идентификаторам. Полный перебор допустим только в фоновых задачах и SHALL выполняться keyset-обходом с постраничной обработкой.

#### Scenario: Листинг отдаёт страницу
- **GIVEN** в таблице больше строк, чем помещается в одну страницу
- **WHEN** клиент запрашивает листинг или поиск
- **THEN** ответ содержит только запрошенную страницу, а не все строки таблицы

#### Scenario: Фоновая задача обходит коллекцию
- **GIVEN** фоновая задача обрабатывает все строки коллекции
- **WHEN** она обходит таблицу
- **THEN** обход идёт батчами по курсору идентификатора, без повторного перечитывания уже пройденных строк, и не требует загрузки всей коллекции в память

## Notes

- Пример: `GET /api/users/query?page=2&perPage=50&sort=-createdAt&filter[email][eq]=a@b.c&filter[city][in][]=Moscow`.
- Операторы: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `like`, `in`, `isNull`, `isNotNull`. Строковые поля — `eq`/`ne`/`like`/`in`/`isNull`/`isNotNull`; int и даты — сравнения + `in` (int)/`isNull`/`isNotNull`.
- Наборы фильтруемых и сортируемых полей по доменам: [users/query](../users/query/spec.md), [tasks/list](../tasks/list/spec.md), [assets/view](../assets/view/spec.md), [task-groups/summary](../task-groups/summary/spec.md). Мета-описание поддерживаемых полей — [users/query-meta](../users/query-meta/spec.md).
- Идентификаторы — UUID v7 и растут со временем, поэтому порядок по идентификатору совпадает с порядком по времени создания: keyset-курсор по `id` даёт корректный обход без `OFFSET`-пагинации.
- Источники: `api/src/Application/Model/Common/SortModel.php`, `api/src/Application/Enum/Common/FilterOperatorEnum.php`, `api/src/Shared/Utils/Filter/**`, `api/src/Shared/Utils/BatchUtils.php`, `api/src/Infrastructure/Persistence/Doctrine/Query/**`.
