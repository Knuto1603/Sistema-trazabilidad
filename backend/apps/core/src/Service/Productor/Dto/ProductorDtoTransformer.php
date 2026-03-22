<?php

namespace App\apps\core\Service\Productor\Dto;

use App\apps\core\Entity\Productor;
use App\apps\core\Entity\ProductorCampahna;
use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class ProductorDtoTransformer extends DtoTransformer
{
    private ?string $campahnaContextId = null;

    /**
     * Establece el contexto de campaña para el transformer.
     * Cuando se transforma un productor, se mostrará la información de esta campaña.
     */
    public function setCampahnaContext(?string $campahnaId): self
    {
        $this->campahnaContextId = $campahnaId;
        return $this;
    }

    /** @param Productor $object */
    public function fromObject(mixed $object): ?ProductorDto
    {
        if (null === $object) {
            return null;
        }

        $dto = new ProductorDto();
        $dto->codigo = $object->getCodigo();
        $dto->nombre = $object->getNombre();
        $dto->clp = $object->getClp();
        $dto->mtdCeratitis = $object->getMtdCeratitis();
        $dto->mtdAnastrepha = $object->getMtdAnastrepha();

        // Obtener datos de campaña desde la relación ProductorCampahna
        $productorCampahna = $this->findProductorCampahna($object);
        if ($productorCampahna) {
            $campahna = $productorCampahna->getCampahna();
            $dto->campahnaId = UidType::toString($campahna?->uuid());
            $dto->campahnaName = $campahna?->getNombre();
            $dto->frutaName = $campahna?->getFruta()?->getNombre();
            $dto->periodoName = $campahna?->getNombreCompleto();
        }

        $dto->ofEntity($object);

        return $dto;
    }

    /**
     * Encuentra la relación ProductorCampahna relevante.
     * Si hay contexto de campaña, busca esa específica.
     * Si no, retorna la primera campaña activa.
     */
    private function findProductorCampahna(Productor $productor): ?ProductorCampahna
    {
        $campahnas = $productor->getCampahnas();

        if ($campahnas->isEmpty()) {
            return null;
        }

        // Si hay contexto de campaña específico, buscar esa
        if ($this->campahnaContextId) {
            foreach ($campahnas as $productorCampahna) {
                if (UidType::toString($productorCampahna->getCampahna()?->uuid()) === $this->campahnaContextId) {
                    return $productorCampahna;
                }
            }
        }

        // Retornar la primera campaña activa
        foreach ($campahnas as $productorCampahna) {
            if ($productorCampahna->isActive()) {
                return $productorCampahna;
            }
        }

        // Si no hay activas, retornar la primera
        return $campahnas->first() ?: null;
    }
}
