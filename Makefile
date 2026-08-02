.DEFAULT_GOAL := help

.PHONY: help setup lint analyse audit test check up down logs migrate demo overdue build clean

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*## "; printf "Backup Catalog commands:\n"} /^[a-zA-Z_-]+:.*?## / {printf "  %-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Create local environment and build images
	@test -f .env || cp .env.example .env
	docker compose build

lint: ## Check PHP coding standards
	docker compose run --rm --no-deps app composer lint

analyse: ## Run PHPStan at level 8
	docker compose run --rm --no-deps app composer analyse

audit: ## Check locked dependencies for known vulnerabilities
	docker compose run --rm --no-deps app composer audit

test: ## Run the complete PHPUnit suite
	docker compose run --rm app composer test

check: lint analyse audit test ## Run all source checks

up: ## Start the local stack and wait for health checks
	docker compose up -d --build --wait

down: ## Stop the local stack
	docker compose down

logs: ## Follow application logs
	docker compose logs -f app nginx

migrate: ## Apply pending database migrations
	docker compose exec app php bin/console migrate

demo: ## Add today's deterministic demo runs
	docker compose exec app php bin/console generate-demo

overdue: ## Print overdue systems and running jobs
	docker compose exec app php bin/console check-overdue

build: ## Build the production image
	docker build --target production -t backup-catalog:local .

clean: ## Remove containers, volumes, and local build output
	docker compose down --volumes --remove-orphans
	rm -rf build coverage .phpunit.cache .php-cs-fixer.cache
