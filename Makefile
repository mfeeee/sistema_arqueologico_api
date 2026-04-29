up:
	docker compose up -d --build

down:
	docker compose down

stop:
	docker compose stop

restart:
	docker compose down && docker compose up -d --build

bash:
	docker compose exec app bash

composer-install:
	docker compose exec app composer install

key:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

queue:
	docker compose exec app php artisan queue:work

logs:
	docker compose logs -f