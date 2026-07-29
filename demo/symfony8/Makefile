.PHONY: help up down down-dev build install shell logs test test-coverage cache-clear update-bundle ensure-up restart verify

help:
	@echo "Symfony 8 Demo - Controller Kit Bundle"
	@echo "  make up             Start containers"
	@echo "  make down           Stop containers"
	@echo "  make down-dev       Stop containers (keep volumes; REQ-MAKE-007)"
	@echo "  make restart        Restart containers"
	@echo "  make build          Build image (no cache)"
	@echo "  make install        Install dependencies"
	@echo "  make shell          Open shell in php container"
	@echo "  make logs           Stream container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make update-bundle  Update nowo-tech/controller-kit-bundle and clear cache"

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	$(COMPOSE) up -d
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	echo "Demo started at: http://localhost:$$PORT"
ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting demo container..."; \
		$(COMPOSE) up -d; \
		sleep 3; \
	fi

down:
	$(COMPOSE) down

# Stop containers without removing volumes (REQ-MAKE-007)
down-dev:
	$(COMPOSE) down --remove-orphans

restart: ensure-up
	$(COMPOSE) restart

build:
	$(COMPOSE) build --no-cache

install: ensure-up
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	$(COMPOSE) exec -T php composer install --no-interaction
	$(COMPOSE) exec -T php php bin/console cache:clear

shell: ensure-up
	$(COMPOSE) exec php sh

logs:
	$(COMPOSE) logs -f

test: ensure-up
	$(COMPOSE) exec -T -e APP_ENV=test php php bin/phpunit

test-coverage: ensure-up
	$(COMPOSE) exec -T -e APP_ENV=test php php bin/phpunit --coverage-text

cache-clear: ensure-up
	$(COMPOSE) exec -T php php bin/console cache:clear

update-bundle: ensure-up
	$(COMPOSE) exec -T php composer update nowo-tech/controller-kit-bundle --no-interaction
	$(COMPOSE) exec -T php php bin/console cache:clear --env=dev

verify: ensure-up
	@code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$${PORT:-8011} 2>/dev/null || echo "000"); \
	if [ "$$code" != "200" ]; then echo "Healthcheck failed: $$code"; exit 1; fi; \
	echo "Healthcheck OK ($$code)"


# REQ-MAKE-008: update-deps (optional: monorepo helper absent on standalone GitHub Actions checkout)
SERVICE_PHP := php
# Prefer Compose V2 plugin (GitHub Actions / modern Docker Desktop); fall back to docker-compose V1 (REQ-MAKE-010).
# Resolve `docker` via `command -v` so the local `docker/` directory cannot shadow the CLI when PATH has empty components.
DOCKER_BIN := $(shell command -v docker 2>/dev/null || echo docker)
COMPOSE := $(shell $(DOCKER_BIN) compose version >/dev/null 2>&1 && echo "$(DOCKER_BIN) compose" || echo "docker-compose")
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../..)
-include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-update-deps.mk
