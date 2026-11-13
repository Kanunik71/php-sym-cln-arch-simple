# Notifications Specification

## Purpose

Домен уведомлений: типы и каналы, каталог настроек по умолчанию, переопределения пользователя, транзакционная и оп-ин отправка. Поведение разложено по use-case'ам на подразделы: [preferences](preferences/spec.md), [delivery](delivery/spec.md).

## Requirements

### Requirement: Типы и каналы уведомлений
The API SHALL поддерживать типы уведомлений `user_registered` и `user_updated` и каналы `email` и `sms`; тип `user_registered` — транзакционный (отправляется всегда, не настраивается), тип `user_updated` — настраиваемый.

#### Scenario: Набор типов и каналов
- **GIVEN** клиент читает настройки уведомлений
- **WHEN** запрошен каталог
- **THEN** в каталоге присутствуют типы `user_registered` и `user_updated`; допустимые каналы — `email` и `sms`

### Requirement: Каталог настроек по умолчанию
The API SHALL возвращать для типа без строки переопределения дефолт каталога: `user_updated` — включён, каналы `email` и `sms`; тип `user_registered` — транзакционный, каналы `email`. Строка в БД хранит только переопределение и создаётся при первой записи; сама регистрация пользователя строки не создаёт.

#### Scenario: Дефолт без переопределения
- **GIVEN** у пользователя нет строки переопределения для `user_updated`
- **WHEN** запрошены настройки
- **THEN** `user_updated` — включён, каналы `[email, sms]`

## Notes

- Каталог дефолтов (`defaults()`/`defaultFor()`): `user_updated = {enabled: true, email, sms}`; `user_registered` — transactional; строка БД (`user_notification_preferences`, unique `owner_id+type`) хранит только переопределение; регистрация строк не создаёт. `NotificationTypeEnum`: `user_registered`, `user_updated`; `NotificationChannelEnum`: `email`, `sms`.
- Отправка (`NotificationService::notify`): transactional → `[email]`; иначе строка или дефолт; без каналов — выход. Шаблоны `api/templates/notification/{type}.{channel}.twig` (`user_registered.email`, `user_updated.email`, `user_updated.sms`); переменные `fname`, `lname`, `email`. Email — Symfony Mailer (`app.mailer_from`), HTML; SMS — заглушка в лог (`LoggingSmsSenderAdapter`).
- События маршрутизируются в `async` (RabbitMQ; тестах `in-memory://`), retry — общий для транспорта: 3 попытки, delay 1 с, multiplier 2, затем `failed` (см. [platform](../platform/spec.md)).
- `codeId`: `VALIDATION_ERROR` (400) — невалидные JSON/`type`/`channels`, transactional-тип; `401` — нет JWT.
- Реализация: `PUT` сохраняет каждый пункт отдельной записью без общей транзакции — при сбое на середине ранее сохранённые пункты остаются применёнными.
- Реализация: SMS отправляется заглушкой — сообщение пишется в лог вместо вызова провайдера.
- Реализация: письма отправляются в HTML без text-альтернативы.
- Решения (из старой спеки): новые типы (задачи, дедлайны) не добавляем.
- Источники: `api/src/Presentation/Controller/Notification/NotificationPreferenceController.php`, `api/src/Application/Service/Notification/**`, `api/src/Enum/Notification/**`, `api/src/Infrastructure/Messaging/User/Handler/**`, `api/tests/Integration/Notification/**`.

## Покрытие тестами

- Не покрыты: обработчики событий и повторная отправка уведомления.
