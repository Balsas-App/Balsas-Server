<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;
use App\Application\Helpers\JWT;
use Slim\Psr7\Response as SlimResponse;

class InitBoardingAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $ferry = trim($data['ferry'] ?? '');
        $route = trim($data['route'] ?? false);
        $date_in = trim($data['date_in'] ?? false);

        if (!$ferry || !$route || !$date_in) {
            return $this->error($response, 'Campos obrigatórios ausentes.');
        }

        // Verifica se balsa já tem embarque aberto (sem horário de saída)
        $stmt = $pdo->prepare("
            SELECT b.id as boarding_id, b.init_time as time_in, f.name as ferry_name, r.route as route_name
            FROM boardings b
            JOIN ferries f ON b.ferry = f.id
            JOIN ferry_routes r ON b.route = r.id
            WHERE b.ferry = ? AND b.closed = 0
            LIMIT 1
        ");
        $stmt->execute([$ferry]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            return $this->error($response, 'Esta balsa já está com embarque aberto.', 409, $exists);
        }

        try {
            // Cria novo embarque
            $stmt = $pdo->prepare('INSERT INTO boardings (`ferry`, `route`, `init_time`) VALUES (?, ?, ?)');
            $stmt->execute([$ferry, $route, $date_in]);

            $lastId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'success' => true,
                'boarding_id' => $lastId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            return $this->error($response, 'Erro ao salvar embarque (' . $e->getCode() . ').', 500);
        }
    }

    private function error(Response $response, string $message, int $code = 400, array $boardingData = []): Response
    {
        $payload = ['error' => $message];
        if (!empty($boardingData)) {
            $payload['boarding_id'] = (int) $boardingData['boarding_id'];
            $payload['ferry_name'] = $boardingData['ferry_name'];
            $payload['route_name'] = $boardingData['route_name'];
            $payload['time_in'] = $boardingData['time_in'];
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
