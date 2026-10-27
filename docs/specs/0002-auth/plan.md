# План: refresh token, logout и политика пароля

| | |
|---|---|
| Спека | [`spec.md`](spec.md) |
| Статус спеки | `approved` |
| Дата | 2026-10-27 |

> Архитектурные правила — в [`api/CLAUDE.md`](../../../api/CLAUDE.md); план
> ссылается на них, а не переписывает.

## Кратко

Добавляем собственный refresh-механизм: непрозрачный токен с хешем в БД,
ротацией и family-reuse detection, плюс logout и политику пароля 6–72 байта.
Готовый bundle не используем: нужен camelCase-контракт и полный контроль
над отзывом.

## Затронутые слои

| Слой | Что меняется |
|------|--------------|
| Domain | нет |
| Application | `PasswordPolicy`, `RefreshTokenModel`, `RefreshTokenRepositoryInterface`, token-pair/refresh/logout services, request/response models, `InvalidRefreshTokenException` |
| Infrastructure | `RefreshTokenEntity`, mapper, Doctrine repository, миграция, cleanup console |
| Presentation | `AuthController`: login response, `/refresh`, `/logout` |
| DB | таблица `refresh_tokens` |
| Docker / config | `token_ttl = 900`, refresh TTL, автозапуск cleanup |

## Контракты

### Вход

| Command / Query | Model | Контекст |
|-----------------|-------|----------|
| `UserLoginService::execute` | `UserLoginModel` | public |
| `UserRefreshService::execute` | `UserRefreshModel` | public |
| `UserLogoutService::execute` | `UserLogoutModel` | public |

### Выход

| Handler | Возвращает |
|---------|------------|
| `UserLoginService` | `UserLoginResponseModel` (access + refresh) |
| `UserRefreshService` | `UserLoginResponseModel` (access + refresh) |
| `UserLogoutService` | `void` |

### Port / Enum / VO

| Тип | Имя | Назначение |
|-----|-----|------------|
| Port | `RefreshTokenRepositoryInterface` | сохранение, поиск по хешу, атомарный отзыв, отзыв family/user, `deleteAllForUser`, cleanup |
| Policy | `PasswordPolicy` | 6–72 байта |
| Enum | `ErrorCodeEnum::InvalidRefreshToken` | `INVALID_REFRESH_TOKEN` |
| Utils | `InputAssertUtils::{requiredPassword,optionalPassword}` | пароль без trim |

## Карта файлов

```
api/src/Application/Exception/InvalidRefreshTokenException.php
api/src/Application/Model/Auth/RefreshTokenModel.php
api/src/Application/Model/User/Action/{UserRefreshModel,UserLogoutModel}.php
api/src/Application/Model/User/Read/UserLoginResponseModel.php
api/src/Application/Policy/User/PasswordPolicy.php
api/src/Application/Port/Auth/RefreshTokenRepositoryInterface.php
api/src/Application/Service/Auth/{RefreshTokenService,UserTokenPairService}.php
api/src/Application/Service/User/Action/{UserLoginService,UserRefreshService,UserLogoutService}.php
api/src/Application/Service/User/Action/{UserRegisterService,UserUpdateService}.php
api/src/Infrastructure/Persistence/Doctrine/Entity/RefreshTokenEntity.php
api/src/Infrastructure/Persistence/Doctrine/Mapper/RefreshTokenMapper.php
api/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineRefreshTokenRepository.php
api/src/Presentation/Console/Auth/CleanupRefreshTokensConsole.php
api/src/Infrastructure/Scheduler/AppScheduleProvider.php
api/src/Presentation/Controller/Auth/AuthController.php
api/src/Shared/Utils/Asserts/InputAssertUtils.php
api/migrations/Version20260930100000.php
api/tests/Application/Policy/User/PasswordPolicyTest.php
api/tests/Application/Service/Auth/RefreshTokenServiceTest.php
api/tests/Integration/Auth/AuthControllerTest.php
```

## Данные и миграции

- `refresh_tokens`: `id` BINARY(16) PK, `user_id` BINARY(16) NOT NULL,
  `token_hash` CHAR(64) NOT NULL unique, `family_id` BINARY(16) NOT NULL,
  `expires_at` DATETIME NOT NULL, `revoked_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL.
- FK `user_id -> users.id` без `ON DELETE CASCADE`. Удаление строк — `UserDeleteService` через `deleteAllForUser`.
- Индексы: `user_id`, `family_id`, `expires_at`.
- Plaintext refresh-токен не хранится.

## Async, события, кэш

- Новых доменных событий нет.
- Cleanup — console-команда по расписанию (не async-сообщение).

## Безопасность и доступы

- `/refresh` и `/logout` публичные, работают по refresh-токену.
- Не логировать refresh-токен и его plaintext.
- Logout идемпотентен: неизвестный токен → `204`.
- Ротация и повторное использование — в транзакции, атомарный отзыв.
- При непустом `password` — `revokeAllForUser` в той же транзакции, что и сохранение профиля.
- При удалении пользователя — явный `deleteAllForUser` до удаления строки `users`.

## Тесты

| Тип | Что покрываем |
|-----|---------------|
| Application | password policy; выдача/ротация/expired/revoked/reuse/family revoke |
| Integration | register без токенов; login pair; refresh rotation; reuse; logout; password change; короткий пароль |

## Риски и компромиссы

- Access-токен после logout живёт до 900 секунд; мгновенный отзыв не делаем.
- Скользящий refresh TTL: каждая ротация продлевает сессию на 30 дней.
- Очистка удаляет только давно истёкшие/отозванные токены.

## Ссылки на соглашения

- `api/CLAUDE.md` → слои, суффиксы, Port/Policy/Service.
- `api/src/Application/CLAUDE.md` → масштаб, batch, Model.
- `api/tests/CLAUDE.md` → Integration/Application tests.
