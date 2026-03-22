<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;
use App\apps\core\Service\Parametro\Dto\ParametroDto;
use App\apps\core\Service\Parametro\Dto\ParametroFactory;
use App\shared\Exception\MissingParameterException;
use App\shared\Exception\RepositoryException;

final readonly class CreateParametroService
{
    public function __construct(
        private ParametroRepository $parametroRepository,
        private ParametroFactory $parametroFactory,
    ) {
    }

    public function execute(ParametroDto $parametroDto): Parametro
    {
        $this->isValid($parametroDto);

        $parametro = $this->parametroFactory->ofDto($parametroDto);
        $this->parametroRepository->save($parametro);

        return $parametro;
    }

    public function isValid(ParametroDto $parametroDto): void
    {
        if (null === $parametroDto->name) {
            throw new MissingParameterException('Missing parameter name');
        }

        $parent = null;
        if ($parametroDto->parentId) {
            $parent = $this->parametroRepository->ofId($parametroDto->parentId);
        }

        if (null !== $this->parametroRepository->findOneBy(['alias' => $parametroDto->alias, 'parent' => $parent])) {
            throw new RepositoryException(\sprintf('Alias %s already exists', $parametroDto->alias));
        }
    }
}
