# Errors Specification

## Purpose

Сквозной формат ошибок API: JSON-конверт с `codeId`, маппинг ошибок без собственного кода и реакция на некорректный ввод (невалидный JSON, невалидный идентификатор в пути). Перечень кодов, которые отдаёт каждый домен, — в `## Notes` спеки этого домена.

## Requirements

### Requirement: Формат ошибки приложения

The API SHALL возвращать ошибки приложения как JSON-объект `{"error": "<human readable>", "codeId": "<CODE>"}`: оба поля обязательны, `codeId` — строковый код из перечня, зафиксированного в спеке домена, к которому относится ошибка. HTTP-статус задаёт приложение независимо от `codeId`.

#### Scenario: Форма тела ошибки

- **GIVEN** запрос завершается ошибкой приложения
- **WHEN** клиент читает ответ
- **THEN** тело — JSON с полями `error` (непустая строка) и `codeId` (строка из перечня домена)

### Requirement: Ошибки без собственного кода

The API SHALL подставлять `codeId` для ошибок, которые приложение не оформило как `{"error", "codeId"}`: ответ `401` без кода SHALL переписываться в `codeId = INVALID_CREDENTIALS`; `InvalidArgumentException` SHALL давать `400`; любое другое необработанное исключение SHALL давать `500` с `codeId = VALIDATION_ERROR`, причём в prod текст исключения в ответ не попадает.

#### Scenario: Необработанное исключение

- **GIVEN** обработка запроса падает с исключением без собственного кода
- **WHEN** клиент читает ответ
- **THEN** в prod — `500` с `codeId = VALIDATION_ERROR` без текста исключения

#### Scenario: Отказ аутентификации без кода

- **GIVEN** JWT отсутствует или невалиден
- **WHEN** запрос на защищённый `/api/*`
- **THEN** `401` с `codeId = INVALID_CREDENTIALS`

### Requirement: Невалидный JSON

The API SHALL возвращать `400` с `codeId = VALIDATION_ERROR`, если тело `POST`/`PUT`/`PATCH` не разбирается как JSON.

#### Scenario: Битый JSON в теле

- **GIVEN** тело запроса не является валидным JSON
- **WHEN** `POST`/`PUT`/`PATCH`
- **THEN** `400` с `codeId = VALIDATION_ERROR`

### Requirement: Идентификатор в пути

The API SHALL принимать в path-параметре `{id}` 36-символьную строку из hex-символов и дефисов; строка, не удовлетворяющая этому виду, SHALL давать `404` (маршрут не совпадает); строка вида 36 hex-символов, не разбираемая как UUID, SHALL давать `400` с `codeId = VALIDATION_ERROR` и текстом `Invalid UUID.`.

#### Scenario: Идентификатор не по формату маршрута

- **GIVEN** в `{id}` передана строка, не совпадающая с 36 hex-символами и дефисами
- **WHEN** клиент запрашивает ресурс по этому идентификатору
- **THEN** `404` (маршрут не совпал)
- **Covers:** `tests/Integration/Platform/PathIdentifierTest::testRejectsIdentifierNotMatchingRouteFormat`

#### Scenario: Идентификатор не разбирается как UUID

- **GIVEN** в `{id}` передана 36-символьная hex-строка без корректного расположения дефисов
- **WHEN** клиент запрашивает ресурс по этому идентификатору
- **THEN** `400` с `codeId = VALIDATION_ERROR` и текстом `Invalid UUID.`
- **Covers:** `tests/Integration/Platform/PathIdentifierTest::testRejectsIdentifierNotParsableAsUuid`

## Notes

- Источники: `api/src/Presentation/Controller/BaseController.php`, `api/src/Infrastructure/Http/ApiExceptionSubscriber.php`, `api/src/Shared/Utils/Http/ErrorResponseUtils.php`, `api/src/Application/Model/Http/ErrorResponseModel.php`, `api/src/Application/Exception/**`.
- Перечень `codeId` намеренно не дублируется здесь: каждый домен перечисляет свои коды в `## Notes` своей спеки (например, [tasks](../tasks/spec.md), [files](../files/spec.md), [assets](../assets/spec.md)).
- Доменная ошибка конкретного use case (например, недопустимый переход статуса задачи) описана в спеке этого use case, а не здесь.
