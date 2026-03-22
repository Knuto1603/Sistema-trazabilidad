<?php

namespace App\apps\core\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:test-senasa',
    description: 'Testea la conexión con SENASA y diagnostica problemas',
    aliases: ['test:senasa']
)]
class TestSenasaCommand extends Command
{
    protected static $defaultName = 'app:test-senasa';
    protected static $defaultDescription = 'Prueba la conexión con SENASA y diagnostica problemas';

    protected function configure(): void
    {
        $this
            ->addArgument('codigo', InputArgument::OPTIONAL, 'Código del lugar de producción', '0020621406')
            ->addArgument('fecha', InputArgument::OPTIONAL, 'Fecha (dd/mm/yyyy)', date('d/m/Y'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $codigo = $input->getArgument('codigo');
        $fecha = $input->getArgument('fecha');

        $io->title('Diagnóstico de Conexión SENASA');
        $io->section('Parámetros de prueba');
        $io->table(
            ['Parámetro', 'Valor'],
            [
                ['Código', $codigo],
                ['Fecha', $fecha],
                ['URL', 'https://servicios.senasa.gob.pe/inspeccionweb/faces/consultaMTDP.xhtml']
            ]
        );

        $client = HttpClient::create([
            'timeout' => 30,
            'verify_peer' => false,
            'verify_host' => false
        ]);

        try {
            // Paso 1: Obtener página inicial
            $io->section('Paso 1: Obteniendo página inicial');

            $response = $client->request('GET', 'https://servicios.senasa.gob.pe/inspeccionweb/faces/consultaMTDP.xhtml', [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $io->text("Status Code: $statusCode");

            $headers = $response->getHeaders();
            $io->text("Headers recibidos:");
            foreach ($headers as $name => $values) {
                $io->text("  $name: " . implode(', ', $values));
            }

            $content = $response->getContent();
            $io->text("Tamaño del contenido: " . strlen($content) . " bytes");

            // Analizar el HTML
            $crawler = new Crawler($content);

            // Buscar ViewState
            $io->section('Paso 2: Buscando ViewState');
            $viewState = null;

            $viewStateSelectors = [
                'input[name="javax.faces.ViewState"]',
                'input[name="jakarta.faces.ViewState"]',
                'input[id*="ViewState"]',
                'input[type="hidden"][name*="ViewState"]'
            ];

            foreach ($viewStateSelectors as $selector) {
                $nodes = $crawler->filter($selector);
                if ($nodes->count() > 0) {
                    $viewState = $nodes->first()->attr('value');
                    $io->success("ViewState encontrado con selector: $selector");
                    $io->text("Valor (primeros 50 chars): " . substr($viewState, 0, 50) . "...");
                    break;
                }
            }

            if (!$viewState) {
                // Buscar en el HTML crudo
                if (preg_match('/name="javax\.faces\.ViewState"[^>]*value="([^"]+)"/', $content, $matches)) {
                    $viewState = $matches[1];
                    $io->success("ViewState encontrado en HTML crudo");
                } else {
                    $io->error("ViewState NO encontrado");
                }
            }

            // Buscar formulario e IDs
            $io->section('Paso 3: Analizando formulario');

            $forms = $crawler->filter('form');
            $io->text("Formularios encontrados: " . $forms->count());

            if ($forms->count() > 0) {
                $form = $forms->first();
                $formId = $form->attr('id') ?: $form->attr('name') ?: 'sin-id';
                $io->text("ID del formulario: $formId");

                // Buscar inputs
                $inputs = $form->filter('input');
                $io->text("Inputs encontrados: " . $inputs->count());

                $inputData = [];
                $inputs->each(function (Crawler $input) use (&$inputData, $io) {
                    $type = $input->attr('type') ?: 'text';
                    $id = $input->attr('id') ?: 'sin-id';
                    $name = $input->attr('name') ?: 'sin-name';
                    $value = $input->attr('value') ?: '';

                    $inputData[] = [
                        'type' => $type,
                        'id' => $id,
                        'name' => $name,
                        'value' => substr($value, 0, 30)
                    ];
                });

                $io->table(['Type', 'ID', 'Name', 'Value'], $inputData);
            }

            // Paso 4: Intentar hacer la consulta
            $io->section('Paso 4: Realizando consulta POST');

            if ($viewState && $forms->count() > 0) {
                // Construir datos POST basados en lo encontrado
                $formId = $forms->first()->attr('id') ?: 'j_idt5';

                // Buscar IDs reales de los campos
                $textInputs = $crawler->filter('input[type="text"]');
                $codigoFieldId = $textInputs->count() > 0 ? $textInputs->eq(0)->attr('id') : $formId . ':j_idt8';
                $fechaFieldId = $textInputs->count() > 1 ? $textInputs->eq(1)->attr('id') : $formId . ':j_idt10';

                // Buscar botón
                $buttons = $crawler->filter('input[type="submit"], button[type="submit"], input[type="button"]');
                $buttonId = $buttons->count() > 0 ? $buttons->first()->attr('id') : $formId . ':j_idt13';

                $postData = [
                    'javax.faces.partial.ajax' => 'true',
                    'javax.faces.source' => $buttonId,
                    'javax.faces.partial.execute' => '@all',
                    'javax.faces.partial.render' => $formId,
                    $buttonId => $buttonId,
                    $formId => $formId,
                    $codigoFieldId => $codigo,
                    $fechaFieldId => $fecha,
                    'javax.faces.ViewState' => $viewState
                ];

                $io->text("Datos POST preparados:");
                foreach ($postData as $key => $value) {
                    $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    $io->text("  $key => $displayValue");
                }

                // Obtener cookies de la respuesta anterior
                $cookies = $headers['set-cookie'] ?? [];
                $cookieHeader = '';
                foreach ($cookies as $cookie) {
                    $cookiePart = explode(';', $cookie)[0];
                    $cookieHeader .= $cookiePart . '; ';
                }

                $io->text("Cookies: " . $cookieHeader);

                // Hacer POST
                $postResponse = $client->request('POST', 'https://servicios.senasa.gob.pe/inspeccionweb/faces/consultaMTDP.xhtml', [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept' => 'application/xml, text/xml, */*; q=0.01',
                        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Faces-Request' => 'partial/ajax',
                        'Cookie' => trim($cookieHeader),
                        'Referer' => 'https://servicios.senasa.gob.pe/inspeccionweb/faces/consultaMTDP.xhtml'
                    ],
                    'body' => http_build_query($postData)
                ]);

                $postContent = $postResponse->getContent();
                $io->text("Respuesta POST recibida: " . strlen($postContent) . " bytes");

                // Mostrar primeros 500 caracteres de la respuesta
                $io->text("Contenido de la respuesta:");
                $io->text(substr($postContent, 0, 500));

                // Analizar respuesta
                if (strpos($postContent, '{') === 0) {
                    $io->warning("Respuesta es JSON:");
                    $json = json_decode($postContent, true);
                    $io->text(json_encode($json, JSON_PRETTY_PRINT));
                } elseif (strpos($postContent, '<partial-response') !== false) {
                    $io->info("Respuesta es JSF partial-response");
                    // Extraer CDATA
                    if (preg_match('/<!\[CDATA\[(.*?)\]\]>/s', $postContent, $matches)) {
                        $io->text("Contenido CDATA extraído:");
                        $io->text(substr($matches[1], 0, 500));
                    }
                } else {
                    $io->text("Tipo de respuesta no identificado");
                }
            }

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            $io->text('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        $io->success('Diagnóstico completado');
        return Command::SUCCESS;
    }
}
