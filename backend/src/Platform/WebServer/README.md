# Модуль WebServer

`WebServer` предоставляет техническую основу для HTTP-входов: запускает Amp HTTP-сервер и собирает маршруты,
опубликованные прикладными модулями. Он не определяет маршруты и не содержит прикладную логику.

## Архитектура и структура модуля

```text
WebServer/
├── Domain/
│   └── RouteRegister.php
└── Presentation/
    ├── Config/
    │   ├── HttpRouteTag.php
    │   ├── HttpServerEnv.php
    │   ├── RouterFactory.php
    │   └── SocketHttpServerFactory.php
    └── Console/
        └── ServerHttp.php
```

`HttpModule` создаёт `HttpServerEnv`, импортирует `LoggergDi`, получает все регистрации с тегом `HttpRouteTag` и
экспортирует `ServerHttp`.

## Предметная область

`RouteRegister` - публичный контракт для модулей, добавляющих HTTP-маршруты. 
Его метод `register()` получает `Amp\Http\Server\Router` и добавляет в него маршруты до запуска сервера.

`HttpRouteTag` помечает реализации `RouteRegister`, чтобы `HttpModule` передал их в `RouterFactory`. 
Например, `VacancyCatalogHttpDi` экспортирует и помечает `VacancyCatalogRoutes`.

## Бизнес-логика

Application-слой отсутствует: модуль предоставляет техническую инфраструктуру HTTP-сервера и не реализует
пользовательские сценарии.

## Точки входа

`backend/bin/http.php` создаёт `HttpModule` с настройками PostgreSQL и HTTP-сервера, затем запускает экспортированный
`ServerHttp` через `Dic::run()`.

`ServerHttp::run()` выполняет следующие действия:

1. Открывает сокет на адресе из `HttpServerEnv`.
2. Запускает сервер с собранным маршрутизатором и обработчиком ошибок.
3. Ожидает `SIGINT` или `SIGTERM`, после чего останавливает сервер и возвращает код `0`.

## Инфраструктура

`SocketHttpServerFactory` создаёт `SocketHttpServer` через `createForDirectAccess()`. Фабрика нужна потому, что
статический метод Amp требует `LoggerInterface`, а `Thesis\Dic` передаёт в factory без параметров.

`RouterFactory` создаёт `Router` с сервером, `LoggerInterface` и `ErrorHandler`, затем вызывает `register()` у каждой
помеченной реализации `RouteRegister`.

## Зависимости и конфигурация

Модуль использует `amphp/http-server`, `amphp/http-server-router`, `psr/log` и `thesis/dic`.

`HttpServerEnv::fromEnvironment()` читает обязательные переменные окружения:

- `HTTP_HOST` - адрес прослушивания.
- `HTTP_PORT` - порт от `1` до `65535`.

В `.env.dist` для локального запуска заданы `HTTP_HOST=0.0.0.0` и `HTTP_PORT=8080`.

## Тестирование

Сборку HTTP composition root проверяет `backend/tests/Suite/HttpModuleTest.php`. 
Отдельных тестов компонентов `WebServer` пока нет.

## Ограничения

- Сервер доступен напрямую через сокет; TLS и reverse proxy не настраиваются модулем.
- Маршруты регистрируются при сборке `Router`; добавление маршрута в уже запущенный сервер не поддерживается.
