# Symfony Clean Architecture API

API-сервер на Symfony 7: чистая архитектура, JWT, MySQL, Redis, RabbitMQ.

Соглашения по именованию и слоям: [`CLAUDE.md`](CLAUDE.md). Тесты: [`tests/CLAUDE.md`](tests/CLAUDE.md).

Локальный Docker-стек: [`docker/README.md`](../docker/README.md) (`up -d --build` сам делает `composer install` + migrate).

## Console

Из каталога `api/` (после `up`; migrate уже в entrypoint — см. [`docker/README.md`](../docker/README.md)):

```bash
# Docker
docker compose -f ../docker/docker-compose.yml exec php php bin/console cache:clear
docker compose -f ../docker/docker-compose.yml exec php php bin/console debug:router
docker compose -f ../docker/docker-compose.yml exec php php bin/console debug:messenger
docker compose -f ../docker/docker-compose.yml exec php php bin/console doctrine:migrations:migrate --no-interaction
```

## Тесты

```bash
# Docker
docker compose -f ../docker/docker-compose.yml exec php php bin/phpunit
docker compose -f ../docker/docker-compose.yml exec php php bin/phpunit tests/Application
docker compose -f ../docker/docker-compose.yml exec php php bin/phpunit tests/Integration
```

### Code style / static analysis

```bash
docker compose -f ../docker/docker-compose.yml exec php composer cs-check
docker compose -f ../docker/docker-compose.yml exec php composer cs-fix
docker compose -f ../docker/docker-compose.yml exec php composer phpstan
```
