<?php

namespace App\Application\Actions\Checkin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListCheckinsByBoardingAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $boardingId = $args['boarding_id'] ?? null;

        if (!$boardingId) {
            return $this->error($response, 'ID do embarque não informado.', 400);
        }

        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                f.name AS ferry_name,
                vc.name AS vehicle_category_name,
                vc.id AS vehicle_category_id,
                v.name AS vehicle_name
            FROM 
                checkins c
            LEFT JOIN 
                boardings b ON c.boarding = b.id
            LEFT JOIN 
                ferries f ON b.ferry = f.id
            LEFT JOIN 
                vehicles v ON c.vehicle = v.id
            LEFT JOIN 
                vehicle_categories vc ON v.category = vc.id
            WHERE
                b.id = ?
            ORDER BY c.date_in ASC
        ");
        $stmt->execute([$boardingId]);

        $checkins = $stmt->fetchAll();

        $response->getBody()->write(json_encode($checkins));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code): Response {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
