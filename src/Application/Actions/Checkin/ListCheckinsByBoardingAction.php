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

        $stmt = $pdo->prepare("SELECT * FROM checkins WHERE boarding = ? ORDER BY date_in ASC");
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
