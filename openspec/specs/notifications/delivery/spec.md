# Notifications Delivery Specification

## Purpose

Отправка уведомлений по событиям: транзакционная отправка и оп-ин отправка с учётом настроек.

## Requirements

### Requirement: Транзакционная отправка
The API SHALL по событию `UserRegisteredEvent` (публикуется после регистрации) отправлять email `user_registered` **независимо от настроек** — обработчик вызывает отправку уведомления.

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

#### Scenario: Тип отключён
- **GIVEN** для `user_updated` задан `enabled=false`
- **WHEN** обработан `UserUpdatedEvent`
- **THEN** уведомление не отправлено

## Notes

- Типы, каналы и каталог дефолтов — в [notifications](../spec.md); настройки пользователя — в [preferences](../preferences/spec.md); асинхронность и retry — в [platform](../../platform/spec.md).
- Реализация: SMS отправляется заглушкой — сообщение пишется в лог вместо вызова провайдера.
- Реализация: письма отправляются в HTML без text-альтернативы.
- Источники: `api/src/Application/Service/Notification/Lifecycle/NotificationService.php`, `api/src/Infrastructure/Messaging/User/Handler/UserRegisteredMessageHandler.php`, `api/src/Infrastructure/Messaging/User/Handler/UserUpdatedMessageHandler.php`, `api/tests/Application/Service/Notification/Lifecycle/NotificationServiceTest.php`.

## Покрытие тестами

- Не покрыты: обработчики событий и повторная отправка уведомления.
