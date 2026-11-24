# cln-arch-sym-simple

Локальный монорепозиторий. В корне три каталога:

| Каталог | Назначение |
|---------|-----------|
| [`api/`](api/README.md) | Основной Symfony API (PostgreSQL, JWT, бизнес-логика) |
| [`docker/`](docker/README.md) | Docker Compose для локальной разработки (nginx, PHP, база, воркеры) |
| [`openspec/`](openspec/README.md) | Спеки, changes и процесс Spec-Driven Development |

## Быстрый старт

С нуля, одной командой (Windows, PowerShell 5.1+):

```powershell
.\build.ps1
# или двойным кликом по build.cmd
```

Скрипт проверяет git и Docker, включает git-хуки, ставит PHP-зависимости на
хосте (если есть `php` + `composer`) и поднимает стек:
`docker compose up -d --wait`. Образ php собирается автоматически, если его
нет, а entrypoint контейнера сам выполняет `composer install` и миграции.

Флаги: `-WithWorkers` (поднять messenger-worker), `-Build` (пересобрать
образы), `-SkipComposer` (не ставить зависимости на хосте),
`-TimeoutSeconds <сек>` (ожидание готовности, по умолчанию 600).

После запуска API — <http://localhost:8080> (проверка: `GET /api/assets`
отвечает 401 без токена), RabbitMQ UI — <http://localhost:15673>.

## Разработка по SDD (OpenSpec)

