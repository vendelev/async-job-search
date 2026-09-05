# Модуль Logging

`Logging` предоставляет процессу PSR-3 logger на базе Monolog. Он не задаёт прикладные события и не хранит логи.

## Архитектура и структура модуля

```text
Logging/
└── Presentation/
    └── Config/
        └── LoggergDi.php
```

Domain, Application и Infrastructure-слои отсутствуют: модуль состоит из DI-конфигурации технической библиотеки.

## Предметная область и бизнес-логика

Публичный контракт модуля - `Psr\Log\LoggerInterface`. Бизнес-логика и собственные доменные типы отсутствуют.

## Точки входа

`LoggergDi` импортируется composition roots `HttpModule` и `VacancyDiscoveryDaemonModule`. 
Каждый импорт создаёт `LoggerInterface` для соответствующего процесса и передаёт его зависимым компонентам.

## Инфраструктура

`LoggergDi` создаёт `Monolog\Logger` с именем `async-job-search` и `Monolog\Handler\ErrorLogHandler`.
Все записи направляются в error log процесса.

## Зависимости и конфигурация

Модуль использует `monolog/monolog`, `psr/log` и `thesis/dic`. Переменные окружения не читаются.

## Тестирование

Отдельных тестов модуля нет. `backend/tests/Suite/HttpModuleTest.php` косвенно проверяет его DI-конфигурацию при
сборке `HttpModule`.

## Ограничения

- Назначение logger и обработчик фиксированы в коде.
- Модуль не настраивает формат, ротацию или внешнее хранилище логов.
