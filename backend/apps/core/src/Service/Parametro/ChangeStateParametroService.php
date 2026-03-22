<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;

final readonly class ChangeStateParametroService
{
    public function __construct(
        private ParametroRepository $repository,
    ) {
    }

    public function execute(string $id, bool $state): Parametro
    {
        $parametro = $this->repository->ofId($id, true);

        match ($state) {
            false => $parametro->disable(),
            true => $parametro->enable(),
        };

        $this->repository->save($parametro);

        return $parametro;
    }
}
