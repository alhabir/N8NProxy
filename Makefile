.PHONY: up test seed serve

up:
	cp -n app/.env.example app/.env || true
	cd app && composer install
	cd app && php artisan key:generate
	cd app && php artisan migrate --force

seed:
	cd app && php artisan db:seed --class=DemoMerchantSeeder

test:
	cd app && php artisan test -q

serve:
	cd app && php artisan serve --host=127.0.0.1 --port=8000
