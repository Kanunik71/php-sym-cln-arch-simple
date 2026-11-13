# Notifications Preferences Specification

## Purpose

Чтение и обновление настроек уведомлений пользователя: `GET` и `PUT /api/users/me/notification-preferences`.

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
- **Covers:** `tests/Application/Service/Notification/Action/NotificationUpdateServiceTest::testAppliesLastEntryWhenTypeRepeats`

### Requirement: Транзакционные типы не настраиваются
The API SHALL отклонять `PUT` с транзакционным типом (`user_registered`) кодом `400 VALIDATION_ERROR`.

#### Scenario: Попытка настроить user_registered
- **GIVEN** запрос содержит `user_registered`
- **WHEN** `PUT /api/users/me/notification-preferences`
- **THEN** `400 VALIDATION_ERROR`
- **Covers:** `testUpdatePreferencesRejectsTransactionalType`, `NotificationUpdateServiceTest::testThrowsWhenTypeIsTransactional`

## Notes

- Типы, каналы и каталог дефолтов — в [notifications](../spec.md); отправка — в [delivery](../delivery/spec.md).
- Реализация: `PUT` сохраняет каждый пункт отдельной записью без общей транзакции — при сбое на середине ранее сохранённые пункты остаются применёнными.
- Источники: `api/src/Presentation/Controller/Notification/NotificationPreferenceController.php`, `api/src/Application/Service/Notification/Action/NotificationUpdateService.php`, `api/src/Application/Service/Notification/Query/NotificationQueryService.php`, `api/src/Application/Policy/Notification/UpdateNotificationPreferencesPolicy.php`, `api/tests/Integration/Notification/NotificationPreferenceControllerTest.php`.
