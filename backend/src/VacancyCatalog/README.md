# Модуль VacancyCatalog

`VacancyCatalog` владеет пользовательской PostgreSQL-проекцией вакансий. Он получает новые вакансии из
`VacancyDiscovery` через событие `VacancyDiscovered`, сохраняет данные для списка и карточки, а также предоставляет
сценарии чтения.

## Архитектура и структура модуля

```text
VacancyCatalog/
├── Application/UseCase/
│   ├── GetVacancy.php
│   └── ListVacancies.php
├── Domain/
│   ├── Dto/
│   │   ├── Vacancy.php
│   │   └── VacancyListItem.php
│   └── VacancyCatalog.php
├── Infrastructure/
│   ├── PostgresVacancyCatalog.php
│   └── VacancyCatalogMigrationProvider.php
├── Presentation/
│   ├── Config/
│   │   ├── VacancyCatalogEventSubscriberDi.php
│   │   ├── VacancyCatalogHttpDi.php
│   │   ├── VacancyCatalogMigrationDi.php
│   │   └── VacancyCatalogRoutes.php
│   ├── Http/
│   │   └── Controller/
│   │       ├── GetVacancyController.php
│   │       └── ListVacanciesController.php
│   └── Listener/VacancyDiscoveredSubscriber.php
└── README.md
```

```mermaid
flowchart LR
    discovery[VacancyDiscovery] --> event[VacancyDiscovered]
    event --> subscriber[VacancyDiscoveredSubscriber]
    subscriber --> catalog[VacancyCatalog]
    catalog --> postgres[(PostgreSQL)]
    list[ListVacancies] --> catalog
    get[GetVacancy] --> catalog
```

`Domain` публикует модель проекции и контракт хранилища. 
`Application` реализует сценарии чтения. 
`Infrastructure` содержит PostgreSQL-адаптер и provider миграции.
`Presentation` регистрирует зависимости, обрабатывает событие другого модуля и предоставляет HTTP-входы чтения.

## Предметная область

- `Vacancy` содержит полные данные карточки: источник, внешний идентификатор, заголовок, URL, работодателя,
  локацию, описание и дополнительные JSON-совместимые сведения.
- `VacancyListItem` содержит данные, необходимые для списка: источник, внешний идентификатор, заголовок, URL,
  работодателя и локацию.
- `VacancyCatalog` определяет сохранение `ExternalVacancy`, получение списка и карточки по
  `ExternalVacancyId`. Внешний идентификатор является парой `source` и `externalVacancyId`.

Модуль использует опубликованные типы `ExternalVacancy`, `ExternalVacancyId` и событие `VacancyDiscovered` из
`VacancyDiscovery\Domain`. Синхронного вызова внутренностей `VacancyDiscovery` нет.

## Бизнес-логика

`ListVacancies` возвращает все элементы пользовательской проекции в порядке `source`, затем
`externalVacancyId`.

`GetVacancy` получает `ExternalVacancyId` и возвращает полную карточку либо `null`, если вакансия не найдена.

## Точки входа

`VacancyDiscoveredSubscriber` реализует `EventSubscriber`, подписывается на `VacancyDiscovered` и передаёт
вакансию в `VacancyCatalog::add()`.

В `VacancyDiscoveryDaemonModule` subscriber передаётся в `EventBusDi`. 
Поэтому в runtime опубликованное `VacancyDiscovered` доставляется в каталог через `EventBus`.

`VacancyCatalogHttpDi` создаёт use cases чтения, их контроллеры и `VacancyCatalogRoutes`. Последний реализует
`RouteRegister` и помечается `HttpRouteTag`. `HttpModule` собирает tagged registrars в общий `Router`; точка
входа `bin/http.php` запускает сервер.

| Маршрут | Статус | Ответ |
| --- | --- | --- |
| `GET /vacancies` | `200` | `{"vacancies":[...]}`; элемент содержит `source`, `externalVacancyId`, `title`, `url`, `employerName`, `location` |
| `GET /vacancies/{source}/{externalVacancyId}` | `200` | `{"vacancy":{...}}`; карточка дополнительно содержит `description` и `details` |
| `GET /vacancies/{source}/{externalVacancyId}` | `404` | `{"error":"Вакансия не найдена"}` |

## Инфраструктура

`PostgresVacancyCatalog` реализует Domain-контракт через `Platform\Postgres\Domain\PostgresExecutor`.
Он сохраняет проекцию в таблицу `vacancy_catalog_vacancies`:

- первичный ключ: `source`, `external_vacancy_id`;
- поля списка и карточки хранятся отдельными колонками;
- дополнительные сведения сохраняются в `details` типа `JSONB`.

Повторная доставка события не создаёт вторую запись и не перезаписывает сохранённую проекцию: вставка использует
`ON CONFLICT (source, external_vacancy_id) DO NOTHING`.

`VacancyCatalogMigrationProvider` публикует миграцию `vacancy_catalog_001_create_vacancies`.
`VacancyCatalogMigrationDi` передаёт её в общий `MigrateModule`.

## Зависимости и конфигурация

`VacancyCatalogHttpDi` принимает `Ref<PostgresDatabase>` и регистрирует только зависимости HTTP-входов. Его export
реализует `RouteRegister` и собирается HTTP-платформой по `HttpRouteTag`.

`VacancyCatalogEventSubscriberDi` принимает тот же `Ref<PostgresDatabase>` и экспортирует только
`Ref<EventSubscriber>` для `VacancyDiscoveryDaemonModule`. В каждом entry point создаётся отдельный
`PostgresVacancyCatalog`, поэтому конфигурации не передают между собой use cases или контроллеры.

Модуль использует:

- `Platform\Postgres` для доступа к PostgreSQL;
- `Platform\Migration` для применения миграции;
- `Platform\EventBus` для доставки Domain-события;
- `thesis/dic` для регистрации зависимостей.

Собственных переменных окружения у модуля нет. Конфигурацию подключения к PostgreSQL предоставляет
`Platform\Postgres`.

## Тестирование

Тесты расположены в `backend/tests/Suite/VacancyCatalog/`:

- unit-тесты use cases проверяют делегирование чтения в `VacancyCatalog`;
- unit-тест subscriber проверяет передачу вакансии из `VacancyDiscovered` в каталог;
- integration-тест PostgreSQL-адаптера проверяет сохранение, чтение и идемпотентность вставки;
- integration-тест миграции проверяет наличие таблицы пользовательской проекции.
- integration-тест HTTP проверяет список, карточку и `404` через PostgreSQL.

## Ограничения

- Список не поддерживает фильтрацию, лимит или пагинацию.
- HTTP-слой не содержит аутентификации, middleware, versioning API и HTML-представлений.
- Доставка определяется текущим `InMemoryEventBus`: она process-local и best-effort; сохранённое до аварии,
  но не доставленное событие автоматически не обрабатывается после рестарта.
