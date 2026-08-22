# Модуль Migration

Технический модуль для последовательного применения PostgreSQL-миграций, опубликованных другими модулями.
Он не содержит бизнес-логики и не определяет прикладную схему базы данных.

## Архитектура и структура модуля

Модуль состоит из трёх слоёв. 
Слоя `Application` нет: `PostgresMigrationRunner` реализует единственный технический сценарий непосредственно в `Infrastructure`.

```text
Migration/
├── Domain/
│   ├── Migration.php
│   └── MigrationProvider.php
├── Infrastructure/
│   ├── PostgresMigrationRunner.php
│   └── PostgresPdoFactory.php
└── Presentation/
    ├── Config/
    │   ├── MigrationConfig.php
    │   └── MigrationModule.php
    └── Console/
        └── MigrateCommand.php
```

```mermaid
flowchart LR
    Provider[MigrationProvider] --> Runner[PostgresMigrationRunner]
    Config[MigrationConfig] --> Module[MigrationModule]
    Module --> Factory[PostgresPdoFactory]
    Factory --> PDO[PDO PostgreSQL]
    Module --> Runner
    Runner --> Database[(PostgreSQL)]
    Command[MigrateCommand] --> Runner
```

`MigrationModule` регистрирует в контейнере `PostgresPdoFactory`, PDO-соединение, `PostgresMigrationRunner` и экспортирует `MigrateCommand`.
Корневой `AppModule` передаёт в него конфигурацию и ссылки на опубликованные `MigrationProvider`.

## Предметная область

Публичный контракт модуля находится в `Domain`.

- `Migration` - immutable DTO с полями `version` и `sql`. 
   Конструктор отклоняет пустую версию или пустой SQL-код через `InvalidArgumentException`.
- `MigrationProvider` - интерфейс с методом `migrations(): iterable`, который возвращает набор `Migration`.

Модуль, которому нужна собственная PostgreSQL-схема, реализует `MigrationProvider` и публикует его через composition root.
`Migration` не зависит от реализации мигратора или PostgreSQL.

## Бизнес-логика

Слой `Application` отсутствует, поскольку модуль не реализует пользовательский или бизнес-сценарий. 
Техническую координацию применения миграций выполняет `PostgresMigrationRunner` в `Infrastructure`.

## Точки входа

`backend/bin/migrate.php` создаёт `MigrationConfig` из окружения, собирает `AppModule` и передаёт выполнение `MigrateCommand` через `Dic::run()`.

`MigrateCommand::execute()` запускает `PostgresMigrationRunner::migrate()` и возвращает код завершения `0` при успехе.
Исключения мигратора не перехватываются командой.

Для запуска предусмотрены команды из корня репозитория:

```bash
make migrate
make remigrate
```

`make migrate` применяет ещё не выполненные миграции. 
`make remigrate` удаляет PostgreSQL volume, затем запускает мигратор; команда уничтожает все данные в этой базе.

## Инфраструктура

`PostgresPdoFactory` открывает PDO-соединение с PostgreSQL и `PDO::ERRMODE_EXCEPTION`.

`PostgresMigrationRunner` выполняет следующий алгоритм:

1. Создаёт техническую таблицу журнала, если её ещё нет:

   ```sql
   CREATE TABLE IF NOT EXISTS schema_migrations (
       version TEXT PRIMARY KEY,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
   )
   ```

2. Получает миграции всех `MigrationProvider`, проверяет уникальность `version` и сортирует их лексикографически по версии.
3. Пропускает версии, уже присутствующие в `schema_migrations`.
4. Для каждой новой миграции в одной транзакции выполняет SQL-код и записывает версию; время применения проставляет PostgreSQL.
5. При любой ошибке в транзакции откатывает её и повторно выбрасывает исключение. Повтор версии среди providers приводит к `RuntimeException` до начала применения.

Мигратор не создаёт прикладные таблицы самостоятельно: их SQL должен поступать от providers.

## Зависимости и конфигурация

`MigrationConfig::fromEnvironment()` читает обязательные переменные `DATABASE_DSN`, `DATABASE_USER` и `DATABASE_PASSWORD`.
Compose формирует их из значений в [`.env.dist`](../../../.env.dist).
Пустое или отсутствующее значение приводит к `InvalidArgumentException` до запуска мигратора.

Модуль использует встроенные расширения `ext-pdo`, `ext-pdo_pgsql` и библиотеку `thesis/dic` для сборки зависимостей.
Работа с PostgreSQL выполняется через PDO.

Единственная межмодульная граница - `MigrationProvider` из собственного `Domain`. 
На текущем этапе `AppModule` передаёт в `MigrationModule` пустой список providers, поэтому запуск команды создаёт 
журнал `schema_migrations`, но не применяет прикладных миграций.

## Тестирование

Тесты находятся в `backend/tests/Suite/Migration/`.

- `Domain/MigrationTest.php` проверяет отклонение пустых версии и SQL-кода.
- `Infrastructure/PostgresMigrationRunnerTest.php` работает с изолированной схемой PostgreSQL и проверяет порядок и
  идемпотентность применения, дубликаты версий и откат записи в журнал при ошибке SQL.

## Ограничения

- Модуль поддерживает только применение миграций; механизм отката отсутствует.
- Успешно применённые SQL-миграции не изменяются модулем и не сверяются с их исходным содержимым.
- В текущей сборке не зарегистрирован ни один `MigrationProvider`, поэтому прикладная схема базы данных не создаётся.
