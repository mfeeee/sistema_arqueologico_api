.PHONY: up down build restart shell logs migrate fresh seed test key

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

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

shell:
	docker compose exec app bash

seed:
	docker compose exec app php artisan db:seed

setup: up install key migrate
	@echo "API em http://localhost:8000"