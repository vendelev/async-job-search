# Модуль EventStore

`EventStore` хранит неизменяемую историю доменных событий в PostgreSQL. 
Это технический журнал, а не полная реализация Event Sourcing: прикладные модули
могут хранить собственные проекции независимо от истории событий.

## Структура

```text
EventStore/
├── Domain/
│   ├── EventId.php
│   ├── EventStore.php
│   └── StoredEvent.php
├── Infrastructure/
│   ├── EventStoreMigrationProvider.php
│   └── PostgresEventStore.php
└── Presentation/Config/
    ├── EventStoreMigrationModule.php
    └── EventStoreModule.php
```

## Domain

- `EventId` - value object идентификатора события. 
  Для новых событий создаёт UUIDv7, а при чтении принимает только корректный UUIDv7.
- `StoredEvent` - immutable событие с идентификатором, именем потока, типом,
  UTC-временем возникновения и JSON-совместимым payload.
- `EventStore` - контракт добавления события и чтения полной истории потока в порядке добавления.

## Infrastructure

`PostgresEventStore` использует экспортированный модулем `Postgres` контракт `PostgresExecutor`. 
Поэтому он одинаково работает с pool и транзакцией.
Таблица `event_store_events` хранит `id` как UUID primary key. 
Техническое поле `position` задаёт строгий порядок добавления, в том числе для событий с одинаковым `occurred_at`.
`EventStoreMigrationProvider` публикует миграцию создания таблицы и индекса по `stream_name, position`.

## DI-Конфигурация

`EventStoreModule` экспортирует только `Ref<EventStore>`.
Он не импортируется `AppModule`, пока нет runtime-потребителя хранилища.

`EventBusModule` уже принимает `Ref<EventStore>` и добавляет событие в журнал
до запуска его подписчиков. Когда появится первый runtime-потребитель шины,
`AppModule` импортирует оба модуля и передаст export `EventStore` в `EventBusModule`.

`EventStoreMigrationModule` экспортирует отдельно `Ref<MigrationProvider>`.
Он уже импортируется `AppModule` и передаётся в `MigrationModule`.

## Ограничения

- EventStore не обеспечивает атомарность между append и запуском обработчиков
  in-memory шины.
