<?php

namespace App\apps\core\Service\TipoCambioSunat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TipoCambioSunatService
{
    private string $url = 'https://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function obtenerTipoCambio(?string $fecha = null): array
    {
        $fechaConsulta = $fecha ?? date('Y-m-d');

        try {
            $response = $this->httpClient->request('GET', $this->url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => 15,
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $html = $response->getContent(false);

            return $this->extraerTipoCambio($html, $fechaConsulta);
        } catch (\Throwable $e) {
            $this->logger->error('Error obteniendo tipo de cambio SUNAT', ['error' => $e->getMessage()]);
            throw new \RuntimeException('No se pudo obtener el tipo de cambio de SUNAT: ' . $e->getMessage());
        }
    }

    private function extraerTipoCambio(string $html, string $fecha): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $compra = null;
        $venta = null;

        $rows = $xpath->query('//table//tr');
        foreach ($rows as $row) {
            $cells = $xpath->query('td', $row);
            if ($cells->length >= 3) {
                $moneda = trim($cells->item(0)->textContent ?? '');
                if (stripos($moneda, 'DOLAR') !== false || stripos($moneda, 'USD') !== false || trim($moneda) === 'Dólar') {
                    $compraText = trim($cells->item(1)->textContent ?? '');
                    $ventaText = trim($cells->item(2)->textContent ?? '');
                    $compra = (float) str_replace(',', '.', $compraText);
                    $venta = (float) str_replace(',', '.', $ventaText);
                    break;
                }
            }
        }

        if ($compra === null) {
            if (preg_match('/D[oó]lar[^<]*<\/[^>]+>\s*<[^>]+>\s*([\d.,]+)\s*<\/[^>]+>\s*<[^>]+>\s*([\d.,]+)/i', $html, $m)) {
                $compra = (float) str_replace(',', '.', $m[1]);
                $venta = (float) str_replace(',', '.', $m[2]);
            }
        }

        if ($compra === null) {
            throw new \RuntimeException('No se encontró el tipo de cambio del dólar en la respuesta de SUNAT');
        }

        return [
            'compra' => $compra,
            'venta' => $venta,
            'fecha' => $fecha,
        ];
    }
}
