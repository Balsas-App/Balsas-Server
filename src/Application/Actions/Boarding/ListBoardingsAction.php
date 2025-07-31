<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListBoardingsAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $params = $request->getQueryParams();

        $start = $params['start'] ?? null;
        $end = $params['end'] ?? null;
        $closed = $params['closed'] ?? null;
        $open = $params['open'] ?? null;

        $sql = "SELECT b.id as boarding_id, 
            b.init_time as time_in, 
            f.name as ferry_name, 
            r.route as route_name,
            COUNT(c.id) as checkins_count,
            b.closed,
            b.agent as agent_id,
            u.username as agent_username,
            u.data as agent_data
        FROM boardings b
        JOIN ferries f ON b.ferry = f.id
        JOIN ferry_routes r ON b.route = r.id
        JOIN users u ON b.agent = u.id
        LEFT JOIN checkins c ON c.boarding = b.id
        WHERE 1";

        $bindings = [];

        if ($start) {
            $sql .= " AND b.init_time >= ?";
            $bindings[] = $start;
        }

        if ($end) {
            $sql .= " AND b.init_time <= ?";
            $bindings[] = $end;
        }

        if ($closed) {
            $sql .= " AND b.closed = ?";
            $bindings[] = 1;
        }

        if ($open) {
            $sql .= " AND b.closed = ?";
            $bindings[] = 0;
        }

        $sql .= " GROUP BY b.id, b.init_time, f.name, r.route
                ORDER BY b.init_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        $boardings = $stmt->fetchAll();

        $response->getBody()->write(json_encode($boardings));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
