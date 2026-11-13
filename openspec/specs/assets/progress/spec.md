# Assets Progress Specification

## Purpose

Вычисление флага `inProgress` ассета по статусам связанных задач: правило вычисления и следование за изменениями статусов.

## Requirements

### Requirement: Вывод `inProgress`
The API SHALL вычислять флаг `inProgress` как «есть связанная задача в `Initial` или `Active`»; флаг следует за статусами связанных задач.

#### Scenario: Активность ассета следует за задачами
- **GIVEN** ассет привязан к задаче
- **WHEN** задача создаётся/активируется/завершается
- **THEN** `inProgress` = true для `Initial`/`Active` и false для `Canceled`/`Finished`
- **Covers:** `AssetAttachToTaskServiceTest::testAttachesExistingAssetAndInProgressFollowsStatuses`

## Notes

- Состав ассета — в [assets](../spec.md); статусы задач — в [tasks](../../tasks/status/spec.md); привязка к задаче — в [binding](../binding/spec.md).
- Источники: `api/src/Application/Service/Asset/Action/AssetAttachToTaskService.php`, `api/src/Infrastructure/Persistence/Doctrine/Mapper/AssetMapper.php`, `api/tests/Application/Service/Asset/Action/AssetAttachToTaskServiceTest.php`.
