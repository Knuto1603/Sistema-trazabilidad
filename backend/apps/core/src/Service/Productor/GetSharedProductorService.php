<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;

final readonly class GetSharedProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
    ) {
    }

    public function execute(): array
    {
        return $this->productorRepository->allShared();
    }
}
