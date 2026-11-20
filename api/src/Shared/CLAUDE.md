# Shared: кросс-слойная техника

Слой держит то, что нужно нескольким слоям и не знает ни о домене, ни о
wire-контрактах. Карта слоёв — [`api/CLAUDE.md`](../../CLAUDE.md), разбор
wire-значений и правила `Utils` — [`Application/CLAUDE.md`](../Application/CLAUDE.md#utils),
тесты — [`tests/CLAUDE.md`](../../tests/CLAUDE.md).

## Что живёт в слое

```
Shared/Utils/
├── UidUtils.php        # UUID v7: генерация, строковый формат, nil-идентификатор
├── DateUtils.php       # атомарный формат даты (wire и БД)
├── CacheKeyUtils.php   # стабильный отпечаток набора id — курсор ключа кеша/лока
└── ContainerUtils.php  # типизированный доступ к контейнеру
```

Класс: `final`, только `public static`, без состояния и конструктора, суффикс
`Utils`. Никаких текстов ошибок для клиента: `400`-контракт формирует
`Application/Utils/Wire`.

## Порог: почему класс общий

| Класс | Потребители | Основание |
|-------|-------------|-----------|
| `UidUtils` | Application (Model, Service) + Infrastructure (Entity, Mapper, Fetcher) | ≥ 2 слоя |
| `DateUtils` | Application/Mapper + Infrastructure/Persistence | ≥ 2 слоя |
| `CacheKeyUtils` | Infrastructure/Lock | общий **формат** ключа: завтра его требует `Application/Cache` (роль пуста) |
| `ContainerUtils` | `tests/Support/TestBed` | тесты — потребитель наравне со слоями |

Правило: класс остаётся в Shared, если его зовут ≥ 2 src-слоя **или** он задаёт
формат, который слои делят (формат id, формат даты, отпечаток ключа). Единственный
потребитель в одном слое → переезд в этот слой: `Application/Utils` или
соответствующий технический слой `Infrastructure`.

## Границы

Shared не импортирует `App\Application` и `App\Infrastructure` — ни реализаций,
ни контрактов. Направление строго вниз: слои знают про Shared, Shared не знает
ни про кто. Разрешены только внешние типы-значения без I/O (`Symfony\Uid\Uuid`,
`DateTimeInterface`).

Отсюда — что уехало из слоя и куда:

| Было в Shared | Стало | Почему |
|---------------|-------|--------|
| `Utils/Asserts/*`, `Utils/Filter/*` | `Application/Utils/Wire/*` | это wire-контракт Application (у `InputAssertUtils` 151 из 153 вызовов — в `Application/Model::fromArray`), а не общая техника |
| `Utils/ArrayUtils`, `BatchUtils`, `EnumUtils` | `Application/Utils/Collection/*` | потребители только в Application; `ArrayUtils` к тому же импортировал `IdentifiableInterface` |
| `Utils/Http/ErrorResponseUtils` | `BaseController::errorResponse` (Presentation) + `ErrorResponseModel::fromThrowable` (Application) | HTTP-обёртка нужна только контроллерам, а формат конверта — контракт Application; в Shared импорта `JsonResponse` не осталось |

## Чеклист

1. `final`, `public static`, без состояния и конструктора, суффикс `Utils`.
2. Основание быть в Shared: ≥ 2 слоя-потребителя или общий формат; иначе — переезд в слой.
3. Ни одного импорта `App\Application` и `App\Infrastructure`.
4. Имя и сигнатура не называют домен: `fingerprintIds`, а не `taskGroupKey`.
5. Новые wire-ассерты и разбор `filter`/`sort` — в `Application/Utils/Wire`, не сюда.
