SHELL := /bin/bash


up:
	docker compose up -d --build


stop:
	docker compose stop


down:
	docker compose down -v


composer-install:
	docker compose exec php composer install


composer-update:
	docker compose exec php composer update


migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction


fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction


cc:
	docker compose exec php php bin/console cache:clear


front-dev:
	docker compose up -d frontend


front-build:
	docker compose run --rm frontend npm run build