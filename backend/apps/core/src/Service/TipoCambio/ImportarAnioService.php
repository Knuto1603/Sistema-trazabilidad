<?php

namespace App\apps\core\Service\TipoCambio;

use App\apps\core\Repository\TipoCambioRepository;
use App\apps\core\Service\TipoCambio\Dto\TipoCambioDto;
use App\apps\core\Service\TipoCambioSunat\TipoCambioSunatService;
use Psr\Log\LoggerInterface;

final readonly class ImportarAnioService
{
    public function __construct(
        private TipoCambioSunatService $sunatService,
        private CreateOrUpdateTipoCambioService $createOrUpdateService,
        private TipoCambioRepository $tipoCambioRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Importa todos los tipos de cambio desde el 1 de enero del año actual
     * hasta hoy, mes a mes. Omite fechas ya registradas.
     *
     * @return array{importados: int, omitidos: int, errores: int, detalle: string[]}
     */
    public function execute(): array
    {
        $hoy      = new \DateTimeImmutable();
        $anio     = (int) $hoy->format('Y');
        $mesHoy   = (int) $hoy->format('n');
        $diaHoy   = (int) $hoy->format('j');

        $importados = 0;
        $omitidos   = 0;
        $errores    = 0;
        $detalle    = [];

        for ($mes = 1; $mes <= $mesHoy; $mes++) {
            try {
                $items = $this->sunatService->obtenerMes($anio, $mes);

                if (empty($items)) {
                    $detalle[] = "Mes {$mes}: sin datos";
                    continue;
                }

                foreach ($items as $item) {
                    $item = (array) $item;

                    $fecha  = $this->parsearFecha($item, $anio, $mes);
                    $compra = $this->leerCompra($item);
                    $venta  = $this->leerVenta($item);

                    if (!$fecha || !$compra || !$venta) {
                        $errores++;
                        continue;
                    }

                    // No importar fechas futuras
                    if ($fecha > $hoy->format('Y-m-d')) {
                        $omitidos++;
                        continue;
                    }

                    // Omitir si ya existe
                    $existing = $this->tipoCambioRepository->findByFecha(new \DateTime($fecha));
                    if ($existing) {
                        $omitidos++;
                        continue;
                    }

                    $dto = new TipoCambioDto(fecha: $fecha, compra: $compra, venta: $venta);
                    $this->createOrUpdateService->execute($dto);
                    $importados++;
                }

                $detalle[] = "Mes {$mes}: OK";
            } catch (\Throwable $e) {
                $errores++;
                $detalle[] = "Mes {$mes}: ERROR - " . $e->getMessage();
                $this->logger->error("Error importando mes {$mes}/{$anio}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'errores'    => $errores,
            'detalle'    => $detalle,
        ];
    }

    private function parsearFecha(array $item, int $anio, int $mes): ?string
    {
        // Formato Y-m-d (de apis.net.pe)
        foreach (['fecha', 'fecPublicacion', 'Fecha'] as $k) {
            if (!empty($item[$k])) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $item[$k])) {
                    return $item[$k];
                }
                // Formato dd/mm/yyyy (legado SUNAT)
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $item[$k], $m)) {
                    return "{$m[3]}-{$m[2]}-{$m[1]}";
                }
            }
        }

        // Construir desde número de día (legado SUNAT)
        foreach (['dia', 'Dia', 'DIA', 'numDia', 'nroDia'] as $k) {
            if (isset($item[$k])) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, (int) $item[$k]);
            }
        }

        return null;
    }

    private function leerCompra(array $item): ?float
    {
        foreach (['compra', 'preCompra', 'numCompra', 'Compra', 'valorCompra'] as $k) {
            if (isset($item[$k]) && $item[$k] !== '') {
                return (float) str_replace(',', '.', (string) $item[$k]);
            }
        }
        return null;
    }

    private function leerVenta(array $item): ?float
    {
        foreach (['venta', 'preVenta', 'numVenta', 'Venta', 'valorVenta'] as $k) {
            if (isset($item[$k]) && $item[$k] !== '') {
                return (float) str_replace(',', '.', (string) $item[$k]);
            }
        }
        return null;
    }
}
