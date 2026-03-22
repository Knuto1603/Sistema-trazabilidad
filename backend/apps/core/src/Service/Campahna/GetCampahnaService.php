<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Entity\Campahna;
use App\apps\core\Repository\CampahnaRepository;

final readonly class GetCampahnaService
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
    ) {
    }

    public function execute(string $id, bool $isEnabled = false): Campahna
    {
        return $this->campahnaRepository->ofId($id, $isEnabled);
    }
}
