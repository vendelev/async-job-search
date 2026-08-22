include .env

DOCKER_COMPOSE = docker compose

.PHONY: help build install composer-install up down restart daemon search-once php-test php-unit-tests php-cli php-log

H1 = echo === ${1} ===
BR = echo
TAB = echo "\t"

help:
	@$(call H1,Application)
	@$(TAB) make install - Собрать образ и установить Composer-зависимости.
	@$(TAB) make up - Запустить daemon в фоне.
	@$(TAB) make down - Остановить и удалить контейнеры.
	@$(TAB) make restart - Перезапустить daemon.
	@$(TAB) make daemon - Запустить daemon в текущем терминале.
	@$(TAB) make search-once - Выполнить один поиск вакансий.
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

up:
	$(DOCKER_COMPOSE) up --detach app

down:
	$(DOCKER_COMPOSE) down

restart: down up

daemon:
	$(DOCKER_COMPOSE) up app

search-once:
	$(DOCKER_COMPOSE) run --rm app php bin/search-once.php

php-test:
	$(DOCKER_COMPOSE) run --rm app composer test

php-unit-tests:
ifeq ($(TEST),)
	$(DOCKER_COMPOSE) run --rm app composer phpunit
else
	$(DOCKER_COMPOSE) run --rm app composer phpunit -- $(TEST)
endif

php-cli:
	$(DOCKER_COMPOSE) run --rm app bash

php-log:
	$(DOCKER_COMPOSE) logs app
