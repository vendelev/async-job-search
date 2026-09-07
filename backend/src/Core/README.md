# Модуль Core

`Core` содержит общие технические контракты, которые используют конфигурации нескольких модулей.
Он не зависит от прикладных модулей и не содержит бизнес-логики.

## Структура

```text
Core/
└── Presentation/Config/
    ├── ConsoleCommandTag.php
    └── MigrationProviderTag.php
```

## Presentation

`ConsoleCommandTag` помечает invokable-команды Symfony Console.
`AppModule` собирает их через `Dic::taggedList()` и передаёт в `Application::addCommands()`, поэтому новый контекст
может добавить команду без изменения корневого модуля.

`MigrationProviderTag` помечает реализации `MigrationProvider`.
`MigrationDi` собирает эти реализации через `Dic::taggedList()`.

Оба тега зависят от `thesis/dic` и используются только в `Presentation\Config`.
Domain, Application и Infrastructure-слои не зависят от `Core`.

## Ограничения

- В `Core` размещаются только технические контракты без предметной семантики.
- `Core` не импортирует прикладные модули.
