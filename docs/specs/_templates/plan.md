# План: <Название фичи>

| | |
|---|---|
| Спека | [`spec.md`](spec.md) |
| Статус спеки | `approved` |
| Дата | YYYY-MM-DD |

> Перед началом убедись, что `spec.md` в статусе `approved`. Архитектурные
> правила — в [`api/CLAUDE.md`](../../../api/CLAUDE.md); план ссылается на них,
> а не переписывает.

## Кратко

Одно-два предложения: какой подход выбран и почему.

## Затронутые слои

| Слой | Что меняется |
|------|--------------|
| Domain | агрегаты / VO / enum / события / исключения |
| Application | Command/Query, Handler, Model, Port, Policy, Service, Dispatcher, Cache |
| Infrastructure | Entity, Repository, Mapper, MessageHandler, Adapter, CriteriaApplier |
| Presentation | Controller, Console |
| DB | миграции |
| Docker / config | |

## Контракты

### Вход

| Command / Query | Model | Контекст (путь, security) |
|-----------------|-------|---------------------------|
| | | |

### Выход

| Handler | Возвращает (Model / void) |
|---------|---------------------------|
| | |

### Port / Enum / VO

| Тип | Имя | Назначение |
|-----|-----|------------|
| | | |

## Карта файлов

```
api/src/Application/...
api/src/Domain/...
api/src/Infrastructure/...
api/src/Presentation/...
api/tests/...
```

## Данные и миграции

- Изменения схемы и миграция:
- Обратная совместимость / порядок выката:

## Async, события, кэш

- Какие события публикуются и кем (`*Dispatcher`).
- Какие `*MessageHandler` обрабатывают и что вызывают.
- Ключи/инвалидация кэша (`*Cache`).

## Безопасность и доступы

- `*Policy`, проверки авторства/ролей.
- Что нельзя логировать.

## Тесты

| Тип | Что покрываем |
|-----|---------------|
| Application | Handler/Policy/Service |
| Integration | HTTP/CLI сценарии из `spec.md` |

## Риски и компромиссы

- ...

## Ссылки на соглашения

- `api/CLAUDE.md` → <раздел>
- `api/src/Application/CLAUDE.md` → <раздел>
- `api/tests/CLAUDE.md` → <раздел>
