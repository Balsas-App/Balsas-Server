<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListUsersAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);

        $stmt = $pdo->query("SELECT id, username, email, level, data FROM users");
        $users = $stmt->fetchAll();

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
