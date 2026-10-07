# Локальный Docker-стек

При старте `cln-simple-php` entrypoint сам делает `composer install` и `doctrine:migrations:migrate`. Workers ждут healthy PHP и только поднимают consume.

## Запуск с нуля

Из корня репозитория:

```bash
# API (MySQL + Redis + RabbitMQ): composer + migrate автоматически
docker compose -f docker/docker-compose.yml up -d --build

# Со workers
docker compose -f docker/docker-compose.yml --profile workers up -d --build
```

## Остановить / логи

```bash
docker compose -f docker/docker-compose.yml ps
docker compose -f docker/docker-compose.yml logs -f php

docker compose -f docker/docker-compose.yml logs -f
docker compose -f docker/docker-compose.yml logs -f php messenger-worker

docker compose -f docker/docker-compose.yml stop
docker compose -f docker/docker-compose.yml down
```
