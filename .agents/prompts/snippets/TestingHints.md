# Тестирование

**Особенности написания тестов**:

Тесты должны быть минимально достаточны.

**Когда писать тесты**:

- ✅ Компоненты Application и Domain с логикой или валидацией должны быть протестированы
- ❌ Тесты для конфигурации и простых DTO **НЕ НУЖНО** создавать (если они не содержат логики)
- ✅ Infrastructure слой (Adapter) должен быть протестирован (Integration тесты)
- ✅ Presentation слой с поведением, например CLI-команды, должен быть протестирован integration или E2E тестом

**Минимальное требование**:

- Тестируй значимое наблюдаемое поведение, ошибки и существенные ветви.
- Для методов с собственной валидацией добавляй тесты на invalid данные.
- Используй test double только на внешней границе или когда нужно проверить взаимодействие с зависимостью.

## Типы тестов

Структура файлов с тестами должна повторять структуру модуля.
Разделение по типу тестов только логическое.

- `backend/tests/Suite/{ModuleName}` содержит тесты модулей.
- `backend/tests/Architecture` содержит PHPat-проверки и не смешивается с тестами поведения.

### Unit тесты

Тестирование отдельных компонентов в изоляции (без зависимостей).

Их пишем для чистой логики без I/O:

- Service с чистыми расчётами,
- Validation,
- Entity,
- ValueObject,
- парсеров и мапперов.

Если в классе, например, с доступом к БД, есть самостоятельная бизнес-логика, выделяй её в отдельный класс и
тестируй изолированно.

- **Расположение**: `backend/tests/Suite/{ModuleName}/Application`, `backend/tests/Suite/{ModuleName}/Domain`
- **Охватывает**:
  - Бизнес-логика в Service
  - Валидация в Validation классах
  - Логика в Domain объектах и ValueObject

### Integration тесты

Тестирование отдельных компонентов при взаимодействии с БД.

Если класс напрямую зависит от Query, Command, репозитория или другого компонента с доступом к БД, не создавай
интерфейс только ради mock-объекта.
Проверяй такой класс Integration тестом с временной тестовой БД.
Интерфейс оправдан только для реальной архитектурной границы: внешней системы, взаимодействия модулей или нескольких
подтверждённых реализаций.

- **Расположение**:
  - `backend/tests/Suite/{ModuleName}/Application/UseCase`,
  - `backend/tests/Suite/{ModuleName}/Application/Query`,
  - `backend/tests/Suite/{ModuleName}/Application/Command`
- **Охватывает**:
  - Бизнес-логика в UseCase, Query, Command

Тестирование взаимодействия компонентов с внешними системами.

- **Расположение**: `backend/tests/Suite/{ModuleName}/Infrastructure`
- **Охватывает**:
  - Adapter (взаимодействие с внешними сервисами)
  - Anti-corruption layer между модулями

### End-To-End (E2E) тесты

Тестирование полного пользовательского потока через HTTP или CLI, включая все слои.

- **Расположение**: `backend/tests/Suite/{ModuleName}/Presentation`
- **Охватывает**:
  - HTTP контроллеры и маршруты
  - Middleware
  - CLI-команды с fake-адаптерами
  - Полный жизненный цикл запроса и данных

## Общие рекомендации

### Написание тестов

1. **Именование тестовых методов**: Используйте `#[Test]` и
   `#[TestDox('Описание сценария, проверки и ожидаемого результата')]`.
   Метод может иметь короткое техническое имя и не должен дублировать русское описание:

    ```php
    #[Test]
    #[TestDox('Создаёт пользователя с валидными данными')]
    public function itCreates(): void
    ```

2. **Использование dataProvider**:

    ```php
    #[Test]
    #[DataProvider('invalidLoginRequestProvider')]
    public function itRejects(?string $login, ?string $password): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LoginRequest($login, $password);
    }

    public static function invalidLoginRequestProvider(): iterable
    {
        yield 'пустой логин' => ['login' => '', 'password' => 'secret'];
        yield 'логин не задан' => ['login' => null, 'password' => 'secret'];
    }
    ```

      **Конкретная рекомендация**: Используйте dataProvider, когда сценарий отличается только входными данными,
      например для нескольких допустимых или недопустимых значений одного правила.

