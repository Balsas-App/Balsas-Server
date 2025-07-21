<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Helpers\JWT;
use Slim\Psr7\Response as SlimResponse;

class InitBoardingAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $ferry = trim($data['ferry'] ?? '');
        $route = trim($data['route'] ?? false);
        $date_in = trim($data['date_in'] ?? false);

        if (!$ferry || !$route || !$date_in) {
            return $this->error($response, 'Campos obrigatórios ausentes.');
        }

        // Verifica se balsa já existe
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM boardings WHERE `ferry` = ?');
        $stmt->execute([$ferry]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            return $this->error($response, 'Esta balsa já está com embarque aberto.', 409);
        }

        // Cria novo usuário
        $stmt = $pdo->prepare('INSERT INTO boardings (`ferry`, `route`, `init_time`) VALUES (?, ?, ?)');
        $stmt->execute([
            $ferry,
            $route,
            $date_in
        ]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code = 400): Response {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
