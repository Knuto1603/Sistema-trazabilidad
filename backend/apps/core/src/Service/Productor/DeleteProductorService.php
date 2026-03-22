<?php

namespace App\apps\core\Service\Productor;

use App\apps\core\Repository\ProductorRepository;

final readonly class DeleteProductorService
{
    public function __construct(
        private ProductorRepository $productorRepository,
    )
    {
    }

    public function execute(string $id): void
    {
        $this->productorRepository->removeById($id);
    }

}
