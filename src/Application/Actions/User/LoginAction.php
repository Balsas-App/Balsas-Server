<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Helpers\JWT;

class LoginAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $body = (array)$request->getParsedBody();

        $email = $body['email'] ?? '';
        $senha = $body['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($senha, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Credenciais inválidas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $accessToken = JWT::generateToken([
            'id' => $user['id'],
            'email' => $user['email'],
            'level' => $user['level'],
            'data' => $user['data'],
        ]);

        $refreshToken = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7);

        $stmt = $pdo->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $accessToken, date('Y-m-d H:i:s', time() + 3600 * 5)]);

        $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $refreshToken, $expiresAt]);

        $response->getBody()->write(json_encode([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
