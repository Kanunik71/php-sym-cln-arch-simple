# Auth — refresh token, logout и политика пароля

| | |
|---|---|
| Тип | `change` (to-be) |
| Статус | `implemented` |
| Дата | 2026-10-27 |
| Источники | `api/src/Presentation/Controller/Auth/AuthController.php`, `api/src/Application/Service/User/**`, `api/src/Infrastructure/Security/**`, `api/tests/Integration/Auth/**` |

## Контекст

Регистрация и вход по email/паролю. Аутентификация stateless, через JWT.
Публичные маршруты — единственные вне JWT.

Нужно добавить refresh-токены с ротацией, logout и минимальную политику
пароля. Регистрация по-прежнему не аутентифицирует пользователя.

## Состояние до изменений (as-is)

- `POST /api/auth/register` создаёт пользователя и не выдаёт JWT.
- `POST /api/auth/login` возвращает только access JWT с TTL `3600`.
- Refresh-токенов, `/refresh` и `/logout` нет. Файл
  `api/config/routes/security.yaml` подключает route loader logout, но у
  firewall `api` не настроен `logout`, поэтому endpoint не зарегистрирован.
- Минимальная длина и сложность пароля не проверяются.
- Пароль при разборе payload обрезается `trim()`.

## Акторы и доступ

- Аноним: `POST /api/auth/register`, `POST /api/auth/login`,
  `POST /api/auth/refresh`, `POST /api/auth/logout`.
- Firewall `api_auth` (`^/api/auth`) — `security: false`; `access_control`
  разрешает `PUBLIC_ACCESS`.
- `GET /api/auth/me` и подтверждение email отсутствуют.

## User stories

| ID | Как | Хочу | Чтобы |
|----|-----|------|-------|
| US-1 | новый пользователь | зарегистрироваться по email и паролю | получить аккаунт без автоматической аутентификации |
| US-2 | зарегистрированный пользователь | войти и получить access + refresh токены | вызывать защищённые методы и продлевать сессию |
| US-3 | клиент с истёкшим access-токеном | обновить пару токенов по refresh-токену | не заставлять пользователя вводить пароль заново |
| US-4 | пользователь | выйти из текущей сессии | отозвать family этого логина, не трогая другие сессии |
| US-5 | пользователь | сменить пароль | отозвать все активные refresh-сессии |

## Требования

| ID | Требование |
|----|------------|
| FR-1 | Регистрация создаёт пользователя без выдачи access/refresh токенов |
| FR-2 | Email нормализуется (trim, lower-case) и должен быть уникальным |
| FR-3 | Пароль при регистрации и смене должен содержать 6–72 байта; пробелы по краям не обрезаются |
| FR-4 | Регистрация не создаёт строку notification preference; дефолт — каталог в `0008` |
| FR-5 | Логин при верных credentials возвращает access JWT и refresh-токен |
| FR-6 | Access JWT живёт 900 секунд; refresh-токен — 30 дней от выдачи или последней ротации, без абсолютного потолка сессии |
| FR-7 | Refresh-токен непрозрачный, в БД хранится только `sha256`-хеш |
| FR-8 | `/refresh` ротирует refresh-токен: старый отзывается, выдаётся новая пара |
| FR-9 | Повторное использование отозванного refresh-токена отзывает всю его family |
| FR-10 | `/logout` отзывает family предъявленного токена, в том числе уже ротированного, и возвращает `204`; другие family пользователя живы |
| FR-11 | Непустое `password` в `PUT /api/users/{id}` отзывает все family в той же транзакции, что и сохранение профиля |
| FR-12 | `UserDeleteService` явно удаляет refresh-токены в транзакции удаления; FK без `ON DELETE CASCADE` |
| FR-13 | Неверный email/пароль и неизвестный refresh-токен не раскрывают причину отказа |

## Сценарии

### S-1: Успешная регистрация и логин

- **Given** email свободен
- **When** `POST /api/auth/register` с валидными `email` и `password`
- **Then** `201`, поля `UserRegisterResponseModel`, без `token` и
  `refreshToken`; затем `POST /api/auth/login`
  → `200`, непустые `token`, `refreshToken`, `tokenType = Bearer`,
  `expiresIn = 900`
- **Тест:** `tests/Integration/Auth/AuthControllerTest::testRegisterAndLoginFlow`

### S-2: Дубликат email

- **Given** пользователь с таким email существует
- **When** `POST /api/auth/register`
- **Then** `409`, `codeId = USER_ALREADY_EXISTS`
- **Тест:** `testRegisterDuplicateEmailReturnsConflict`

### S-3: Необязательные поля

