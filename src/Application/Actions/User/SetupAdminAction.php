<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class SetupAdminAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE level = 5");
        $stmt->execute();
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            $response->getBody()->write(json_encode(['error' => 'Admin já existe.']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $body = (array)$request->getParsedBody();

        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!$username || !$email || !$password) {
            $response->getBody()->write(json_encode(['error' => 'Dados obrigatórios ausentes.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, level, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            5,
            json_encode([])
        ]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Admin criado com sucesso.']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}