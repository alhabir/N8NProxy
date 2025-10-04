.PHONY: help up down test seed serve migrate fresh install

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies and setup
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	php artisan migrate

up: ## Start development environment
	php artisan serve &
	php artisan queue:work &

down: ## Stop development environment
	pkill -f "php artisan serve" || true
	pkill -f "php artisan queue:work" || true

serve: ## Start development server only
	php artisan serve

test: ## Run tests
	php artisan test

migrate: ## Run migrations
	php artisan migrate

fresh: ## Fresh database with migrations
	php artisan migrate:fresh

seed: ## Seed demo merchant
	php artisan db:seed --class=DemoMerchantSeeder

retry: ## Retry failed webhook forwards
	php artisan webhooks:retry-failed
