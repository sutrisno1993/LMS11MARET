# LMS Docker Helper Commands

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

logs:
	docker compose logs -f app

logs-mysql:
	docker compose logs -f mysql

shell:
	docker compose exec app sh

mysql:
	docker compose exec mysql mysql -u lms_user -plms_password lms_db

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

migrate-all:
	docker compose exec app sh -c "DB_DATABASE=lms_db php artisan migrate --force && DB_DATABASE=lms_bekasi php artisan migrate --force"

artisan:
	docker compose exec app php artisan $(cmd)

composer:
	docker compose exec app composer $(cmd)

setup:
	cp .env.docker .env
	docker compose up -d --build
