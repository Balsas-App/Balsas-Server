<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Services\PdfGenerator;
use GuzzleHttp\Client;

class SendReportAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->error($response, 'ID do embarque não informado.', 400);
        }

        $sql = "SELECT 
                    b.id as boarding_id, 
                    b.init_time as time_in, 
                    f.name as ferry_name,
                    f.cnpj as cnpj,
                    f.whatsapp as whatsapp
                FROM boardings b
                JOIN ferries f ON b.ferry = f.id
                WHERE b.id = ?
                GROUP BY b.id, b.init_time, f.name
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$boardingId]);

        $boarding = $stmt->fetch();

        if (!$boarding["whatsapp"]) {
            return $this->error($response, 'Whatsapp não definido.', 400);
        }

        // Gera o PDF
        $pdf = new PdfGenerator();
        $filePath = $pdf->generatePdf($id);

        // Configura o cliente HTTP
        $client = new Client([
            'base_uri' => 'http://whatsapp:3333',
            'timeout'  => 30,
        ]);

        try {
            $res = $client->post('/send-media', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    [
                        'name'     => 'number',
                        'contents' => $boarding['whatsapp'], // <-- telefone fixo
                    ],
                    [
                        'name'     => 'caption',
                        'contents' => 'Segue o relatório em anexo.', // <-- legenda
                    ],
                ],
            ]);

            $body = (string) $res->getBody();
            $decoded = json_decode($body, true);

            $response->getBody()->write(json_encode([
                "success" => true,
                "whatsapp_response" => $decoded ?? $body,
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->error($response, "Falha ao enviar para WhatsApp: " . $e->getMessage(), 500);
        }
    }

    private function error(Response $response, string $message, int $code): Response
    {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
