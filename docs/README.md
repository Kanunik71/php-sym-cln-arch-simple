# Разработка по SDD

Spec-Driven Development: сначала фиксируем **что и зачем**, затем **как**, затем
упорядоченные задачи, и только после этого — код.

Архитектурные правила проекта живут в [`api/CLAUDE.md`](../api/CLAUDE.md) и
вложенных файлах:

- [`api/src/Application/CLAUDE.md`](../api/src/Application/CLAUDE.md)
- [`api/src/Infrastructure/CLAUDE.md`](../api/src/Infrastructure/CLAUDE.md)
- [`api/tests/CLAUDE.md`](../api/tests/CLAUDE.md)

Спеки **не дублируют** эти правила, а ссылаются на нужные разделы.

`CLAUDE.md` описывает **целевое** состояние архитектуры. Фактический код может от
него отличаться — это фиксируется в baseline-спеках и в
[ADR 0001](adr/0001-as-is-vs-target-architecture.md). Для change-спек (to-be)
ориентир — `CLAUDE.md`; для baseline (as-is) — код и тесты.

## Когда нужен спек

Спек обязателен для:

- новой фичи или use case;
- изменения публичного контракта (HTTP/CLI, wire-Model);
- миграции БД или изменения схемы;
- async / очередей / scheduler / кэша;
- изменений в безопасности и доступах.

Для мелких правок (опечатка, локальный рефакторинг без изменения поведения)
спек не нужен.

## Baseline-спеки (as-is)

Baseline-спека описывает **фактическое** поведение уже работающего кода, а не
желаемое. Она нужна, чтобы зафиксировать текущий контракт и расхождения с
целевой архитектурой.

- Тип в шапке — `baseline`, статус — `implemented`.
- Файл — только `spec.md` (шаблон
  [`specs/_templates/spec-baseline.md`](specs/_templates/spec-baseline.md));
  `plan.md` и `tasks.md` не создаются.
- Источник истины — код `api/src` и тесты `api/tests`, а не `CLAUDE.md`.
- Расхождения с `CLAUDE.md` и дефекты фиксируются в разделе
  «Известные расхождения»; исправление оформляется отдельной change-спекой.
- При изменении поведения baseline-спека обновляется в том же изменении, что и
  код.

## Поток

```
spec.md  →  plan.md  →  tasks.md  →  реализация  →  проверки
```

1. **spec.md** — требования, сценарии, границы. Согласуется до начала работ.
   Статус `draft` → `approved`.
2. **plan.md** — технический план по слоям, контракты, файлы, миграции, тесты.
   Пишется после утверждения спеки.
3. **tasks.md** — упорядоченный чеклист. Единственный файл, который меняется по
   ходу реализации.
4. **Реализация** — по задачам, с проверками после каждой фазы.
5. **Проверки** — см. Definition of Done.

Для крупных решений (выбор подхода, отказ от альтернативы) заводится ADR в
[`adr/`](adr/README.md).

## Структура

```
docs/
├── README.md                 # этот файл
├── adr/                      # Architecture Decision Records
│   ├── README.md
│   └── _template.md
└── specs/
    ├── _templates/
    │   ├── spec.md
    │   ├── spec-baseline.md
    │   ├── plan.md
    │   └── tasks.md
    ├── 0001-project-overview/   # baseline-спека проекта
    │   └── spec.md
    ├── 0002-auth/               # change-спека: refresh/logout/password
    │   ├── spec.md
    │   ├── plan.md
    │   └── tasks.md
    └── NNNN-<feature-slug>/     # change-спека (to-be)
        ├── spec.md
        ├── plan.md
        └── tasks.md
```

Имя папки: `NNNN-kebab-case`, номер сквозной (`0001-user-registration`).
Шаблоны копируются в новую папку, `_templates` не редактируются под фичу.

## Статусы

В шапке `spec.md`:

| Статус | Значение |
|--------|----------|
| `draft` | обсуждается, код писать нельзя |
| `approved` | согласован, можно планировать и делать |
| `implemented` | реализовано и проверено (в т.ч. baseline-спеки) |
| `superseded` | заменён другой спекой (указать ссылку) |

## Definition of Done

Задача считается сделанной, когда:

- [ ] все пункты `tasks.md` закрыты;
- [ ] `composer cs-check` проходит;
- [ ] `composer phpstan` проходит;
- [ ] добавлены/обновлены тесты и `php bin/phpunit` проходит;
- [ ] обновлены `spec.md` (статус) и `tasks.md`;
- [ ] при изменении поведения обновлён `README.md` / `CLAUDE.md`.

Команды выполняются из `api/` (в Docker — через
`docker compose -f ../docker/docker-compose.yml exec php ...`, см.
[`api/README.md`](../api/README.md)).

Для baseline-спек пункт про `tasks.md` не применяется: критерий —
соответствие коду, тестам и разделам «Известные расхождения».

## Команды opencode

Если используешь opencode, доступны:

- `/spec <описание>` — создать/обновить `spec.md`;
- `/plan <NNNN-slug>` — построить `plan.md` по утверждённой спеке;
- `/tasks <NNNN-slug>` — разбить план на `tasks.md`.

Команды только готовят документы; код пишется отдельно после `approved`.
