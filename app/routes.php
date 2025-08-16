<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Helpers\JWT;
use App\Application\Middleware\AuthMiddleware;
use Slim\Exception\HttpNotFoundException;

use App\Application\Actions\User\SetupAdminAction;
use App\Application\Actions\User\AddUserAction;
use App\Application\Actions\User\LoginAction;
use App\Application\Actions\User\RefreshTokenAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\Ferry\AddFerryAction;
use App\Application\Actions\Ferry\ListFerriesAction;
use App\Application\Actions\Vehicle\ListVehiclesAction;
use App\Application\Actions\Boarding\InitBoardingAction;
use App\Application\Actions\Boarding\ListRoutesAction;
use App\Application\Actions\Boarding\ListBoardingsAction;
use App\Application\Actions\Boarding\GetBoardingAction;
use App\Application\Actions\Boarding\FinishBoardingAction;
use App\Application\Actions\Boarding\SendReportAction;
use App\Application\Actions\Checkin\CreateCheckinAction;
use App\Application\Actions\Checkin\ListCheckinsByBoardingAction;
use App\Application\Actions\Checkin\GetCheckinInfoAction;

return function (App $app) {

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
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
    $app->get('/boardings/routes', ListRoutesAction::class)->add(new AuthMiddleware());

    $app->get('/boardings', ListBoardingsAction::class)->add(new AuthMiddleware());
    $app->get('/boardings/{id}', GetBoardingAction::class)->add(new AuthMiddleware());
    $app->get('/boardings/{id}/send-report', SendReportAction::class)->add(new AuthMiddleware());
    $app->post('/checkins', CreateCheckinAction::class)->add(new AuthMiddleware());
    $app->get('/boardings/{boarding_id}/checkins', ListCheckinsByBoardingAction::class)->add(new AuthMiddleware());
    $app->get('/checkins/{id}', GetCheckinInfoAction::class)->add(new AuthMiddleware());


    $app->put('/boardings/{id}/finish', FinishBoardingAction::class)->add(new AuthMiddleware());

    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
