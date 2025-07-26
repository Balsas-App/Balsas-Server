<?php

namespace App\Application\Actions\Checkin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;

class CreateCheckinAction {
    public function __invoke(Request $request, Response $response, array $args): Response {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $data = (array) $request->getParsedBody();

        $boarding = $data['boarding'] ?? null;
        $plate = trim($data['plate'] ?? '');
        $pax = $data['pax'] ?? null;
        $vehicle = $data['vehicle'] ?? null;
        $value = $data['value'] ?? null;
        $add_value = $data['add_value'] ?? null;
        $observation = trim($data['observation'] ?? '');
        $add_value_reason = trim($data['add_value_reason'] ?? '');

        if (!$boarding || !$plate) {
            return $this->error($response, 'ID de embarque e placa são obrigatórios.');
        }

        try {
            // Verifica se já existe check-in com a mesma placa nesse embarque
            $check = $pdo->prepare("SELECT id FROM checkins WHERE boarding = ? AND plate = ?");
            $check->execute([$boarding, $plate]);
            $checkin_data = $check->fetch();
            if ($checkin_data) {
                return $this->error($response, 'Veículo '.$plate.' já embarcado.', $checkin_data['id'], 409);
            }

            // Executa o INSERT normalmente
            $stmt = $pdo->prepare("
                INSERT INTO checkins (`boarding`, `plate`, `pax`, `vehicle`, `value`, `add_value`, `observation`, `add_value_reason`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $boarding, $plate, $pax, $vehicle, $value, $add_value, $observation, $add_value_reason
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'checkin_id' => $pdo->lastInsertId()
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
            return $this->error($response, 'Erro ao salvar check-in (' . $e->getCode() . ')', 0, 500);
        }
    }

    private function error(Response $response, string $message, int $checkin_id, int $code = 400): Response {
        if($checkin_id){
            $response->getBody()->write(json_encode(['error' => $message, 'checkin_id' => $checkin_id]));
        }else{
            $response->getBody()->write(json_encode(['error' => $message]));
        }
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
