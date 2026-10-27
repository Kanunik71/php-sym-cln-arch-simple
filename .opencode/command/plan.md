---
description: Построить технический plan.md по утверждённой SDD-спеке
agent: build
---

Построй технический план для спеки: $ARGUMENTS

Работай по правилам `docs/README.md`.

1. Найди папку спеки в `docs/specs/` (аргумент — номер или slug). Если аргумент
   пуст — попроси его.
2. Прочитай `docs/specs/<папка>/spec.md`, шаблон
   `docs/specs/_templates/plan.md`, `api/CLAUDE.md`, а также
   `api/src/Application/CLAUDE.md`, `api/src/Infrastructure/CLAUDE.md` и
   `api/tests/CLAUDE.md` — по необходимости.
3. Если статус спеки не `approved` — предупреди и остановись, план не строй.
4. Изучи существующий код в `api/src` для аналогичных use case, чтобы план
   соответствовал текущим паттернам.
5. Создай `docs/specs/<папка>/plan.md` по шаблону: слои, контракты
   (Command/Query, Model, Port, Enum, VO), карта файлов, миграции,
   async/события/кэш, безопасность, тесты, риски, ссылки на разделы
   `CLAUDE.md`.
6. **Не пиши код, не создавай `tasks.md`.**

В конце покажи путь к `plan.md` и ключевые развилки.
