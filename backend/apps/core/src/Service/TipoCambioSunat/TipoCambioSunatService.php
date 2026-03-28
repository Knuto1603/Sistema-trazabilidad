<?php

namespace App\apps\core\Service\TipoCambioSunat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TipoCambioSunatService
{
    private string $baseUrl  = 'https://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias';
    private string $listUrl  = 'https://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias/listarTipoCambio';
    private string $fallback = 'https://api.apis.net.pe/v1/tipo-cambio-sunat';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function obtenerTipoCambio(?string $fecha = null): array
    {
        $fechaConsulta = $fecha ?? date('Y-m-d');

        try {
            return $this->obtenerDesdeSunatDirecto($fechaConsulta);
        } catch (\Throwable $e) {
            $this->logger->warning('SUNAT directo falló, usando fallback', ['error' => $e->getMessage()]);
            return $this->obtenerDesdeFallback($fechaConsulta);
        }
    }

    /**
     * Obtiene el tipo de cambio desde la API oficial de SUNAT:
     * 1. GET a la página principal para extraer el token de sesión
     * 2. POST a listarTipoCambio con {anio, mes, token}
     */
    private function obtenerDesdeSunatDirecto(string $fecha): array
    {
        $dt    = new \DateTimeImmutable($fecha);
        $anio  = (int) $dt->format('Y');
        $mes   = (int) $dt->format('n');
        $dia   = (int) $dt->format('j');

        $token = $this->extraerToken();

        $response = $this->httpClient->request('POST', $this->listUrl, [
            'json' => ['anio' => $anio, 'mes' => $mes, 'token' => $token],
            'headers' => [
                'Accept'   => 'application/json, text/javascript, */*',
                'Origin'   => 'https://e-consulta.sunat.gob.pe',
                'Referer'  => $this->baseUrl,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $items = $response->toArray(false);

        if (empty($items)) {
            throw new \RuntimeException('SUNAT devolvió lista vacía para ' . $fecha);
        }

        $this->logger->debug('SUNAT listarTipoCambio muestra', ['first' => $items[0]]);

        return $this->parsearListaMensual($items, $dia, $fecha);
    }

    /**
     * Carga la página principal de SUNAT y extrae el token de sesión.
     */
    private function extraerToken(): string
    {
        $response = $this->httpClient->request('GET', $this->baseUrl, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $html = $response->getContent(false);

        // Patrón 1: token como propiedad JS  → token: "abc123"
        if (preg_match('/["\']?token["\']?\s*:\s*["\']([a-z0-9]{20,})["\']/', $html, $m)) {
            return $m[1];
        }
        // Patrón 2: hidden input             → <input name="token" value="abc123">
        if (preg_match('/<input[^>]+name=["\']token["\'][^>]+value=["\']([a-z0-9]{20,})["\']/', $html, $m)) {
            return $m[1];
        }
        // Patrón 3: valor antes del nombre   → value="abc123" name="token"
        if (preg_match('/<input[^>]+value=["\']([a-z0-9]{20,})["\'][^>]+name=["\']token["\']/', $html, $m)) {
            return $m[1];
        }
        // Patrón 4: variable JS asignada     → var token = "abc123"
        if (preg_match('/var\s+token\s*=\s*["\']([a-z0-9]{20,})["\']/', $html, $m)) {
            return $m[1];
        }

        $this->logger->error('No se encontró token en HTML de SUNAT', ['html_snippet' => substr($html, 0, 500)]);
        throw new \RuntimeException('No se pudo extraer el token de sesión de SUNAT');
    }

    /**
     * Busca en la lista mensual la entrada correspondiente al día solicitado.
     * Acepta múltiples formatos de respuesta conocidos de SUNAT.
     */
    private function parsearListaMensual(array $items, int $dia, string $fecha): array
    {
        $compra = null;
        $venta  = null;
        $fechaReal = $fecha;

        foreach ($items as $item) {
            $item = (array) $item;

            // Extraer el día del ítem según diferentes formatos conocidos
            $diaCandidato = $this->extraerDia($item);

            if ($diaCandidato === $dia) {
                $compra    = $this->extraerCompra($item);
                $venta     = $this->extraerVenta($item);
                $fechaReal = $this->extraerFecha($item) ?? $fecha;
                break;
            }
        }

        // Si no encontramos el día exacto, usar el último disponible (días sin cotización)
        if ($compra === null && !empty($items)) {
            $ultimo = (array) end($items);
            $compra    = $this->extraerCompra($ultimo);
            $venta     = $this->extraerVenta($ultimo);
            $fechaReal = $this->extraerFecha($ultimo) ?? $fecha;
        }

        if ($compra === null) {
            throw new \RuntimeException('No se encontró tipo de cambio para ' . $fecha);
        }

        return ['compra' => $compra, 'venta' => $venta, 'fecha' => $fechaReal];
    }

    private function extraerDia(array $item): ?int
    {
        foreach (['dia', 'Dia', 'DIA', 'numDia', 'nroDia'] as $key) {
            if (isset($item[$key])) return (int) $item[$key];
        }
        // Intentar parsear desde campo de fecha string dd/mm/yyyy
        foreach (['fecPublicacion', 'fecha', 'Fecha', 'fechaPublicacion'] as $key) {
            if (!empty($item[$key])) {
                $parts = preg_split('/[\/\-]/', $item[$key]);
                if (count($parts) === 3) return (int) $parts[0];
            }
        }
        return null;
    }

    private function extraerCompra(array $item): ?float
    {
        foreach (['preCompra', 'compra', 'Compra', 'COMPRA', 'numCompra', 'valorCompra', 'precioCompra'] as $key) {
            if (isset($item[$key]) && $item[$key] !== '') {
                return (float) str_replace(',', '.', (string) $item[$key]);
            }
        }
        return null;
    }

    private function extraerVenta(array $item): ?float
    {
        foreach (['preVenta', 'venta', 'Venta', 'VENTA', 'numVenta', 'valorVenta', 'precioVenta'] as $key) {
            if (isset($item[$key]) && $item[$key] !== '') {
                return (float) str_replace(',', '.', (string) $item[$key]);
            }
        }
        return null;
    }

    private function extraerFecha(array $item): ?string
    {
        foreach (['fecPublicacion', 'fecha', 'Fecha', 'fechaPublicacion'] as $key) {
            if (!empty($item[$key])) {
                // Convertir dd/mm/yyyy → yyyy-mm-dd
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $item[$key], $m)) {
                    return "{$m[3]}-{$m[2]}-{$m[1]}";
                }
                return $item[$key];
            }
        }
        return null;
    }

    /**
     * Fallback: apis.net.pe (fuente: SUNAT, sin token requerido).
     */
    private function obtenerDesdeFallback(string $fecha): array
    {
        $response = $this->httpClient->request('GET', $this->fallback, [
            'query'   => ['fecha' => $fecha],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 15,
        ]);

        $data = $response->toArray(false);

        if (empty($data['compra']) || empty($data['venta'])) {
            throw new \RuntimeException('Fallback también falló para ' . $fecha);
        }

        return [
            'compra' => (float) $data['compra'],
            'venta'  => (float) $data['venta'],
            'fecha'  => $data['fecha'] ?? $fecha,
        ];
    }

    /**
     * Devuelve la respuesta raw de listarTipoCambio para inspección/debug.
     */
    public function debugListarRaw(?string $fecha = null): array
    {
        $dt   = new \DateTimeImmutable($fecha ?? date('Y-m-d'));
        $anio = (int) $dt->format('Y');
        $mes  = (int) $dt->format('n');

        $token = $this->extraerToken();

        $response = $this->httpClient->request('POST', $this->listUrl, [
            'json' => ['anio' => $anio, 'mes' => $mes, 'token' => $token],
            'headers' => [
                'Accept'  => 'application/json, text/javascript, */*',
                'Origin'  => 'https://e-consulta.sunat.gob.pe',
                'Referer' => $this->baseUrl,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ],
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        return $response->toArray(false);
    }
}
