.PHONY: help up down build install shell logs test test-coverage cache-clear update-bundle ensure-up restart verify

help:
	@echo "Symfony 7 Demo - Controller Kit Bundle"
	@echo "  make up             Start containers"
	@echo "  make down           Stop containers"
	@echo "  make restart        Restart containers"
	@echo "  make build          Build image (no cache)"
	@echo "  make install        Install dependencies"
	@echo "  make shell          Open shell in php container"
	@echo "  make logs           Stream container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make update-bundle  Update nowo-tech/controller-kit-bundle and clear cache"

up:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	docker-compose up -d --build

ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting demo container..."; \
		docker-compose up -d; \
		sleep 3; \
	fi

down:
	docker-compose down

restart: ensure-up
	docker-compose restart

build:
	docker-compose build --no-cache

install: ensure-up
	docker-compose exec -T php composer install --no-interaction
	docker-compose exec -T php php bin/console cache:clear

shell: ensure-up
	docker-compose exec php sh

logs:
	docker-compose logs -f

test: ensure-up
	docker-compose exec -T -e APP_ENV=test php php bin/phpunit

test-coverage: ensure-up
	docker-compose exec -T -e APP_ENV=test php php bin/phpunit --coverage-text

cache-clear: ensure-up
	docker-compose exec -T php php bin/console cache:clear

update-bundle: ensure-up
	docker-compose exec -T php composer update nowo-tech/controller-kit-bundle --no-interaction
	docker-compose exec -T php php bin/console cache:clear --env=dev

verify: ensure-up
	@code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$${PORT:-8010} 2>/dev/null || echo "000"); \
	if [ "$$code" != "200" ]; then echo "Healthcheck failed: $$code"; exit 1; fi; \
	echo "Healthcheck OK ($$code)"
