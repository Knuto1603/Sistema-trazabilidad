<?php

namespace App\apps\core\Service\TipoCambioSunat;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TipoCambioSunatService
{
    private string $baseUrl = 'https://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias';
    private string $listUrl = 'https://e-consulta.sunat.gob.pe/cl-at-ittipcam/tcS01Alias/listarTipoCambio';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function obtenerTipoCambio(?string $fecha = null): array
    {
        $fechaConsulta = $fecha ?? date('Y-m-d');
        $dt   = new \DateTimeImmutable($fechaConsulta);
        $anio = (int) $dt->format('Y');
        $mes  = (int) $dt->format('n');
        $dia  = (int) $dt->format('j');

        [$token, $cookies] = $this->extraerTokenYCookies();
        $items = $this->listar($anio, $mes, $token, $cookies);

        return $this->buscarDia($items, $dia, $fechaConsulta);
    }

    /**
     * Carga todos los tipos de cambio de un mes completo.
     */
    public function obtenerMes(int $anio, int $mes): array
    {
        [$token, $cookies] = $this->extraerTokenYCookies();
        return $this->listar($anio, $mes, $token, $cookies);
    }

    private function listar(int $anio, int $mes, string $token, string $cookies): array
    {
        $response = $this->httpClient->request('POST', $this->listUrl, [
            'json' => ['anio' => $anio, 'mes' => $mes, 'token' => $token],
            'headers' => [
                'Accept'           => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language'  => 'es-PE,es;q=0.8',
                'Content-Type'     => 'application/json; charset=UTF-8',
                'Cookie'           => $cookies,
                'Origin'           => 'https://e-consulta.sunat.gob.pe',
                'Referer'          => $this->baseUrl,
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $data = $response->toArray(false);

        // SUNAT devuelve {"status":false,"message":"Token no encontrado."} si el token expiró
        if (isset($data['status']) && $data['status'] === false) {
            throw new \RuntimeException($data['message'] ?? 'Error SUNAT');
        }

        return $data;
    }

    /**
     * Hace GET a la página de SUNAT, extrae el token del HTML
     * y las cookies de la respuesta para usarlas en el POST.
     *
     * @return array{0: string, 1: string} [token, cookieHeader]
     */
    private function extraerTokenYCookies(): array
    {
        $response = $this->httpClient->request('GET', $this->baseUrl, [
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'es-PE,es;q=0.9',
            ],
            'timeout'     => 15,
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $html    = $response->getContent(false);
        $headers = $response->getHeaders(false);

        // Construir cabecera Cookie a partir de los Set-Cookie recibidos
        $cookieParts = [];
        foreach ($headers['set-cookie'] ?? [] as $setCookie) {
            // Tomar solo "nombre=valor" (primera parte antes del ";")
            $cookieParts[] = explode(';', $setCookie)[0];
        }
        $cookieHeader = implode('; ', $cookieParts);

        // Patrones donde SUNAT puede incrustar el token
        $patterns = [
            '/["\']token["\']\s*:\s*["\']([a-z0-9]{20,})["\']/',
            '/var\s+token\s*=\s*["\']([a-z0-9]{20,})["\']/',
            '/name=["\']token["\']\s+value=["\']([a-z0-9]{20,})["\']/',
            '/value=["\']([a-z0-9]{20,})["\']\s+name=["\']token["\']/',
            '/token["\']?\s*[=:]\s*["\']([a-z0-9]{30,})["\']/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                return [$m[1], $cookieHeader];
            }
        }

        throw new \RuntimeException('No se pudo extraer el token de la página de SUNAT');
    }

    private function buscarDia(array $items, int $dia, string $fecha): array
    {
        $compra = null;
        $venta  = null;
        $fechaReal = $fecha;

        foreach ($items as $item) {
            $item = (array) $item;
            $diaCandidato = $this->leerDia($item);

            if ($diaCandidato === $dia) {
                $compra    = $this->leerCompra($item);
                $venta     = $this->leerVenta($item);
                $fechaReal = $this->leerFecha($item) ?? $fecha;
                break;
            }
        }

        // Días sin cotización (feriados/fines de semana): usar el último disponible
        if ($compra === null && !empty($items)) {
            $ultimo    = (array) end($items);
            $compra    = $this->leerCompra($ultimo);
            $venta     = $this->leerVenta($ultimo);
            $fechaReal = $this->leerFecha($ultimo) ?? $fecha;
        }

        if ($compra === null) {
            throw new \RuntimeException('No se encontró tipo de cambio para ' . $fecha);
        }

        return ['compra' => $compra, 'venta' => $venta, 'fecha' => $fechaReal];
    }

    private function leerDia(array $item): ?int
    {
        foreach (['dia', 'Dia', 'DIA', 'numDia', 'nroDia', 'numDiaAnio'] as $k) {
            if (isset($item[$k])) return (int) $item[$k];
        }
        foreach (['fecPublicacion', 'fecha', 'Fecha'] as $k) {
            if (!empty($item[$k])) {
                $p = preg_split('/[\/\-]/', $item[$k]);
                if (count($p) === 3) return (int) $p[0];
            }
        }
        return null;
    }

    private function leerCompra(array $item): ?float
    {
        foreach (['preCompra', 'numCompra', 'compra', 'Compra', 'valorCompra'] as $k) {
            if (isset($item[$k]) && $item[$k] !== '') {
                return (float) str_replace(',', '.', (string) $item[$k]);
            }
        }
        return null;
    }

    private function leerVenta(array $item): ?float
    {
        foreach (['preVenta', 'numVenta', 'venta', 'Venta', 'valorVenta'] as $k) {
            if (isset($item[$k]) && $item[$k] !== '') {
                return (float) str_replace(',', '.', (string) $item[$k]);
            }
        }
        return null;
    }

    private function leerFecha(array $item): ?string
    {
        foreach (['fecPublicacion', 'fecha', 'Fecha'] as $k) {
            if (!empty($item[$k])) {
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $item[$k], $m)) {
                    return "{$m[3]}-{$m[2]}-{$m[1]}";
                }
                return $item[$k];
            }
        }
        return null;
    }

    /**
     * Debug: devuelve HTML recortado + token extraído + primer ítem de la lista.
     */
    public function debugInfo(): array
    {
        $token     = null;
        $cookies   = null;
        $error     = null;
        $firstItem = null;
        $htmlSnippets = [];

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl, [
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'es-PE,es;q=0.9',
                ],
                'timeout'     => 15,
                'verify_peer' => false,
                'verify_host' => false,
            ]);

            $html = $response->getContent(false);

            // Mostrar fragmentos donde aparezca "token" en el HTML
            $pos = 0;
            $found = 0;
            while ($found < 5 && ($pos = stripos($html, 'token', $pos)) !== false) {
                $htmlSnippets[] = substr($html, max(0, $pos - 80), 200);
                $pos += 5;
                $found++;
            }

            if (empty($htmlSnippets)) {
                $htmlSnippets[] = substr($html, 0, 500);
                $htmlSnippets[] = substr($html, 500, 500);
            }

            [$token, $cookies] = $this->extraerTokenYCookies();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($token && $cookies) {
            try {
                $items     = $this->listar((int) date('Y'), (int) date('n'), $token, $cookies);
                $firstItem = $items[0] ?? null;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return [
            'token_extraido' => $token,
            'cookies_header' => $cookies,
            'token_error'    => $error,
            'primer_item'    => $firstItem,
            'html_token_snippets' => $htmlSnippets,
        ];
    }


}
