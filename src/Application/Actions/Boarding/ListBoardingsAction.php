<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListBoardingsAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $params = $request->getQueryParams();

        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;

        $sql = "SELECT * FROM boardings WHERE 1";
        $bindings = [];

        if ($start) {
            $sql .= " AND init_time >= ?";
            $bindings[] = $start;
        }

        if ($end) {
            $sql .= " AND init_time <= ?";
            $bindings[] = $end;
        }

        $sql .= " ORDER BY init_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        $boardings = $stmt->fetchAll();

        $response->getBody()->write(json_encode($boardings));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