- **Given** запрос без `fname`/`lname` или без `location`
- **When** регистрация
- **Then** `201`, соответствующие поля `null`
- **Тесты:** `testRegisterWithoutNames`, `testRegisterWithCity`

### S-4: Слишком короткий пароль

- **Given** пароль короче 6 байт
- **When** регистрация или смена пароля
- **Then** `400`, `codeId = VALIDATION_ERROR`
- **Тест:** `testRegisterRejectsShortPassword`,
  `testUpdateRejectsShortPassword`

### S-5: Refresh-ротация

- **Given** пользователь вошёл и получил пару токенов
- **When** `POST /api/auth/refresh` с текущим `refreshToken`
- **Then** `200`, новая пара; старый refresh больше не работает (`401`)
- **Тест:** `testRefreshRotatesToken`

### S-6: Повторное использование refresh-токена

- **Given** refresh-токен уже был ротирован
- **When** клиент повторно присылает старый токен
- **Then** `401 INVALID_REFRESH_TOKEN`; все активные токены family отозваны
- **Тест:** `testRefreshReuseRevokesFamily`

### S-7: Logout

- **Given** пользователь имеет активный refresh-токен
- **When** `POST /api/auth/logout` с этим токеном
- **Then** `204`; повторный `/refresh` с ним → `401`
- **Тест:** `testLogoutRevokesRefreshToken`

### S-8: Смена пароля отзывает сессии

- **Given** у пользователя есть refresh-токены
- **When** `PUT /api/users/{id}` с непустым `password`
- **Then** все прежние refresh-токены не работают; отзыв зафиксирован в той же транзакции, что и новый хэш
- **Тест:** `testPasswordChangeRevokesRefreshTokens`

## API-контракт

| Метод | Путь | Доступ | Вход | Успех | Ошибки |
|-------|------|--------|------|-------|--------|
| POST | `/api/auth/register` | public | `UserRegisterModel` | `201`, `UserRegisterResponseModel` | `400 VALIDATION_ERROR`, `409 USER_ALREADY_EXISTS`, `500` при гонке unique-email |
| POST | `/api/auth/login` | public | `UserLoginModel` | `200`, `UserLoginResponseModel` | `400 VALIDATION_ERROR`, `401 INVALID_CREDENTIALS` |
| POST | `/api/auth/refresh` | public | `UserRefreshModel` | `200`, `UserLoginResponseModel` | `400 VALIDATION_ERROR`, `401 INVALID_REFRESH_TOKEN` |
| POST | `/api/auth/logout` | public | `UserLogoutModel` | `204` | `400 VALIDATION_ERROR` |

### Request `UserRegisterModel`

| Поле | Тип | Обяз. | Ограничения |
|------|-----|-------|-------------|
| `email` | string | да | непустой; `Email`: trim, lower-case, ≤255, `FILTER_VALIDATE_EMAIL` |
| `password` | string | да | 6–72 байта; непустой; пробелы по краям сохраняются |
| `fname` | string/null | нет | trim; пусто → `null`; ≤100 |
| `lname` | string/null | нет | trim; пусто → `null`; ≤100 |
| `location` | object/null | нет | `{"city": string}`; `city` непустой, ≤100 |
| `phone` | string/null | нет | trim; пусто → `null`; `Phone` E.164, ≤20 |

### Request `UserLoginModel`

| Поле | Тип | Обяз. |
|------|-----|-------|
| `email` | string | да |
| `password` | string | да |

### Request `UserRefreshModel`

| Поле | Тип | Обяз. |
|------|-----|-------|
| `refreshToken` | string | да |

### Request `UserLogoutModel`

| Поле | Тип | Обяз. |
|------|-----|-------|
| `refreshToken` | string | да |

### Response `UserLoginResponseModel`

