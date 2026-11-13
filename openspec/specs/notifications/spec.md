# Notifications Specification

## Purpose
Уведомления по событиям пользователя: `user_registered` — транзакционное (всегда email), `user_updated` — настраиваемое (email/SMS). Настройки хранятся как переопределения над каталогом дефолтов; отправка асинхронная через Messenger.

## Requirements

### Requirement: Чтение настроек
The API SHALL возвращать `GET /api/users/me/notification-preferences` как каталог типов с учётом переопределений в БД; пункт без строки — дефолт каталога (`user_updated` включён, каналы email и sms).

#### Scenario: Чтение настроек
- **GIVEN** пользователь без строки переопределения
- **WHEN** `GET /api/users/me/notification-preferences`
- **THEN** `200`, каталог; `user_updated` — enabled, `[email, sms]`
- **Covers:** `tests/Integration/Notification/NotificationPreferenceControllerTest::testGetPreferencesReturnsOk`

### Requirement: Обновление переопределений
The API SHALL принимать `PUT /api/users/me/notification-preferences` (`preferences[]`: `type`, `enabled`, `channels` ⊆ `{email, sms}`, пустой список каналов допустим), перезаписывая только присланные пункты и создавая отсутствующие строки; неприсланные пункты каталога не меняются; ответ — каталог с переопределениями (`{type, enabled, channels}`; `id`/`ownerId` не возвращаются). Повтор одного типа в запросе не отклоняется — применяется последнее вхождение.

#### Scenario: Обновление переопределения
- **GIVEN** пользователь присылает `{"preferences":[{"type":"user_updated","enabled":false,"channels":["email"]}]}`
- **WHEN** `PUT /api/users/me/notification-preferences`
- **THEN** `200`, настройка сохранена; при отключённом типе отправок не будет
- **Covers:** `testUpdatePreferencesReturnsOk`, `NotificationUpdateServiceTest::testUpdatesPreferences`

#### Scenario: Создание строки при отсутствии
- **GIVEN** записи для типа нет
- **WHEN** `PUT` с `user_updated`
- **THEN** `200`, строка создана и заменяет дефолт каталога, остальные пункты не меняются
- **Covers:** `NotificationUpdateServiceTest::testCreatesOverrideWhenPreferenceMissing`

#### Scenario: Повтор типа в запросе
- **GIVEN** запрос содержит два пункта с одним и тем же `type`
- **WHEN** `PUT /api/users/me/notification-preferences`
- **THEN** `200`, сохранено значение последнего пункта; в ответе тип присутствует один раз

### Requirement: Транзакционные типы не настраиваются
The API SHALL отклонять `PUT` с транзакционным типом (`user_registered`) кодом `400 VALIDATION_ERROR`.

#### Scenario: Попытка настроить user_registered
- **GIVEN** запрос содержит `user_registered`
- **WHEN** `PUT /api/users/me/notification-preferences`
- **THEN** `400 VALIDATION_ERROR`
- **Covers:** `testUpdatePreferencesRejectsTransactionalType`, `NotificationUpdateServiceTest::testThrowsWhenTypeIsTransactional`

### Requirement: Транзакционная отправка
The API SHALL по событию `UserRegisteredEvent` (публикуется после регистрации) отправлять email `user_registered` **независимо от настроек** — handler `UserRegisteredMessageHandler` вызывает `NotificationService::notify(user_registered)`.

#### Scenario: Письмо после регистрации
- **GIVEN** успешная регистрация
- **WHEN** worker обрабатывает `UserRegisteredEvent`
- **THEN** email-уведомление отправлено
- **Covers:** `NotificationServiceTest::testUserRegisteredAlwaysSendsEmail`

### Requirement: Оп-ин отправка с уважением настроек и телефона
The API SHALL по событию `UserUpdatedEvent` (публикуется после успешного `PUT` профиля) отправлять уведомление `user_updated` по эффективным настройкам: `enabled=false` → ничего; каналы берутся из строки переопределения или дефолта каталога; SMS опускается при отсутствии телефона (warning в лог), email остаётся. При `DELETE` аккаунта событие не публикуется.

#### Scenario: Отправка с телефоном
- **GIVEN** `user_updated` включён, у пользователя есть телефон
- **WHEN** обработан `UserUpdatedEvent`
- **THEN** отправлены email и SMS
- **Covers:** `testUserUpdatedSendsSmsWhenPhonePresent`

#### Scenario: Без телефона
- **GIVEN** телефона нет
- **WHEN** обработан `UserUpdatedEvent` (тип включён)
- **THEN** только email; SMS пропущен с warning в логах
- **Covers:** `testUserUpdatedRespectsPreferencesAndSkipsSmsWithoutPhone`

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
- Не покрыт: сценарий «Повтор типа в запросе».
