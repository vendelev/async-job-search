# Учебный проект: асинхронный поиск вакансий

CLI-приложение периодически получает PHP-вакансии с Habr Career, исключает уже обработанные и сохраняет новые
вакансии в PostgreSQL. 
Отдельный HTTP-процесс отображает сохранённый список и карточки вакансий.

Проект служит практикой асинхронного PHP, `thesis/dic` и модульной Clean Architecture.

## Требования

- Docker с Docker Compose v2
- GNU Make

PHP и Composer устанавливаются внутри Docker-образа; локальная установка PHP не требуется.

## Быстрый старт

Создайте локальный файл окружения. 
Укажите в `HABR_CAREER_COOKIE` cookie сессии Habr Career:

```bash
cp .env.dist .env
```

Соберите образ, установите зависимости и примените миграции:

```bash
make install
make up
make migrate
```

`make up` запускает PostgreSQL, daemon поиска вакансий, HTTP-сервер. 
Исходники из `backend/` монтируются в контейнер приложения в `/var/www`.

HTTP-сервер также запускается вместе с остальными сервисами. 
Откройте `http://localhost:${HTTP_PORT}/vacancies`: 

- `GET /vacancies` отображает список, 
- `GET /vacancies/{source}/{externalVacancyId}` -- карточку вакансии.

## Команды

```bash
make help           # список доступных команд
make vacancy-discovery-daemon # daemon в текущем терминале
make app-log        # логи HTTP-сервера
make daemon-log     # логи daemon поиска вакансий
make restart        # перезапуск сервисов приложения
make down           # остановка и удаление контейнеров
make migrate        # применение миграций PostgreSQL
make remigrate      # пересоздание PostgreSQL volume и повторное применение миграций
make php-test       # все Composer-проверки
make php-unit-tests # PHPUnit
make php-cli        # Bash в PHP-контейнере
```

`make php-unit-tests TEST=путь/к/тесту` запускает выбранный PHPUnit-тест.

## Реализованные возможности

- Получение первой страницы PHP-вакансий Habr Career, отсортированной по дате.
- Дедупликация вакансий по паре источника и внешнего идентификатора в PostgreSQL.
- Сохранение новых вакансий в пользовательской проекции PostgreSQL.
- HTML-список и карточки сохранённых вакансий.

Подробности реализации описаны в README модулей: 

- [VacancyDiscovery](backend/src/VacancyDiscovery/README.md)
- [VacancyCatalog](backend/src/VacancyCatalog/README.md)
