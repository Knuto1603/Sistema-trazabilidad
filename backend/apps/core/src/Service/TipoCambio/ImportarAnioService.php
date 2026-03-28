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
     * Importa los tipos de cambio día a día en lotes de $limite días.
     * Comienza desde $desde (o 01/01 del año si no se indica).
     * Las fechas ya registradas se omiten. Devuelve la próxima fecha pendiente.
     *
     * @return array{importados: int, omitidos: int, errores: int, detalle: string[], proxima: string|null}
     */
    public function execute(?string $desde = null, int $limite = 20): array
    {
        set_time_limit(120);

        $hoy    = new \DateTimeImmutable('today');
        $inicio = $desde
            ? new \DateTimeImmutable($desde)
            : new \DateTimeImmutable($hoy->format('Y') . '-01-01');

        $importados  = 0;
        $omitidos    = 0;
        $errores     = 0;
        $detalle     = [];
        $procesados  = 0;
        $proxima     = null;

        $current = $inicio;

        while ($current <= $hoy) {
            $fecha = $current->format('Y-m-d');

            // Omitir si ya existe
            if ($this->tipoCambioRepository->findByFecha(new \DateTime($fecha))) {
                $omitidos++;
                $current = $current->modify('+1 day');
                continue;
            }

            // Cortar si alcanzamos el límite de peticiones HTTP
            if ($procesados >= $limite) {
                $proxima = $fecha;
                break;
            }

            try {
                $data = $this->sunatService->obtenerTipoCambio($fecha);
                $dto  = new TipoCambioDto(fecha: $fecha, compra: $data['compra'], venta: $data['venta']);
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

            $procesados++;
            $current = $current->modify('+1 day');
        }

        return [
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'errores'    => $errores,
            'detalle'    => $detalle,
            'proxima'    => $proxima, // null = completado
        ];
    }
}
