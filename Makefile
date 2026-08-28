include .env

DOCKER_COMPOSE = docker compose

.PHONY: help build install composer-install up down restart vacancy-discovery-daemon migrate remigrate search-once php-test php-unit-tests php-cli php-log

H1 = echo === ${1} ===
BR = echo
TAB = echo "\t"

help:
	@$(call H1,Application)
	@$(TAB) make install - Собрать образ и установить Composer-зависимости.
	@$(TAB) make up - Запустить daemon поиска вакансий в фоне.
	@$(TAB) make vacancy-discovery-daemon - Запустить daemon поиска вакансий в foreground.
	@$(TAB) make down - Остановить и удалить контейнеры.
	@$(TAB) make restart - Перезапустить daemon.
	@$(TAB) make migrate - Применить миграции PostgreSQL.
	@$(TAB) make remigrate - Удалить PostgreSQL volume и применить миграции заново.
	@$(call BR)
	@$(call H1,PHP)
	@$(TAB) make php-test - Выполнить Composer-проверки.
	@$(TAB) make php-unit-tests - Запустить PHPUnit. TEST=... выбирает тест.
	@$(TAB) make php-cli - Открыть Bash в PHP-контейнере.
	@$(TAB) make php-log - Показать логи daemon.

build:
	$(DOCKER_COMPOSE) build

install: build composer-install

composer-install:
	$(DOCKER_COMPOSE) run --rm app composer install

vacancy-discovery-daemon:
	$(DOCKER_COMPOSE) up app

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

php-test:
	$(DOCKER_COMPOSE) exec -it app composer test

php-unit-tests:
ifeq ($(TEST),)
	$(DOCKER_COMPOSE) exec -it app composer phpunit
else
	$(DOCKER_COMPOSE) exec -it app composer phpunit -- $(TEST)
endif

php-cli:
	$(DOCKER_COMPOSE) exec -it app bash

php-log:
	$(DOCKER_COMPOSE) logs app
