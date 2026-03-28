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
     * Importa los tipos de cambio día a día desde el 01/01 del año actual hasta hoy.
     * Omite fines de semana (sin cotización) y fechas ya registradas.
     *
     * @return array{importados: int, omitidos: int, errores: int, detalle: string[]}
     */
    public function execute(): array
    {
        $hoy   = new \DateTimeImmutable('today');
        $inicio = new \DateTimeImmutable($hoy->format('Y') . '-01-01');

        $importados = 0;
        $omitidos   = 0;
        $errores    = 0;
        $detalle    = [];

        $current = $inicio;

        while ($current <= $hoy) {
            $fecha     = $current->format('Y-m-d');
            // Omitir si ya existe
            if ($this->tipoCambioRepository->findByFecha(new \DateTime($fecha))) {
                $omitidos++;
                $current = $current->modify('+1 day');
                continue;
            }

            try {
                $data = $this->sunatService->obtenerTipoCambio($fecha);

                $dto = new TipoCambioDto(
                    fecha:  $fecha,
                    compra: $data['compra'],
                    venta:  $data['venta'],
                );
                $this->createOrUpdateService->execute($dto);
                $importados++;
            } catch (\RuntimeException $e) {
                if (str_starts_with($e->getMessage(), 'Sin cotización')) {
                    $omitidos++;
                } else {
                    $errores++;
                    $detalle[] = "{$fecha}: ERROR - " . $e->getMessage();
                    $this->logger->warning("Error importando {$fecha}: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                $errores++;
                $detalle[] = "{$fecha}: ERROR - " . $e->getMessage();
                $this->logger->warning("Error importando {$fecha}: " . $e->getMessage());
            }

            $current = $current->modify('+1 day');
        }

        $detalle[] = "Total: {$importados} importados, {$omitidos} ya existían, {$errores} errores";

        return [
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'errores'    => $errores,
            'detalle'    => $detalle,
        ];
    }
}
