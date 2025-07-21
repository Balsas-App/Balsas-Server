<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;
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
        $stmt = $pdo->prepare('SELECT id FROM boardings WHERE `ferry` = ? LIMIT 1');
        $stmt->execute([$ferry]);
        $exists = $stmt->fetch();

        if ($exists > 0) {
            return $this->error($response, 'Esta balsa já está com embarque aberto.', 409, $exists['id']);
        }

        try {
            // Cria novo embarque
            $stmt = $pdo->prepare('INSERT INTO boardings (`ferry`, `route`, `init_time`) VALUES (?, ?, ?)');
            $stmt->execute([
                $ferry,
                $route,
                $date_in
            ]);

            $lastId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'success' => true,
                'boarding_id' => $lastId
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
            return $this->error($response, 'Erro ao salvar embarque (' . $e->getCode() .  ').', 500);
        }
    }

    private function error(Response $response, string $message, int $code = 400, int $boarding_id = 0): Response {

        if($boarding_id){
            $response->getBody()->write(json_encode(['error' => $message, 'boarding_id' => $boarding_id]));
        }else{
            $response->getBody()->write(json_encode(['error' => $message]));
        }
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
