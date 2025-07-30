<?php

namespace App\Application\Actions\Vehicle;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListVehiclesAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);

        $query = "
            SELECT
                v.id,
                vc.name AS category,
                v.name AS model,
                v.tax AS value
            FROM vehicle_categories vc
            LEFT JOIN vehicles v ON v.category = vc.id
            ORDER BY vc.name, v.name
        ";

        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($results as $row) {
            if (!$row['model']) {
                continue; // ignora categorias sem veículos
            }

            $type = $row['category'];

            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'models' => []
                ];
            }

            $grouped[$type]['models'][] = [
                'id' => $row['id'],
                'name' => $row['model'],
                'value' => $row['value']
            ];
        }

        $output = array_values($grouped);

        $response->getBody()->write(json_encode($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
