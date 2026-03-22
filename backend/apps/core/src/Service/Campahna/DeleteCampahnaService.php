<?php

namespace App\apps\core\Service\Campahna;

use App\apps\core\Repository\CampahnaRepository;

final readonly class DeleteCampahnaService
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $campahna = $this->campahnaRepository->ofId($id, true);
        $this->campahnaRepository->delete($campahna);
    }
}