```json
{
  "token": "<jwt>",
  "refreshToken": "<rt_...>",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

## Модели и инварианты

- `UserModel`: `id` (UUID v7), `email`, `passwordHash`, `fname`, `lname`,
  `city`, `phone`, `avatarFileId`, `createdAt`, `updatedAt`.
- `UserModel::create()` генерирует UUID v7 и ставит текущие даты.
- `Email` и `Phone` — VO в `Application/ValueObject/Common/`.
- `Phone` нормализует `+7 (900) 123-45-67` → `+79001234567`.
- Пароль не хранится в открытом виде; хэш — через `SymfonyPasswordHasher`.
- `RefreshTokenModel`: `id`, `userId`, `tokenHash`, `familyId`, `createdAt`,
  `expiresAt`, `revokedAt`.
- `tokenHash = sha256(refreshToken)`, длина 64 hex; plaintext в БД не пишется.
- Refresh-токен: `rt_` + 32 случайных байта в base64url.
- Family — одна сессия логина. `issue` создаёт новый `familyId`; `/refresh` сохраняет его. Параллельные login не делят family.

## JWT и refresh

- `token_ttl = 900`, `signature_algorithm = HS256`, `secret_key`/`public_key`
  из `JWT_SECRET`.
- `LexikTokenProvider::generateToken()`: claims `roles = ["ROLE_USER"]`,
  `username = userId`, `email`.
- `SecurityUser::getUserIdentifier()` → id; `getRoles()` → `["ROLE_USER"]`.
- `DoctrineUserProvider` загружает пользователя по id.
- Refresh TTL — 30 дней от момента выдачи. Ротация ставит новому токену
  `expiresAt = now + 30 дней` и не ограничивает суммарную длину сессии.
  Ротация выполняется в транзакции; отзыв старого токена атомарный
  (`UPDATE ... WHERE revoked_at IS NULL`).
- При обнаружении отозванного токена отзывается вся `family_id`.
- Logout отзывает всю family предъявленного токена, включая уже ротированный
  токен той же family. Неизвестный токен не меняет данные и тоже даёт `204`.
- Access-токен после logout остаётся валиден до истечения TTL (≤900 секунд).

## Async и события

- После регистрации `UserLifecycleService::afterUserPersisted(created: true)`
  публикует `UserRegisteredEvent` → `async` → `UserRegisteredMessageHandler` →
  `NotificationService::notify(user_registered)` (email).
- После логина, refresh и logout событий нет.
- `app:auth:cleanup-refresh-tokens` запускается ежедневно (`30 3 * * *`,
  `SCHEDULER_REFRESH_TOKEN_CLEANUP_CRON`) через `AppScheduleProvider`.
  Удаляются только строки, у которых `expires_at` или `revoked_at` старше
  7 суток (`app.refresh_token.cleanup_retention_seconds = 604800`). Окно нужно
  для FR-9: пока отозванная строка на месте, повтор токена гасит family;
  после удаления строки повтор выглядит как неизвестный токен и family не
  отзывается.

## Ошибки

| `codeId` | HTTP | Когда |
|----------|------|-------|
| `VALIDATION_ERROR` | 400 | невалидный JSON/поля/email/phone/пароль/refresh-токен |
| `USER_ALREADY_EXISTS` | 409 | email занят (precheck) |
| `INVALID_CREDENTIALS` | 401 | пользователь не найден или пароль неверный |
| `INVALID_REFRESH_TOKEN` | 401 | refresh-токен неизвестен, истёк или отозван |
| `INVALID_CREDENTIALS` | 401 | нет или невалиден JWT |

## Нефункциональные требования

- Refresh-токены не логируются и не возвращаются повторно после выдачи.
- Запросы `/refresh` и `/logout` не требуют access-токена.
- Logout не раскрывает существование токена: неизвестный токен → `204`.

## Известные расхождения

| Где | Факт | Ожидание/документация | Ссылка |
|-----|------|-----------------------|--------|
| Гонка email | unique-violation не превращается в `409` | стабильный `409` | `UserRegisterService` |
| Тесты | нет `UserLoginServiceTest` и теста `401 INVALID_CREDENTIALS` | покрытие ошибок | `tests/Application/Service/User` |

## Первоисточники

- `api/src/Presentation/Controller/Auth/AuthController.php`
- `api/src/Application/Model/User/Action/{UserRegisterModel,UserLoginModel,UserRefreshModel,UserLogoutModel}.php`
- `api/src/Application/Model/User/Read/{UserRegisterResponseModel,UserLoginResponseModel}.php`
- `api/src/Application/Model/Auth/RefreshTokenModel.php`
- `api/src/Application/Port/Auth/RefreshTokenRepositoryInterface.php`
- `api/src/Application/Policy/User/PasswordPolicy.php`
- `api/src/Application/Service/Auth/{RefreshTokenService,UserTokenPairService}.php`
- `api/src/Application/Service/User/Action/{UserRegisterService,UserLoginService,UserRefreshService,UserLogoutService,UserUpdateService}.php`
- `api/src/Application/Policy/User/{RegisterUserPolicy,LoginUserPolicy}.php`
- `api/src/Application/ValueObject/Common/{Email,Phone}.php`
- `api/src/Infrastructure/Persistence/Doctrine/{Entity/RefreshTokenEntity,Mapper/RefreshTokenMapper,Repository/DoctrineRefreshTokenRepository}.php`
- `api/src/Infrastructure/Security/{LexikTokenProvider,SecurityUser,DoctrineUserProvider,SymfonyPasswordHasher}.php`
- `api/migrations/Version20260930100000.php`
- `api/config/packages/security.yaml`, `lexik_jwt_authentication.yaml`, `services.yaml`

## Открытые вопросы

Нет.
