---
description: Разбить SDD-план на упорядоченный tasks.md
agent: build
---

Разбей план на задачи для спеки: $ARGUMENTS

Работай по правилам `docs/README.md`.

1. Найди папку спеки в `docs/specs/` (аргумент — номер или slug). Если аргумент
   пуст — попроси его.
2. Прочитай `docs/specs/<папка>/spec.md` и `plan.md`, шаблон
   `docs/specs/_templates/tasks.md`.
3. Создай `docs/specs/<папка>/tasks.md` по шаблону: фазы Domain → Application →
   Infrastructure → Presentation → Tests → Финализация. Каждая задача — файл(ы)
   и проверка.
4. Убедись, что тесты покрывают все сценарии из `spec.md`, а команды проверок
   совпадают с `docs/README.md` (Definition of Done).
5. **Не пиши код.** Отметь статус `in progress`.

В конце покажи путь к `tasks.md` и предложи начать реализацию с первой фазы.
