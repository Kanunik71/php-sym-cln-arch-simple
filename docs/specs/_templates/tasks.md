# Задачи: <Название фичи>

| | |
|---|---|
| Спека | [`spec.md`](spec.md) |
| План | [`plan.md`](plan.md) |
| Статус | `in progress` |

Отмечай пункты по мере выполнения. После каждой фазы — проверки.

## 1. Domain

- [ ] <класс/файл> — <что делаем>
- [ ] инварианты и исключения

Проверка: `composer phpstan`

## 2. Application

- [ ] Model: `<...Model>`
- [ ] Command/Query: `<...Command|Query>`
- [ ] Handler: `<...Handler>`
- [ ] Port: `<...RepositoryInterface>`
- [ ] Policy / Service / Dispatcher / Cache — при необходимости
- [ ] ModelMapper

Проверка: `composer phpstan`

## 3. Infrastructure

- [ ] Entity + Mapper
- [ ] Repository + CriteriaApplier
- [ ] MessageHandler / Adapter / Provider
- [ ] Миграция `php bin/console doctrine:migrations:diff`

Проверка: `composer phpstan`

## 4. Presentation

- [ ] Controller / Console
- [ ] Маршруты / аргументы

Проверка: `php bin/console debug:router` / `debug:messenger`

## 5. Тесты

- [ ] Application-тесты
- [ ] Integration-тесты по сценариям `spec.md`

Проверка: `php bin/phpunit`

## 6. Финализация

- [ ] `composer cs-fix`
- [ ] `composer cs-check`
- [ ] `composer phpstan`
- [ ] `php bin/phpunit`
- [ ] Статус `spec.md` → `implemented`
- [ ] Обновить `README.md` / `CLAUDE.md`, если изменилось поведение
