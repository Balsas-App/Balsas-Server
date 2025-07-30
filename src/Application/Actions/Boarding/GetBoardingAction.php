<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetBoardingAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->error($response, 'ID do embarque não informado.', 400);
        }

        $sql = "SELECT 
                    b.id as boarding_id, 
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
                WHERE b.id = ?
                GROUP BY b.id, b.init_time, f.name, r.route
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $boarding = $stmt->fetch();

        if (!$boarding) {
            return $this->error($response, 'Embarque não encontrado.', 404);
        }

        $response->getBody()->write(json_encode($boarding));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
