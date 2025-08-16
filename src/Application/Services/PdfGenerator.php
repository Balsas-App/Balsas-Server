<?php

namespace App\Application\Services;

use PDO;
use Dompdf\Dompdf;

class PdfGenerator
{
    public function generateCheckinsTable(int $boarding): string
    {
        $pdo = $GLOBALS['container']->get(PDO::class);
        if (!$boarding) {
            return "";
        }

        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                f.name AS ferry_name,
                vc.name AS vehicle_category_name,
                vc.id AS vehicle_category_id,
                v.name AS vehicle_name
            FROM 
                checkins c
            LEFT JOIN 
                boardings b ON c.boarding = b.id
            LEFT JOIN 
                ferries f ON b.ferry = f.id
            LEFT JOIN 
                vehicles v ON c.vehicle = v.id
            LEFT JOIN 
                vehicle_categories vc ON v.category = vc.id
            WHERE
                b.id = ?
            ORDER BY c.date_in ASC
        ");
        $stmt->execute([$boarding]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Se não houver registros, retorna algo simples (evita PDF em branco)
        if (!$rows) {
            return '<p style="font-family:Arial, sans-serif; font-size:12px;">Sem registros.</p>';
        }

        // Utilitários
        $money = fn($v) => "R$ " . number_format((float)$v, 2, ",", ".");
        $esc   = fn($s) => htmlspecialchars((string)$s ?? "", ENT_QUOTES, 'UTF-8');
// Agrupamentos
        $categories = [];
// [category_name => [rows...]] (somente não reembolsados)
        $additional = [];
// rows com add_value > 0 e não reembolsados
        $refunds    = [];
// rows com refunded = 1

        foreach ($rows as $r) {
            if (!empty($r['refunded'])) {
                $refunds[] = $r;
                continue;
            }
            // Base por categoria
            $cat = $r['vehicle_category_name'] ?? 'Sem categoria';
            $categories[$cat][] = $r;
// Valores adicionais
            if (!empty($r['add_value']) && (float)$r['add_value'] > 0) {
                $additional[] = $r;
            }
        }

        // Totais
        $baseTotal = 0.0;
// soma dos 'value' por categoria
        $addTotal  = 0.0;
// soma dos 'add_value'
        $refTotal  = 0.0;
// soma dos 'value' a reembolsar
        foreach ($categories as $catRows) {
            foreach ($catRows as $r) {
                $baseTotal += (float)$r['value'];
            }
        }
        foreach ($additional as $r) {
            $addTotal += (float)$r['add_value'];
        }
        foreach ($refunds as $r) {
            $refTotal += (float)$r['value'];
        }

        $grandTotal = $baseTotal + $addTotal - $refTotal;
// HTML (layout igual ao modelo anterior)
        $html = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .content { margin: 0 30px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; }
            th { background: #f5f5f5; text-align: left; }
            .cat { background:#fafafa; font-weight:bold; }
            .right { text-align: right; }
            .center { text-align: center; }
            .total-row { background:#f5f5f5; font-weight:bold; }
            .neg { color: red; }
            small { color:#666; }
        </style>';
        $html .= '<div class="content"><table>
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Veículo</th>
                    <th class="center">Pax</th>
                    <th class="right">Pago</th>
                </tr>
            </thead>
            <tbody>';
// Categorias
        foreach ($categories as $catName => $items) {
            $html .= '<tr><td class="cat" colspan="4">' . $esc($catName) . '</td></tr>';
            $catSubtotal = 0.0;
            foreach ($items as $i) {
                $catSubtotal += (float)$i['value'];
                $html .= '<tr>
                    <td>' . $esc($i['plate']) . '</td>
                    <td>' . $esc($i['vehicle_name']) . '</td>
                    <td class="center">' . $esc($i['pax']) . '</td>
                    <td class="right">' . $money($i['value']) . '</td>
                </tr>';
            }

            $html .= '<tr>
                <td colspan="3" class="right" style="font-weight:bold;">Valor categoria</td>
                <td class="right" style="font-weight:bold;">' . $money($catSubtotal) . '</td>
            </tr>';
        }

        // Valores adicionais
        if (!empty($additional)) {
            $html .= '<tr><td class="cat" colspan="4">Valores adicionais</td></tr>';
            $sumAdd = 0.0;
            foreach ($additional as $i) {
                $sumAdd += (float)$i['add_value'];
                $reason = trim((string)($i['add_value_reason'] ?? ""));
                $html .= '<tr>
                    <td>' . $esc($i['plate']) . '</td>
                    <td>' . $esc($i['vehicle_name']) . ($reason !== '' ? '<br><small>Motivo: ' . $esc($reason) . '</small>' : '') . '</td>
                    <td class="center">' . $esc($i['pax']) . '</td>
                    <td class="right">' . $money($i['add_value']) . '</td>
                </tr>';
            }
            $html .= '<tr>
                <td colspan="3" class="right" style="font-weight:bold;">Valores adicionais total</td>
                <td class="right" style="font-weight:bold;">' . $money($sumAdd) . '</td>
            </tr>';
        }

        // Reembolsos
        if (!empty($refunds)) {
            $html .= '<tr><td class="cat" colspan="4">Reembolso</td></tr>';
            foreach ($refunds as $i) {
                $html .= '<tr>
                    <td>' . $esc($i['plate']) . '</td>
                    <td>' . $esc($i['vehicle_name']) . '</td>
                    <td class="center">' . $esc($i['pax']) . '</td>
                    <td class="right neg">- ' . $money($i['value']) . '</td>
                </tr>';
            }
        }

        // Total geral
        $html .= '<tr class="total-row">
            <td colspan="3" class="right">TOTAL</td>
            <td class="right">' . $money($grandTotal) . '</td>
        </tr>';
        $html .= '</tbody></table></div>';
        return $html;
    }



    public function generatePdf(int $boardingId): string
    {
        $dompdf = new Dompdf();
        $table = $this->generateCheckinsTable($boardingId);
        $header = file_get_contents(__DIR__ . "/../../Assets/PdfComponents/header.html");
        $body = file_get_contents(__DIR__ . "/../../Assets/PdfComponents/body.html");
        $dompdf->set_option("isPhpEnabled", true);
        $header_args = [
            "page-break-before: always;" => "",
        ];
        $documentTemplate = str_replace(array_keys($header_args), array_values($header_args), $header);
        $documentTemplate .= $table;
        $html = str_replace("{{body}}", $documentTemplate, $body);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();
        $fileName = uniqid('report_', true) . ".pdf";
        $filePath = __DIR__ . "/../../../public/storage/pdfs/" . $fileName;
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($filePath, $output);
        return $filePath;
    }
}
