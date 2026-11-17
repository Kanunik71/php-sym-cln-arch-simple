#!/bin/sh
# Общий хелпер git-хуков (подключается через `. "$HOOK_DIR/lib/common.sh"`).
#
# Выбирает окружение для PHP-инструментов:
#   host   — локальные php + api/vendor (быстро);
#   docker — контейнер cln-simple-php из docker/docker-compose.yml;
#   none   — ничего не доступно, хук пропускает проверки.

ROOT=$(git rev-parse --show-toplevel 2>/dev/null) || {
    echo "git-hook: не удалось определить корень репозитория — пропуск." >&2
    exit 0
}

API="$ROOT/api"
COMPOSE_FILE="$ROOT/docker/docker-compose.yml"
RUNNER=none

has_host_toolchain() {
    command -v php >/dev/null 2>&1 && [ -f "$API/vendor/autoload.php" ]
}

has_docker_php() {
    command -v docker >/dev/null 2>&1 || return 1
    [ -n "$(docker compose -f "$COMPOSE_FILE" ps -q php 2>/dev/null)" ]
}

detect_runner() {
    if has_host_toolchain; then
        RUNNER=host
    elif has_docker_php; then
        RUNNER=docker
    else
        RUNNER=none
    fi
}

# Запускает `php <args>` из каталога api/ (в контейнере это /var/www/html).
run_php_in_api() {
    (
        cd "$API" || exit 1
        case "$RUNNER" in
            host)   php "$@" ;;
            docker) docker compose -f "$COMPOSE_FILE" exec -T php php "$@" ;;
            *)      exit 127 ;;
        esac
    )
}

# php-cs-fixer в режиме проверки. С аргументами — только указанные файлы
# (пути относительно api/), без аргументов — весь проект по конфигу.
cs_fixer_check() {
    if [ "$#" -gt 0 ]; then
        run_php_in_api vendor/bin/php-cs-fixer fix --dry-run --diff \
            --path-mode=intersection --config=.php-cs-fixer.dist.php "$@"
    else
        run_php_in_api vendor/bin/php-cs-fixer fix --dry-run --diff \
            --config=.php-cs-fixer.dist.php
    fi
}

phpstan_analyse() {
    run_php_in_api -d memory_limit=1G vendor/bin/phpstan analyse \
        --configuration=phpstan.neon.dist
}

print_runner_skip() {
    echo "$1: не найдены рабочие PHP-инструменты — нужны локальные" >&2
    echo "  php + api/vendor либо запущенный контейнер cln-simple-php." >&2
    echo "  Проверка пропущена." >&2
}
