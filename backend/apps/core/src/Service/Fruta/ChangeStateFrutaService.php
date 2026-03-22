<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Entity\Fruta;
use App\apps\core\Repository\FrutaRepository;

class ChangeStateFrutaService
{
    public function __construct(
        private FrutaRepository $frutaRepository
    )
    {
    }

    public function execute(string $id, bool $state): Fruta{
        $fruta = $this->frutaRepository->ofId($id, true);

        match ($state) {
            false => $fruta->disable(),
            true => $fruta->enable(),
        };
        $this->frutaRepository->save($fruta);

        return $fruta;
    }
}
