<?php

namespace App\apps\core\Service\SenasaScraping;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SenasaScraperService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $baseUrl = 'https://servicios.senasa.gob.pe/inspeccionweb/faces/consultaMTDP.xhtml';
    private ?string $jsessionId = null;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }


    /**
     * Consulta un código de lugar de producción en SENASA
     */
    public function consultarLugarProduccion(string $codigoLugar, string $fecha): array
    {
        try {
            // Paso 1: Obtener la página inicial y establecer sesión
            $initialResponse = $this->httpClient->request('GET', $this->baseUrl);

            // Extraer JSESSIONID
            $cookies = $initialResponse->getHeaders()['set-cookie'] ?? [];
            foreach ($cookies as $cookie) {
                if (strpos($cookie, 'JSESSIONID') !== false) {
                    preg_match('/JSESSIONID=([^;]+)/', $cookie, $matches);
                    if (isset($matches[1])) {
                        $this->jsessionId = $matches[1];
                        $this->logger->info('JSESSIONID obtenido', ['sessionId' => substr($this->jsessionId, 0, 20) . '...']);
                    }
                }
            }

            $htmlContent = $initialResponse->getContent();


            // Extraer ViewState
            $viewState = '';
            if (preg_match('/name="javax\.faces\.ViewState"\s+(?:id="[^"]*"\s+)?value="([^"]+)"/', $htmlContent, $matches)) {
                $viewState = $matches[1];
                $this->logger->info('ViewState extraído', ['length' => strlen($viewState)]);
            }


            if (empty($viewState)) {
                throw new \Exception('No se pudo obtener el ViewState');
            }

            // Pequeña pausa para simular comportamiento humano
            usleep(500000); // 0.5 segundos


            // Paso 2: Realizar la consulta con los IDs correctos
            $postData = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'j_idt21:j_idt24',
                'javax.faces.partial.execute' => '@all',
                'javax.faces.partial.render' => 'j_idt18:panelResultado j_idt18:chartcera j_idt18:chartanas',
                'javax.faces.behavior.event' => 'action',
                'javax.faces.partial.event' => 'click',
                'j_idt18:j_idt24' => 'j_idt18:j_idt24',
                'j_idt18' => 'j_idt18',
                'j_idt18:codigoLP' => $codigoLugar,
                'j_idt18:fechaMTD_input' => $fecha,
                'javax.faces.ViewState' => $viewState
            ];



            $headers = [
                'Accept' => 'application/xml, text/xml, */*; q=0.01',
                'Accept-Encoding' => 'gzip, deflate, br, zstd', // ⚠️ evita br si Symfony no lo soporta
                'Accept-Language' => 'es-PE,es;q=0.6',
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request' => 'partial/ajax',
                'Host' => 'servicios.senasa.gob.pe',
                'Origin' => 'https://servicios.senasa.gob.pe',
                'Referer' => $this->baseUrl.';jsessionid=' . $this->jsessionId,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest'
            ];

            if ($this->jsessionId) {
                $headers['Cookie'] = 'JSESSIONID=' . $this->jsessionId;
            }


            $this->logger->info('Enviando consulta POST', [
                'codigo' => $codigoLugar,
                'fecha' => $fecha
            ]);

            $response = $this->httpClient->request('POST', $this->baseUrl, [
                'headers' => $headers,
                'body' => $postData
            ]);

            $responseContent = $response->getContent();

            $this->logger->info('Respuesta recibida', [
                'status' => $response->getStatusCode(),
                'size' => strlen($responseContent)
            ]);

            return $this->parsearRespuesta($responseContent);

        } catch (\Exception $e) {
            $this->logger->error('Error en consulta SENASA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => true,
                'message' => 'Error al consultar SENASA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parsea la respuesta JSF
     */
    private function parsearRespuesta(string $content): array
    {
        // Si es una respuesta JSON directa
        if (strpos(trim($content), '{') === 0) {
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($json['status']) && !$json['success']) {
                    return [
                        'error' => true,
                        'message' => $json['message'] ?? 'Error desconocido'
                    ];
                }
            }
        }

        // Si es una respuesta JSF partial-response
        if (strpos($content, '<partial-response') !== false) {
            $this->logger->info('Parseando respuesta JSF partial-response');

            // Buscar actualizaciones en el panel de resultados
            if (preg_match('/<update[^>]*id="j_idt21:panelResultado"[^>]*><!\[CDATA\[(.*?)\]\]><\/update>/s', $content, $matches)) {
                $htmlResultado = $matches[1];
                return $this->extraerDatosDeResultado($htmlResultado);
            }

            // Buscar cualquier actualización que pueda contener los datos
            if (preg_match_all('/<update[^>]*><!\[CDATA\[(.*?)\]\]><\/update>/s', $content, $matches)) {
                foreach ($matches[1] as $htmlContent) {
                    $datos = $this->extraerDatosDeResultado($htmlContent);
                    if (!$datos['error'] && !empty($datos['data'])) {
                        return $datos;
                    }
                }
            }

            // Buscar errores específicos
            if (preg_match('/<error[^>]*><error-message><!\[CDATA\[(.*?)\]\]><\/error-message><\/error>/s', $content, $matches)) {
                return [
                    'error' => true,
                    'message' => strip_tags($matches[1])
                ];
            }

            // Si llegamos aquí, no encontramos datos
            return [
                'error' => true,
                'message' => 'No se encontraron datos en la respuesta. Es posible que el código o fecha sean incorrectos.'
            ];
        }

        return [
            'error' => true,
            'message' => 'Formato de respuesta no reconocido'
        ];
    }

    /**
     * Extrae los datos del HTML del resultado
     */
    private function extraerDatosDeResultado(string $html): array
    {
        $datos = [];

        // Preparar HTML
        $html = str_replace(['&nbsp;', "\n", "\r", "\t"], [' ', ' ', ' ', ' '], $html);
        $html = preg_replace('/\s+/', ' ', $html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Cargar DOM
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Ignorar warnings de HTML malformado
        $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // Lista de campos a extraer con sus etiquetas asociadas
        $labels = [
            'semana' => 'SEMANA:',
            'nombreProductor' => 'Nombre del Productor:',
            'nombreLugar' => 'Nombre del Lugar Produccion:',
            'direccion' => 'Direccion del Lugar Produccion:',
            'departamento' => 'Departamento:',
            'provincia' => 'Provincia:',
            'distrito' => 'Distrito:',
            'zona' => 'Zona:',
            'sector' => 'Sector:',
            'subsector' => 'Subsector:'
        ];

        foreach ($labels as $key => $labelText) {
            // Buscar el elemento que contiene el label
            $labelNode = $xpath->query("//*[contains(text(), '$labelText')]")->item(0);
            if ($labelNode) {
                // Buscar el siguiente elemento hermano con texto
                $valueNode = $labelNode->parentNode->nextSibling;
                while ($valueNode && $valueNode->nodeType !== XML_ELEMENT_NODE) {
                    $valueNode = $valueNode->nextSibling;
                }

                if ($valueNode) {
                    $value = trim($valueNode->textContent);
                    $datos[$key] = $value;
                } else {
                    $datos[$key] = '';
                }
            } else {
                $datos[$key] = '';
            }
        }

        // Extraer MTDs desde los scripts (siguen usando regex)
        if (preg_match('/title:"Mtd Ceratitis\s*:\s*([0-9.]+)"/', $html, $ceratitisMatch)) {
            $datos['mtd_ceratitis'] = $ceratitisMatch[1];
        }

        if (preg_match('/title:"Mtd Anastrepha\s*:\s*([0-9.]+)"/', $html, $anastrephaMatch)) {
            $datos['mtd_anastrepha'] = $anastrephaMatch[1];
        }

        return [
            'error' => false,
            'data' => $datos
        ];
    }



    /**
     * Método auxiliar para validar el formato de fecha
     */
    public function validarFormatoFecha(string $fecha): bool
    {
        return preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha) === 1;
    }

    /**
     * Método auxiliar para formatear fecha
     */
    public function formatearFecha(\DateTime $fecha): string
    {
        return $fecha->format('d/m/Y');
    }
}
