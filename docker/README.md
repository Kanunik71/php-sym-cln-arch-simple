# Локальный Docker-стек

При старте `cln-simple-php` entrypoint сам делает `composer install` и `doctrine:migrations:migrate`. Workers ждут healthy PHP и только поднимают consume.

## Запуск с нуля

Из корня репозитория:

```bash
# API (PostgreSQL + Redis + RabbitMQ): composer + migrate автоматически
docker compose -f docker/docker-compose.yml up -d --build

# Со workers
docker compose -f docker/docker-compose.yml --profile workers up -d --build
```

PostgreSQL 16 публикуется на `5432`, данные лежат в томе `cln-simple-postgres-data`. `docker/postgres/init.sql` (создаёт БД `app_test` для интеграционных тестов) выполняется **только при первой инициализации тома**: после правки скрипта нужен `docker compose -f docker/docker-compose.yml down -v`.

Для подключения с хоста: `postgresql://app:app@127.0.0.1:5432/app`.

## Окружения и секреты

- **dev/test**: значения по умолчанию закоммичены в `api/.env`, `api/.env.dev`, `api/.env.test`; Docker-стек дополнительно прокидывает их через `environment:` в `docker-compose.yml`. Личные переопределения — `api/.env.local` (в gitignore).
- **prod**: `APP_ENV=prod` и `APP_DEBUG=0` обязаны приходить из окружения процесса (systemd / compose / k8s), а не из файла. В `api/.env` закоммичено `APP_ENV=dev`, поэтому без явной переменной приложение поднимется в dev и не прочитает `.env.prod`.
- **Секреты prod** (`APP_SECRET`, `JWT_SECRET`, пароли PostgreSQL/Redis/RabbitMQ/SMTP) в git не попадают: `api/.env.prod` лежит на сервере (права `600`, владелец — deploy-пользователь) либо приходит переменными окружения. `.env.prod` и `.env.stage` добавлены в `api/.gitignore` — проверить: `git check-ignore -v api/.env.prod`.
- **Symfony Secrets Vault** (`config/secrets/`) намеренно не используется: он нужен, когда зашифрованные секреты надо хранить в git, а здесь они доставляются на сервер окружением.

Порядок приоритета в Symfony: `api/.env` → `api/.env.local` → `api/.env.$APP_ENV` → `api/.env.$APP_ENV.local`; уже существующие переменные окружения процесса всегда выигрывают у файлов.

## Деплой в prod

```bash
cd api
composer install --no-dev --optimize-autoloader
composer deploy:prod        # dump-env prod + cache:clear/cache:warmup --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

`composer deploy:prod` вызывает `composer dump-env prod`: содержимое dotenv-файлов один раз фиксируется в сгенерированном `api/.env.local.php` (в git его нет), и в рантайме парсинг не нужен. Значения, пришедшие реальными переменными окружения, `dump-env` не фиксирует — они сохраняют приоритет.

Все процессы (`php`, `messenger-worker`, scheduler/cron) должны получать одинаковый набор переменных, включая `APP_ENV`: сейчас в `docker/docker-compose.yml` он не задан нигде, в prod его нужно добавить во все сервисы.

Чек-лист перед первым prod-деплоем:

- [ ] `APP_ENV=prod`, `APP_DEBUG=0` во всех сервисах;
- [ ] `APP_SECRET` и `JWT_SECRET` сгенерированы (`php -r "echo bin2hex(random_bytes(32));"`), уникальны для каждого окружения;
- [ ] пароль PostgreSQL не `app/app`, пароли Redis/RabbitMQ не дефолтные;
- [ ] `MAILER_DSN` / `MAILER_FROM` с боевыми значениями, если письма нужны;
- [ ] `CORS_ALLOW_ORIGIN` и `DEFAULT_URI` указывают на боевые домены;
- [ ] `git status` чист: `.env.prod`, `.env.local.php` не попадают в репозиторий.

## Остановить / логи

```bash
docker compose -f docker/docker-compose.yml ps
docker compose -f docker/docker-compose.yml logs -f php

docker compose -f docker/docker-compose.yml logs -f
docker compose -f docker/docker-compose.yml logs -f php messenger-worker

docker compose -f docker/docker-compose.yml stop
docker compose -f docker/docker-compose.yml down
```
