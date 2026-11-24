---
name: custom-analyse
description: >-
  Шаг 2 потока change: AI-анализ planning-артефактов и утверждение планa человеком.
  Субагент analyst → СТОП → openspec-update-change. Также при возврате 6.2
  (проблема в плане). Диаграмма — openspec/README.md. После шага — ветка и apply.
---

# custom-analyse

Шаг **2** в [`openspec/README.md`](../../openspec/README.md) («Поток работы над
change»). Ты **оркестратор**: analyst и update-change не пишут прод-код.

## Перед стартом

1. Убедись, что change существует (иначе человеку — шаг 1 `@openspec-propose`).
2. `openspec status --change "<id>" --json` и `proposal.md` (`**Задача**`, `**Трекер**`).
3. При возврате **6.2** передай analyst причину: findings verify/critic, решение
   пользователя.

## Порядок

1. Субагент **`analyst`** (`.agents/roles/analyst.md`) — текст запроса, имя change,
   трекер только если указан. Запуск — по правилам в `openspec/README.md`
   («Субагенты»).
2. Если трекер использовался, а `**Трекер**:` нет — добавь в `proposal.md`.
3. Покажи отчёт пользователю. **СТОП — жди утверждения.**
4. После правок — `@openspec-update-change` (только planning-артефакты). При
   расхождениях — analyst снова, пока план не согласован.

Вопросы, меняющие AC или контракт, закрываются **до** `@openspec-apply-change`.
Остальные — пометка в `tasks.md`.

## Дальше

После «план ок»: ветка `feat/<slug>` (согласие) → `@openspec-apply-change`.