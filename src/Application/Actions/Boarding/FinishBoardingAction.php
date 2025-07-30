<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class FinishBoardingAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->error($response, 'ID do embarque não informado.', 400);
        }

        // Verifica se o embarque existe
        $stmt = $pdo->prepare("SELECT * FROM boardings WHERE id = ?");
        $stmt->execute([$id]);
        $boarding = $stmt->fetch();

        if (!$boarding) {
            return $this->error($response, 'Embarque não encontrado.', 404);
        }

        // Atualiza o campo `closed` para 1
        $update = $pdo->prepare("UPDATE boardings SET closed = 1 WHERE id = ?");
        $update->execute([$id]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Embarque finalizado com sucesso.',
            'boarding_id' => $id
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
