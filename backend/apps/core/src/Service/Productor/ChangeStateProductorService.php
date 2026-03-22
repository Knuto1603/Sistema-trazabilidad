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
        $productor->setIsActive($isActive);
        $this->productorRepository->save($productor);

        return $productor;
    }
}
