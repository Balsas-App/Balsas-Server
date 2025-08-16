<?php

namespace App\Application\Actions\Boarding;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Application\Services\PdfGenerator;

class SendReportAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->error($response, 'ID do embarque não informado.', 400);
        }

        $pdf = new PdfGenerator();
        $pdf->generatePdf($id);

        $response->getBody()->write(json_encode(array(
            "success" => true
        )));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function error(Response $response, string $message, int $code): Response
    {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $message]));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
