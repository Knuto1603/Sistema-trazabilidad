<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\ProductorRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad maestra que representa un Productor.
 * Un productor puede participar en múltiples campañas a través de ProductorCampahna.
 */
#[ORM\Entity(repositoryClass: ProductorRepository::class)]
#[ORM\Table(name: 'core_productor')]
#[ORM\HasLifecycleCallbacks]
class Productor implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 4)]
    private ?string $codigo = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 12)]
    private ?string $clp = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $mtdCeratitis = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $mtdAnastrepha = null;

    #[ORM\Column(length: 255)]
    private ?string $productor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $departamento = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $provincia = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $distrito = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $zona = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subsector = null;

    /**
     * @var Collection<int, ProductorCampahna>
     */
    #[ORM\OneToMany(targetEntity: ProductorCampahna::class, mappedBy: 'productor', cascade: ['persist', 'remove'])]
    private Collection $campahnas;

    public function __construct()
    {
        $this->campahnas = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getNombre() ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getClp(): ?string
    {
        return $this->clp;
    }

    public function setClp(string $clp): static
    {
        $this->clp = $clp;
        return $this;
    }

    public function getMtdCeratitis(): ?string
    {
        return $this->mtdCeratitis;
    }

    public function setMtdCeratitis(?string $mtdCeratitis): static
    {
        $this->mtdCeratitis = $mtdCeratitis;
        return $this;
    }

    public function getMtdAnastrepha(): ?string
    {
        return $this->mtdAnastrepha;
    }

    public function setMtdAnastrepha(?string $mtdAnastrepha): static
    {
        $this->mtdAnastrepha = $mtdAnastrepha;
        return $this;
    }

    public function getProductor(): ?string
    {
        return $this->productor;
    }

    public function setProductor(string $productor): static
    {
        $this->productor = $productor;
        return $this;
    }

    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $v): static { $this->direccion = $v; return $this; }
    public function getDepartamento(): ?string { return $this->departamento; }
    public function setDepartamento(?string $v): static { $this->departamento = $v; return $this; }
    public function getProvincia(): ?string { return $this->provincia; }
    public function setProvincia(?string $v): static { $this->provincia = $v; return $this; }
    public function getDistrito(): ?string { return $this->distrito; }
    public function setDistrito(?string $v): static { $this->distrito = $v; return $this; }
    public function getZona(): ?string { return $this->zona; }
    public function setZona(?string $v): static { $this->zona = $v; return $this; }
    public function getSector(): ?string { return $this->sector; }
    public function setSector(?string $v): static { $this->sector = $v; return $this; }
    public function getSubsector(): ?string { return $this->subsector; }
    public function setSubsector(?string $v): static { $this->subsector = $v; return $this; }

    /**
     * @return Collection<int, ProductorCampahna>
     */
    public function getCampahnas(): Collection
    {
        return $this->campahnas;
    }

    public function addCampahna(ProductorCampahna $productorCampahna): static
    {
        if (!$this->campahnas->contains($productorCampahna)) {
            $this->campahnas->add($productorCampahna);
            $productorCampahna->setProductor($this);
        }
        return $this;
    }

    public function removeCampahna(ProductorCampahna $productorCampahna): static
    {
        if ($this->campahnas->removeElement($productorCampahna)) {
            if ($productorCampahna->getProductor() === $this) {
                $productorCampahna->setProductor(null);
            }
        }
        return $this;
    }

    /**
     * Verifica si el productor está en una campaña específica
     */
    public function isInCampahna(Campahna $campahna): bool
    {
        foreach ($this->campahnas as $productorCampahna) {
            if ($productorCampahna->getCampahna() === $campahna && $productorCampahna->isActive()) {
                return true;
            }
        }
        return false;
    }
}
