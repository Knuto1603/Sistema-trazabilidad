<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Entity\Campahna;
use App\apps\core\Repository\CampahnaRepository;

final readonly class ChangeStateCampahnaService
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
    ) {
    }

    public function execute(string $id, bool $isActive): Campahna
    {
        $campahna = $this->campahnaRepository->ofId($id, true);
        $campahna->setIsActive($isActive);
        $this->campahnaRepository->save($campahna);

        return $campahna;
    }
}
