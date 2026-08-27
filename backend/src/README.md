# Composition Root

`AppModule` в `AppModule.php` является composition root приложения. 
Он не создаёт подключения или адаптеры напрямую: импортирует технические модули и
передаёт между ними только `Ref<T>` exports.

## Текущая композиция

```mermaid
flowchart LR
    Config[PostgresConfig] --> AppModule
    AppModule --> PostgresModule
    PostgresModule -->|Ref<PostgresDatabase>| MigrationModule
    AppModule --> EventStoreMigrationModule
    EventStoreMigrationModule -->|Ref<MigrationProvider>| MigrationModule
    MigrationModule -->|Ref<MigrateCommand>| AppModule
```

Порядок сборки в `AppModule::configure()`:

1. `PostgresModule` создаёт общий async pool PostgreSQL и экспортирует `Ref<PostgresDatabase>`.
2. `EventStoreMigrationModule` экспортирует `Ref<MigrationProvider>` с миграцией таблицы журнала событий.
3. `MigrationModule` получает оба export и возвращает `Ref<MigrateCommand>`.

`backend/bin/migrate.php` создаёт `PostgresConfig` из окружения, передаёт его
в `AppModule` и запускает экспортированный `MigrateCommand`.

## Runtime EventStore

`EventStoreModule` существует, но пока не импортируется корневым модулем:
реализованного runtime-потребителя `EventStore` ещё нет. 
Его миграции уже подключены независимо через `EventStoreMigrationModule`.

`EventBus` уже реализован, но пока не импортируется корневым модулем: первого
runtime-потребителя шины ещё нет. При его добавлении `AppModule` должен:

1. Импортировать `EventStoreModule`, передав ему `Ref<PostgresDatabase>`.
2. Передать возвращённый `Ref<EventStore>` в `EventBusModule`.
3. Импортировать `EventBusModule` и использовать его export на соответствующей точке входа.

EventBus добавляет событие в EventStore до запуска обработчиков. In-memory
доставка не переживает рестарт процесса; это ограничение определено в
`docs/ARCHITECTURE.md`.

## Правило изменения композиции

При добавлении модуля не передавайте его Infrastructure-объекты напрямую.
Модуль должен экспортировать `Ref` на Domain-контракт, а `AppModule` передаёт эту ссылку следующему потребителю. 
Одновременно обновляйте этот документ и README соответствующего модуля.
