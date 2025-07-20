<?php

namespace App\Application\Middleware;

use App\Application\Helpers\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware {
    private ?int $minLevel;

    public function __construct(int $minLevel = null) {
        $this->minLevel = $minLevel;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token ausente ou inválido');
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::validateToken($token);

            // Pega PDO da container para consultar a tabela tokens
            $pdo = $request->getAttribute('pdo') ?? null;
            if (!$pdo) {
                return $this->error('PDO não encontrado no container', 500);
            }

            // Consulta o token no banco
            $stmt = $pdo->prepare("SELECT * FROM tokens WHERE token = ? AND revoked = 0 AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$token]);
            $tokenRecord = $stmt->fetch();

            if (!$tokenRecord) {
                return $this->unauthorized('Token inválido, revogado ou expirado');
            }

            // Verifica o level do usuário se necessário
            if ($this->minLevel !== null) {
                $userLevel = $decoded->level ?? 0;
                if ($userLevel < $this->minLevel) {
                    return $this->forbidden('Permissão insuficiente');
                }
            }

            // Passa os dados do usuário adiante
            $request = $request->withAttribute('user', $decoded);
            $request = $request->withAttribute('tokenRecord', $tokenRecord);

            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorized('Token inválido ou expirado');
        }
    }

    private function unauthorized(string $message): Response {
        return $this->error($message, 401);
    }

    private function forbidden(string $message): Response {
        return $this->error($message, 403);
    }

    private function error(string $message, int $code): Response {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
