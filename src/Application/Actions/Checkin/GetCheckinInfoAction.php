<?php

namespace App\Application\Actions\Checkin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetCheckinInfoAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->error($response, 'ID do check-in não informado.', 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM checkins WHERE id = ?");
        $stmt->execute([$id]);

        $checkin = $stmt->fetch();

        if (!$checkin) {
            return $this->error($response, 'Check-in não encontrado.', 404);
        }

        $response->getBody()->write(json_encode($checkin));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code): Response {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
