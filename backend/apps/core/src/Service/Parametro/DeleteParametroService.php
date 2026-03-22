<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Repository\ParametroRepository;

final readonly class  DeleteParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->parametroRepository->removeById($id);
    }
}
