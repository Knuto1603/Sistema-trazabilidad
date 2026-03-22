<?php

namespace App\apps\core\Service\Fruta;

use App\apps\core\Repository\FrutaRepository;

class DeleteFrutaService
{

    public function __construct(
        private FrutaRepository $frutaRepository,
    )
    {
    }

    public function execute(string $id): void
    {
        $this->frutaRepository->removeById($id);

    }

}
