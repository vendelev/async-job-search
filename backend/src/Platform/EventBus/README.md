# Модуль EventBus

`EventBus` доставляет Domain-события подписчикам внутри текущего процесса.
Перед запуском обработчиков событие добавляется в `EventStore`.

## Структура

```text
EventBus/
├── Domain/
│   ├── DomainEvent.php
│   ├── EventBus.php
│   └── EventSubscriber.php
├── Infrastructure/
│   └── InMemoryEventBus.php
└── Presentation/Config/
    └── EventBusDi.php
```

## Domain

- `DomainEvent` расширяет `Thesis\Message\Event` и явно публикует имя потока и JSON-совместимый payload для журнала.
- `EventBus` публикует Domain-событие.
- `EventSubscriber` сообщает поддерживаемый класс события и обрабатывает его.

## Infrastructure

`InMemoryEventBus` создаёт `StoredEvent` с временем от `ClockInterface` и сначала вызывает `EventStore::append()`.
Затем каждый подходящий подписчик запускается независимо через `Amp\async()`.
Ошибка обработчика записывается в лог и не мешает другим подписчикам.

Доставка process-local и best-effort.
После завершения процесса незаконченные обработчики прекращаются, а сохранённые события не доставляются повторно.

## DI-конфигурация

`EventBusDi` принимает `Ref<EventStore>` и список `Ref<EventSubscriber>`.
Он создаёт `WallClock` и экспортирует только `Ref<EventBus>`.

Модуль не импортирован в `MigrateModule`, пока нет прикладного publisher.
Первый runtime-модуль передаст EventBus подписчиков через composition root.

## Следующий шаг

При необходимости надёжной межпроцессной доставки `InMemoryEventBus` будет заменён PGMQ-реализацией на основе
`thesis/message-bus`. Публичные Domain-контракты изменять не потребуется.