3. **Проверка исключений в PHPUnit**:

    - Для стандартных исключений используй `expectException()`, `expectExceptionCode()` и `expectExceptionMessage()`
      до Act.
    - Не смешивай `expectException*()` с `try/catch` в одном тесте без необходимости: это делает проверку неявной.
      `try/finally` допустим, если после исключения необходимо проверить побочный эффект, например rollback транзакции.
    - Если у исключения есть собственные поля (например, `HttpException::$errors`), используй `expectExceptionObject()`
      и передавай полностью собранный объект ожидаемого исключения.

    ```php
    $this->expectExceptionObject(new HttpException(
        errors: ['statCardId' => ['Приём пациента не найден в МИС.']],
        message: 'Приём пациента не найден в МИС.',
        status: HttpStatus::NOT_FOUND,
    ));

    $useCase->create(new CreateQuickOrderRequest(999999999));
    ```

4. **AAA паттерн (Arrange-Act-Assert)**:

    ```php
    #[Test]
    #[TestDox('Создаёт пользователя с валидными данными')]
    public function itCreates(): void
    {
        $login = 'test@example.com';
        $expertId = '123';

        $user = new User($login, $expertId);

        self::assertSame($login, $user->login);
        self::assertSame($expertId, $user->expert_id);
    }
    ```

### Работа с тестовыми двойниками

- Используй fake или stub для внешних систем и межмодульных контрактов.
- Проверяй адаптеры HTTP на тестовом HTTP-клиенте, без реальной сети.
- Не мокируй SQLite: используй временную тестовую базу.
- Создавай mock только когда нужно проверить конкретное взаимодействие с зависимостью.

### Покрытие кода

1. **Целевое покрытие**: >= 75%
2. **Проверка покрытия**:

    ```bash
    make php-unit-tests  # Запуск тестов с генерацией отчета
    ```

3. **Отчет будет в**: `storage/temp/phpunit/coverage.txt`

4. **Конкретные рекомендации для достижения 75% покрытия**:
    - Тестируйте значимое поведение и существенные ветви.
    - Добавьте dataProvider для нескольких вариантов одного правила.
    - Тестируйте выбрасывание исключений:

    ```php
    #[Test]
    #[TestDox('Выбрасывает исключение для пользователя без логина')]
    public function itRejects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new User(null, '123');
    }
    ```

    - Тестируйте условия if/else в Entity:

    ```php
    #[Test]
    #[TestDox('Создаёт токен для пользователя')]
    public function itCreates(): void
    {
        $user = new User('test@example.com', '123');
        $token = $user->createToken('test');
        self::assertNotNull($token);
    }
    ```

### Документация тестов

1. **Сложные сценарии**: добавьте `#[TestDox]` для документации:

    ```php
    #[Test]
    #[TestDox('Выбрасывает исключение при невалидных данных')]
    public function itFails(): void
    ```

### Обработка ошибок и краевые случаи

1. **Тестируйте значимые граничные случаи для ValueObject**:

    ```php
    // Пустые значения
    $request = new LoginRequest('', 'secret'); // Ожидается исключение

    // Null значения
    $request = new LoginRequest(null, 'secret'); // Ожидается исключение

    // Слишком длинные значения
    $request = new LoginRequest(str_repeat('a', 256), 'secret'); // Ожидается исключение
    ```

2. **Тестируйте сценарии отсутствия данных**:

    ```php
    #[Test]
    #[TestDox('Возвращает null, когда пример не существует')]
    public function itQueries(): void
    {
        $query = $this->service(GetExampleQuery::class);
        $result = $query->getById(999999);
        self::assertNull($result);
    }
    ```

## Итог

- Чистые классы без I/O и инфраструктуры: unit-тесты. Например, валидаторы, Value Object, расчёты, парсеры, маппинг данных.
- UseCase, Command, Query, сервисы с БД: integration-тесты с настоящей временной тестовой БД.
- Адаптеры внешних API: integration-тесты компонента, при этом HTTP-клиент можно замокать, потому что это внешняя
  граница и обычно уже есть контракт.
- Контроллеры: E2E-тесты.

**Важно:** final не должен быть способом принудительно перевести любой класс на integration-тесты.
Если в классе есть важная чистая логика, её лучше выделить в отдельный класс и тестировать изолированно.
