# Shared: слой утилит

Правила слоя, а не список классов. Смежные документы: карта слоёв и суффиксы —
[`api/CLAUDE.md`](../../CLAUDE.md), роли Application —
[`Application/CLAUDE.md`](../Application/CLAUDE.md), wire-ошибки —
[`Presentation/CLAUDE.md`](../Presentation/CLAUDE.md), тесты —
[`tests/CLAUDE.md`](../../tests/CLAUDE.md).

## Форма слоя

```
Shared/
└── Utils/
    ├── (корень)   # вычисления и форматы: ArrayUtils, BatchUtils, CacheKeyUtils, DateUtils, EnumUtils, UidUtils, ContainerUtils
    ├── Asserts/   # разбор wire-значений: InputAssertUtils, NumberAssertUtils, StringAssertUtils
    ├── Filter/    # разбор wire-критериев поиска: FilterCriteriaUtils, FilterFieldUtils, SortFieldUtils
    └── Http/      # форма error-ответа: ErrorResponseUtils
```

- Группа выбирается по тому, **что разбирается или считается**, а не по тому,
  кто зовёт: `InputAssertUtils` нужен и `Application/Model`, и
  `Presentation/Console` — он в `Asserts/`, а не около одного из них.
- `Asserts/`, `Filter/`, `Http/` — про wire-границу: невалидный вход бросает
  `InvalidArgumentException` и становится `400`. Корневые утилиты про HTTP и
  JSON не знают.
- ❌ `Shared/{Entity}/` — сущность на первом уровне: хелпер, знающий про `Task`,
  общим не бывает.
- ❌ новая подпапка под одного потребителя и ❌ метод, который вызывает один
  класс: такое живёт приватным методом в вызывающем. Общий код для одного —
  скрытая связь слоёв.

Класс: `final`, только `public static` методы, без состояния и конструктора,
суффикс `Utils`. Состояние, I/O и решение о доступе невозможны по определению —
это `Service`, `Policy` или `*Cache`.

## Когда Utils, а когда нет

| Код... | Место |
|--------|-------|
| приводит wire-значение к типу и бросает на невалидном | `Shared/Utils/Asserts/*` |
| считает или форматирует без доменных правил (батчи, fingerprint, atom-date, uuid) | `Shared/Utils/*` |
| разбирает `filter` / `sort`, не зная whitelist полей | `Shared/Utils/Filter/*` |
| хранит инвариант **поля** (email, телефон, сумма) | `Application/ValueObject/*Vo` |
| требует I/O: доступ, уникальность, существование | `Application/Policy/*` |
| это шаг use case с репозиторием, шиной или файлами | `Application/Service/**` |
| обёртка над библиотекой или внешний ресурс | `Infrastructure/**` (`*Adapter`, `*Provider`) |

Порог входа в Shared: код одинаково нужен **≥ 2 слоям** и не знает про домен.
Ниже порога — остаётся в слое-потребителе. Тесты — потребитель наравне со
слоями: `ContainerUtils` нужен только `tests/Support/TestBed` и это единственный
хелпер слоя без src-вызова.

Исключений из «не знает про домен» в слое нет; то, что раньше выглядело
исключением, — misplaced:

- деньги — `Application/ValueObject/Common/MoneyVo`: в нём и `PRECISION` /
  `SCALE` / `MAX_AMOUNT` (из них же берёт форму колонка
  `#[ORM\Column(precision: MoneyVo::PRECISION, ...)]`, по образцу
  `EmailVo::MAX_LENGTH`), и нормализация, и `toString` / `fromString` для
  decimal-колонки.
- `Filter/*` — не знание о домене, а форма wire-контракта: `filter`-bag и
  sort-строка одинаковы для всех ресурсов, whitelist даёт вызывающий —
  `FilterFieldUtils::isAllowed($name, {Entity}FilterFieldEnum::class)` получает
  enum из Application **аргументом**. ❌ импортировать `Application/Enum/**`
  внутри `Filter/*` и ❌ `match` по именам полей: тогда Shared начнёт знать
  домены.

## Границы зависимостей

| Можно | Что |
|------|-----|
| PHP и чистые типы библиотек | `BackedEnum`, `DateTimeInterface`, `Generator`, `Symfony\Uid\Uuid`, `HttpFoundation\JsonResponse` |
| **контракты** Application | `Model\Common\IdentifiableInterface` (`ArrayUtils::ids`), `Exception\CodedExceptionInterface`, `Exception\ErrorCodeEnum`, `Model\Http\ErrorResponseModel` (`ErrorResponseUtils`) |

Нельзя: `Application/Service/**`, `Policy/**`, `Mapper/**`, `Cache/**`, что-либо
из `Infrastructure/**`, Doctrine, контейнер как сервис (единственный легальный
вход — `ContainerInterface` **аргументом**, как в `ContainerUtils`) и глобальное
состояние: mutable-статика, `$_ENV`, `date()` без входных данных.

Правило читается по импорту: импортируется **тип-данные** (interface, enum,
Model) — это контракт; импортируется **поведение** (Service, Mapper, адаптер) —
это логика чужого уровня, и ей место там же. Кольцо `Application ↔ Shared`
замкнуто только контрактами.

Расширение слоя — новый метод, а не правка существующего: у `InputAssertUtils`
150+ вызовов в `Application/Model`, у `ErrorResponseUtils` — 76 в контроллерах,
`UidUtils` и `DateUtils` держат Persistence, а тексты `InvalidArgumentException`
попадают в `400`.

## Чеклист

1. `final`, `public static`, без состояния и конструктора, суффикс `Utils`.
2. Группа — по объекту разбора (`Asserts/`, `Filter/`, `Http/`), не по потребителю; сущности на первом уровне нет.
3. В Shared попадает то, что нужно ≥ 2 слоям; для одного — приватный метод в слое.
4. Инвариант поля — VO; предусловие с I/O — Policy; шаг use case — Service; обёртка библиотеки — Infrastructure.
5. Из Application — только контракты (interface, enum, Model); реализаций и `Infrastructure` нет.
6. Денег в Shared нет — это `Application/ValueObject/Common/MoneyVo`; у `Filter/*` enum полей приходит аргументом.
7. Никакого глобального состояния и скрытого I/O.
8. Новый метод вместо правки сигнатуры: вызовов много, тексты ошибок — часть `400`.
