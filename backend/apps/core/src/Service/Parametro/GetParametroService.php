<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;

final readonly class GetParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function execute(string $id, bool $strict = false): ?Parametro
    {
        return $this->parametroRepository->ofId($id, $strict);
    }
}
