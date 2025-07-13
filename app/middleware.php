<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use App\Middleware\DbMiddleware;
use Slim\App;

return function (App $app, $container) {
    $app->add(new DbMiddleware($container));
    $app->add(SessionMiddleware::class);
};
