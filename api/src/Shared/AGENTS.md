# Shared: кросс-слойная техника

Правила слоя, а не карта классов. Смежные документы: карта слоёв и суффиксы —
[`api/AGENTS.md`](../../AGENTS.md), разбор wire-значений и `Application/Utils` —
[`Application/AGENTS.md`](../Application/AGENTS.md#utils), покрытие тестами —
[`tests/AGENTS.md`](../../tests/AGENTS.md).

## Форма слоя и зависимости

`Shared/Utils/` — утилиты, нужные нескольким слоям и не знающие ни о домене, ни о
wire-контрактах. Класс: `final`, только `public static`, без состояния и
конструктора, суффикс `Utils`.

Слой не импортирует `App\Application` и `App\Infrastructure` — ни реализаций, ни
контрактов: направление строго вниз, слои знают про Shared, Shared не знает ни про
кого. Из внешнего мира разрешены только vendor-типы-значения без I/O и стабильные
PSR-контракты, приходящие аргументом.

## Чеклист

1. Класс в `Shared/Utils/` — `final`, `public static`, без состояния, суффикс `Utils`.
2. Нет импортов `App\Application` и `App\Infrastructure`; только vendor-типы-значения и PSR-контракты аргументом.
3. Не называет домен и wire-форматы — иначе переезд в `Application/Utils` или `Infrastructure`.
