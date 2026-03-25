<?php

namespace App\apps\core\Service\Despacho;

use App\apps\core\Entity\Despacho;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Service\Despacho\Dto\DespachoDto;
use App\apps\core\Service\Despacho\Dto\DespachoFactory;

final readonly class UpdateDespachoService
{
    public function __construct(
        private DespachoRepository $despachoRepository,
        private DespachoFactory $despachoFactory,
    ) {
    }

    public function execute(string $id, DespachoDto $dto): Despacho
    {
        $despacho = $this->despachoRepository->ofId($id, true);
        $this->despachoFactory->updateOfDto($dto, $despacho);
        $this->despachoRepository->save($despacho);

        return $despacho;
    }
}
