.PHONY: help up down migrate seed test serve install key optimize clear

# Default target
help:
	@echo "N8NProxy Development Commands"
	@echo "============================="
	@echo "make install    - Install dependencies and setup project"
	@echo "make up         - Start Docker containers (if using Docker)"
	@echo "make down       - Stop Docker containers"
	@echo "make migrate    - Run database migrations"
	@echo "make seed       - Seed database with test data"
	@echo "make test       - Run all tests"
	@echo "make serve      - Start development server"
	@echo "make key        - Generate application key"
	@echo "make optimize   - Clear and optimize caches"
	@echo "make clear      - Clear all caches"

# Install dependencies and setup
install:
	composer install
	npm install
	cp .env.example .env
	php artisan key:generate
	php artisan migrate
	@echo "Installation complete! Don't forget to update your .env file."

# Generate application key
key:
	php artisan key:generate

# Start Docker containers (example for MySQL)
up:
	docker-compose up -d

# Stop Docker containers
down:
	docker-compose down

# Run migrations
migrate:
	php artisan migrate

# Run migrations fresh with seed
fresh:
	php artisan migrate:fresh --seed

# Seed database
seed:
	php artisan db:seed
	php artisan db:seed --class=DemoMerchantSeeder

# Run tests
test:
	php artisan test

# Run specific test file
test-file:
	php artisan test $(FILE)

# Start development server
serve:
	php artisan serve

# Clear all caches
clear:
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear

# Optimize for production
optimize:
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache

# Watch for changes and rebuild assets
watch:
	npm run dev

# Build assets for production
build:
	npm run build