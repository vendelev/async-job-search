# Модуль Postgres

`Postgres` предоставляет другим техническим модулям общий неблокирующий доступ к PostgreSQL через `amphp/postgres`. 
Он является единственным местом, где создаются pool подключений и разбирается конфигурация базы данных.

## Структура

```text
Postgres/
├── Domain/
│   ├── PostgresDatabase.php
│   └── PostgresExecutor.php
├── Infrastructure/
│   ├── AmpPostgresDatabase.php
│   ├── AmpPostgresTransaction.php
└── Presentation/Config/
    ├── PostgresEnv.php
    └── PostgresDi.php
```

## Domain

`PostgresExecutor` - контракт параметризованного SQL. Его реализуют pool и обёртка над транзакцией, поэтому адаптер 
может работать в обоих контекстах.
`execute()` использует именованные параметры и возвращает `Amp\Postgres\PostgresResult`.

`PostgresDatabase` расширяет `PostgresExecutor` методом `beginTransaction()`.
Он резервирует одно подключение пула на время транзакции.

`beginTransaction()` открывает транзакцию и возвращает `Amp\Postgres\PostgresTransaction`. 
Этот объект выполняет SQL-запросы, фиксирует изменения через `commit()` и отменяет их через `rollback()`.
Он нужен, когда несколько операций должны быть атомарными.

`AmpPostgresTransaction` предоставляет уже открытую транзакцию как `PostgresExecutor`. 
Его следует создать и передать компоненту, которому нужен только `execute()`, но запросы которого должны войти 
в текущую транзакцию:

```php
$transaction = $database->beginTransaction();

try {
    $executor = new AmpPostgresTransaction($transaction);
    $eventStore = new PostgresEventStore($executor);
    // Операции $eventStore выполняются в $transaction.

    $transaction->commit();
} catch (Throwable $error) {
    if ($transaction->isActive()) {
        $transaction->rollback();
    }

    throw $error;
}
```

В обычном сценарии, когда не требуются общая транзакция, `commit()` или `rollback()`, зависимости передают 
`PostgresDatabase`: он также реализует `PostgresExecutor` и выполняет отдельные запросы через pool.

`AmpPostgresDatabase` — всегда входная точка (пул + начало транзакции), 
а `AmpPostgresTransaction` — способ выполнить запросы внутри уже открытой транзакции через общий контракт. 

Контракт используют Infrastructure-адаптеры `Migration` и `EventStore`. 
Их Application- и Domain-слои не зависят от этого технического модуля.

## Application

Application-слой отсутствует: модуль не реализует пользовательские сценарии или бизнес-логику.

## Presentation

`PostgresEnv::fromEnvironment()` - объект конфигурации
Читает `DATABASE_HOST`, `DATABASE_PORT`, `POSTGRES_DB`, `POSTGRES_USER` и `POSTGRES_PASSWORD`. 
Все значения обязательны; port должен быть целым числом от 1 до 65535.

`PostgresDi` создаёт `PostgresConnectionPool` и экспортирует ссылку на`AmpPostgresDatabase`, реализующий `PostgresDatabase`. 
Корневой `MigrateModule`передаёт этот export в модули, которым требуется PostgreSQL.

## Infrastructure

`PostgresDi::createPool()` создаёт общий `PostgresConnectionPool` из
конфигурации.
`AmpPostgresDatabase` адаптирует pool к `PostgresDatabase`.
`AmpPostgresTransaction` адаптирует транзакцию к `PostgresExecutor`.

Вызовы API Amp выполняются внутри Fiber и не блокируют event loop при ожидании ответа PostgreSQL. 
Вызывающий код запускает Fiber на своей точке входа, как это делает `MigrateCommand`.

## Тестирование

`PostgresModuleTest` проверяет сборку модуля, подключение к тестовой PostgreSQL базе и выполнение запроса. 
Адаптер дополнительно проверяется интеграционно через тесты `Migration` и `EventStore`.

## Ограничения

- Поддерживается только PostgreSQL.
- Размер пула и параметры его жизненного цикла используют значения по умолчанию `amphp/postgres`.
