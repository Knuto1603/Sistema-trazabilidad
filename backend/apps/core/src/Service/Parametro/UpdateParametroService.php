<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;
use App\apps\core\Service\Parametro\Dto\ParametroDto;
use App\apps\core\Service\Parametro\Dto\ParametroFactory;
use App\shared\Exception\RepositoryException;

final readonly class UpdateParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
        private ParametroFactory $parametroFactory,
    ) {
    }

    public function execute(string $id, ParametroDto $parametroDto): Parametro
    {
        $parametro = $this->parametroRepository->ofId($id, true);
        $this->isValid($parametroDto, $parametro);
        $this->parametroFactory->updateOfDto($parametroDto, $parametro);
        $this->parametroRepository->save($parametro);

        return $parametro;
    }

    public function isValid(ParametroDto $parametroDto, ?Parametro $parametro): void
    {
        $parent = null;
        if ($parametroDto->parentId) {
            $parent = $this->parametroRepository->ofId($parametroDto->parentId);
        }

        if ($parametro->getAlias() !== $parametroDto->alias
            && null !== $this->parametroRepository->findOneBy(['alias' => $parametroDto->alias, 'parent' => $parent])
        ) {
            throw new RepositoryException(\sprintf('Alias %s already exists', $parametroDto->alias));
        }
    }
}
