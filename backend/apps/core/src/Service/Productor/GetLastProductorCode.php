<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;

final readonly class GetLastProductorCode
{
    public function __construct(
        protected ProductorRepository $repository
    ) {
    }

    public function execute(?string $campahnaId = null): ?string
    {
        return $this->repository->findLastProducerCode($campahnaId);
    }

    /**
     * Generar el próximo código sugerido
     */
    public function getNextCode(?string $campahnaId = null): string
    {
        $lastCode = $this->execute($campahnaId);
        
        if (!$lastCode) {
            return '0001'; // Primer código
        }

        // Incrementar el código
        $nextNumber = (int)$lastCode + 1;
        return str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
