<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListRoutesAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);

        $stmt = $pdo->query("SELECT `id`, `route` FROM ferry_routes");
        $users = $stmt->fetchAll();

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
