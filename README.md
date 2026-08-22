# Учебный проект: асинхронный поиск вакансий

CLI-приложение должно периодически получать вакансии с HH.ru, отбирать
подходящие, исключать уже обработанные и отправлять уведомления в Telegram.
Проект служит практикой асинхронного PHP, `thesis/dic` и модульной Clean Architecture.

## Требования

- Docker с Docker Compose v2
- GNU Make

PHP и Composer устанавливаются внутри Docker-образа; локальная установка PHP не требуется.

## Запуск

Создайте локальный файл окружения и соберите образ с зависимостями:

```bash
cp .env.dist .env
make install
```

Запустите daemon в фоне:

```bash
make up
```

Исходники из `backend/` монтируются в контейнер в `/var/www`.

## Команды

```bash
make help           # список доступных команд
make daemon         # daemon в текущем терминале
make php-log        # логи daemon
make restart        # перезапуск daemon
make down           # остановка и удаление контейнеров
make php-test       # все Composer-проверки
make php-unit-tests # PHPUnit
make php-cli        # Bash в PHP-контейнере
```

`make search-once` предназначена для будущей команды однократного поиска и
станет работоспособной после появления `backend/bin/search-once.php`.
