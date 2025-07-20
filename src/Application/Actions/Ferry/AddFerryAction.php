<?php

namespace App\Application\Actions\Ferry;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Helpers\JWT;
use Slim\Psr7\Response as SlimResponse;

class AddFerryAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $name = trim($data['name'] ?? '');
        $owner = trim($data['owner'] ?? false);

        if (!$name || !$owner) {
            return $this->error($response, 'Campos obrigatórios ausentes.');
        }

        // Verifica se balsa já existe
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ferries WHERE `name` = ?');
        $stmt->execute([$name]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            return $this->error($response, 'Uma balsa com este nome já existe.', 409);
        }

        // Cria novo usuário
        $stmt = $pdo->prepare('INSERT INTO ferries (`name`, `owner`) VALUES (?, ?)');
        $stmt->execute([
            $name,
            $owner
        ]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code = 400): Response {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
