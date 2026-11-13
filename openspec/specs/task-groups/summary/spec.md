# Task Groups Summary Specification

## Purpose

Сводка по группам задач с счётчиками статусов: состав сводки, счётчики по четырём статусам, поведение пустой группы.

## Requirements

### Requirement: Сводка по группам
The API SHALL на `GET /api/task-groups/summary` возвращать `200` список всех групп БД (`createdAt desc`) с `taskStatusCounts` (`{initial, active, canceled, finished}`) для каждой; пустая группа — все нули. Сводка не ограничена владельцем и не поддерживает пагинацию — возвращаются все группы.

#### Scenario: Счётчики статусов
- **GIVEN** группы с задачами разных статусов
- **WHEN** `GET /api/task-groups/summary`
- **THEN** `200`, для каждой группы корректные счётчики по четырём статусам
- **Covers:** `testSummaryReturnsOk`, `TaskGroupSummarizeQueryServiceTest::testReturnsStatusCountsPerGroup`

#### Scenario: Состав сводки
- **GIVEN** существуют группы разных владельцев
- **WHEN** `GET /api/task-groups/summary`
- **THEN** `200`, список содержит все группы БД независимо от владельца; счётчики задач каждой группы соответствуют её составу

## Notes

- Состав группы — в [task-groups](../spec.md); права на изменение — в [access](../access/spec.md).
- Реализация: сводка читает все группы батчами по 1000 и сортирует в памяти; при ~10⁶ групп это полный обход таблицы.
- Источники: `api/src/Presentation/Controller/TaskGroup/TaskGroupController.php`, `api/src/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryService.php`, `api/tests/Application/Service/TaskGroup/Query/TaskGroupSummarizeQueryServiceTest.php`.
