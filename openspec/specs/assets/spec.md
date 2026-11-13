# Assets Specification

## Purpose

Домен ассетов: объекты с ценой и типом, привязанные к задачам и изображениям (файлам). Создание, привязка и отвязка к задачам, просмотр, обновление, удаление. Поведение разложено по use-case'ам на подразделы: [create](create/spec.md), [images](images/spec.md), [progress](progress/spec.md), [view](view/spec.md), [binding](binding/spec.md), [update](update/spec.md).

## Requirements

### Requirement: Поля ассета
The API SHALL хранить ассет со следующими наблюдаемыми полями:

- `id` — uuid, 36 симв., уникальный
- `ownerId` — uuid владельца
- `name` — string ≤255, обязателен
- `price` — денежное значение ≥ 0, ≤ 99 999 999.99, до 2 знаков после запятой
- `type` — `Virtual` | `Physical`
- `createdAt` — date-time ATOM

#### Scenario: Формат идентификационных полей
- **GIVEN** существует ассет
- **WHEN** проверяются его идентификационные поля
- **THEN** `id` — 36-символьный UUID v7, `ownerId` — идентификатор создателя, `type` — одно из двух значений, `price` — неотрицательное число с ≤2 знаками

### Requirement: Состав ассета в ответе
The API SHALL возвращать ассет в JSON-объекте с полями: `id` (uuid-строка), `ownerId` (uuid-строка), `name` (string), `taskIds` (массив uuid-строк), `price` (number), `type` (`Virtual`|`Physical`), `createdAt` (date-time ATOM), `inProgress` (bool), `mainImage` (объект `{id, url}`|null). Детальный ответ дополнительно содержит `tasks` (массив `{id, name, status, parentId}`) и полный `images` (массив `{id, url}`).

#### Scenario: Форма ассета в ответе
- **GIVEN** существует ассет с задачами и изображениями
- **WHEN** клиент читает ассет
- **THEN** JSON содержит поля `id`, `ownerId`, `name`, `taskIds`, `price`, `type`, `createdAt`, `inProgress`, `mainImage`; в детальном ответе — `tasks` и `images`

## Notes

- `price` в БД — `NUMERIC(10,2)`, неотрицательное, до 2 знаков (`MoneyUtils`); тип неизменяем после создания.
- `codeId`: `VALIDATION_ERROR` (400), `UNAUTHORIZED_ASSET_ACCESS`/`UNAUTHORIZED_TASK_ACCESS`/`UNAUTHORIZED_FILE_ACCESS` (403), `ASSET_NOT_FOUND`/`TASK_NOT_FOUND`/`FILE_NOT_FOUND` (404).
- Событий Messenger для ассетов нет.
- Источники: `api/src/Presentation/Controller/Asset/AssetController.php`, `api/src/Presentation/Controller/Task/TaskAssetController.php`, `api/src/Application/Service/Asset/**`, `api/tests/Integration/Asset/**`.
