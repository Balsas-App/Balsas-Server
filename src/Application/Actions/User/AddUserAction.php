<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Helpers\JWT;
use Slim\Psr7\Response as SlimResponse;

class AddUserAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $level = $data['level'] ?? 1;
        $extra = $data['data'] ?? [];

        if (!$username || !$email || !$password) {
            return $this->error($response, 'Campos obrigatórios ausentes.');
        }

        // Verifica se username ou email já existem
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            return $this->error($response, 'Usuário com este username ou email já existe.', 409);
        }

        // Cria novo usuário
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, level, data) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $level,
            json_encode($extra),
        ]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code = 400): Response {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
