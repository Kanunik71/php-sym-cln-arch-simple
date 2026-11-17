#Requires -Version 5.1
<#
.SYNOPSIS
    Полная локальная установка проекта cln-arch-sym-simple.

.DESCRIPTION
    Проверяет окружение, включает git-хуки, ставит PHP-зависимости на хосте
    (если есть php + composer) и поднимает Docker-стек. Зависимости в контейнере
    и миграции выполняет entrypoint контейнера php.

.PARAMETER WithWorkers
    Поднять messenger-worker (профиль workers).

.PARAMETER SkipComposer
    Не запускать composer install на хосте (vendor соберётся в контейнере).

.PARAMETER Build
    Пересобрать образы (--build). Без флага образ собирается только если его нет.

.PARAMETER TimeoutSeconds
    Сколько секунд ждать готовности контейнеров. По умолчанию 600.

.EXAMPLE
    .\build.ps1

.EXAMPLE
    .\build.ps1 -WithWorkers
#>
[CmdletBinding()]
param(
    [switch]$WithWorkers,
    [switch]$SkipComposer,
    [switch]$Build,
    [int]$TimeoutSeconds = 600
)

$ErrorActionPreference = 'Stop'

# При перенаправленном выводе (лог, CI, tee) Windows PowerShell 5.1 пишет в
# кодировке консоли и теряет кириллицу — принудительно UTF-8.
if ([Console]::IsOutputRedirected) {
    [Console]::OutputEncoding = New-Object System.Text.UTF8Encoding $false
}

$RepoRoot = $PSScriptRoot
$ComposeFile = Join-Path $RepoRoot 'docker\docker-compose.yml'
$ApiDir = Join-Path $RepoRoot 'api'
$ApiUrl = 'http://localhost:8080'

function Write-Step {
    param([string]$Message)
    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "    $Message" -ForegroundColor Green
}

function Write-Note {
    param([string]$Message)
    Write-Host "    $Message" -ForegroundColor DarkGray
}

function Write-Warn {
    param([string]$Message)
    Write-Host "    $Message" -ForegroundColor Yellow
}

function Stop-WithError {
    param([string]$Message)
    Write-Host ''
    Write-Host "ОШИБКА: $Message" -ForegroundColor Red
    exit 1
}

Set-Location $RepoRoot

# --- Проверка окружения ------------------------------------------------------

Write-Step 'Проверка окружения'

if (-not (Test-Path $ComposeFile)) {
    Stop-WithError "не найден $ComposeFile — запускайте скрипт из корня репозитория."
}
if (-not (Test-Path (Join-Path $ApiDir 'composer.json'))) {
    Stop-WithError "не найден $ApiDir\composer.json — запускайте скрипт из корня репозитория."
}

foreach ($tool in @('git', 'docker')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        Stop-WithError "не найдена команда '$tool'. Установите Git и Docker Desktop."
    }
}

& docker info --format '{{.ServerVersion}}' *> $null
if ($LASTEXITCODE -ne 0) {
    Stop-WithError 'Docker daemon недоступен — запустите Docker Desktop и повторите.'
}

& docker compose version *> $null
if ($LASTEXITCODE -ne 0) {
    Stop-WithError 'Docker Compose v2 недоступен: нужна команда "docker compose".'
}

if (-not (Test-Path (Join-Path $ApiDir '.env'))) {
    Write-Warn 'api/.env не найден — приложение может не подняться (файл должен быть в git).'
}

Write-Ok 'git и Docker готовы'

# --- Git-хуки ----------------------------------------------------------------

Write-Step 'Git-хуки'

& git -C $RepoRoot config core.hooksPath .githooks
if ($LASTEXITCODE -ne 0) {
    Stop-WithError 'не удалось настроить core.hooksPath.'
}

Write-Ok 'core.hooksPath = .githooks'
Write-Note 'pre-commit: php-cs-fixer по staged-файлам api/, pre-push: cs-check + phpstan'

# --- PHP-зависимости на хосте ------------------------------------------------

Write-Step 'PHP-зависимости'

