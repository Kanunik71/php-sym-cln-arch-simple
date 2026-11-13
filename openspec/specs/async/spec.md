# Async Specification

## Purpose

Асинхронная доставка доменных событий через RabbitMQ и планировщик: транспорт `async`, retry-политика с очередью `failed`, состав cron-задач. Имена и полезная нагрузка событий — в спеках доменов, которые их публикуют и обрабатывают.

## Requirements

### Requirement: Доставка событий

The API SHALL доставлять доменные события через транспорт `async` (RabbitMQ); в тестовом окружении — `in-memory://`. Обработчик события выполняется worker-процессом `messenger:consume async scheduler_default`.

#### Scenario: Обработка события worker-ом

- **GIVEN** событие опубликовано при доступном broker
- **WHEN** worker обрабатывает сообщение
- **THEN** handler приложения выполнен (события — в [auth](../auth/spec.md), [tasks](../tasks/spec.md), [task-groups](../task-groups/spec.md), [notifications](../notifications/spec.md))

### Requirement: Retry и разбор неудач

The API SHALL повторять неудачную обработку `async`-сообщения: 3 попытки, задержка 1000 мс, множитель 2; после исчерпания попыток сообщение SHALL уходить в очередь `failed`, которую worker автоматически не читает (разбор вручную — `messenger:failed:show`/`messenger:failed:retry`).

#### Scenario: Повторные неудачные доставки

- **GIVEN** handler падает
- **WHEN** 3 попытки исчерпаны
- **THEN** сообщение уходит в `failed` и далее не автоповторяется

### Requirement: Планировщик

The API SHALL по расписанию `scheduler_default` выполнять только `app:files:cleanup-tmp` (cron из `SCHEDULER_FILE_CLEANUP_CRON`, по умолчанию `0 3 * * *`) и `app:auth:cleanup-refresh-tokens` (cron из `SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`, по умолчанию `30 3 * * *`), с политикой `processOnlyLastMissedRun`. Другие консольные команды в планировщик не регистрируются.

#### Scenario: Пропущенный запуск

- **GIVEN** worker был недоступен в момент cron
- **WHEN** worker поднимается
- **THEN** запускается только последний пропущенный прогон

## Notes

- Env: `MESSENGER_TRANSPORT_DSN`, `MESSENGER_FAILURE_TRANSPORT_DSN`, `SCHEDULER_FILE_CLEANUP_CRON`, `SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`.
- Очереди: `async` (рабочая), `failed` (dead-letter, не читается worker-ом), `sync` (`sync://`).
- Worker `messenger-worker` в docker-compose — профиль `workers`.
- Исполнение cron-задач: [files/cleanup](../files/cleanup/spec.md), [auth/cleanup](../auth/cleanup/spec.md); `app:task-groups:recalculate` в планировщике не зарегистрирована — [task-groups/recalculate](../task-groups/recalculate/spec.md).
- Источники: `api/config/packages/messenger.yaml`, `api/src/Infrastructure/Messaging/**`, `api/config/services.yaml`, `docker/**`.
