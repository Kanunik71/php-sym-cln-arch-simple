# Task Groups Summary Specification

## Purpose

Сводка по группам задач с счётчиками статусов: состав сводки, счётчики по четырём статусам, поведение пустой группы.

## Requirements

### Requirement: Сводка по группам
The API SHALL на `GET /api/task-groups/summary` возвращать `200` список всех групп БД (свежие сверху) с `taskStatusCounts` (`{initial, active, canceled, finished}`) для каждой; пустая группа — все нули. Сводка не ограничена владельцем и не поддерживает пагинацию — возвращаются все группы.

#### Scenario: Счётчики статусов
- **GIVEN** группы с задачами разных статусов
- **WHEN** `GET /api/task-groups/summary`
- **THEN** `200`, для каждой группы корректные счётчики по четырём статусам
- **Covers:** `testSummaryReturnsOk`, `TaskGroupSummarizeQueryServiceTest::testReturnsStatusCountsPerGroup`

#### Scenario: Состав сводки
- **GIVEN** существуют группы разных владельцев
- **WHEN** `GET /api/task-groups/summary`
- **THEN** `200`, список содержит все группы БД независимо от владельца; счётчики задач каждой группы соответствуют её составу

#### Scenario: Каждая группа встречается один раз
- **GIVEN** в таблице групп больше, чем помещается в один батч обхода
- **WHEN** `GET /api/task-groups/summary`
- **THEN** каждая группа присутствует в ответе ровно один раз, без пропусков и повторов
- **Covers:** `tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeKeysetTest::testWalksEveryGroupWithoutDuplicates`

#### Scenario: Порядок сводки
- **GIVEN** создано несколько групп подряд
- **WHEN** `GET /api/task-groups/summary`
- **THEN** группы перечислены свежими сверху — по убыванию времени создания
- **Covers:** `tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeKeysetTest::testReturnsGroupsNewestFirst`

### Requirement: Кеш сводки
The API SHALL отдавать сводку групп из кеша с TTL 60 секунд; при промахе сводка вычисляется обходом БД и сохраняется под ключом `task_group.summaries`; после пересчёта состава групп кеш сбрасывается.

#### Scenario: Промах кеша
- **GIVEN** кеш сводки пуст
- **WHEN** запрашивается сводка групп
- **THEN** сводка вычисляется обходом всех групп, сохраняется в кеш и возвращается клиенту
- **Covers:** `tests/Application/Cache/TaskGroup/TaskGroupSummaryCacheTest::testLoadsThroughCacheOnMiss`

#### Scenario: Попадание в кеш
- **GIVEN** сводка уже сохранена в кеше
- **WHEN** запрашивается сводка групп
- **THEN** клиенту возвращается сохранённая сводка, обход БД не выполняется
- **Covers:** `tests/Application/Cache/TaskGroup/TaskGroupSummaryCacheTest::testServesCachedValueWithoutCallingLoader`

#### Scenario: Сброс кеша
- **GIVEN** сводка сохранена в кеше
- **WHEN** пересчитывается состав групп
- **THEN** кеш сводки сбрасывается и следующий запрос считает сводку заново
- **Covers:** `tests/Application/Cache/TaskGroup/TaskGroupSummaryCacheTest::testInvalidateDropsCachedValue`

## Notes

- Состав группы — в [task-groups](../spec.md); права на изменение — в [access](../access/spec.md); общие правила выборки — в [query](../../query/spec.md).
- Реализация: сводка обходит все группы keyset-курсором по идентификатору (`id < cursor`, порядок по убыванию) и обрабатывает батчи по 1000 по мере чтения — стоимость обхода линейна по числу групп, а вся коллекция не загружается в память. Сортировка выполняется базой данных.
- Сводка отдаётся из кеша: `Application/Cache/TaskGroup/TaskGroupSummaryCache.php` владеет ключом `task_group.summaries` и TTL (60 с), загрузчик (обход БД) передаёт вызывающий. Кеш сбрасывается при пересчёте состава групп (`TaskGroupRecalculationService`), поэтому после изменения статусов задач сводка может оставаться устаревшей до сброса или до истечения TTL.
- Сводка — единственный эндпоинт, чей контракт требует всех групп; остальные листинги пагинированы (см. [query](../../query/spec.md)).
- Источники: `api/src/Presentation/Controller/TaskGroup/TaskGroupController.php`, `api/src/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryService.php`, `api/src/Application/Cache/TaskGroup/TaskGroupSummaryCache.php`, `api/src/Application/Port/TaskGroup/TaskGroupRepositoryInterface.php`, `api/src/Shared/Utils/BatchUtils.php`, `api/tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryServiceTest.php`, `api/tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeKeysetTest.php`, `api/tests/Application/Cache/TaskGroup/TaskGroupSummaryCacheTest.php`.
