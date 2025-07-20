<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Helpers\JWT;
use App\Middleware\AuthMiddleware;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->post('/setup-admin', function ($request, $response) {
        $pdo = $this->get(PDO::class);

        // Verifica se já existe um admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE level = 5");
        $stmt->execute();
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            $response->getBody()->write(json_encode(['error' => 'Admin já existe.']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Pega os dados do corpo da requisição
        $body = (array) $request->getParsedBody();

        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        // Validação simples
        if (!$username || !$email || !$password) {
            $response->getBody()->write(json_encode(['error' => 'Dados obrigatórios ausentes.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Cria o admin
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, level, data)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            5,
            json_encode([])
        ]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Admin criado com sucesso.']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/refresh-token', function ($request, $response) {
        $pdo = $this->get(PDO::class);
        $body = (array)$request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM refresh_tokens WHERE token = ? AND revoked = 0 AND expires_at > NOW()');
        $stmt->execute([$refreshToken]);
        $record = $stmt->fetch();

        if (!$record) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => 'Refresh token inválido ou expirado']));
        }

        // (opcional) Revoga o antigo
        $pdo->prepare('UPDATE refresh_tokens SET revoked = 1 WHERE id = ?')->execute([$record['id']]);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$record['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => 'Usuário não encontrado']));
        }

        // Gera novo access e refresh
        $newAccessToken = \App\Helpers\JWT::generateToken([
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
    });

    $app->post('/login', function ($request, $response) {
        $pdo = $this->get(PDO::class);
        $body = (array) $request->getParsedBody();

        $email = $body['email'] ?? '';
        $senha = $body['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($senha, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Credenciais inválidas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // 1. Gera o access token (curto prazo, 15min ou 1h)
        $accessToken = \App\Helpers\JWT::generateToken([
            'id' => $user['id'],
            'email' => $user['email'],
            'level' => $user['level'],
            'data' => $user['data'],
        ]);

        // 2. Gera o refresh token (string aleatória)
        $refreshToken = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7); // 7 dias

        // 3. Salva no banco
        $stmt = $pdo->prepare("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $accessToken, date('Y-m-d H:i:s', time() + 3600)]);

        
        $stmt = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $refreshToken, $expiresAt]);

        // 4. Retorna os dois tokens
        $response->getBody()->write(json_encode([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 60 * 60 * 1, // opcional: tempo do access token em segundos
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });


    $app->get('/users', function (Request $request, Response $response) {
        $pdo = $this->get(PDO::class);

        $stmt = $pdo->query("SELECT `id`, `username`, `email`, `level`, `created_at` FROM users");
        $users = $stmt->fetchAll();

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    $app->post('/users', function (Request $request, Response $response) {
        $pdo = $this->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $level = $data['level'] ?? 1;
        $extra = $data['data'] ?? [];

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
    })->add(new AuthMiddleware());
};
