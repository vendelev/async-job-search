# Справочник PHP 8.4 и PHP 8.5

> Актуально на февраль 2026 года.

| Версия | Дата релиза | Статус поддержки |
| --- | --- | --- |
| PHP 8.2 | 8 декабря 2022 | Active до 31 декабря 2026 |
| PHP 8.3 | 23 ноября 2023 | Active до 31 декабря 2027 |
| PHP 8.4 | 21 ноября 2024 | Active до 31 декабря 2028 |
| PHP 8.5 | 20 ноября 2025 | Active до 31 декабря 2029 |

## PHP 8.4

### Property Hooks

Свойства могут содержать `get` и `set` логику непосредственно в объявлении.

```php
class User
{
    public string $firstName;
    public string $lastName;

    public string $fullName {
        get => $this->firstName . ' ' . $this->lastName;
    }
}
```

### Asymmetric Visibility

У свойства могут быть разные уровни доступа для чтения и записи.

```php
class Post
{
    public private(set) int $views = 0;
}
```

### Вызов после `new`

В PHP 8.4 не нужны лишние скобки при вызове метода, свойства или константы у нового объекта:

```php
$name = new ReflectionClass($object)->getShortName();
$property = new MyClass()->property;
$constant = new MyClass()::CONSTANT;
```

### Новые функции массивов

Используйте `array_find`, `array_find_key`, `array_any` и `array_all` для поиска и проверки элементов массива.

### Прочие возможности

- `Dom\HTMLDocument` предоставляет HTML5-совместимый DOM API.
- Lazy Objects создаются через Reflection API и инициализируются при первом обращении к свойству.
- Атрибут `#[Deprecated]` помечает API как устаревший.
- `BcMath\Number` предоставляет объектно-ориентированный API BCMath с перегрузкой операторов.
- `mb_trim`, `mb_ltrim` и `mb_rtrim` корректно работают с Unicode.
- JIT включается через `opcache.jit`; для отключения используйте `opcache.jit=off`.

## PHP 8.5

### Pipe Operator

Оператор `|>` строит последовательность преобразований слева направо.

```php
$slug = '  PHP 8.5 Released  '
    |> trim(...)
    |> fn ($value) => str_replace(' ', '-', $value)
    |> strtolower(...);
```

### URI Extension

`Uri\Rfc3986\Uri` реализует RFC 3986, а `Uri\WhatWg\Url` -- WHATWG URL API. Объекты immutable; для изменения используются методы `with*`.

### Clone With

`clone($object, ['property' => $value])` создаёт копию объекта с заменёнными свойствами, включая `readonly`.

### Массивы и атрибуты

- `array_first()` и `array_last()` возвращают первый и последний элемент либо `null` для пустого массива.
- `#[NoDiscard]` предупреждает, если результат вызова не используется.
- Статические замыкания и first-class callables допустимы в константных выражениях.
- `Closure::getCurrent()` упрощает рекурсивные анонимные функции.
- Асимметричная видимость доступна для static-свойств.
- Fatal errors включают stack trace.

### Важные deprecations

- Backtick-оператор как алиас `shell_exec()` устарел.
- Используйте `(bool)`, `(int)` и `(float)` вместо `(boolean)`, `(integer)` и `(double)`.
- Используйте `:` вместо `;` после `case`.
- Не используйте `null` как ключ массива; используйте пустую строку.
- Вместо `__sleep()` и `__wakeup()` используйте `__serialize()` и `__unserialize()`.
