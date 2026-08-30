include .env

DOCKER_COMPOSE = docker compose

.PHONY: help build install composer-install up down restart vacancy-discovery-daemon migrate remigrate search-once php-test php-unit-tests php-cli app-log daemon-log

H1 = echo === ${1} ===
BR = echo
TAB = echo "\t"

help:
	@$(call H1,Application)
	@$(TAB) make install - Собрать образ и установить Composer-зависимости.
	@$(TAB) make up - Запустить сервисы приложения в фоне.
	@$(TAB) make down - Остановить и удалить контейнеры.
	@$(TAB) make restart - Перезапустить сервисы приложения.
	@$(TAB) make migrate - Применить миграции PostgreSQL.
	@$(TAB) make remigrate - Удалить PostgreSQL volume и применить миграции заново.
	@$(call BR)
	@$(call H1,PHP)
	@$(TAB) make php-test - Выполнить Composer-проверки.
	@$(TAB) make php-unit-tests - Запустить PHPUnit. TEST=... выбирает тест.
	@$(TAB) make php-cli - Открыть Bash в PHP-контейнере.
	@$(TAB) make app-log - Показать логи HTTP-сервера.
	@$(TAB) make daemon-log - Показать логи daemon поиска вакансий.

build:
	$(DOCKER_COMPOSE) -f compose.build.yml build

install: build composer-install

composer-install:
	$(DOCKER_COMPOSE) run --rm app composer install

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart: down up

migrate:
	$(DOCKER_COMPOSE) exec -it app php bin/migrate.php

remigrate:
	$(DOCKER_COMPOSE) down --volumes
	$(DOCKER_COMPOSE) up -d app
	$(DOCKER_COMPOSE) exec -it app php bin/migrate.php

php-test: migrate
	$(DOCKER_COMPOSE) exec -it app composer test

php-unit-tests:
ifeq ($(TEST),)
	$(DOCKER_COMPOSE) exec -it app composer phpunit
else
	$(DOCKER_COMPOSE) exec -it app composer phpunit -- $(TEST)
endif

php-cli:
	$(DOCKER_COMPOSE) exec -it app bash

app-log:
	$(DOCKER_COMPOSE) logs app

daemon-log:
	$(DOCKER_COMPOSE) logs app
