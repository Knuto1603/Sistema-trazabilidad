<?php

namespace App\apps\core\Service\FrutaVariedad;

use App\apps\core\Entity\FrutaVariedad;
use App\apps\core\Repository\FrutaVariedadRepository;

final readonly class ChangeStateFrutaVariedadService
{
    public function __construct(
        private FrutaVariedadRepository $repository,
    ) {}

    public function execute(string $id, bool $state): FrutaVariedad
    {
        $variedad = $this->repository->ofId($id, true);

        match ($state) {
            false => $variedad->disable(),
            true => $variedad->enable(),
        };

        $this->repository->save($variedad);
        return $variedad;
    }
}
