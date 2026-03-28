<?php

namespace App\apps\core\Service\TipoCambioSunat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Obtiene tipos de cambio USD/PEN desde apis.net.pe (que a su vez consulta SUNAT).
 * La API de SUNAT directa requiere reCAPTCHA v3 generado por un navegador real,
 * por lo que no es accesible desde el servidor.
 */
class TipoCambioSunatService
{
    private string $apiBase = 'https://api.apis.net.pe/v1/tipo-cambio-sunat';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene el tipo de cambio de una fecha específica (o hoy si no se indica).
     * Si la fecha no tiene cotización (fin de semana/feriado), devuelve el último disponible.
     */
    public function obtenerTipoCambio(?string $fecha = null): array
    {
        $fechaConsulta = $fecha ?? date('Y-m-d');
        return $this->fetchDia($fechaConsulta);
    }

    /**
     * Obtiene todos los tipos de cambio de un mes completo.
     * Hace peticiones concurrentes para cada día del mes y filtra
     * solo los días que tienen cotización real (excluye fines de semana y feriados).
     */
    public function obtenerMes(int $anio, int $mes): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $today       = date('Y-m-d');
        $results     = [];

        // Lanzar todas las peticiones del mes de forma concurrente
        $responses = [];
        for ($dia = 1; $dia <= $daysInMonth; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            if ($fecha > $today) {
                break;
            }
            $responses[$fecha] = $this->httpClient->request('GET', $this->apiBase, [
                'query'   => ['fecha' => $fecha],
                'headers' => [
                    'Accept'     => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (compatible; InterFruits/1.0)',
                ],
                'timeout'     => 30,
                'verify_peer' => false,
                'verify_host' => false,
            ]);
        }

        foreach ($responses as $fecha => $response) {
            try {
                $data = $response->toArray(false);

                // apis.net.pe devuelve la fecha real de cotización:
                // si es fin de semana/feriado, devuelve el último día hábil con esa fecha.
                // Solo guardamos si la fecha coincide (evita duplicados).
                $fechaReal = $data['fecha'] ?? null;
                if ($fechaReal !== $fecha) {
                    continue; // feriado/fin de semana, ya habrá sido guardado como el día hábil
                }

                if (!isset($data['compra'], $data['venta'])) {
                    continue;
                }

                $results[] = [
                    'fecha'  => $fechaReal,
                    'compra' => (float) $data['compra'],
                    'venta'  => (float) $data['venta'],
                ];
            } catch (\Throwable $e) {
                $this->logger->warning("No se pudo obtener tipo de cambio para {$fecha}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Debug: comprueba conectividad con apis.net.pe y devuelve el dato de hoy.
     */
    public function debugInfo(): array
    {
        $error     = null;
        $firstItem = null;

        try {
            $firstItem = $this->fetchDia(date('Y-m-d'));
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'fuente'      => 'apis.net.pe v2',
            'url'         => $this->apiBase . '?fecha=' . date('Y-m-d'),
            'fecha_hoy'   => date('Y-m-d'),
            'primer_item' => $firstItem,
            'error'       => $error,
        ];
    }

    private function fetchDia(string $fecha): array
    {
        $response = $this->httpClient->request('GET', $this->apiBase, [
            'query'   => ['fecha' => $fecha],
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (compatible; InterFruits/1.0)',
            ],
            'timeout'     => 30,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $data = $response->toArray(false);

        if (!isset($data['compra'], $data['venta'])) {
            throw new \RuntimeException('Respuesta inesperada de apis.net.pe para ' . $fecha . ': ' . json_encode($data));
        }

        return [
            'fecha'  => $data['fecha'] ?? $fecha,
            'compra' => (float) $data['compra'],
            'venta'  => (float) $data['venta'],
        ];
    }
}
