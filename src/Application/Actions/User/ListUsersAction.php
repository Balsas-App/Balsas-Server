<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListUsersAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);

        $stmt = $pdo->query("SELECT id, username, email, level, data FROM users");
        $users = $stmt->fetchAll();

        foreach ($users as &$user) {
            if (isset($user['data'])) {
                $user['data'] = json_decode($user['data'], true);
            }
        }

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
