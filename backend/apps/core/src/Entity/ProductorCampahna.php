<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\ProductorCampahnaRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad de asociación que representa la participación de un Productor en una Campaña.
 * Permite que un productor maestro participe en múltiples campañas sin duplicación.
 */
#[ORM\Entity(repositoryClass: ProductorCampahnaRepository::class)]
#[ORM\Table(name: 'core_productor_campahna')]
#[ORM\UniqueConstraint(name: 'unique_productor_campahna', columns: ['productor_id', 'campahna_id'])]
#[ORM\HasLifecycleCallbacks]
class ProductorCampahna implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Productor::class, inversedBy: 'campahnas')]
    #[ORM\JoinColumn(name: 'productor_id', referencedColumnName: 'id', nullable: false)]
    private ?Productor $productor = null;

    #[ORM\ManyToOne(targetEntity: Campahna::class, inversedBy: 'productorCampahnas')]
    #[ORM\JoinColumn(name: 'campahna_id', referencedColumnName: 'id', nullable: false)]
    private ?Campahna $campahna = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fechaIngreso = null;

    public function __construct()
    {
        $this->fechaIngreso = new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->productor?->getNombre(), $this->campahna?->getNombre());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductor(): ?Productor
    {
        return $this->productor;
    }

    public function setProductor(?Productor $productor): static
    {
        $this->productor = $productor;
        return $this;
    }

    public function getCampahna(): ?Campahna
    {
        return $this->campahna;
    }

    public function setCampahna(?Campahna $campahna): static
    {
        $this->campahna = $campahna;
        return $this;
    }

    public function getFechaIngreso(): ?\DateTimeInterface
    {
        return $this->fechaIngreso;
    }

    public function setFechaIngreso(\DateTimeInterface $fechaIngreso): static
    {
        $this->fechaIngreso = $fechaIngreso;
        return $this;
    }
}
