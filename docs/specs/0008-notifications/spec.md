# Notifications — baseline

| | |
|---|---|
| Тип | `baseline` (as-is) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/src/Presentation/Controller/Notification/NotificationPreferenceController.php`, `api/src/Application/Service/Notification/**`, `api/src/Application/Model/Notification/**`, `api/src/Application/Enum/Notification/**`, `api/src/Infrastructure/Notification/**`, `api/src/Infrastructure/Messaging/User/Handler/**`, `api/templates/notification/**`, `api/tests/Integration/Notification/**`, `api/tests/Application/Service/Notification/**` |

## Контекст

Уведомления отправляются по событиям пользователя. `user_registered` —
транзакционное (всегда email), `user_updated` — настраиваемое (email/SMS).
Чтение и отправка берутся из каталога дефолтов; строка в БД хранит только
переопределение. Отправка идёт асинхронно через Messenger.

## Акторы и доступ

- Оба маршрута — `ROLE_USER`, JWT обязателен.
- Пользователь работает только со своими настройками (`/users/me`).
- Отправка выполняется worker-ом при обработке `async`-событий.
- Email — Symfony Mailer; SMS — заглушка, пишет в лог.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | пользователь | видеть свои настройки уведомлений | понимать, что включено |
| US-2 | пользователь | включать/отключать типы и каналы | управлять шумом |
| US-3 | система | всегда отправлять транзакционные письма | не терять важное |
| US-4 | система | уважать настройки и отсутствие телефона | не слать лишнее |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | Настройки — список `{type, enabled, channels}` |
| FR-2 | `channels` ⊆ `{email, sms}`; пустой список допустим |
| FR-3 | `user_registered` нельзя настроить (`400`) |
| FR-4 | `PUT` перезаписывает пункты каталога, которые прислал клиент; отсутствующая строка создаётся |
| FR-5 | Транзакционные типы всегда шлют email, настройки игнорируются |
| FR-6 | Нет строки → действует каталог дефолтов (`user_updated`: enabled, email и sms) |
| FR-7 | SMS не отправляется при отсутствии телефона (warning в лог) |
| FR-8 | Регистрация не пишет настройки; строка в БД — только переопределение каталога |

## Сценарии

### S-1: Чтение настроек

- **Given** пользователь зарегистрирован
- **When** `GET /api/users/me/notification-preferences`
- **Then** `200`, каталог с учётом переопределений (без строки — дефолт
  `user_updated`)
- **Тест:** `NotificationPreferenceControllerTest::testGetPreferencesReturnsOk`

### S-2: Обновление

- **Given** настройка `user_updated` существует
- **When** `PUT` с `{"preferences":[{"type":"user_updated","enabled":false,"channels":["email"]}]}`
- **Then** `200`, настройки сохранены; `enabled=false` → отправки нет
- **Тесты:** `testUpdatePreferencesReturnsOk`,
  `NotificationUpdateServiceTest::testUpdatesPreferences`,
  `NotificationServiceTest::testUserUpdatedSkipsWhenDisabled`

### S-3: Транзакционный тип

- **Given** запрос содержит `user_registered`
- **When** `PUT`
- **Then** `400 VALIDATION_ERROR`
- **Тесты:** `testUpdatePreferencesRejectsTransactionalType`,
  `NotificationUpdateServiceTest::testThrowsWhenTypeIsTransactional`

### S-4: Переопределение без строки

- **Given** записи для типа нет
- **When** `PUT` с `user_updated`
- **Then** `200`, строка создана и заменяет дефолт; остальные пункты каталога не меняются
- **Тест:** `NotificationUpdateServiceTest::testCreatesOverrideWhenPreferenceMissing`

### S-5: Регистрация

- **Given** опубликован `UserRegisteredEvent`
- **When** handler выполняется
- **Then** email отправлен независимо от настроек
- **Тест:** `NotificationServiceTest::testUserRegisteredAlwaysSendsEmail`

### S-6: Обновление профиля

- **Given** `user_updated` включён, есть телефон
- **When** опубликован `UserUpdatedEvent`
- **Then** отправлены email и SMS; без телефона — только email + warning
- **Тесты:** `testUserUpdatedRespectsPreferencesAndSkipsSmsWithoutPhone`,
  `testUserUpdatedSendsSmsWhenPhonePresent`

## API-контракт

| Метод | Путь | Вход | Успех | Ошибки |
|-------|------|------|-------|--------|
| GET | `/api/users/me/notification-preferences` | — | `200`, `NotificationPreferenceViewModel[]` | `401` |
| PUT | `/api/users/me/notification-preferences` | `UpdateNotificationPreferencesModel` | `200`, каталог с переопределениями | `400`, `401` |

### Request `UpdateNotificationPreferencesModel`

```json
{
  "preferences": [
    {"type": "user_updated", "enabled": true, "channels": ["email", "sms"]}
  ]
}
```

| Поле | Тип | Обяз. |
|------|-----|-------|
| `preferences` | array | да |
| `preferences[].type` | enum `NotificationTypeEnum` | да |
| `preferences[].enabled` | bool | да |
| `preferences[].channels` | enum[] `NotificationChannelEnum` | да |

### Response `NotificationPreferenceViewModel`

`type` (string), `enabled` (bool), `channels` (string[]). `id`, `ownerId` не
возвращаются.

`NotificationTypeEnum`: `user_registered` (transactional), `user_updated`
(opt-in). `NotificationChannelEnum`: `email`, `sms`.

## Модели и инварианты

`NotificationPreferenceModel`:

- `id` (UUID v7), `ownerId`, `type`, `enabled`, `emailEnabled`, `smsEnabled`;
- конструктор запрещает transactional-типы;
- `getChannels()` → включённые каналы;
- `withChannels(enabled, channels)` полностью перезаписывает каналы;
  `enabled` не влияет на каналы;
- каталог: `defaults()` / `defaultFor()` — `user_updated = {enabled: true, email, sms}`;
  `resolve()` подставляет сохранённую строку вместо пункта каталога;
- `createDefaultUserUpdated(ownerId)` → этот дефолт;
- таблица `user_notification_preferences`, уникальность `(owner_id, type)`.

Хранение каналов — две булевы колонки (`email_enabled`, `sms_enabled`), не
массив.

## Отправка

`NotificationService::notify(userId, type)`:

1. Загрузить пользователя.
2. `resolveChannels`:
   - transactional → `[Email]`;
   - иначе строка или дефолт каталога; `enabled=false` → `[]`;
   - убрать `Sms`, если `phone === null` (warning).
3. Если каналов нет — выйти.
4. Для каждого канала: `templateRenderer->render(type, channel, vars)`.
5. Email → `mailer->send(to, subject, body)`; SMS → `smsSender->send(phone, body)`.

Переменные шаблона: `fname`, `lname`, `email` (пустая строка вместо `null`).

Шаблоны (`api/templates/notification/`):

- `user_registered.email.twig`
- `user_updated.email.twig`
- `user_updated.sms.twig`

Имя шаблона — `notification/{type}.{channel}.twig`. Блок `subject` опционален;
блок `body` рендерится отдельно, иначе — весь шаблон.

Адаптеры:

- `SymfonyMailerAdapter` — HTML-письмо, `from = app.mailer_from`.
- `LoggingSmsSenderAdapter` — заглушка: `info('SMS notification (stub).')`.

## Async и события

| Событие | Публикует | Handler | Тип |
|---------|-----------|---------|-----|
| `UserRegisteredEvent` | `UserLifecycleService` после регистрации | `UserRegisteredMessageHandler` | `user_registered` |
| `UserUpdatedEvent` | `UserLifecycleService` после `PUT /api/users/{id}` | `UserUpdatedMessageHandler` | `user_updated` |

Оба маршрутизируются в `async` (RabbitMQ). В тестах — `in-memory://`.
Повтор — политика транспорта `async`: 3 попытки, delay 1 с, multiplier 2, затем `failed`
(`docs/specs/0009-platform`). Отдельного retry для SMS нет: падает всё сообщение handler-а.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидный JSON/`type`/`channels`; transactional-тип |
| — | 401 | нет/невалиден JWT |

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| `PUT` | обновление нескольких записей без транзакции; ошибка на N-й оставляет предыдущие сохранёнными | атомарность | `NotificationUpdateService` |
| `PUT` | дубли `type` в запросе не проверяются | валидация контракта | `UpdateNotificationPreferencesPolicy` |
| SMS | доставка — заглушка в лог; реального провайдера не будет | — | `LoggingSmsSenderAdapter` |
| Тесты | нет тестов handlers и повторной отправки | покрытие async | `tests/Application/Service/Notification` |
| Email | HTML-шаблоны без text-альтернативы | deliverability | `SymfonyMailerAdapter` |

## Первоисточники

- `api/src/Presentation/Controller/Notification/NotificationPreferenceController.php`
- `api/src/Application/Model/Notification/**`
- `api/src/Application/Service/Notification/**`
- `api/src/Application/Policy/Notification/UpdateNotificationPreferencesPolicy.php`
- `api/src/Application/Enum/Notification/**`
- `api/src/Application/Port/Notification/**`
- `api/src/Application/Mapper/Notification/NotificationPreferenceViewModelMapper.php`
- `api/src/Infrastructure/Notification/**`
- `api/src/Infrastructure/Messaging/User/Handler/**`
- `api/src/Application/Event/User/{UserRegisteredEvent,UserUpdatedEvent}.php`
- `api/templates/notification/**`
- `api/tests/Integration/Notification/**`
- `api/tests/Application/Service/Notification/**`

## Решения

- Новые типы (задачи, группы, дедлайны) не добавляем.
- Реального SMS-провайдера не будет. Retry — общий `async` (см. выше).
- База — каталог дефолтов. `PUT` перезаписывает только присланные пункты.
