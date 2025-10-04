.PHONY: help up down serve test seed fresh migrate install pint

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Start all services (equivalent to sail up)
	@echo "Starting N8NProxy services..."
	@cd app && php artisan serve --host=0.0.0.0 --port=8000 &
	@cd app && php artisan queue:work --daemon &
	@echo "Services started. App available at http://localhost:8000"

serve: ## Start the Laravel development server
	@cd app && php artisan serve --host=0.0.0.0 --port=8000

test: ## Run all tests
	@cd app && php artisan test

pest: ## Run Pest tests specifically
	@cd app && vendor/bin/pest

seed: ## Seed the database with demo data
	@cd app && php artisan db:seed --class=DemoMerchantSeeder

fresh: ## Fresh migration with demo seeding
	@cd app && php artisan migrate:fresh --seed
	@make seed

migrate: ## Run database migrations
	@cd app && php artisan migrate

install: ## Install PHP dependencies
	@cd app && composer install --no-dev --optimize-autoloader

dev-install: ## Install PHP dependencies for development
	@cd app && composer install

pint: ## Format code with Laravel Pint
	@cd app && vendor/bin/pint

check: ## Run code quality checks
	@cd app && vendor/bin/pint --test
	@make test

key: ## Generate application key
	@cd app && php artisan key:generate

cache: ## Clear all caches
	@cd app && php artisan cache:clear
	@cd app && php artisan config:clear
	@cd app && php artisan route:clear
	@cd app && php artisan view:clear

logs: ## Show application logs
	@cd app && tail -f storage/logs/laravel.log

demo-curl: ## Show demo curl commands for testing actions API
	@echo "Demo curl commands (set ACTIONS_TOKEN environment variable first):"
	@echo ""
	@echo "# List orders:"
	@echo "curl -H \"Authorization: Bearer \$$ACTIONS_TOKEN\" \"http://localhost:8000/api/actions/orders/list?merchant_id=demo_merchant_123&page=1&per_page=5\""
	@echo ""
	@echo "# Create a product:"
	@echo "curl -X POST -H \"Authorization: Bearer \$$ACTIONS_TOKEN\" -H \"Content-Type: application/json\" \\"
	@echo "  -d '{\"merchant_id\":\"demo_merchant_123\",\"payload\":{\"name\":\"Test Product\",\"price\":99.99}}' \\"
	@echo "  \"http://localhost:8000/api/actions/products/create\""
	@echo ""
	@echo "# List customers:"
	@echo "curl -H \"Authorization: Bearer \$$ACTIONS_TOKEN\" \"http://localhost:8000/api/actions/customers/list?merchant_id=demo_merchant_123\""