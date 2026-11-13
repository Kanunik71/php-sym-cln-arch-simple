# Wire Specification

## Purpose

Формат полей в wire-контракте, общий для всех доменов: идентификаторы и даты. Состав объектов по доменам — в их спеках ([users](../users/spec.md), [tasks](../tasks/spec.md), [assets](../assets/spec.md), [files](../files/spec.md), [task-groups](../task-groups/spec.md)).

## Requirements

### Requirement: Идентификаторы и даты

The API SHALL использовать UUID v7 как идентификаторы (в JSON — строка в каноническом виде, 36 символов с дефисами) и отдавать все даты в формате `DateTimeInterface::ATOM`.

#### Scenario: Форматы полей

- **GIVEN** любой ответ с объектами
- **WHEN** клиент читает `id` и дату
- **THEN** `id` — UUID-строка (36 символов, hex и дефисы), дата — ATOM (ISO-8601 с часами, минутами и секундами)

## Notes

- В БД идентификаторы хранятся как `BINARY(16)`; наружу отдаются только строкой — см. `api/src/Infrastructure/Persistence/Doctrine/Utils/DoctrineUuidUtils.php`.
- Денежные значения — свойство домена [assets](../assets/spec.md) (`NUMERIC(10,2)`, неотрицательные, ≤ 2 знаков).
- Наружу отдаются только `*ViewModel`/`*PageModel`; правила архитектуры — `api/CLAUDE.md`, `api/src/Application/CLAUDE.md`.
- Источники: `api/src/Shared/Utils/**` (`UidUtils`, `DateUtils`), `api/config/packages/**`.
