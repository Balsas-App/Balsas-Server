<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Helpers\JWT;
use App\Application\Middleware\AuthMiddleware;

use App\Application\Actions\User\SetupAdminAction;
use App\Application\Actions\User\AddUserAction;
use App\Application\Actions\User\LoginAction;
use App\Application\Actions\User\RefreshTokenAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\Ferry\AddFerryAction;
use App\Application\Actions\Ferry\ListFerriesAction;
use App\Application\Actions\Vehicle\ListVehiclesAction;
use App\Application\Actions\Boarding\InitBoardingAction;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Balsas REST API');
        return $response;
    });

    $app->post('/setup-admin', SetupAdminAction::class);

    $app->post('/refresh-token', RefreshTokenAction::class);

    $app->post('/login', LoginAction::class);

    $app->get('/users', ListUsersAction::class)->add(new AuthMiddleware());
    $app->post('/users', AddUserAction::class)->add(new AuthMiddleware(5));

    $app->get('/ferries', ListFerriesAction::class)->add(new AuthMiddleware());
    $app->post('/ferries', AddFerryAction::class)->add(new AuthMiddleware(5));

    $app->get('/vehicles', ListVehiclesAction::class)->add(new AuthMiddleware());

    $app->post('/boardings', InitBoardingAction::class)->add(new AuthMiddleware());
};
