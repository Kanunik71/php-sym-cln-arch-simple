<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Docker Compose injects DATABASE_URL=@mysql into the php service; that shadows
// .env.test (127.0.0.1:3306) and messenger DSN. Tests use mysql + dbname_suffix _test.
if (is_file('/.dockerenv')) {
    $dockerTestEnv = dirname(__DIR__).'/.env.test.docker';
    if (is_file($dockerTestEnv)) {
        (new Dotenv())->overload($dockerTestEnv);
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
