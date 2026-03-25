<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\DespachoRepository;

final readonly class GetDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
    ) {
    }

    public function execute(string $id): Despacho
    {
        return $this->despachoRepository->ofId($id, true);
    }
}
