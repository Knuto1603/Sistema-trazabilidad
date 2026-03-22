<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Entity\Productor;
use App\apps\core\Repository\ProductorRepository;

final readonly class GetProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
    ) {
    }

    public function execute(string $id, bool $isEnabled = false): Productor
    {
        return $this->productorRepository->ofId($id, $isEnabled);
    }
}
