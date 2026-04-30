<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Entity\Productor;
use App\apps\core\Repository\ProductorRepository;

final readonly class ChangeStateProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
    ) {
    }

    public function execute(string $id, bool $isActive): Productor
    {
        $productor = $this->productorRepository->ofId($id, true);
        $isActive ? $productor->enable() : $productor->disable();
        $this->productorRepository->save($productor);

        return $productor;
    }
}
