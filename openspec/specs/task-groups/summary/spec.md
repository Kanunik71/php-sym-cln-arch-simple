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

## Notes

- Состав группы — в [task-groups](../spec.md); права на изменение — в [access](../access/spec.md); общие правила выборки — в [query](../../query/spec.md).
- Реализация: сводка обходит все группы keyset-курсором по идентификатору (`id < cursor`, порядок по убыванию) и обрабатывает батчи по 1000 по мере чтения — стоимость обхода линейна по числу групп, а вся коллекция не загружается в память. Сортировка выполняется базой данных.
- Сводка — единственный эндпоинт, чей контракт требует всех групп; остальные листинги пагинированы (см. [query](../../query/spec.md)).
- Источники: `api/src/Presentation/Controller/TaskGroup/TaskGroupController.php`, `api/src/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryService.php`, `api/src/Application/Port/TaskGroup/TaskGroupRepositoryInterface.php`, `api/src/Shared/Utils/BatchUtils.php`, `api/tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryServiceTest.php`, `api/tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeKeysetTest.php`.
