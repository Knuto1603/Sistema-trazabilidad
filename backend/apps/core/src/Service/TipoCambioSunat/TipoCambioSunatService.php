<?php

namespace App\apps\core\Service\TipoCambioSunat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TipoCambioSunatService
{
    private string $url = 'https://api.apis.net.pe/v1/tipo-cambio-sunat';

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
                'query' => ['fecha' => $fechaConsulta],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray(false);

            if (empty($data['compra']) || empty($data['venta'])) {
                throw new \RuntimeException('Respuesta inválida de la API');
            }

            return [
                'compra' => (float) $data['compra'],
                'venta'  => (float) $data['venta'],
                'fecha'  => $data['fecha'] ?? $fechaConsulta,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Error obteniendo tipo de cambio SUNAT', ['error' => $e->getMessage()]);
            throw new \RuntimeException('No se pudo obtener el tipo de cambio de SUNAT: ' . $e->getMessage());
        }
    }
}
