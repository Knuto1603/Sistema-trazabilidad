<?php

namespace App\apps\core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Este Trait añade la relación con Campahna a las entidades de proceso.
 * Permite el aislamiento automático de datos.
 */
trait ContextTrait
{
    #[ORM\ManyToOne(targetEntity: Campahna::class)]
    #[ORM\JoinColumn(name: 'campahna_id', referencedColumnName: 'id', nullable: false)]
    protected ?Campahna $campahna = null;

    public function getCampahna(): ?Campahna
    {
        return $this->campahna;
    }

    public function setCampahna(?Campahna $campahna): self
    {
        $this->campahna = $campahna;
        return $this;
    }
}
