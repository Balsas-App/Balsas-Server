<?php

namespace App\Middleware;

use App\Helpers\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Token ausente ou inválido']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::validateToken($token);
            
            // Pega PDO da container para consultar a tabela tokens
            $pdo = $request->getAttribute('pdo') ?? null;
            if (!$pdo) {
                $response = new SlimResponse();
                $response->getBody()->write(json_encode(['error' => 'PDO não encontrado no container']));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Consulta o token no banco
            $stmt = $pdo->prepare("SELECT * FROM tokens WHERE token = ? AND revoked = 0 AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$token]);
            $tokenRecord = $stmt->fetch();

            if (!$tokenRecord) {
                $response = new SlimResponse();
                $response->getBody()->write(json_encode(['error' => 'Token inválido, revogado ou expirado', 'token' => $tokenRecord]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Passa os dados do usuário adiante
            $request = $request->withAttribute('user', $decoded);
            $request = $request->withAttribute('tokenRecord', $tokenRecord);
            
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Token inválido ou expirado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