$hasPhp = Get-Command php -ErrorAction SilentlyContinue
$hasComposer = Get-Command composer -ErrorAction SilentlyContinue

if ($SkipComposer) {
    Write-Note 'пропущено (-SkipComposer): vendor соберётся в контейнере'
}
elseif ($hasPhp -and $hasComposer) {
    Push-Location $ApiDir
    try {
        & composer install --no-interaction --prefer-dist
        $composerExit = $LASTEXITCODE
    }
    finally {
        Pop-Location
    }

    if ($composerExit -ne 0) {
        Stop-WithError 'composer install завершился с ошибкой.'
    }

    Write-Ok 'api/vendor готов — хуки и IDE используют локальный PHP'
}
else {
    Write-Warn 'локальные php/composer не найдены — vendor соберётся в контейнере,'
    Write-Warn 'а git-хуки будут запускать инструменты через docker compose exec php.'
}

# --- Docker ------------------------------------------------------------------

Write-Step 'Docker: сборка и запуск'

$composeArgs = @('compose', '-f', $ComposeFile)
if ($WithWorkers) {
    $composeArgs += @('--profile', 'workers')
}
$composeArgs += @('up', '-d')
if ($Build) {
    $composeArgs += '--build'
}
$composeArgs += @('--wait', '--wait-timeout', "$TimeoutSeconds")

Write-Note ('docker ' + ($composeArgs -join ' '))

& docker @composeArgs
if ($LASTEXITCODE -ne 0) {
    Write-Host ''
    Write-Host 'Логи контейнера php:' -ForegroundColor Yellow
    & docker compose -f $ComposeFile logs --tail 50 php
    Stop-WithError "docker compose up завершился с ошибкой. Полные логи: docker compose -f docker/docker-compose.yml logs php"
}

Write-Ok 'контейнеры запущены, php healthy (composer install и миграции выполнены entrypoint-ом)'

# --- Проверка API ------------------------------------------------------------

Write-Step 'Проверка API'

$status = 0
for ($attempt = 1; $attempt -le 10; $attempt++) {
    $status = 0
    try {
        $response = Invoke-WebRequest -Uri "$ApiUrl/api/assets" -UseBasicParsing -TimeoutSec 10
        $status = [int]$response.StatusCode
    }
    catch {
        $webResponse = $_.Exception.Response
        if ($null -ne $webResponse) {
            $status = [int]$webResponse.StatusCode
        }
    }

    if ($status -ne 0) {
        break
    }

    # nginx стартует без healthcheck — даём ему пару секунд.
    Start-Sleep -Milliseconds 500
}

if ($status -eq 401 -or $status -eq 200) {
    Write-Ok "API отвечает: $ApiUrl (HTTP $status)"
}
elseif ($status -gt 0) {
    Write-Warn "API отвечает HTTP $status на $ApiUrl/api/assets — проверьте логи, если это неожиданно."
}
else {
    Write-Warn "API пока не отвечает на $ApiUrl — смотрите: docker compose -f docker/docker-compose.yml logs php"
}

# --- Итог --------------------------------------------------------------------

Write-Step 'Статус'
& docker compose -f $ComposeFile ps

Write-Host ''
Write-Host 'Проект готов к работе.' -ForegroundColor Green
Write-Host "  API:         $ApiUrl"
Write-Host '  PostgreSQL:  127.0.0.1:5432 (app / app)'
Write-Host '  Redis:       127.0.0.1:6380'
Write-Host '  RabbitMQ:    127.0.0.1:5673, UI http://localhost:15673 (guest / guest)'
Write-Host ''
Write-Host 'Полезные команды:'
Write-Host '  docker compose -f docker/docker-compose.yml ps'
Write-Host '  docker compose -f docker/docker-compose.yml logs -f php'
Write-Host '  docker compose -f docker/docker-compose.yml exec php php bin/phpunit'
Write-Host '  cd api; composer cs-fix'
Write-Host '  docker compose -f docker/docker-compose.yml down'

if (-not $WithWorkers) {
    Write-Host ''
    Write-Host '  messenger-worker не запущен: .\build.ps1 -WithWorkers' -ForegroundColor DarkGray
}

exit 0
