# Задачи: refresh token, logout и политика пароля

| | |
|---|---|
| Спека | [`spec.md`](spec.md) |
| План | [`plan.md`](plan.md) |
| Статус | `implemented` |

## 1. Пароль

- [x] `InputAssertUtils::{requiredPassword,optionalPassword}` — без trim
- [x] `PasswordPolicy` — 6–72 байта
- [x] Вызовы в `UserRegisterService` и `UserUpdateService`

Проверка: `composer phpstan`

## 2. Refresh-токены

- [x] `RefreshTokenModel` + инварианты
- [x] `RefreshTokenRepositoryInterface`
- [x] `RefreshTokenEntity` + `RefreshTokenMapper`
- [x] `DoctrineRefreshTokenRepository` + атомарный отзыв
- [x] Миграция `refresh_tokens`

Проверка: `composer phpstan`; `doctrine:schema:validate --env=test`

## 3. Сервисы

- [x] `RefreshTokenService`: issue/rotate/revoke/cleanup
- [x] `UserTokenPairService`: JWT + refresh
- [x] `UserLoginService`: выдаёт пару
- [x] `UserRefreshService`: ротация
- [x] `UserLogoutService`: отзыв family
- [x] `InvalidRefreshTokenException` + `ErrorCodeEnum`
- [x] Отзыв всех токенов при смене пароля

Проверка: `composer phpstan`

## 4. Presentation

- [x] `UserLoginResponseModel`: `refreshToken`, `tokenType`, `expiresIn`
- [x] `UserRefreshModel`, `UserLogoutModel`
- [x] `AuthController`: `/refresh`, `/logout`
- [x] Cleanup console + scheduler

Проверка: `php bin/console debug:router`

## 5. Тесты

- [x] `PasswordPolicyTest`
- [x] `RefreshTokenServiceTest`
- [x] Integration-сценарии S-1, S-4–S-8:
  `testUpdateRejectsShortPassword`, `testRefreshRotatesToken`,
  `testRefreshReuseRevokesFamily`, `testLogoutRevokesRefreshToken`,
  `testPasswordChangeRevokesRefreshTokens`

Проверка: `php bin/phpunit`

## 6. Финализация

- [x] `php-cs-fixer` по изменённым PHP-файлам
- [x] `composer phpstan`
- [x] `php bin/phpunit`
- [x] `doctrine:schema:validate --env=test`
- [x] Статус `spec.md` → `implemented`
- [x] Обновить `0001` (37 маршрутов) и `docs/README.md`

Общий `composer cs-check` остаётся красным: 95 файлов вне scope `0002` имеют
предсуществующие style-diff.
