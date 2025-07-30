<?php

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Helpers\JWT;

class RefreshTokenAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $body = (array)$request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM refresh_tokens WHERE token = ? AND revoked = 0 AND expires_at > NOW()');
        $stmt->execute([$refreshToken]);
        $record = $stmt->fetch();

        if (!$record) {
            $response->getBody()->write(json_encode(['error' => 'Refresh token inválido ou expirado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $pdo->prepare('UPDATE refresh_tokens SET revoked = 1 WHERE id = ?')->execute([$record['id']]);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$record['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não encontrado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $newAccessToken = JWT::generateToken([
            'id' => $user['id'],
            'email' => $user['email'],
            'level' => $user['level'],
            'data' => $user['data'],
        ]);

        $newRefreshToken = bin2hex(random_bytes(64));

        $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$record['user_id'], $newRefreshToken, date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7)]);

        $pdo->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$user['id'], $newAccessToken, date('Y-m-d H:i:s', time() + 60 * 60)]);

        $response->getBody()->write(json_encode([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