Проект использует [OpenSpec](https://openspec.dev) — Spec-Driven Development:
сначала согласуем план (proposal → specs `deltas` → design → tasks), затем
пишем код, после — архивируем change с обновлением main-спек.

```
openspec/changes/<change-id>/proposal.md + specs/ + design.md + tasks.md → код → archive
```

Итоговое поведение системы — в `openspec/specs/` (источник истины).
Карта доменов, связи схемы данных и глоссарий — в
[`openspec/README.md`](openspec/README.md).

Спеки пишутся на русском; структурные заголовки (`## Purpose`,
`### Requirement:`, `#### Scenario:`, GIVEN/WHEN/THEN) и ключевые слова
SHALL/MUST — на английском.

Архитектурные правила (конституция) — в [`api/AGENTS.md`](api/AGENTS.md) и
вложенных файлах (`api/src/Application/AGENTS.md`,
`api/src/Infrastructure/AGENTS.md`, `api/tests/AGENTS.md`). Спеки не
дублируют правила, а ссылаются на разделы. Расхождения фактического
поведения с задокументированным контрактом фиксируются в `## Notes` спек.

### Структура

```
openspec/
├── config.yaml      # контекст проекта и правила для артефактов
├── README.md        # индекс: карта доменов, схема данных, глоссарий
├── schemas/         # workflow-схема проекта (fork`spec-driven` → cln-flow)
│   └── cln-flow/    #   schema.yaml + templates/*.md — правим здесь
├── specs/           # источник истины: как система работает СЕЙЧАС
│   ├── auth/ users/ tasks/ task-groups/ assets/ files/
│   ├── notifications/ wire/ errors/ query/ async/   (spec.md в каждой)
└── changes/         # изменения в работе + archive/ (история)
```

Change называется `<slug>` в kebab-case (`avatar-replace`): CLI отвергает заглавные
буквы, а номер задачи в имя не переносится даже когда он упомянут в запросе.
Первая строка `proposal.md` под заголовком — `**Задача**: <текст запроса>`, из неё
берутся критерии приёмки. Трекер опционален: если работа ведётся по задаче
WorkHub, она добавляется второй строкой `**Трекер**: <id> — <ссылка>`, и именно
наличие этой строки (а не имя change) определяет, ходят ли шаги flow в трекер.

### Когда нужен change

Change (папка в `openspec/changes/`) обязателен для:

- новой фичи или use case;
- изменения публичного контракта (HTTP/CLI, wire-Model);
- миграции БД или изменения схемы;
- async / очередей / scheduler / кэша;
- изменений в безопасности и доступах.

Для мелких правок (опечатка, локальный рефакторинг без изменения поведения)
change не нужен.

Задача в трекере для change **не обязательна**: требование живёт в тексте
`**Задача**` внутри `proposal.md`, а не в номере. Если работы нет в трекере —
propose запускают просто текстом, `**Трекер**` не заводится, и шаги flow,
которые ходят в API трекера, молча не выполняются. Обратное верно тоже: ссылка
на work package в тексте — единственный способ подключить трекер, номер в имени
change ничего не значит.

**До выхода в продакшен** допускается вносить изменения в код и сразу
обновлять `openspec/specs/` без change — внешних потребителей контракта
нет, риск несовместимости отсутствует. С появлением продакшена это правило
перестаёт действовать: изменения публичного контракта снова идут через
propose → apply → archive.

### Поток

```
propose (proposal + specs deltas + design + tasks) → анализ и утверждение
        → apply → тесты → verify → reviewer → critic ─┐
              ↑                                        │ правки — возврат на тесты
              └────────────────────────────────────────┘
        → verification.md → archive (deltas → specs/, история в archive/)
```

1. **propose** — человек запускает `@openspec-propose <текст запроса>`; агент
   создаёт change `<slug>`: `proposal.md` (зачем, scope, строки `**Задача**` и,
   при работе по задаче трекера, `**Трекер**`), `specs/`
   (дельты ADDED/MODIFIED/REMOVED), `design.md` (когда нужен), `tasks.md`.
   Тикет не обязателен и не запрашивается: нужен трекер — его упоминают в тексте.
2. **анализ** — субагент `analyst` сверяет артефакты с текстом запроса (и с
   work package, если на него указывают), main-спеками и `AGENTS.md`; расхождения
   правятся через `@openspec-update-change`. Дальше — только после утверждения.
3. **apply** — `@openspec-apply-change`: реализация по `tasks.md`, галочки по
   ходу, проверки (`cs-check`, `phpstan`, `phpunit`) на каждой группе задач.
4. **тесты** — субагент `test-runner`: итоги прогонов и разбор падений без логов,
   с номером прогона и хешем дерева.
5. **verify** — `@openspec-verify-change`: реализация ↔ план, три измерения
   (Completeness / Correctness / Coherence), уровни `CRITICAL`/`WARNING`/`SUGGESTION`.
   Отчёт advisory: решаешь ты, но незакрытый `CRITICAL` — блок.
6. **reviewer** — субагент `reviewer`: конвенции `AGENTS.md`, которых не видит
   ни `verify`, ни линтер (слои, суффиксы, границы зависимостей, DI, дельты спек).
7. **critic** — независимая проверка по `BASE` (без рассуждений исполнителя),
   не больше двух раундов; затем заполняется `verification.md`.
8. **archive** — дельты вливаются в `openspec/specs/`, change уезжает в
   `changes/archive/YYYY-MM-DD-<id>/`. Статус задачи в трекере обновляется только
   по явному согласию и только если `**Трекер**` заполнен; без него шаг просто
   не выполняется и хвостом не считается.

Шаги 2–8 ведает workflow **`task-flow`** (см. ниже): он вызывает openspec-скиллы
и добавляет то, чего в OpenSpec нет — прогоны, ревью конвенций, критика и петлю
между ними. Планирование остаётся ручным — propose запускает человек.

Коммиты делает **только человек**. Агент готовит их (staging, разбивку, тексты), но
`git commit` не выполняет — ни в основной сессии, ни в субагентах. Текстом это
зафиксировано в `AGENTS.md` («Коммиты»), механически — в `opencode.jsonc`
(`permissions: deny` на `git commit`, `git merge`, `git rebase`, `git cherry-pick`,
`git revert`, `git am`).

Инвариант цикла: **любая правка после прогона обнуляет прогон**. Зелёным и
действующим считается только прогон на текущем дереве; вердикт, не подкреплённый
таким прогоном, — не готовность. Поэтому после исправлений из шагов 5–7 шаг 4
повторяется, а `verification.md` хранит номер прогона и хеш.

Артефакты правятся в любой момент — фазовых ворот нет, кроме двух явных:
утверждение плана перед реализацией и `verification.md` перед архивацией.

### Установка и обновление OpenSpec

С 2.x скиллы лежат **в одном месте** — `.agents/skills/` (vendor-neutral корень),
плюс маркер владения `.agents/skills/.openspec-target`; инструменты, которые читают
только свою папку, получают по ссылке на каждый скилл (`.claude/skills/openspec-*`
и т.п.). OpenCode читает `.agents/skills/` сам, поэтому для него ссылок нет.

Версионируются в git: `.agents/skills/` (и `openspec-*`, и наш `task-flow`),
`.agents/roles/` (тексты ролей проверки) и адаптеры `.opencode/agents/`. Не
отслеживаются служебные `.opencode/node_modules/`, `package.json`, lock-файлы
(см. `.gitignore`).

Шаг 1 — установить CLI 2.x (standalone, Node не нужен):

```powershell
irm https://openspec.dev/cli/install.ps1 | iex
openspec version          # должно быть 2.x и (standalone)
```

CLI кладётся в `%LOCALAPPDATA%\Programs\openspec`; если параллельно стоит
npm-копия (`npm ls -g @fission-ai/openspec`), она может идти раньше по PATH —
тогда `npm uninstall -g @fission-ai/openspec` и проверь `(Get-Command openspec).Source`.

Шаг 2 — сгенерировать файлы скиллов в проекте:

```bash
openspec update
```

После этого в opencode появляются скиллы `openspec-*` — модель видит их
нативно и подхватывает сама. Вызвать конкретный можно явно: `@openspec-propose`
в промпте или фразой «используй openspec-propose».

**Версия CLI — одна на команду.** Пока у разработчиков разные версии, каждый
`openspec update` возвращает тексты скиллов из *своей* версии, и в репозитории
ходют туда-обратно чужие diffs. Обновляем CLI, потом `openspec update` отдельным
коммитом (diff от обновления скиллов не должен смешиваться с изменениями проекта).
Если скиллы в проекте дополнены ссылками для другого инструмента, на Windows для
них нужны `git config --global core.symlinks true` и Developer Mode — иначе при
checkout ссылка становится текстовым файликом, а `openspec update` заменяет её
копией.

**Не правьте `.agents/skills/openspec-*` руками** — `update` заменяет папки
built-in-скиллов целиком, включая файлы, добавленные внутрь них. Штатные слои
кастомизации, которые `update` не трогает:

| Хочешь изменить | Куда |
|-----------------|------|
| как пишутся артефакты, их шаблоны, набор артефактов, граф зависимостей | `openspec/schemas/cln-flow/` (`schema.yaml` + `templates/*.md`; форк сделан, он резолвится выше встроенной `spec-driven`) |
| конвенции проекта, правила к артефактам, подсказки apply/archive | `openspec/config.yaml` (`context`, `rules`, `operations.*.guidance`) |
| порядок работы и гейты проверки | `.agents/skills/task-flow/` (своё имя → `update` его не касается) |
| что именно проверяет каждая роль | `.agents/roles/<роль>.md` — канон, общий для всех инструментов |
| как роль запускается и с какими правами (OpenCode) | `.opencode/agents/<роль>.md` — тонкий адаптер: `mode: subagent`, `permissions`, ссылка на канон |
| набор установленных воркфлоу | `openspec config profile` (у нас ядро + `verify`) |

Схеме — `openspec schema validate cln-flow`, `openspec schema which --all`,
`openspec templates --schema cln-flow --json`. Команды `schema` в CLI всё ещё
помечены experimental; форк — копия, изменения из новых версий пакета в него не
подтягиваются, их мерджируют руками.

Альтернатива без standalone-бинаря: `npm install -g @fission-ai/openspec` — но 2.x
требует Node ≥ 22.20; если Node старее, npm всё равно поставит пакет (предупреждение
`EBADENGINE`) и запустит его, однако рекомендованный путь — standalone, он носит
рантайм с собой.

### Скиллы workflow

Лежат в `.agents/skills/`; вызов — `@<имя>` в промпте или запросом своими словами.

| Скилл | Что делает | Чей |
|-------|-----------|-----|
| `openspec-explore` | обдумать идею с чтением кода | генерируется |
| `openspec-propose` | создать change (артефакты планирования) | генерируется |
| `openspec-apply-change` | реализовать задачи из `tasks.md` | генерируется |
| `openspec-verify-change` | сверить реализацию с артефактами change (Completeness / Correctness / Coherence) | генерируется |
| `openspec-update-change` | скорректировать артефакты плана | генерируется |
| `openspec-sync-specs` | влить дельты в main-спеки (без архива) | генерируется |
| `openspec-archive-change` | завершить и заархивировать change | генерируется |
| [`task-flow`](.agents/skills/task-flow/SKILL.md) | полный цикл задачи: анализ → утверждение → apply → тесты → verify → reviewer → critic → verification → archive | проект |

Профиль воркфлоу — ядро + `verify` (`openspec config profile`, затем
`openspec update`). Остальные опции (`new`, `continue`, `ff`, `bulk-archive`,
`onboard`) при необходимости добавляются туда же.

### Агенты проверки

Текст каждой роли — канон в `.agents/roles/`, он один для всех инструментов. В
OpenCode к нему добавляется тонкий адаптер `.opencode/agents/<роль>.md`
(`mode: subagent`, права, ссылка на канон): именно он делает роль отдельной
сессией с чистым контекстом — то, ради чего `reviewer` и `critic` вообще вынесены
из основной сессии. Инструмент без субагентов читает тот же канон и выполняет его
отдельным прохождением; режим фиксируется в `verification.md` (`субагенты` /
`inline`), потому что inline-критик слабее изолированного и выдавать одно за
другое нельзя. `verify` — не роль, а скилл: он дешёвый, детерминированный по форме
отчёта и работает в основном контексте.

| Роль | Канон | Что проверяет | Вход |
|------|-------|---------------|------|
| `analyst` | `.agents/roles/analyst.md` | сверяет артефакты change с текстом запроса (и с work package, если на него указывают), main-спеками и `AGENTS.md`; ищет потерянные AC и расхождения | текст запроса, имя change |
| `test-runner` | `.agents/roles/test-runner.md` | `cs-check`, `phpstan`, `phpunit`, `ArchitectureTest` — итоги, разбор падений, номер прогона и хеш дерева | затронутые области, `BASE` |
| `reviewer` | `.agents/roles/reviewer.md` | конвенции проекта: слои, суффиксы ролей, границы зависимостей, DI-регистрация, формат ошибок, тесты | `BASE` (диф от него) |
| `critic` | `.agents/roles/critic.md` | состязательная проверка: трассировка AC, edge cases, конкурентность, откат посередине, миграции, качество и свежесть тестов | задача + AC, имя change, `BASE` |

Новый инструмент подключается добавлением своего адаптера (и ссылкой на
`.agents/skills/task-flow`, если он не читает общий корень скиллов) — тексты
ролей при этом не дублируются.

### Definition of Done

Change считается сделанным, когда:

- [ ] все пункты `tasks.md` отмечены;
- [ ] `composer cs-check` проходит;
- [ ] `composer phpstan` проходит;
- [ ] добавлены/обновлены тесты и `php bin/phpunit` проходит;
- [ ] дельты спек отражают итоговое поведение (и влиты в `specs/` при archive);
- [ ] при изменении поведения обновлён `README.md` / `AGENTS.md`;
- [ ] `openspec-verify-change` не даёт незакрытых `CRITICAL`;
- [ ] `reviewer` и `test-runner` отработаны, `critic` дал `PASS` (или решение
      после второго `NEEDS_WORK` принял пользователь);
- [ ] зелёный прогон `test-runner` относится к текущему дереву (правки после
      прогона — значит прогон был раньше, а не был);
- [ ] заполнен `verification.md` — трассировка AC, замечания, фактические прогоны,
      раунды критика, хвосты.

Команды выполняются из `api/` (в Docker — через
`docker compose -f ../docker/docker-compose.yml exec php ...`, см.
[`api/README.md`](api/README.md)).

### Git-хуки

Общие хуки лежат в версионируемом `.githooks/` и подключаются через
`core.hooksPath` — копировать файлы в `.git/hooks` не нужно.

```bash
# включить (один раз)
git config core.hooksPath .githooks
# или с хоста: cd api && composer hooks:install

# выключить
git config --unset core.hooksPath
```

| Хук | Что проверяет | Когда срабатывает |
|-----|---------------|-------------------|
| `pre-commit` | `php-cs-fixer --dry-run` только по staged-файлам `api/**/*.php` | коммит с изменениями PHP в `api/` |
| `pre-push` | полный `cs-check` + `phpstan` | любой push |

Проверки выполняются локальным PHP (`api/vendor`), если его нет — через
`docker compose exec php`; если недоступно ни то ни другое, хук печатает
предупреждение и пропускает проверку. Автофикса нет: `pre-commit` только
показывает diff и блокирует коммит, исправление — `composer cs-fix`.
Аварийный обход — `git commit --no-verify` / `git push --no-verify`.

Хуки — локальная страховка, а не гарантия: их можно обойти, поэтому
строгая проверка должна жить в CI.

---

## Как устроен проект (от пользователя к данным)

```mermaid
flowchart TB
  Human([Person / client])

  subgraph Edge["Вход"]
    NG8080["nginx :8080 → API"]
    CLI["CLI / Console"]
  end

  Human --> NG8080
  Human --> CLI

  subgraph API["api — Symfony Clean Architecture"]
    direction TB
    Pres["Presentation<br/>Controller / Console"]
    App["Application<br/>Model → Service::execute<br/>Action · Query · Lifecycle<br/>Policy · ViewModelMapper · Dispatcher · Cache"]
    Port["Port<br/>Repository · Storage · Mailer · Token · Cache"]
    Infra["Infrastructure<br/>Doctrine Repository · Mapper · Entity<br/>MessageHandler · Adapter · Provider"]

    Pres --> App
    App --> Port
    Port --> Infra
    App -.->|Application Event| Infra
    Infra -.->|MessageHandler → Service| App
  end

  NG8080 --> Pres
  CLI --> Pres

  subgraph InfraStore["Хранилища и очереди API"]
    PostgreSQL[(PostgreSQL)]
    Redis[(Redis<br/>cache / locks)]
    FS[("files<br/>local storage")]
    RMQ_async["RabbitMQ<br/>messages queue"]
  end

  Infra --> PostgreSQL
  Infra --> Redis
  Infra --> FS
  Infra -->|Application Event| RMQ_async

  subgraph WorkersAPI["Воркеры api"]
    MW["messenger-worker<br/>async + scheduler"]
    SideFx["*MessageHandler<br/>notifications, recalc, …"]
  end

  RMQ_async --> MW
  MW --> SideFx
  SideFx -.-> App
```

### Коротко

1. **Человек** обращается к `nginx` (`:8080` — API) или запускает Console.
2. **Presentation** собирает запрос `*Model` (`fromArray`) и вызывает `*Service::execute`. Console разбирает CLI и вызывает Lifecycle-`*Service`.
3. **Application**: Policy → persistence `*Model` → Port. Action / Query возвращают только ответ `*ViewModel` | список | страницу | void. После записи Lifecycle обновляет кэш и публикует Application Event (`*Dispatcher`).
4. **Infrastructure** реализует Port: PostgreSQL (Doctrine), Redis, файлы. Application Event уходит в очередь RabbitMQ `messages`.
5. **messenger-worker** читает очередь: `*MessageHandler` вызывает Application-`*Service` (notifications, recalc). Тот же воркер исполняет scheduler.

Соглашения по слоям: [`api/src/Application/AGENTS.md`](api/src/Application/AGENTS.md), [`api/src/Infrastructure/AGENTS.md`](api/src/Infrastructure/AGENTS.md).
