%:
	@:

# Prepare enviroment variables from defaults
$(shell false | cp -i \.env.local \.env 2>/dev/null)


start:
	docker-compose up -d

stop:
	docker-compose stop

exec:
	docker-compose exec -T php ash 

console:
	docker compose exec -T php bin/console ${1}

init:
	@echo "Init project"
	docker-compose up -d
	docker-compose exec php composer install
	docker-compose exec php bin/console doctrine:migration:migrate
