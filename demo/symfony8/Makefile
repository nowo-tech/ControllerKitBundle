.PHONY: help up down composer-install down-dev build install shell logs test test-coverage cache-clear update-bundle ensure-up restart verify test-e2e demo-screenshots

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

composer-install:
	@echo "Installing Composer dependencies (ephemeral container)..."
	$(DC) run --rm --no-deps --entrypoint "" -e COMPOSER_MEMORY_LIMIT=-1 php \
		composer install --no-interaction

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	@$(MAKE) composer-install
	$(DC) up -d
	@echo "Waiting for FrankenPHP to stay up..."
	@ok=0; \
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if $(COMPOSE) exec -T php true 2>/dev/null; then ok=1; break; fi; \
		sleep 2; \
	done; \
	if [ "$$ok" != "1" ]; then \
		echo "Demo php container failed to stay up. Recent logs:"; \
		$(COMPOSE) logs --tail=100 php || true; \
		exit 1; \
	fi
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
DC := :; $(COMPOSE)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../..)
-include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-update-deps.mk

# REQ-DEMO-013: Playwright e2e + README screenshots (official Playwright image; host Chromium libs are often missing on WSL)
# The image tag follows the @playwright/test version pinned in e2e/package.json (they must match).
PLAYWRIGHT_VERSION := $(shell sed -n 's/.*"@playwright\/test": "\([0-9.]*\)".*/\1/p' e2e/package.json)
PLAYWRIGHT_IMAGE ?= mcr.microsoft.com/playwright:v$(PLAYWRIGHT_VERSION)-jammy

test-e2e: ensure-up
	@mkdir -p e2e/node_modules
	@PORT=$$(grep -E '^PORT=' .env 2>/dev/null | cut -d= -f2); PORT=$${PORT:-8011}; \
	docker run --rm --network host \
		-v "$(CURDIR)/e2e:/e2e" -w /e2e \
		-e DEMO_BASE_URL=http://127.0.0.1:$$PORT \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'npm install --no-fund --no-audit && npx playwright test --project=chromium'

demo-screenshots: ensure-up
	@mkdir -p ../../docs/images/demo e2e/node_modules
	@PORT=$$(grep -E '^PORT=' .env 2>/dev/null | cut -d= -f2); PORT=$${PORT:-8011}; \
	docker run --rm --network host \
		-v "$(CURDIR)/e2e:/e2e" \
		-v "$(CURDIR)/../../docs/images/demo:/screenshots" \
		-w /e2e \
		-e DEMO_BASE_URL=http://127.0.0.1:$$PORT \
		-e SCREENSHOT_DIR=/screenshots \
		$(PLAYWRIGHT_IMAGE) \
		bash -lc 'npm install --no-fund --no-audit && npx playwright test --project=screenshots && node scripts/to-webp.mjs /screenshots'
	@echo "Screenshots written under docs/images/demo/ (WebP)"
