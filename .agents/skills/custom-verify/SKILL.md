---
name: custom-verify
description: >-
  Шаг 5 потока change: test-runner, openspec-verify-change, reviewer, critic;
  петли 6.1/6.2, verification.md; затем финальное ревью человека и archive.
  Диаграмма — openspec/README.md. Лимит трёх итераций п.6.
---

# custom-verify

Шаги **5–8** в [`openspec/README.md`](../../openspec/README.md). Ты **оркестратор**:
код, `tasks.md`, `verification.md`, вызов субагентов и скиллов.

## Перед стартом

1. `BASE=$(git rev-parse HEAD)`, запомни ветку.
2. `git status --porcelain` — чужой дифф не смешивать молча.
3. Счётчик **итераций п.6**; на **четвёртой** — **СТОП**, эскалация, без archive.

Субагенты: правила запуска — `openspec/README.md` («Субагенты»). Пока субагент
работает, в затронутых файлах не правь.

## 5. custom-verify (по порядку)

### 5.1 test-runner (субагент)

`.agents/roles/test-runner.md`, затронутые области, `BASE`. Красный прогон →
**6.1**, снова **5.1**. Не «прошли», если не запускалось.

### 5.2 openspec-verify-change (скилл)

`@openspec-verify-change <id>`. CRITICAL по коду → **6.1** → **5.1**; по плану →
**6.2** → `@custom-analyse` → ветка → apply → снова **5** целиком.

### 5.3 reviewer (субагент)

`.agents/roles/reviewer.md`, `BASE`. `major` → **6.1** → **5.1**. Не дублируй verify.

### 5.4 critic (субагент, ≤ 2 раунда)

`.agents/roles/critic.md`: только `**Задача**`, AC, change, `BASE` (и трекер при
`**Трекер**`). Без оправданий исполнителя.

- `PASS` только при зелёном **5.1** на текущем дереве.
- `NEEDS_WORK` по коду → **6.1**; по плану → **6.2**.
- После второго `NEEDS_WORK` — **СТОП**, решение у человека.

## 6. Корректировки

**6.1** — правка кода/`tasks.md`; +1 итерация; с **5.1** (правка обнуляет прогон).

**6.2** — +1 итерация; `@custom-analyse` → ветка → apply → **5** целиком.

## 7–8. Завершение

1. Без blocker после **5.1–5.4** — `verification.md` по шаблону
   `openspec/schemas/cln-flow/templates/verification.md` (режим ролей, прогоны с хешем).
2. **СТОП** — человек: беглый осмотр diff, AC, хвосты.
3. DoD из корневого `README.md` → **«архивировать?»** → `@openspec-archive-change`.
4. Трекер — только при `**Трекер**` и явном «да» (skill `openproject`).

Коммиты — только человек (`AGENTS.md`).