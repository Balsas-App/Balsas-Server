<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Helpers\JWT;
use App\Application\Middleware\AuthMiddleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

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
use App\Application\Actions\Checkin\CreateCheckinAction;
use App\Application\Actions\Checkin\ListCheckinsByBoardingAction;
use App\Application\Actions\Checkin\GetCheckinInfoAction;

return function (App $app) {

    $app->add(function (Request $request, RequestHandler $handler): Response {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response(200);
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        }

        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
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

    
    $app->group('', function (Group $group) {
        // Listar boardings (com filtro de data opcional ?start=...&end=...)
        $group->get('/boardings', ListBoardingsAction::class);
        $group->get('/boardings/{id}', GetBoardingAction::class);

        // Criar novo check-in
        $group->post('/checkins', CreateCheckinAction::class);

        // Listar check-ins de um boarding específico
        $group->get('/boardings/{boarding_id}/checkins', ListCheckinsByBoardingAction::class);

        // Obter detalhes de um check-in específico
        $group->get('/checkins/{id}', GetCheckinInfoAction::class);
    })->add(new AuthMiddleware());

    $app->put('/boardings/{id}/finish', FinishBoardingAction::class)->add(new AuthMiddleware());
};
